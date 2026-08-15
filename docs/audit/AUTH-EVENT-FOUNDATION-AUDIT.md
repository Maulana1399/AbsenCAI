# AUTH & EVENT FOUNDATION AUDIT

> Audit foundation sebelum pembuatan modul **Competition/Lomba**.
> Fase ini **hanya audit** — tidak ada implementasi fitur baru, tidak ada migration baru, tidak ada perubahan authorization.
> Source of truth: actual code + actual migrations + actual tests. Dokumentasi hanya sebagai konteks dan diverifikasi terhadap kode.

- **Tanggal audit:** 2026-08-11
- **Scope:** authentication, authorization, event-scoped access, User↔Person, role model, event chair, guest-without-person, competition readiness, database, tests.
- **Status:** AUDIT COMPLETE — 1 laporan, 0 perubahan kode.

---

## 1. Executive Summary

Codebase **sudah bergerak hampir penuh** ke arah arsitektur yang diinginkan:

```
User / Account
 ├── Person (optional) ─── sudah ada (users.person_id nullable unique)
 └── Event Membership
       ├── Event          ─── event_committee_assignments
       ├── Role           ─── event_roles (code + name, per-event)
       └── Permission     ─── event_roles.permissions (JSON, Permission Engine)
```

`users.role` secara runtime **hanya** menentukan hak platform (`super_admin`/`admin`); seluruh ability event di-resolve dari `User → Person → EventCommitteeAssignment → EventRole.permissions` (`app/Providers/AppServiceProvider.php`, `app/Services/Event/EventPermissionService.php`). Ini cocok dengan target `SUPER_ADMIN / ADMIN / EVENT_CHAIR / GUEST`.

**Namun** ada 5 kesimpulan utama yang menghalangi langsung membangun modul Competition di atas foundation ini:

1. **Event access masih berbasis Person secara wajib.** `EventAccessService` dan `EventPermissionService` mengembalikan *no access* bila `user->person_id === null` (`app/Services/Event/EventAccessService.php:22-24,33-35`; `app/Services/Event/EventPermissionService.php:16-18,34-36`). **Guest account tanpa Person tidak bisa mengakses event apa pun.** Belum ada tabel relasi langsung `User ↔ Event`.

2. **Terdapat beberapa celah cross-event (IDOR) yang nyata** di Livewire components pada halaman yang sudah diproteksi route middleware `can:`. Menyembunyikan menu ≠ authorization; beberapa komponen membaca/menulis record by-ID tanpa verifikasi `event_id` terhadap active event (`SuratIzin/Index`, `QRLabel/Index`, `Competition/MatchCenter`, `Competition/OfficialPanel`, `IdentityCorrectionReview`, `ImportParticipation`, `GantiPeserta`).

3. **Route `events/{event}/pengajian/*` dan `competition.viewer` tanpa `auth`** (by design untuk token-based/public flow), tetapi **`koreksi-data` dan `import/*` tanpa `resolve.active-event`** membuat gate event di-resolve dari session — bukan dari URL — sehingga bisa cross-event.

4. **Role enum & beberapa UI masih menyimpan event-role di `users.role`** (legacy), sementara dokumentasi menyatakan `users.role` hanya platform. Ini **DOCUMENTATION DRIFT** parsial: `Role::values()` masih memuat `ketua_event, sekretariat, pj_divisi, operator_registrasi, operator_scan, juri, viewer`; `UserFactory`/tests/artisan `user:set-role` masih bisa menaruh event-role di `users.role`, meski runtime engine mengabaikannya.

5. **Test suite hijau penuh** (2229 passed, 5782 assertions, 0 failed, 0 skipped) saat dijalankan dengan `memory_limit` yang cukup. Ini baseline yang bisa dipakai untuk memastikan rekomendasi berikut tidak merusak behavior saat ini.

**Rekomendasi inti (lihat §13):** jangan mengubah `User`, jangan paksa Person. Buat jalur membership yang bisa menunjuk **User langsung** (tanpa Person) sebagai pelengkap jalur person-based yang sudah ada, lalu perketat event-scoping record-level sebelum menambahkan Competition data model yang lebih dalam (Team/Match/Result).

---

## 2. Current Architecture

Stack: **Laravel 12 + Livewire 3 (Volt) + Flux + Pest + SQLite (prod MySQL-compatible) + maatwebsite/excel**.

```
Platform Dashboard  (/dashboard)
        ↓  EventSwitcher (filtered by assignment / platform)
   Pilih Event
        ↓  ActiveEventContext (session, di-resolve dari URL oleh middleware)
   Event Dashboard  (/events/{event}/dashboard + module routes)
```

Alur authorization saat ini:

1. Route middleware `resolve.active-event` (`app/Http/Middleware/ResolveActiveEvent.php`) membaca `{event}` dari URL → `app(ActiveEventContext::class)->set($event)` (session). Event non-`active` → 404.
2. Route middleware `can:{ability}` → `Gate::define` di `AppServiceProvider`.
   - `Gate::before`: `super_admin` → true (bypass semua).
   - `$eventAbility` closure: `admin` → true (bypass ability event), selain itu → `EventPermissionService::allows($user, $ability)`.
3. `EventPermissionService::allows` query: `event_committee_assignments` untuk `event_id` (dari ActiveEventContext) + `person_id` (dari `user->person_id`) + `eventRole` aktif + `permissions` JSON mengandung ability.
4. Livewire mutation: `Gate::authorize(...)` di method-method mutator; mount authorization via trait `ResolvesEventDashboard` (verifikasi `EventAccessService::canAccess`) atau `Gate::authorize` di `mount`.

Layer data (Design C):

```
Person (master identity)
 └── Participation (event-scoped)
       ├── event_id, participant_number, attendance_code, status_registrasi, regu_id
       ├── EventAttendance (status hadir/izin, method)
       └── ActivityRegistration / CompetitionRegistration
```

Layer akses (terpisah dari partisipasi):

```
User.person_id → Person
   └── EventCommitteeAssignment → Event + EventRole.permissions
```

Tidak ada relasi langsung `User ↔ Event`.

---

## 3. Authentication Audit

