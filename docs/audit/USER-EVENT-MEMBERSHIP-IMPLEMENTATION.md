# USER EVENT MEMBERSHIP IMPLEMENTATION

> Implementasi: **User/Account tanpa Person + Event Membership + Event Chair + Guest**.
> Tetap kompatibel dengan architecture existing (EventAccessService, EventPermissionService, ActiveEventContext, EventSwitcher, Platform Dashboard, Event Dashboard, Design C).
> Regu = FREEZE. Competition = FREEZE. Migration additive + backward compatible.

- **Tanggal:** 2026-08-13
- **Status:** IMPLEMENTATION COMPLETE
- **Laporan audit konteks:** `docs/audit/AUTH-EVENT-FOUNDATION-AUDIT.md`, `docs/audit/REGU-SCOPE-AUDIT.md`
- **Baseline:** 2229 passed / 5782 assertions / 0 failed / 0 skipped

---

## 1. Before Architecture

```
User
 ├── person_id (nullable di DB, TAPI runtime WAJIB)
 └── users.role (platform: super_admin/admin; legacy event roles diabaikan)

Event access (Person-wajib):
User → User.person_id → Person → event_committee_assignments.person_id
        → Event → EventRole.permissions

EventAccessService  : person_id === null → false (Guest tanpa Person tidak bisa akses apa pun)
EventPermissionService : person_id === null → false (sama)

EventCommitteeAssignment : person_id NOT NULL → tidak ada jalur User langsung
```

Masalah yang diperbaiki:
- Guest/PJ (email+password, tanpa Person) tidak dapat mengakses event mana pun.
- `Register.php` membuat user role=null, person_id=null → akun "mati".
- `ConfirmPassword` (`Auth::validate(['email' => ...])`) gagal untuk user email=null.
- `Profile` (`email required`) tidak bisa disimpan untuk user tanpa email.
- Role global tidak memiliki konsep Event Chair / Guest sebagai role akun.

---

## 2. Changes Implemented

| Area | Perubahan |
|---|---|
| Event Access | `EventAccessService` resolve membership `user_id` ATAU `person_id`. Super Admin/Admin semantics dipertahankan. |
| Event Permission | `EventPermissionService` resolve membership `user_id` ATAU `person_id` untuk ability event. |
| Event Membership | `event_committee_assignments.user_id` nullable (additive). Person-based tetap berfungsi. |
| Role | `Role` enum tambah `EventChair` (`event_chair`), `Guest` (`guest`) — role akun NON-platform (tanpa akses global). |
| EventRole | `EventRolePermissionDefaults` tambah template `event_chair` (7 ability = ketua_event) & `guest` (view-dashboard). |
| Service | `EventCommitteeService::assign()` terima `user_id` ATAU `person_id`; `assignUser()`; `createGuestAndAssign()`. |
| Register | Public registration kini membuat **Guest** (role=guest, person_id=null, TANPA akses event otomatis). |
| Profile / ConfirmPassword | Email nullable di Profile; ConfirmPassword pakai hash-check langsung. |
| UI | User Management dropdown mendukung `guest`/`event_chair`; Committee Management "Tambah Akun Guest / PJ (tanpa Person)". |
| Dashboard/Switcher | PlatformDashboard roleNames + label role akun; EventSwitcher otomatis (via service). |
| Tests | +36 test baru (authorization lengkap). |

---

## 3. Database Changes

**Satu migration additive, backward compatible** — `database/migrations/2026_08_20_000002_add_user_id_to_event_committee_assignments_table.php`:

- `event_committee_assignments.user_id` nullable FK → `users` (`nullOnDelete`) + index.
- `event_committee_assignments.person_id` diubah menjadi nullable (untuk assignment Guest tanpa Person).
- Unique baru `eca_event_user_role_unique` (event_id, user_id, event_role_id). Unique lama `eca_event_person_role_unique` dipertahankan. NULL bersifat distinct di MySQL & SQLite → tidak saling tabrakan.
- **Backfill**: `user_id` diisi dari `users.person_id` yang cocok untuk setiap assignment person-based → User-based resolution otomatis melihat membership existing.
- `users.person_id` sudah nullable (tidak diubah).

**Tidak ada**: DROP COLUMN, DROP TABLE, perubahan `people`, `participations`, `event_attendances`, `regus`, tabel competition.