| Aspek | Temuan | Lokasi |
|---|---|---|
| Model User | `Authenticatable`; `$fillable` termasuk `role`, `person_id`, `username`, `is_active`; casts: `role` → `Role` enum, `password` hashed | `app/Models/User.php` |
| Migration users | `name!`, `email?` (dibuat nullable `2026_08_18_000001`), `email_verified_at?`, `password!`, `role?` string(50) (`2026_08_03_000001`), `person_id?` unique nullOnDelete (`2026_08_04_000001`), `username?` unique, `is_active!` default true (`2026_08_18_000001`) | `database/migrations/0001_01_01_000000_create_users_table.php`, `..._add_role_to_users_table.php`, `..._add_person_id_to_users_table.php`, `..._add_username_and_is_active_to_users_table.php` |
| Login flow | Livewire `Login`; identifikasi via email **atau username** (auto-detect `@`); cek `is_active === false` → ditolak; rate limit 5/menit; `Session::regenerate()` | `app/Livewire/Auth/Login.php:30-62` |
| Logout | `POST /logout` → `Livewire\Actions\Logout` (session invalidate + regenerate) | `routes/auth.php:35` |
| Password reset | Livewire `ForgotPassword`, `ResetPassword` (token), `password_reset_tokens` table | `routes/auth.php:17-18`; `app/Livewire/Auth/*` |
| Email verification | Route `verify-email` + signed verify controller (wajib `verified` pada mayoritas route) | `routes/auth.php:23-33`; `app/Http/Controllers/Auth/VerifyEmailController.php` |
| Middleware auth | `auth` + `verified` pada route internal; `guest` pada login/register | `routes/web.php`, `routes/auth.php` |
| Role implementation | Enum `App\Enums\Role`; helper `hasRole()`, `hasAnyRole()`, `isPlatformUser()`; `platformCases()` (SuperAdmin/Admin) untuk UI User Management | `app/Enums/Role.php`, `app/Models/User.php:50-72` |
| Permission implementation | Permission Engine: `EventRole.permissions` JSON + `EventPermissionService` | `app/Services/Event/EventPermissionService.php`, `app/Support/EventRolePermissionDefaults.php` |
| Gate / Policy | Semua via `Gate::define` di `AppServiceProvider` (18 abilities: 4 platform + 14 event); tidak ada Policy class | `app/Providers/AppServiceProvider.php` |
| Helper isAdmin/isSuperAdmin | **Tidak ada** `isAdmin()`/`isSuperAdmin()`; yang ada `isPlatformUser()`; bypass via `Gate::before` (`super_admin`) dan `$eventAbility` (`admin`) | `app/Models/User.php:70`, `AppServiceProvider:28-40` |
| `can()` / `authorize()` | Route middleware `can:`; Livewire `Gate::authorize()`; Blade `@can()`/`@canany()` | `routes/web.php`, banyak `app/Livewire/*`, `resources/views/components/layouts/app/sidebar.blade.php` |
| Hardcoded role checks | `user->role === Role::SuperAdmin`/`Role::Admin` hanya di AppServiceProvider; `UserManagementService` proteksi "last super admin"; `PlatformDashboard`/`EventSwitcher` pakai `isPlatformUser()`; `MatchCenter:168` pakai `whereIn('role', ['super_admin','admin','juri'])` untuk daftar official (gap, lihat §8) | `AppServiceProvider:28-40`, `UserManagementService:79-83,183-248`, `MatchCenter:168` |

**Kesimpulan Auth:** authorisasi **tidak** lagi bergantung penuh pada Admin/SuperAdmin untuk fitur event — seluruh ability event sudah via Permission Engine. Ketergantungan platform-role hanya tersisa pada: master data (`view-master-data`/`manage-master-data` = SuperAdmin only), `manage-users` (SuperAdmin), `manage-events` (SuperAdmin/Admin), dan bypass Admin untuk ability event.

---

## 4. User vs Person Audit

**Status:** DB dan sebagian besar kode sudah mendukung `User → Person (optional)`, **tetapi runtime access dan beberapa fitur masih berasumsi Person selalu ada.**

### Yang sudah mendukung "Person optional"
- `users.person_id` **nullable** + `unique` + `nullOnDelete` (`2026_08_04_000001_add_person_id_to_users_table.php`).
- `User::hasPerson()`, `Person::user()`, `User::person()` relations (`app/Models/User.php:75-83`).
- `UserManagementService::create()` menerima `person_id` opsional (`app/Services/User/UserManagementService.php:44`).
- `EventCommitteeService::ensureUserForPerson()` membuat user ber-`email=null`, `role=null`, `person_id` terisi — arah Person→User (`app/Services/Activity/EventCommitteeService.php:140-169`).
- Sebagian besar blade memakai `?->person?->nama`.

### Yang MASIH mengasumsikan User→Person wajib / rusak bila person null

| # | Lokasi | Masalah |
|---|---|---|
| 1 | `app/Services/Event/EventAccessService.php:22-24,33-35` | `person_id === null` → **no access ke event mana pun** (Guest tanpa Person tidak bisa akses event). |
| 2 | `app/Services/Event/EventPermissionService.php:16-18,34-36` | `person_id === null` → **no permissions** untuk semua ability event. |
| 3 | `app/Livewire/Auth/Register.php` | Membuat User **tanpa person_id dan tanpa role** (`User::create($validated)` hanya name/email/password) → akun "mati" (dashboard kosong, 403 saat switch, semua gate denied). |
| 4 | `app/Livewire/Auth/ConfirmPassword.php:25` | `Auth::validate(['email' => Auth::user()->email, ...])` gagal untuk user `email=null` (komite auto-created) → **password confirmation tidak pernah bisa lulus**. |
| 5 | `app/Livewire/Settings/Profile.php:23,36-43` | `email` wajib (`required`) → user `email=null` tidak bisa menyimpan profile. |
| 6 | `app/Livewire/SuratIzin/Create.php:106` | `$participation->person->nama` dereference langsung tanpa null-check. |
| 7 | `resources/views/livewire/pengajian/identity-correction-review.blade.php:28` | `{{ $request->person->nama }}` tanpa `?->` (risiko rendah, FK restrict). |
| 8 | `app/Livewire/Competition/MatchCenter.php:168` | Daftar official filter `users.role IN ('super_admin','admin','juri')` → user komite role-`juri` (role=null) **tidak pernah muncul** sebagai official. |
| 9 | `app/Livewire/MasterData/User/EditUser.php:112` + `UserManagementService:71-77` | User `role=null` **tidak bisa diedit** di UI (validasi `role required`); mengedit berarti memaksa jadi platform role. |
| 10 | `app/Livewire/MasterData/Person/DeletePerson.php` | Delete person tidak mengecek relasi `users`; `nullOnDelete` membuat user kehilangan akses event diam-diam. |
| 11 | `app/Livewire/Dashboard/PlatformDashboard.php:20-23,51-53` | User non-platform + `person_id=null` → daftar event kosong + label "Tidak ada peran" (silent dead-end). |

### Flow registrasi
- `/register-user` (`Auth\Register`): buat User login, **tanpa Person** (memang optional).
- `/register` (`Registrasi\SelfRegister`): buat **Person + Participation**, **tanpa User** (flow publik partisipan).
- Komite: `CommitteeManagement → EventCommitteeService::assignAndEnsureUser` → Person harus sudah ada, lalu User di-auto-create (email null).

**Kesimpulan:** Relasi `users.person_id` nullable sudah ada, tapi runtime authorization **masih mewajibkan person** (EventAccessService/EventPermissionService). Guest-account tanpa Person saat ini **tidak berguna** — ini adalah gap utama untuk kebutuhan §6 & §8.

---

## 5. Event Access Audit

Mekanisme:
- `ActiveEventContext` (session singleton) — `current()` baca session, `set()` simpan session, `switchTo()` ganti; hanya event `active`. `app/Support/ActiveEventContext.php`.
- `ResolveActiveEvent` middleware — set context dari URL `{event}` **sebelum** gate berjalan (`app/Http/Middleware/ResolveActiveEvent.php`). Ini mencegah stale-session untuk route yang punya middleware ini.
- `EventAccessService::canAccess()` — platform user → true; selain itu butuh `EventCommitteeAssignment` via `person_id`. `EventAccessService::isUserAssignedToEvent()`.
- `EventSwitcher` — dropdown hanya event yang di-assign; `switchTo()` throw `AuthorizationException` untuk unassigned. `app/Livewire/Event/EventSwitcher.php`.
- `ResolvesEventDashboard` trait — mount-level `abort_unless(EventAccessService::canAccess(...), 403)` + `abort_unless(isActive, 404)`. `app/Livewire/Traits/ResolvesEventDashboard.php`.
- Sidebar — `@can()`/`@canany()` per menu. `resources/views/components/layouts/app/sidebar.blade.php`.
- Route model binding `{event}` → implicit binding by `id` (Event tidak override `getRouteKeyName`; `slug` unik tapi tak dipakai untuk binding).