**Verifikasi**: migration berhasil di SQLite (`migrate:fresh` via test suite + uji terpisah). `.env` production menunjuk MariaDB yang tidak dapat dijangkau dari sandbox ini — migration siap dijalankan dengan `php artisan migrate` di environment yang memiliki akses DB.

---

## 4. Authentication Changes

- **Login/Logout**: tidak berubah (`Login` mendukung email/username; `is_active` check). Guest login dengan email berhasil.
- **Register** (`app/Livewire/Auth/Register.php`): kini membuat user dengan state jelas —
  `role = Role::Guest`, `person_id = null`, `is_active = true`. Guest TIDAK otomatis mendapat akses event.
- **ConfirmPassword** (`app/Livewire/Auth/ConfirmPassword.php`): ganti `Auth::validate(['email' => ...])` → `Hash::check($password, Auth::user()->getAuthPassword())` — aman untuk user tanpa email.
- **Profile** (`app/Livewire/Settings/Profile.php` + blade): email nullable; user tanpa email tetap bisa menyimpan nama tanpa crash.
- **DeleteUserForm / Settings/Password**: sudah aman (pakai `current_password` → hash check pada authenticated user).

---

## 5. Event Membership

`event_committee_assignments` sekarang mendukung dua jalur:

```
User / Account
 ├── Person (optional) ──► person_id  (Person-based membership — existing)
 └── Event Membership ──► user_id     (User-based membership — Guest / Event Chair tanpa Person)
```

- `EventAccessService::isUserAssignedToEvent()` / `getAssignedEventIds()` / `canAccess()` — resolve via `user_id === user.id` ATAU `person_id === user.person_id`.
- `EventPermissionService::allows()` / `permissionsFor()` — sama (ditambah filter `eventRole.is_active` + `permissions` JSON).
- `EventCommitteeService::assign()` — validasi "exactly one of user_id/person_id"; duplicate check per jalur; `EventOwnership` checks dipertahankan.

---

## 6. Event Chair

- Role akun: `users.role = event_chair` (non-platform, tanpa akses global).
- EventRole code `event_chair` → 7 ability (view-dashboard, manage-registration, manage-participants, manage-attendance, manage-sessions, manage-secretariat, view-reports).
- Akses event HANYA dari membership:
  - `EventChair + membership CAI 2026` → CAI 2026 ALLOW.
  - `EventChair` tanpa membership / coba event lain → **403** (direct URL, Livewire `switchTo`, EventSwitcher tidak menampilkan).

---

## 7. Guest

- Role akun: `users.role = guest`, `person_id = null`.
- EventRole code `guest` → `['view-dashboard']`.
- Jalur pembuatan:
  1. **Committee Management** — form "Tambah Akun Guest / PJ (tanpa Person)" (`createGuestAndAssign`): email + nama opsional + event role → user dibuat (role=guest, person_id=null) + assignment `user_id`.
  2. **User Management** — dropdown role kini memuat `guest`/`event_chair` (`Role::accountCases()`).
  3. **Public Register** — membuat Guest (tanpa access otomatis).
- Guest hanya dapat mengakses event yang diberikan (direct URL 403 untuk event lain).

---

## 8. Register Changes

`app/Livewire/Auth/Register.php`:
- Sebelum: `User::create(['name','email','password'])` → role=null, person_id=null → akun tanpa state ("mati").
- Sesudah: membuat **Guest** (`role=Guest`, `person_id=null`, `is_active=true`) → state jelas, TANPA akses event otomatis.
- Public user TIDAK otomatis mendapat akses event; akses hanya lewat membership eksplisit.

---

## 9. Profile / ConfirmPassword

- **ConfirmPassword**: hash-check langsung → user tanpa email (komite / guest) bisa confirm.
- **Profile**: `email` nullable → user tanpa email tidak crash; simpan nama tetap jalan; email kosong → null.
- Halaman yang butuh Person: tetap memakai guard eksplisit (mis. `participation->person?->...`), tidak membuat Person dummy.

---

## 10. Authorization Changes

- `AppServiceProvider` Gate TIDAK diubah (Super Admin bypass via `Gate::before`; Admin bypass ability event via `$eventAbility`; 14 ability event via `EventPermissionService`).
- Perubahan hanya pada resolusi membership di service: `user_id` ATAU `person_id`.
- `Role` enum: `platformCases()` tetap `[super_admin, admin]`; `accountCases()` = platform + `event_chair` + `guest`.
- Tidak ada hardcode `if role === 'admin' → semua event` baru. Super Admin/Admin semantics existing dipertahankan persis.