### Uji konseptual "User A hanya boleh Event 1"

| Skenario direct URL | Hasil | Keterangan |
|---|---|---|
| `GET /events/2` | ⚠️ 200 public | `PublicEventController::event` hanya cek `isActive()` — halaman publik event (by design). Data publik (nama, jadwal publik). |
| `GET /events/2/dashboard` | ✅ 403 | route middleware `can:view-dashboard` + `resolve.active-event`; gate eval event=2 dari URL. |
| `GET /events/2/registrasi` | ✅ 403 | `can:manage-registration`. |
| `GET /events/2/database` | ✅ 403 | `can:manage-participants`. |
| `GET /events/2/qr-label/print/selected/{p}` | ⚠️ | route gated, controller verifikasi `EventOwnership::belongsToEvent` (`PublicEventController.php:194,201`) → aman untuk controller. |
| `GET /events/2/surat-izin` | ⚠️ 403 di route, **TAPI** halaman tidak scoped | gate lolos untuk Event 1, list + mutator baca **semua** surat izin lintas event → tembus data Event 2 (C1). |
| `GET /events/2/competition/match-center` | ⚠️ | gate `manage-matches` (event 2, denied untuk A) — aman di route; **TAPI** jika A juga punya akses Event 1, `startMatch($scheduleId)` bisa menarget schedule Event 2 (C4). |
| `GET /koreksi-data` | ⚠️ CROSS-EVENT | route tanpa `resolve.active-event` → gate di-resolve dari **session**; list pending + approve/reject semua event (C3). |
| `GET /events/2/pengajian/report` | ⚠️ | route gated `can:view-reports` + resolve → aman. |
| `GET /events/2/pengajian/admin/manual-entry` | ⚠️ | `can:manage-pengajian` + resolve → aman di route. |

**Kesimpulan:** Defense-in-depth di level route **baik** untuk route yang punya `resolve.active-event`. Kelemahan ada di: (a) komponen Livewire yang tidak melakukan event-scoping record-level, (b) route yang **tidak** punya `resolve.active-event` tetapi pakai gate event, (c) session fallback di `ActiveEventContext::current()` yang memungkinkan gate bernilai event lama bila middleware tidak berjalan.

---

## 6. Role & Permission Audit

### Model saat ini (terverifikasi dari kode)

```
User.role (users.role, string nullable, enum Role)
  ├── super_admin  → platform global (Gate::before bypass semua)
  ├── admin        → platform (bypass semua ability event via $eventAbility)
  └── (legacy) ketua_event, sekretariat, pj_divisi, operator_registrasi,
      operator_scan, juri, viewer  → DISIMPAN tapi runtime engine MENGABAIKAN

EventRole (event_roles, per-event)
  ├── event_id, name, code, permissions (JSON), is_active
  └── EventCommitteeAssignment → person → event → role + permissions
```

- 4 Gate platform: `view-master-data`, `manage-master-data` (SuperAdmin only), `manage-events` (SuperAdmin/Admin), `manage-users` (SuperAdmin only).
- 14 Gate event-scoped: `view-dashboard`, `manage-registration`, `manage-participants`, `manage-attendance`, `manage-sessions`, `manage-qr-labels`, `manage-secretariat`, `manage-import`, `view-reports`, `manage-pengajian`, `view-activity-log`, `manage-matches`, `manage-officials`, `submit-result`. Semua via `EventPermissionService`.
- Default permissions per code: `app/Support/EventRolePermissionDefaults.php` (`ketua_event`=7 ability, `sekretariat`=11, `operator_registrasi`=1, `operator_scan`=1, `pj_divisi`=2, `juri`=`['submit-result']`, `viewer`=2, `admin_event`=semua, `ketua_fosda`=3).

### Analisis: Role global vs Role + Event Membership + Permission

Sistem sudah punya **keduanya**, dan runtime sudah memakai **Role (EventRole) + Membership (EventCommitteeAssignment) + Permission (permissions JSON)** — persis konsep target. Masalah yang tersisa:

1. **Dual-identity role**: `users.role` masih bisa berisi event-role (enum, factory, artisan, test) sementara runtime mengabaikannya → kebingungan dan potensi salah-pahami. **DOCUMENTATION DRIFT** (lihat §12).
2. **Membership terikat Person**: `EventCommitteeAssignment.person_id` NOT NULL. Tidak ada jalur `User` langsung → Guest tidak bisa jadi anggota event.
3. **Role-per-event belum "langsung" untuk User**: untuk mendapat role di event, user harus di-connect ke Person dulu, lalu Person di-assign. Untuk Guest (PJ) yang bukan orang di database `people`, ini paksaan artifisial.
4. **`manage-events` sebagai gate platform** dipakai juga untuk konfigurasi Competition (category/class/venue/schedule/operator/bracket). Artinya konfigurasi Competition hanya untuk SuperAdmin/Admin — **Ketua Event (EVENT_CHAIR) tidak bisa** mengkonfigurasi eventnya sendiri. Ini bertentangan dengan kebutuhan Event Chair bila nanti `EVENT_CHAIR` harus bisa mengelola event yang di-CHAIR.

---

## 7. Event Chair Gap Analysis

**Kebutuhan:** Ahmad (EVENT_CHAIR, Event CAI 2026) → boleh CAI 2026, tidak boleh Pengajian 2026 / event lain.

**Status sistem saat ini:**

| Aspek | Kondisi | Verdict |
|---|---|---|
| Assignment per event | `EventCommitteeAssignment` per (event, person, role) → akses tersegmentasi per event | ✅ Didukung |
| EventSwitcher visibility | Non-platform hanya melihat event yang di-assign | ✅ Didukung (`EventSwitcher.php:51-62`, `PlatformDashboard.php:20-28`) |
| Switch enforcement | `switchTo()` throw AuthorizationException utk unassigned | ✅ Didukung |
| Route gate event-scoped | `can:` di-resolve dari event URL (bila `resolve.active-event` ada) | ✅ Sebagian — gap di route tanpa resolve (C3) & record-level |
| Role EVENT_CHAIR | Belum ada code `event_chair`; yang ada `ketua_event` (7 ability) | ⚠️ Hanya soal penamaan/backfill |
| Admin bypass | `admin` bypass semua ability event | ⚠️ Wajar (platform), tapi pastikan tidak ada user event yang kebetulan ber-role admin |
| Gate `manage-events` untuk konfigurasi | Platform-only (SuperAdmin/Admin) | ⚠️ Ketua Event tidak bisa konfigurasi event sendiri |
| Record-level scoping | Banyak komponen by-ID tanpa cek event_id | ❌ GAP (bagian §11) |
| Stale session fallback | Gate tanpa resolve memakai session | ❌ GAP (C3) |