---

## 11. Security Verification

- **Direct URL cross-event**: `Event A user → /events/B/dashboard` → **403** (tested).
- **Livewire cross-event**: `EventSwitcher::switchTo(B)` → AuthorizationException/403 (tested).
- **EventSwitcher**: hanya menampilkan event yang di-assign (user-based maupun person-based).
- **Guest tanpa Person**: tidak dapat mengakses event apa pun tanpa membership (403).
- **Person-based existing**: tetap bekerja (tested).
- **Platform roles**: Super Admin & Admin behavior existing dipertahankan (tested).
- Catatan: celah IDOR record-level (C1–C7/H1–H2 dari audit sebelumnya) **di luar scope** task ini dan tidak disentuh (sesuai instruksi).

---

## 12. Tests

**Perintah:** `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`

**Baseline:** 2229 passed / 5782 assertions / 0 failed / 0 skipped / Duration ±72.7s

**Final:** 2265 passed / 5881 assertions / 0 failed / 0 skipped / Duration 73.94s

(+36 test, +99 assertions)

### Test baru — `tests/Feature/Event/UserEventMembershipTest.php`
- User tanpa Person valid; schema `user_id`/`person_id` nullable.
- Guest: exist tanpa Person, login, logout, confirm password, profile tidak crash.
- Committee account tanpa email: confirm password + profile tidak crash.
- User with membership → access; tanpa membership → 403.
- Event Chair: akses event yang ditugaskan; tolak event lain (direct URL + Livewire).
- Guest: akses event ditugaskan; tolak event tak ditugaskan.
- EventSwitcher hanya menampilkan event authorized; platform user tetap melihat semua.
- Person-based access tetap bekerja; permission service resolve user & person.
- Super Admin / Admin behavior preserved.
- Cross-event: route + Livewire DENY.
- `createGuestAndAssign` create/reuse; public Register → Guest tanpa access.
- EventRole codes `guest`/`event_chair` default permission; Committee Management guest flow; User Management create guest.

### Test di-update
- `tests/Feature/Security/RbacFoundationTest.php` — `Role::values()` + `event_chair` + `guest`.
- `tests/Feature/MasterData/User/UserManagementRbacConsistencyTest.php` — dropdown akun memuat `guest`/`event_chair`.

---

## 13. Documentation Updated

- `docs/TERMINOLOGY.md` — User/Account ≠ Person, Event Membership, Guest, Event Chair, Global Role vs Event-Scoped Access.
- `docs/ARCHITECTURE.md` — Authentication / Permission Engine (user_id OR person_id).
- `docs/PERMISSION.md` — nota evolusi Permission Engine.
- `docs/SECURITY.md` — S7 Architecture + Key Design Decisions (User tanpa Person = valid).
- `docs/DATABASE.md` — Event Membership section (event_committee_assignments user_id/person_id).
- `docs/HANDOFF.md` — canonical data flow + note User ≠ Person.
- `docs/ROADMAP.md` — status Event Membership.
- `docs/CHANGELOG.md` — [Unreleased] → EVENT-MEMBERSHIP.

---

## 14. Files Changed

**Migration (additive):**
- `database/migrations/2026_08_20_000002_add_user_id_to_event_committee_assignments_table.php` (baru)

**App code:**
- `app/Enums/Role.php` (EventChair, Guest, accountCases/accountValues, isAccountRole)
- `app/Support/EventRolePermissionDefaults.php` (code `event_chair`, `guest`)
- `app/Models/User.php` (committeeAssignments, isGuest, isEventChair)
- `app/Models/EventCommitteeAssignment.php` (user_id fillable + user relation)
- `app/Services/Event/EventAccessService.php` (user OR person membership)
- `app/Services/Event/EventPermissionService.php` (user OR person membership)
- `app/Services/Activity/EventCommitteeService.php` (assign user/person, assignUser, createGuestAndAssign, nameFromEmail)
- `app/Livewire/Auth/Register.php` (Guest account)
- `app/Livewire/Auth/ConfirmPassword.php` (hash-check)
- `app/Livewire/Settings/Profile.php` (email nullable)
- `app/Livewire/Dashboard/PlatformDashboard.php` (roleNames user/person; account role label)
- `app/Livewire/MasterData/User/{IndexUser,CreateUser,EditUser}.php` (account roles)
- `app/Livewire/Event/EventRoleManager.php` (template options event_chair/guest)
- `app/Livewire/Event/CommitteeManagement.php` (guest flow)