**Gap yang harus ditutup untuk Event Chair yang benar-benar aman:**
1. **Record-level ownership** pada semua mutator Livewire yang memuat model by ID (C1–C7 di §11).
2. **Route `koreksi-data`, `import/*` diberi `resolve.active-event`** atau diubah jadi event-scoped.
3. **Keputusan** apakah Ketua Event boleh akses konfigurasi Competition (`manage-events` saat ini platform-only). Rekomendasi: tambahkan ability event seperti `manage-competition-config` (atau relaksasi `manage-events` di level event) — harus dibahas (lihat §17).
4. **Users.role tidak boleh lagi menyimpan event-role** (rapikan agar tidak ada user event yang "kebetulan" super_admin/admin).

---

## 8. Guest Without Person Gap Analysis

**Kebutuhan:** Guest/PJ `pj@example.com` dengan email+password+access, **tanpa Person**, bisa mengakses event tertentu.

**Kondisi sekarang:**
- DB: `users.person_id` nullable → **secara skema diperbolehkan**.
- Runtime: `EventAccessService` (person_id null → false) dan `EventPermissionService` (person_id null → false) → **Guest tanpa Person tidak bisa mengakses event apa pun**.
- Tidak ada tabel `user ↔ event` langsung (tanpa person). Satu-satunya jalur: `users.person_id → person → event_committee_assignments` atau `→ participations` (partisipan, bukan akses dashboard).
- Akun yang dibuat via `/register-user` (tanpa person, tanpa role) → "dead account".
- Komite auto-created (`ensureUserForPerson`) → punya Person, tapi `email=null` & `role=null` → bermasalah di `ConfirmPassword`, `Profile`, `MatchCenter` officials, dan tidak bisa diedit di UI.