**Views:**
- `resources/views/livewire/event/committee-management.blade.php` (guest form; user name display)
- `resources/views/livewire/settings/profile.blade.php` (email optional)

**Tests:**
- `tests/Feature/Event/UserEventMembershipTest.php` (baru, 36 test)
- `tests/Feature/Security/RbacFoundationTest.php`
- `tests/Feature/MasterData/User/UserManagementRbacConsistencyTest.php`

**Docs:**
- `docs/TERMINOLOGY.md`, `docs/ARCHITECTURE.md`, `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/DATABASE.md`, `docs/HANDOFF.md`, `docs/ROADMAP.md`, `docs/CHANGELOG.md`

**Backup:**
- `database/database.sqlite.backup.pre-event-membership-20260813_125933`

**Laporan:**
- `docs/audit/USER-EVENT-MEMBERSHIP-IMPLEMENTATION.md` (file ini)

**TIDAK disentuh:** `regus`, `Database/Regu/*`, `PlacementService`, model/kode/migration regu, seluruh tabel & kode Competition, `people`/`participations`/`event_attendances` (Design C).

---

## 15. Remaining Risks

1. **Migration belum dijalankan di DB production** (sandbox tidak dapat mengakses MariaDB). Migration bersifat additive & backward compatible; jalankan `php artisan migrate` di environment dengan akses DB setelah backup. Verifikasi SQLite sudah lolos di test suite.
2. **Celah IDOR record-level (C1–C7 / H1–H2)** dari `AUTH-EVENT-FOUNDATION-AUDIT.md` **belum diperbaiki** (di luar scope). Saat membangun modul berikutnya, perbaiki sebelum memperluas permukaan.
3. **Route `events/{event}/pengajian/*` & `competition.viewer`** masih tanpa `auth` (by design token-based/public) — bukan bagian perubahan ini.
4. **Guest `guest` template** hanya `view-dashboard` — permission tambahan untuk Guest/PJ bila dibutuhkan harus disesuaikan via EventRole di event.
5. **`users.role` legacy event roles** (ketua_event/sekretariat/…) masih dapat disimpan di beberapa path lama (factory/artisan) — runtime mengabaikannya; pembersihan penuh menyentuh banyak test dan bukan scope task ini.
6. **Nama test command**: gunakan `php -d memory_limit=1G vendor/bin/pest`; `php artisan test` (128M) gagal di zipstream (constraint lingkungan, bukan kode).

---

## IMPLEMENTATION COMPLETE

**Baseline:** 2229 passed / 5782 assertions / 0 failed / 0 skipped

**Final:**
- Tests: **2265 passed / 5881 assertions / 0 failed / 0 skipped**
- Duration: **73.94s**
- Command: `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`

**Migration:**
- `2026_08_20_000002_add_user_id_to_event_committee_assignments_table.php` — additive (user_id nullable, person_id nullable, unique baru, backfill). Backward compatible; siap dijalankan di environment dengan akses DB.

**User without Person:** PASS — `users.person_id` nullable; runtime tidak lagi mewajibkan Person; Profile/ConfirmPassword/Register aman tanpa Person.

**Guest:** PASS — bisa dibuat tanpa Person (Committee Management / User Management / Register), bisa login, punya Event Membership (`user_id`), hanya akses event yang ditugaskan (403 untuk event lain).

**Event Chair isolation:** PASS — hanya event membership; direct URL & Livewire cross-event → 403; EventSwitcher hanya menampilkan event yang ditugaskan.

**Cross-event authorization:** PASS — Event A user → Event B route/dashboard/Livewire → DENY (tested).

**Regu changed:** MUST BE NO → **NO** (verified: seluruh file regu mtime lama, tidak tersentuh).

**Competition changed:** MUST BE NO → **NO** (verified: tidak ada file competition berubah).

**Files changed:**
- Migration: 1 (additive). App: 15 file. Views: 2. Tests: 3 (1 baru + 2 update). Docs: 8. Backup: 1. Laporan: 1.
- Total lintas: Regu = 0, Competition = 0, Design C = 0.