**Kode yang mengasumsikan `$user->person` selalu ada:** lihat tabel §4 (#1,2,3,6,7) dan `EventAccessService`, `EventPermissionService`, `PlatformDashboard`, `EventSwitcher`. Hampir semua sudah null-safe atau memakai `?->` — yang bermasalah adalah service authorization yang **sengaja** menolak person null.

**Rekomendasi (untuk fase implementasi nanti, bukan sekarang):** tambahkan jalur membership berbasis **User** langsung (nullable `user_id` di `event_committee_assignments` atau tabel `event_memberships`), sehingga:
```
User (Guest, tanpa Person)
 └── EventMembership → Event + Role + Permission
```
tanpa mengubah `users` atau memaksa Person. Person tetap opsional untuk mereka yang memang orang nyata (ketua, peserta).

---

## 9. Database Audit

Ringkasan skema (detail constraint dari migration; `!` = NOT NULL, `?` = nullable).

| Table | Purpose | Current Relation | Problem | Recommendation |
|---|---|---|---|---|
| `users` | akun login | `person_id?`→people (nullOnDelete), `role?` string, `email?` unique, `username?` unique, `is_active!` | `email` dibuat nullable tapi banyak UI/flow masih anggap wajib; `role` masih bisa simpan event-role (legacy) | Tidak ada migration baru di fase ini. Saat Guest: pastikan flow email-null benar; rapikan role. |
| `people` | master identitas | `desa_id?`→desas (nullOnDelete), `kelompok_id?`→kelompoks (nullOnDelete); `nip` retired | Tidak ada unique `(nama, desa_id)` → duplikat Person mungkin | Saat Competition: pertimbangkan dedupe policy |
| `events` | event | `slug` unique, `event_type` default 'cai', `status` string | `status` free-string (tidak ada enum/check); mayoritas FK child `restrictOnDelete` → delete event hampir mustahil bila ada data | Biarkan; sudah ada `hasRuntimeDependencies()` |
| `event_roles` | role per event | `event_id`→events (restrict); unique `(event_id,name)` & `(event_id,code)`; `permissions` JSON; `is_active` | `code` nullable di schema tapi model mewajibkan known-code saat create | OK |
| `event_committee_assignments` | membership komite (access) | `event_id`!→events, `person_id`!→people, `participation_id?`→participations (nullOnDelete), `event_role_id`!→event_roles; unique `(event_id,person_id,event_role_id)`; target `activity_group_id/activity_id/venue_id?` | **person_id NOT NULL** → tidak bisa untuk Guest; unique 3-tuple tidak meliputi `participation_id` sehingga multi-target bisa bentrok bila person_id null | Tambah `user_id?` nullable (jalur Guest) + adjust unique — fase implementasi |
| `participations` | partisipasi Design C | `person_id`!→people, `event_id`!→events, `regu_id?`→regus (nullOnDelete); unique `(event_id,person_id)`, `(event_id,participant_number)`, `attendance_code`; `status_registrasi?` | `status_registrasi` nullable tanpa default; `attendance_code` unique global (bukan per-event) | Perhatikan unikness global attendance_code saat multi-event besar |
| `event_attendances` | absensi kanonik | `participation_id`!→participations (restrict), `sesi_absensi_id?`, `event_id`!→events, `recorded_by?`→users; unique partial (SQLite) / trigger (MySQL) | Unikness trigger-based di MySQL (raw SQL bisa bypass); `sesi_absensi_id.event_id` tidak dicek sama `event_id` | — |
| `desa_access_grants` | akses desa (token) | `event_id`!→events, `desa_id`!→desas, `created_by?`→users; nonce unique; token_hash tidak unique | — | — |
| `competition_categories/classes` | kategori/kelas | `event_id`!; unique `(event_id,name)` & `(event_id,code)`; `class.gender!` (NOT NULL no default — rantai migration kontradiktif) | Migration `gender` bolak-balik (nullable→NOT NULL default 'L'→NOT NULL no default); `competition_category_id` ditambah NOT NULL tanpa backfill | Rapikan saat menambah struktur Competition baru |
| `competition_registrations` | registrasi lomba | `participation_id`!→participations, `category`/`class`!; unique `(participation_id,class)`; `registration_type` string default 'individual' | **Tidak ada event_id/person_id langsung** (transitif via participation); tidak ada cek cross-event category/class/participation; "team" = `registration_type` + `participations.regu_id` (tidak ada tabel team) | Competition baru harus punya scoping eksplisit |
| `competition_schedules` / `_entries` | jadwal | class→classes, venue?, winner_registration?; entries unique `(schedule,registration)` | Tidak dicek registration.class == schedule.class; entry cascade delete | — |
| `competition_outcomes` | hasil | `competition_registration_id` unique (cascade) | redundant index | — |
| `competition_brackets` / `_matches` | bracket | class→classes (restrict); matches: schedule cascade, self source nullOnDelete; unique `(bracket,round,position)` | `competition_schedule_id` nullable + cascade (hapus schedule menghapus match); tidak ada unique bracket name | — |
| `competition_match_officials` | official | `schedule`!→schedules (cascade), `user_id`!→users (cascade); unique `(schedule,user,role)` | cascade delete user → official hilang; role free-string | — |
| `pesertas` (legacy) | data lama | `kelompok_id?`→kelompoks (cascade), `desa_id?`→desas (cascade); `regu_id` & `nip` retired | `absensis.nip` masih dangling; double-cascade desa→kelompok→peserta bisa hapus banyak | — |
| `kelompoks` / `regus` / `desas` | master data | `regus` global (tidak event-scoped); unique `regu` | `regu` global tanpa event → potensi tabrakan antar-event | Saat Competition team: pertimbangkan scoping |
| `surat_izins` | izin | `peserta_id?`→pesertas (**cascade**), `event_id?`, `participation_id?`, `created_by`!→users (**cascade**), `approved_by?` | **`created_by` cascadeOnDelete** — hapus user menghapus semua surat izin buatannya (destruktif & tidak konsisten vs `approved_by` nullOnDelete); `peserta_id` nullable+cascade | Ganti ke nullOnDelete (fase perbaikan; bukan sekarang) |
| `legacy_peserta_mappings` / `legacy_participation_mappings` | jembatan legacy | `peserta_id`!→pesertas, `person_id`!→people, `participation_id`!→participations (pada LPM) | Migration `2026_08_09_000001_make_legacy_mapping_fk_nullable` adalah **NO-OP/dead**; up/down asimetris | — |
| `activity_*` / `rundown_*` / `venues` | kegiatan | semua `event_id`! restrict; unique per-event | Tidak ada composite FK memastikan participation.event_id == activity.event_id → cross-event mismatch mungkin | — |
| `cai_participant_replacements` | penggantian | `peserta_id?`, `old/new_participation_id?` nullable tapi FK restrict | NULL tolerated saat insert tapi delete parent diblokir | — |
| `identity_correction_requests` | koreksi | `person_id`!→people (restrict), `event_id?`→events, `reviewed_by?` | — | — |

**Anomali utama DB:**
1. Rantai migration `competition_classes.gender` kontradiktif (nullable → NOT NULL 'L' → NOT NULL tanpa default).
2. `2026_08_05_000001_add_competition_category_id_to_competition_classes` menambah FK NOT NULL tanpa backfill.
3. `surat_izins.created_by` cascadeOnDelete (destruktif, inkonsisten dengan user-FK lain yang nullOnDelete).
4. `absensis.nip` dangling (nip sudah di-retire dari `pesertas` & `people`).
5. `izin_absensis` unique `(peserta_id,sesi_id)` bisa dilanggar bila peserta_id NULL.
6. `competition_brackets` tidak punya unique `(class_id, name)`.
7. Banyak tabel kompetisi/activity tidak punya composite FK cross-event (orphan-by-inconsistency).
8. Tidak ada tabel relasi `user ↔ event` langsung.

---

## 10. Competition Readiness

Struktur Competition **sudah ada sebagian besar** (Sprint 7–10):

```
Event
 └── CompetitionCategory
 └── CompetitionClass (event + category + gender)
 └── CompetitionRegistration → Participation → Person
 └── CompetitionSchedule / ScheduleEntry
 └── CompetitionOutcome
 └── CompetitionBracket / BracketMatch / MatchOfficial
 └── CompetitionAnnouncement
```

### Cocok/tidak untuk target Competition (Event → Competition → CompetitionClass → Participation → Team → TeamMember → Match/Heat → Result → Schedule)

| Kebutuhan | Kondisi sekarang | Verdict |
|---|---|---|
| Event discriminator | `events.event_type` ('cai'/'pengajian'/'competition') + `Event::isCompetition()`, `dashboardRoute()` | ✅ |
| Design C (Person→Participation→Attendance) | `people`, `participations`, `event_attendances` — kanonik, sudah cutover | ✅ **Jangan diubah** |
| CompetitionClass | `competition_classes` (event_id, category_id, gender, unique name/code per event) | ✅ |
| Participation → Competition | `competition_registrations.participation_id` (unique per class) | ✅ |
| Team / Regu | `participations.regu_id → regus` (nullable) + `registration_type` string ('individual') | ⚠️ **Tidak ada tabel Team/TeamMember**. `regus` adalah entity global non-event-scoped. Team asli (anggota majemuk per kelas) butuh struktur baru |
| Match / Heat | `competition_schedules` (status Scheduled/Playing/…) + `_entries` (registrations per schedule) | ⚠️ Jadwal per kelas; belum ada konsep heat/round generik di luar bracket |
| Result | `competition_outcomes` (position/score/status, unique per registration) + `winner_registration_id` di schedule | ✅ parsial; hasil per registrasi, belum per team-member |
| Schedule | `competition_schedules` + `_entries` + venue | ✅ |
| Event-scoping | Semua tabel competition punya `event_id` (langsung atau transitif) | ⚠️ `competition_registrations` hanya transitif via participation; perlu scoping eksplisit |
| Identifier | `attendance_code` (global unique), `participant_number` (unique per event) | ✅ |

**Rekomendasi Competition (fase berikutnya, bukan sekarang):**
- Jangan menyentuh `people`/`participations`/`event_attendances` (Design C tetap utuh).
- Tambah struktur Team/TeamMember sebagai **entitas baru** yang menunjuk `event_id` + `competition_class_id` + (opsional) anggota via `competition_registrations`/`participation_id` — jangan menempatkan anggota di `regus` global.
- Pastikan semua query Competition di-scope `event_id` eksplisit (bukan transitif), dan perbaiki celah IDOR di MatchCenter/OfficialPanel/ParticipantList terlebih dahulu karena modul Competition akan memperluas permukaan ini.

---

## 11. Security Risks

Ditemukan dalam audit ini (belum diperbaiki — fase audit).

### CRITICAL — cross-event data access/manipulation (IDOR)

| ID | Lokasi | Risiko |
|---|---|---|
| C1 | `app/Livewire/SuratIzin/Index.php:29,56,76,89,100,114` | List + `submit/approve/reject/markReturned` tanpa filter `event_id`; user `manage-secretariat` Event 1 bisa setujui/kelola surat izin Event 2; `SuratIzinService::approve()` menulis attendance ke event aktif (cross-event attendance). |
| C2 | `app/Livewire/QRLabel/Index.php:84-85,324` | `Participation::find($id)` tanpa scope event → user Event 1 bisa unduh QR/`attendance_code` partisipan Event 2 (kode scan absensi) + PII. |
| C3 | `routes/web.php:205-208` + `app/Livewire/Pengajian/IdentityCorrectionReview.php:20,38,80` | Route `koreksi-data` **tanpa `resolve.active-event`** → gate event di-resolve dari session; `IdentityCorrectionService::listPending()` query semua event; approve/reject request lintas event. |
| C4 | `app/Livewire/Competition/MatchCenter.php:58,75,100-110,120` | `CompetitionSchedule::findOrFail($id)` tanpa cek event; user `manage-matches` Event 1 bisa start/complete match & assign official Event 2. |
| C5 | `app/Livewire/Competition/OfficialPanel.php:50-52,92-94` | `findOrFail($scheduleId)` tanpa event check; user `submit-result` Event 1 bisa submit hasil Event 2. |
| C6 | `app/Livewire/Registrasi/ImportParticipation.php:37-43` + `app/Services/Import/Adapters/Participation/ParticipationImportCommitter.php:28,54` | `parameters['event_id']` adalah public property tanpa validasi = active event; user `manage-registration` Event 1 bisa import partisipan ke Event 2. |
| C7 | `app/Livewire/Database/Peserta/GantiPeserta.php:49,113-115,157-188` | `participation_id` + `event_id` dari input user tanpa verifikasi = active event; user `manage-participants` Event 1 bisa membuat participation/replacement di event lain. |

### HIGH

| ID | Lokasi | Risiko |
|---|---|---|
| H1 | `routes/web.php:62-64` + `app/Livewire/Database/Regu/{Tambah,Edit,Hapus,Data}Regu.php` | Route `/regu` hanya `auth+verified` (tanpa `can:`); komponen CRUD **tanpa `Gate::authorize`** → semua user login bisa create/edit/delete `regus` global. |
| H2 | `app/Livewire/Audit/ActivityLogIndex.php:23` | Query semua `ActivityLog` (tabel tidak punya `event_id`); user `view-activity-log` Event 1 bisa baca log audit semua event. |
| H3 | `app/Livewire/Database/Peserta/TambahPeserta.php:323-328,346` | Person search meng-expose participation lintas event (minor). |

### MEDIUM

| ID | Lokasi | Risiko |
|---|---|---|
| M1 | `app/Livewire/Competition/ParticipantList.php:45,56-65`, `Competition/Registration.php:232` | Query `CompetitionClass`/`CompetitionRegistration` by attacker-controlled ID tanpa event check (read cross-event). |
| M2 | Competition CRUD by-ID tanpa event check (`Category/Class/Venue/Schedule/Operator/Bracket/EntryManager/OutcomeManager`) | IDOR latent, tapi halaman `manage-events` = platform-only (Admin/SA) → risiko rendah sekarang; menjadi HIGH bila `manage-events` dibuka untuk event chair. |
| M3 | `app/Livewire/SuratIzin/Create.php:114` | Fallback ke `peserta::find($id)` tanpa cek event; bisa buat surat izin untuk person bukan partisipan event aktif. |
| M4 | `app/Livewire/Competition/MatchCenter.php:168` | Official list hanya user ber-role platform/juri → official dari komite (role=null) tidak muncul. |

### Route tanpa auth (by design, tapi perlu dicatat)
- `events/{event}/pengajian/*` (enter-token, desa, desa/tambah, qr-print) — **tanpa `auth`**, proteksi via grant token + validity di dalam komponen.
- `pengajian/hadir/{nonce}` — publik (throttle).
- `events/{event}/competition/viewer` — publik TV, di-scope oleh mount (`Competition\Viewer.php:25-35`).
- Route publik `/events/{event}` + schedule/bracket/announcements.

---

## 12. Breaking Changes Risk

Perubahan yang disarankan di fase implementasi berikut **tidak** seharusnya merusak: Platform Dashboard → Pilih Event → Event Dashboard, Design C, Permission Engine. Berikut pemetaan risikonya:

| Perubahan | Risiko ke code sekarang | Mitigasi |
|---|---|---|
| Tambah jalur membership berbasis User (nullable `user_id` di ECA atau tabel baru `event_memberships`) | **Rendah** — additive; `person_id` path tetap; `EventAccessService`/`EventPermissionService` menambah OR branch tanpa mengubah branch existing | Selalu resolved via `user_id OR person_id`; tes existing (S7Two, PermissionEngine, Rbac) tetap hijau |
| Perbaiki record-level scoping (C1–C7) | **Rendah–Sedang** — menambah `where('event_id', activeEvent->id)` / `EventOwnership` check; bisa mengubah behavior test yang mengharapkan perilaku lama (jika ada) | Jalankan suite penuh; tambah test negatif cross-event |
| Route `koreksi-data` + `import/*` diberi `resolve.active-event` | **Sedang** — mengubah source gate dari session ke URL; ada kemungkinan test mengharapkan perilaku session-based | Audit test terkait; sesuaikan ekspektasi |
| Hapus event-role dari `users.role` (enum/platform) | **Sedang** — enum & beberapa test & factory masih memakai event-role di users.role | Lakukan bertahap: deprecated dulu, pertahankan `Role::values()` compat, update test |
| Gate konfigurasi Competition untuk event chair | **Sedang** — `manage-events` dipakai di banyak komponen; perlu ability baru per-event | Tambah ability event baru, jangan repurpose `manage-events` |
| Perubahan UI (menu/role) | Rendah — sidebar sudah `@can`-driven | — |

**Risiko terbesar saat ini** bukan dari perubahan di atas, tapi dari **celah yang sudah ada** (C1–C7, H1–H2) jika dibiarkan saat modul Competition dibuat — karena Competition akan menambah permukaan by-ID yang sama.

---

## 13. Recommended Architecture

```
CURRENT (implementasi saat ini)
User
 ↓
Role (users.role — platform only secara runtime)
 ↓
Event (via Person → EventCommitteeAssignment → EventRole.permissions)

PROPOSED
User / Account
 ├── Person (optional)
 │      └── Participation (Design C — tidak berubah)
 │
 └── Event Membership
       ├── Event
       ├── Role            (EventRole: code + permissions JSON)
       └── Permission      (EventRole.permissions)
```

**Catatan penting:** PROPOSED **hampir seluruhnya sudah ada** di codebase sekarang. Yang perlu ditambahkan hanyalah:

1. Jalur membership yang bisa menunjuk **User langsung** (tanpa Person) → `event_committee_assignments.user_id` nullable, atau tabel baru `event_memberships` (event_id, user_id, event_role_id). Opsi terendah-risiko: tambah `user_id?` di `event_committee_assignments` + ubah unique menjadi `(event_id, user_id, event_role_id)` / `(event_id, person_id, event_role_id)` yang salah satunya null (partial unique di SQLite/MySQL).
2. `EventAccessService` & `EventPermissionService` resolve membership via `user_id` **atau** `person_id`.
3. Role target `SUPER_ADMIN / ADMIN / EVENT_CHAIR / GUEST`:
   - `SUPER_ADMIN`, `ADMIN` = platform role (`users.role`) — sudah ada.
   - `EVENT_CHAIR`, `GUEST` = **EventRole code** (`ketua_event` sebagai seed code `event_chair`, dan code `guest`/`pj` baru) — **bukan** kolom `users.role`.

### Kompatibilitas (jawaban 6 pertanyaan wajib)

1. **Kompatibel dengan code sekarang?** Ya — arsitektur ini adalah perpanjangan dari apa yang sudah berjalan (Permission Engine). Perubahan bersifat *additive* pada membership, tidak mengganti arsitektur.
2. **Membutuhkan migration?** Minimal 1 migration additive: `event_committee_assignments.user_id` nullable (+ index) atau tabel `event_memberships`. Tanpa perubahan tabel `users`. Opsional backfill: salin `person→user` linkage ke `user_id` untuk data existing.
3. **Membutuhkan perubahan authorization?** Ya, terbatas: update `EventAccessService`/`EventPermissionService` untuk support `user_id` OR `person_id`; tambah Gate untuk Guest; dan perbaiki gap record-level (C1–C7) + route `resolve.active-event` (C3).
4. **Membutuhkan perubahan UI?** Ringan: EventSwitcher/PlatformDashboard otomatis terbaca (karena pakai service); User Management perlu form "Guest/PJ" (tanpa person, tanpa role platform); sidebar otomatis (sudah `@can`). Tidak perlu redesign.
5. **Berisiko merusak Design C?** **Tidak** — membership terpisah dari `Person → Participation → Attendance`. `people`, `participations`, `event_attendances` tidak disentuh.
6. **Cara migrasi paling aman?** Urutan: (a) tambah kolom `user_id` nullable (additive), (b) backfill dari `person→user`, (c) update service + gate (test penuh hijau), (d) tambah role EventRole `event_chair`/`guest` sebagai template, (e) perbaiki celah IDOR + record-level scoping, (f) baru buat modul Competition lanjutan (Team/TeamMember/Result) dengan event-scoping eksplisit.

---

## 14. Files That Would Need Changes

(Untuk fase implementasi berikut — **bukan** dieksekusi sekarang.)

**Authorization & access (inti):**
- `app/Services/Event/EventAccessService.php` — support user_id OR person_id
- `app/Services/Event/EventPermissionService.php` — support user_id OR person_id
- `app/Providers/AppServiceProvider.php` — gate Guest; ability config baru (opsional)
- `app/Support/EventRolePermissionDefaults.php` — tambah template `event_chair`, `guest`
- `app/Support/ActiveEventContext.php` — (tidak wajib)
- `app/Http/Middleware/ResolveActiveEvent.php` — (tidak wajib)

**Celah IDOR / event-scoping (perbaiki sebelum Competition lanjutan):**
- `app/Livewire/SuratIzin/Index.php` (C1) + `app/Services/Attendance/SuratIzinService.php`
- `app/Livewire/QRLabel/Index.php` (C2)
- `app/Livewire/Pengajian/IdentityCorrectionReview.php` + `routes/web.php` (C3)
- `app/Livewire/Competition/MatchCenter.php` (C4)
- `app/Livewire/Competition/OfficialPanel.php` (C5)
- `app/Livewire/Registrasi/ImportParticipation.php` + `ParticipationImportCommitter.php` (C6)
- `app/Livewire/Database/Peserta/GantiPeserta.php` (C7)
- `app/Livewire/Database/Regu/*` + `routes/web.php` (H1)
- `app/Livewire/Audit/ActivityLogIndex.php` (H2)
- `app/Livewire/Competition/ParticipantList.php` (M1), `SuratIzin/Create.php` (M3)

**User/Guest:**
- `app/Livewire/Auth/Register.php`, `app/Livewire/Auth/ConfirmPassword.php`, `app/Livewire/Settings/Profile.php` — user email-null / person-null
- `app/Livewire/MasterData/User/{IndexUser,CreateUser,EditUser}.php` + `app/Services/User/UserManagementService.php` — Guest/PJ creation
- `app/Livewire/Competition/MatchCenter.php` — officials list
- `app/Livewire/MasterData/Person/DeletePerson.php` — cek relasi user

**Migration (fase implementasi):**
- `event_committee_assignments.user_id` nullable (+ unique adjust) ATAU tabel `event_memberships`
- (opsional) perbaikan FK `surat_izins.created_by` → nullOnDelete

---

## 15. Migration Plan

Fase **audit** ini tidak membuat migration. Rencana untuk fase implementasi (urutan paling aman):

1. **Migration additive:** tambah `user_id` nullable + index pada `event_committee_assignments`. Data backfill: untuk setiap assignment dengan person → user yang sudah ada, isi `user_id`. (Rollback: drop column.)
2. **Backfill role (opsional):** tambah template EventRole `event_chair` (= permission `ketua_event`), `guest`/`pj` (= permission `viewer`/`manage-pengajian` sesuai kebutuhan) via seeder/command, bukan migration yang menjalankan model (hindari pola model-driven migration).
3. **Data-cleanup migration (terpisah, setelah service update):** ubah `surat_izins.created_by` FK ke `nullOnDelete`; drop `absensis.nip` bila memang mati; rapikan rantai `competition_classes.gender`.
4. Semua migration baru **additive-first**, diuji dengan `migrate:fresh` + suite penuh sebelum dipotong ke prod.

---

## 16. Test Plan

### Baseline (dijalankan pada audit ini)

- Command: `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest` (PHP 8.4 CLI static; PDO sqlite tersedia).
- Hasil: **Tests: 2229 passed (5782 assertions). Duration: 72.69s.**
- **Failed: 0. Skipped: 0. Risky: 0.**
- Catatan env: `php artisan test` dengan `memory_limit` default (128M) gagal di test export Excel (zipstream `vendor/maennchen/zipstream-php/src/File.php:334`). Ini **bukan** kegagalan test — constraint lingkungan. Jalankan dengan `-d memory_limit=1G`.

### Cakupan test yang relevan (per area)

| Area | File tes utama |
|---|---|
| Authentication | `tests/Feature/Auth/*` (Authentication, Registration, PasswordReset, PasswordConfirmation, EmailVerification) |
| Authorization / RBAC | `tests/Feature/Security/RbacFoundationTest.php`, `PermissionEngineTest.php`, `UserManagementTest.php`, `UserManagementRbacConsistencyTest.php`, `MasterDataProtectionTest.php`, `RemainingSecurityProtectionTest.php` |
| Event access / switcher | `tests/Feature/Event/ActiveEventContextHardeningTest.php`, `EventFoundationTest.php`, `EventRoleCrudTest.php`, `EventRoleBackfillMigrationTest.php`, `SidebarModeTest.php`, `RoutingConsolidationTest.php`, `DashboardConsolidationTest.php`, `EventTypeTest.php`, `Security/S7TwoEventScopedAuthorizationTest.php`, `S7OneFoundationTest.php`, `S7ThreeAssignmentManagementTest.php` |
| User / Person | `tests/Feature/MasterData/PersonMasterDataTest.php`, `tests/Feature/Person/PersonFoundationTest.php`, `tests/Feature/MasterData/User/*` |
| Participation / Design C | `tests/Feature/Participation/ParticipationFoundationTest.php`, `tests/Feature/Registrasi/*` (17 file), `tests/Feature/DesignCDiagnosticsTest.php`, `Cai/*` |
| Pengajian (guest/token) | `tests/Feature/Pengajian/*` (15 file) — termasuk `PengajianAccessTest`, `PengajianSecurityTest`, `SignedUrlSchemeTest` |
| Competition | `tests/Feature/Competition/*` (5 file) + `tests/Feature/Event/DashboardConsolidationTest.php` |
| Database/migration | `tests/Feature/Database/*` (19 file) — `ProductionLikeMigrationSafetyTest`, `LegacyFkPreservationTest`, `EventRoleBackfillMigrationTest` |

### Test yang perlu ditambah saat implementasi (rekomendasi, bukan sekarang)
1. Negatif cross-event untuk setiap IDOR: user assigned Event 1 + record Event 2 → expect 403/denied (SuratIzin, QR label, MatchCenter, OfficialPanel, GantiPeserta, ImportParticipation, IdentityCorrection).
2. Guest (user tanpa Person) dengan `EventMembership` → bisa akses event; tanpa membership → denied.
3. Route tanpa `resolve.active-event` yang sekarang bermasalah (koreksi-data) → setelah diperbaiki, gate event-scoped dari URL.
4. EventChair tidak bisa akses event lain (sudah ada pola di S7Two — tambahkan untuk code `event_chair`).
5. `ConfirmPassword`/`Profile` untuk user email-null (komite).
6. MatchCenter officials: user role-null dengan EventRole `juri` muncul di daftar official.

---

## 17. Open Questions / Decisions Required

1. **Guest/PJ sebagai apa?** Guest tanpa Person diberi role event `guest`/`pj` — apakah cukup via `event_committee_assignments.user_id`, atau butuh tabel `event_memberships` terpisah? (Rekomendasi: kolom `user_id` nullable di ECA — lebih sedikit permukaan.)
2. **Event Chair & konfigurasi Competition:** bolehkah EVENT_CHAIR mengelola kategori/kelas/venue/schedule/bracket eventnya sendiri? Saat ini `manage-events` platform-only. Rekomendasi: ability event baru (mis. `manage-competition-config`), jangan repurpose `manage-events`.
3. **`users.role` event-role legacy:** kapan menghapus `ketua_event/sekretariat/...` dari `Role` enum & factory? Ini menyentuh banyak test. Rekomendasi: deprecated dulu, pertahankan compat.
4. **`manage-import` vs `manage-participants`:** route `/import/peserta` & `/import/regu` memakai `manage-participants`, ability `manage-import` tidak terpakai di route mana pun (doc S5 mencatat ini). Perlu konsistensi?
5. **Route `/regu` global:** perlu gate apa? (`view-master-data` seperti desa/kelompok, atau event-scoped?) Saat ini un-gated CRUD.
6. **`activity_logs` tanpa `event_id`:** perlu scope event untuk log? (tabel tidak punya kolom event.)
7. **`absensis.nip` dangling & `surat_izins.created_by` cascade:** apakah termasuk backlog perbaikan? (rekomendasi: ya, tapi terpisah dari modul Competition.)
8. **Public event pages vs event privacy:** `/events/{event}` publik menampilkan data event; perlu mode private event? (di luar scope sekarang.)
9. **Competition Team/TeamMember:** struktur baru vs perpanjangan `regus` + `registration_type`. Rekomendasi: tabel baru yang event-scoped, bukan `regus` global.

---

## 18. Dokumentasi Drift

Perbandingan dokumentasi vs kode:

| Docs | Klaim | Kode aktual | Verdict |
|---|---|---|---|
| `docs/PERMISSION.md:5`, `docs/ROLE_MATRIX.md:5` | "`users.role` hanya menentukan hak platform" | `Role::values()` masih memuat event-role; `UserFactory`/test/artisan `user:set-role` masih menulis event-role ke `users.role`; runtime mengabaikannya | **DOCUMENTATION DRIFT parsial** — runtime benar, tapi enum/factory/command belum dirapikan |
| `docs/PERMISSION.md:460,490-491` | "Kelola Event — (no gate), menu visible to all" | Sidebar `@can('manage-events')` (`sidebar.blade.php:165-169`) | **DRIFT** — menu sudah di-gate |
| `docs/PERMISSION.md:358` | "ImportRegu — (tidak ada Gate)" | Benar: `TambahRegu/EditRegu/HapusRegu` tanpa Gate | **Sesuai**, tapi gap nyata (H1) tidak disorot |
| `docs/ROLE_MATRIX.md:99` | `/regu` "(no gate)" | Benar | **Sesuai** (gap dokumentasi: CRUD komponen tanpa gate tidak dicatat) |
| `docs/ARCHITECTURE.md:133` | Permission Engine Design C | Sesuai kode | **Sesuai** |
| `docs/PERMISSION.md:342` | `/import/peserta`,`/import/regu` pakai `manage-participants` | Sesuai kode | **Sesuai** |

---

## 19. Final Recommendation

```
CURRENT
User
 ↓
Role (users.role)
 ↓
Event

PROPOSED
User / Account
 ├── Person (optional)
 │
 └── Event Membership
       ├── Event
       ├── Role       (EventRole)
       └── Permission (EventRole.permissions)
```

1. **Kompatibel dengan code sekarang?** YA — ini perpanjangan additive dari Permission Engine yang sudah berjalan; bukan penggantian arsitektur.
2. **Membutuhkan migration?** Minimal 1 (additive): `event_committee_assignments.user_id` nullable + backfill dari person→user. Tanpa mengubah `users`.
3. **Membutuhkan perubahan authorization?** Ya, terbatas: `EventAccessService`/`EventPermissionService` (user_id OR person_id), Gate Guest, plus perbaikan gap IDOR & route `resolve.active-event`.
4. **Membutuhkan perubahan UI?** Ringan (form Guest/PJ di User Management); dashboard/switcher/sidebar otomatis mengikuti karena berbasis service + `@can`.
5. **Berisiko merusak Design C?** TIDAK — membership terpisah dari `Person → Participation → Attendance`; tabel Design C tidak disentuh.
6. **Migrasi paling aman:** additive-first → backfill → service update → test hijau → role template baru → perbaiki IDOR → baru Competition lanjutan.

---

## AUDIT COMPLETE

**Tests:**
- `2229 passed (5782 assertions)`, `0 failed`, `0 skipped`, `Duration 72.69s`
- Command: `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`
- Catatan: `php artisan test` dengan memory_limit 128M gagal di zipstream (test export Excel) — constraint lingkungan, bukan kegagalan kode.

**Critical Findings:**
1. Event access wajib Person — Guest tanpa Person tidak bisa akses event mana pun (`EventAccessService.php:22-24`, `EventPermissionService.php:16-18`); tidak ada relasi User↔Event langsung.
2. C1 SuratIzin cross-event (list + approve/reject/return, `SuratIzin/Index.php:29,56,76,89,100,114`).
3. C2 QRLabel cross-event QR/attendance-code leak (`QRLabel/Index.php:84-85,324`).
4. C3 `koreksi-data` tanpa `resolve.active-event` + list/approve lintas event (`IdentityCorrectionReview.php:20,38,80`).
5. C4/C5 MatchCenter & OfficialPanel cross-event match/result manipulation.
6. C6/C7 ImportParticipation & GantiPeserta — attacker-controlled `event_id`.

**High Priority:**
- H1 `/regu` route + `Database/Regu/*` CRUD tanpa gate sama sekali.
- H2 ActivityLogIndex query semua event (tabel tanpa event_id).
- Register.php membuat akun "mati" (no person, no role); ConfirmPassword/Profile rusak untuk user email-null; MatchCenter official list tidak mencakup komite juri.
- `surat_izins.created_by` cascadeOnDelete (destruktif).

**Medium Priority:**
- M1 Competition/ParticipantList & Registration query by-ID tanpa event check.
- M2 Competition CRUD IDOR latent (platform-only saat ini).
- M3 SuratIzin/Create fallback legacy peserta tanpa event check.
- DOCUMENTATION DRIFT: `users.role` masih menyimpan event-role (enum/factory/artisan) meski runtime mengabaikan; "Kelola Event (no gate)" padahal sudah di-gate.

**Recommended Next Step:**
1. (Tanpa implementasi dulu) Setujui rekomendasi §13 & jawab pertanyaan §17.
2. Saat implementasi: perbaiki **celah IDOR (C1–C7, H1–H2)** lebih dulu, lalu tambah jalur membership User-based (Guest) secara additive, update `EventAccessService`/`EventPermissionService`, jalankan suite penuh, baru bangun struktur Competition lanjutan (Team/TeamMember/Result) dengan event-scoping eksplisit.

**Files Changed:**
- Hanya `docs/audit/AUTH-EVENT-FOUNDATION-AUDIT.md` yang dibuat (laporan audit).
- Tidak ada kode, migration, database, atau authorization yang diubah.
