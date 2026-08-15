# PERMISSION

> Role & Permission Matrix for KJA Event Manager

> ⚠️ **EVOLUSI PENTING — PERMISSION ENGINE (Design C).** Sejak Sprint 2, ability **event-scoped di-resolve dari `User → EventCommitteeAssignment → EventRole.permissions`**, bukan dari `users.role`. `users.role` hanya menentukan hak platform (SuperAdmin/Admin) dan role akun (EventChair/Guest). Sejak implementasi Event Membership, `event_committee_assignments` mendukung **dua jalur**: `user_id` (User-based membership — Guest/Event Chair tanpa Person) ATAU `person_id` (Person-based membership). Total Gate = **18** (4 platform + 14 event-scoped). Matriks statis di bawah menggambarkan **default EventRole template** (`EventRolePermissionDefaults::BY_CODE`) dan hak platform — bukan lagi sumber authorization runtime untuk role event. Lihat `docs/CHANGELOG.md` (Unreleased → Permission Engine).

---

# S2 Implementation Status

| Komponen | Status |
|----------|--------|
| Role enum | ✅ Implemented — `app/Enums/Role.php` |
| Users role column | ✅ Migration `2026_08_03_000001` |
| User model helpers | ✅ `hasRole()`, `hasAnyRole()` |
| Gate definitions | ✅ 18 abilities (4 platform + 14 event-scoped) |
| Super Admin bypass | ✅ `Gate::before()` |
| Admin bypass (event) | ✅ `$eventAbility` — Admin bypass event-scoped abilities |
| Permission Engine | ✅ `EventPermissionService::allows()` |
| Artisan role command | ✅ `php artisan user:set-role` |
| Route protection (S2) | ✅ Master Data routes protected |
| Livewire authorization (S2) | ✅ Master Data mutations protected |
| Sidebar visibility (S2) | ✅ Master Data menu gated |
| Route protection (S3+) | ✅ Implemented |
| Livewire authorization (S3+) | ✅ Implemented |

---

# Purpose

Dokumen ini mendefinisikan hak akses setiap Role pada KJA Event Manager.

Semua perubahan Role dan Permission wajib didokumentasikan di file ini.

---

# Permission Principles

* Least Privilege
* Role Based Access Control (RBAC)
* Deny by Default
* Every Feature Requires Permission
* Never Hardcode Role

---

# S1 Current Roles

| Role                | Code                | Description                                    | Implemented |
| ------------------- | ------------------- | ---------------------------------------------- | :---------: |
| Super Admin         | `super_admin`       | Mengelola seluruh sistem dan konfigurasi       | ✅ |
| Admin               | `admin`             | Mengelola seluruh Event                        | ✅ |
| Ketua Event         | `ketua_event`       | Mengelola event yang menjadi tanggung jawabnya | ✅ |
| Sekretariat         | `sekretariat`       | Mengelola data peserta dan administrasi        | ✅ |
| PJ Divisi           | `pj_divisi`         | Monitoring divisi masing-masing                | ✅ |
| Operator Registrasi | `operator_registrasi` | Registrasi peserta                           | ✅ |
| Operator Scan       | `operator_scan`     | Scan QR dan absensi                            | ✅ |
| Juri                | `juri`              | Input nilai perlombaan                         | ✅ |
| Viewer              | `viewer`            | Dashboard tanpa hak edit                       | ✅ |

Catatan: Role `Peserta` tidak memiliki akun login terpisah. Peserta menggunakan self-register flow publik.

---

# S1 Gate Abilities

Berikut Gate abilities yang telah didefinisikan di `AppServiceProvider`. **Sudah dipasang ke route/sidebar/Livewire** — S2–S7 telah complete.

> **Catatan:** Kolom di bawah ini menggambarkan hak **platform** (`users.role`) + **default EventRole template**. Untuk role event (Ketua/Sekretariat/PJ Divisi/Registrasi/Scan/Juri/Viewer), akses runtime ditentukan oleh **EventRole permissions** dari assignment-nya — bukan oleh kolom ini.

| Ability              | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
| -------------------- | :---------: | :---: | :---: | :---------: | :-------: | :--------: | :--: | :--: | :----: |
| `view-dashboard`     |      ✅      |   ✅   |   ✅   |      ✅      |     ✅     |     ❌      |  ❌   |  ❌   |   ✅    |
| `view-master-data`   |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-master-data` |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-events`      |      ✅      |   ✅   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-registration`|      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |     ✅      |  ❌   |  ❌   |   ❌    |
| `manage-participants`|      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-attendance`  |      ✅      |   ✅   |   ✅   |      ✅      |     ✅     |     ❌      |  ✅   |  ❌   |   ❌    |
| `manage-sessions`    |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-qr-labels`   |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-secretariat` |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-import`      |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `view-reports`       |      ✅      |   ✅   |   ✅   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ✅    |
| `manage-pengajian`   |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `view-activity-log`  |      ✅      |   ✅   |   ❌   |      ✅      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-users`       |      ✅      |   ❌   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-matches`     |      ✅      |   ✅   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `manage-officials`   |      ✅      |   ✅   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ❌   |   ❌    |
| `submit-result`      |      ✅      |   ✅   |   ❌   |      ❌      |     ❌     |     ❌      |  ❌   |  ✅   |   ❌    |

Keterangan:
- `view-master-data` / `manage-master-data` — **hanya SuperAdmin** sejak Permission Engine (ability platform). Admin tidak lagi mendapat ability ini.
- `manage-matches`, `manage-officials`, `submit-result` — Gate Competition (Sprint 7–10); di-resolve via Permission Engine. Matriks default: `super_admin`/`admin_event` template = semua ability; `juri` template = `['submit-result']`.
- Untuk ability event lainnya, template default: `sekretariat` = 11 ability; `ketua_event` = 7; `operator_registrasi` = manage-registration; `operator_scan` = manage-attendance; `pj_divisi` = view-dashboard + manage-attendance; `viewer` = view-dashboard + view-reports; `ketua_fosda` = view-dashboard + manage-pengajian + view-reports. (Lihat `app/Support/EventRolePermissionDefaults.php`.)

---

# Future Role

Planned:

* Ketua Cabang
* Venue Manager
* Operator Venue
* Official
* Volunteer
* Medical Team
* Security Team
* Finance
* Documentation Team

---

# Permission Naming Convention

Gunakan format berikut:

```
person.view
person.create
person.update
person.delete

attendance.scan
attendance.manual
attendance.export

competition.score
competition.rank

certificate.generate

dashboard.view
dashboard.live
```

---

# Development Rules

* Jangan melakukan pengecekan role secara langsung (`role == 'admin'`).
* Gunakan Gate atau Policy Laravel.
* Semua menu harus disembunyikan jika user tidak memiliki permission.
* Semua endpoint harus memvalidasi permission, meskipun menu tidak tampil.
* Setiap fitur baru wajib menambahkan permission baru sebelum implementasi.

---

# Long Term Goal

Role dan Permission harus sepenuhnya dinamis sehingga organisasi dapat membuat Role sendiri tanpa mengubah source code.

---

# S3 — Event Management & CAI Operational Protection ✅

## Status

✅ COMPLETE (2026-08-04). All operational routes and Livewire mutations protected.

## Route-Level Protection

Setiap operational route memiliki middleware `can:{ability}` sesuai permission matrix:

> **Catatan routing:** routes operasional sekarang berada di bawah prefix `events/{event}/...` (dengan middleware `resolve.active-event`). Path tanpa prefix (`/absensi`, `/registrasi`, dst.) hanyalah redirect shim (auth-only) menuju route event-prefixed.

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/dashboard` (platform) | `auth, verified` | — (landing platform; tidak ada gate) |
| `/events/{event}/dashboard` | `can:view-dashboard` | `view-dashboard` |
| `/events/{event}/registrasi` | `can:manage-registration` | `manage-registration` |
| `/events/{event}/registrasi/ulang` | `can:manage-registration` | `manage-registration` |
| `/events/{event}/database` | `can:manage-participants` | `manage-participants` |
| `/events/{event}/sesi-absensi` | `can:manage-sessions` | `manage-sessions` |
| `/events/{event}/rekap-peserta` | `can:view-reports` | `view-reports` |
| `/events/{event}/rekap-absensi` | `can:view-reports` | `view-reports` |
| `/events/{event}/qr-label` | `can:manage-qr-labels` | `manage-qr-labels` |
| `/events/{event}/absensi` | `can:manage-attendance` | `manage-attendance` |
| `/events/{event}/surat-izin` | `can:manage-secretariat` | `manage-secretariat` |
| `/events/{event}/activity-log` | `can:view-activity-log` | `view-activity-log` |
| `/events` | `can:manage-events` | `manage-events` |

## Livewire Mutation Protection

Semua CRUD mutation methods memiliki `Gate::authorize()` sebelum database write:

| Livewire Component | Method(s) | Ability |
|-------------------|-----------|---------|
| `Event\Index` | `create()`, `archive()`, `activate()`, `delete()` | `manage-events` |
| `Event\EditStatus` | `update()` | `manage-events` |
| `Database\Peserta\TambahPeserta` | `simpan()` | `manage-participants` |
| `Database\Peserta\EditPeserta` | `update()` | `manage-participants` |
| `Database\Peserta\HapusPeserta` | `destroy()` | `manage-participants` |
| `Database\Peserta\ImportPeserta` | `import()` | `manage-participants` |
| `Database\Sesi\TambahSesi` | `simpan()` | `manage-sessions` |
| `Database\Sesi\EditSesi` | `update()` | `manage-sessions` |
| `Database\Sesi\HapusSesi` | `destroy()` | `manage-sessions` |
| `Dashboard\Scan` | `scanPeserta()`, `manualAttend()`, `manualIzin()` | `manage-attendance` |
| `QRLabel\Index` | `downloadPng()`, `printSelectedLabel()`, `printAllFiltered()`, `generateBatchExport()` | `manage-qr-labels` |
| `Rekap\Peserta\RekapPeserta` | `exportExcel()` | `view-reports` |
| `SuratIzin\Index` | `submit()`, `approve()`, `reject()`, `cancel()`, `return()` | `manage-secretariat` |
| `SuratIzin\Create` | `simpan()`, `submit()` | `manage-secretariat` |
| `Registrasi\Ulang` | `registrasiUlang()`, `updatePeserta()` | `manage-registration` |
| `Event\Dashboard` | `activateSesi()` | `manage-sessions` |

## Access Matrix per Role (S3 Operational Routes)

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:----------:|:----:|:----:|:------:|
| `manage-events` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-registration` | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| `manage-participants` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-attendance` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| `manage-sessions` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-qr-labels` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage-secretariat` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view-reports` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| `view-activity-log` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view-dashboard` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |

## Sidebar Visibility (S3)

Sidebar visibility for S3 modules difilter dengan `@can()` directives — diimplementasikan di S6.

---

# S4 — Pengajian Admin Protection ✅

## Status

✅ COMPLETE (2026-07-21). All Pengajian admin routes and Livewire mutations protected.

## Route-Level Protection

Setiap Pengajian admin route memiliki middleware `can:{ability}` sesuai permission matrix:

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/koreksi-data` | `can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/access` | `can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/manual-entry` | `can:manage-pengajian` | `manage-pengajian` |
| `/pengajian/admin/import-massal` | `can:manage-pengajian` | `manage-pengajian` |

Catatan: `/pengajian/report` menggunakan `view-reports` (telah diproteksi di S3).

## Livewire Mutation Protection

Semua CRUD mutation methods memiliki `Gate::authorize()` sebelum database write:

| Livewire Component | Method(s) | Ability |
|-------------------|-----------|---------|
| `Pengajian\Admin\AccessIndex` | `create()`, `revoke()`, `delete()` | `manage-pengajian` |
| `Pengajian\Admin\ManualEntry` | `submit()`, `confirmMatch()`, `createNewPerson()` | `manage-pengajian` |
| `Pengajian\Admin\ImportMassal` | `preview()`, `executeImport()` | `manage-pengajian` |
| `Pengajian\IdentityCorrectionReview` | `approve()`, `reject()` | `manage-pengajian` |

## Access Matrix per Role (S4 Pengajian Admin)

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:----------:|:----:|:----:|:------:|
| `manage-pengajian` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

## Decision on Registration Ability

The `manage-registration` ability is used for re-registration mutations (`Registrasi\Ulang` — `registrasiUlang()`, `updatePeserta()`). This is correct because:

- Re-registration is a registration-domain operation, not participant management
- `manage-registration` is granted to `operator_registrasi` who need this access
- `manage-participants` is reserved for broader participant CRUD (database management)
- This separation follows the Least Privilege principle

## Pre-S4 Audit Result

Before S4 implementation, the following Pengajian admin routes were accessible by ALL authenticated users (no role-based protection):

- `/koreksi-data` — identity correction review
- `/pengajian/admin/access` — access token management (CRUD, revoke, delete)
- `/pengajian/admin/manual-entry` — manual participant entry
- `/pengajian/admin/import-massal` — bulk participant import

All these routes are now protected with `can:manage-pengajian` middleware. Only `super_admin`, `admin`, and `sekretariat` retain access.

---

# S0 — User Management

## Status

✅ COMPLETE (2026-07-21). Super Admin can manage user accounts via `/users`.

## Route-Level Protection

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/users` | `can:manage-users` | `manage-users` |

## Livewire Mutation Protection

| Livewire Component | Method(s) | Ability |
|-------------------|-----------|---------|
| `User\Index` | `render()` | `manage-users` |
| `User\Create` | `save()` | `manage-users` |
| `User\Edit` | `update()` | `manage-users` |
| `User\ResetPassword` | `resetPassword()` | `manage-users` |
| `User\Delete` | `destroy()` | `manage-users` |

## Delete Safety Rules

- **Cannot delete self** — user cannot delete their own account
- **Cannot delete last Super Admin** — at least one Super Admin must remain
- Delete is blocked server-side before any database write

## Access Matrix

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:----------:|:----:|:----:|:------:|
| `manage-users` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

## Implementation Notes

- User Management is **Super Admin only** — no other role has `manage-users`
- Password is always hashed before storage (Laravel Hash)
- Password is never exposed in API responses or logs
- All mutations are recorded in Activity Log:
  - `created` — new user account
  - `updated` — user profile changes
  - `role_changed` — role assignment change
  - `password_reset` — password reset by admin
  - `deleted` — user account deleted

---

# S5 — CAI Module Permissions ✅

## Status

✅ COMPLETE (2026-07-21). CAI module import routes protected. Full CAI permission matrix verified.

## Route-Level Protection

> **Koreksi status aktual (2026-08-03):** `/import/peserta` dan `/import/regu` di kode memakai middleware **`can:manage-participants`** (bukan `manage-import`). Ability `manage-import` tetap didefinisikan (14 event-scoped) dan tersedia di Permission Engine, tetapi belum di-attach ke route ini. Lihat `routes/web.php`.

| Route | Middleware | Ability |
|-------|-----------|---------|
| `/import/peserta` | `can:manage-participants` | `manage-participants` |
| `/import/regu` | `can:manage-participants` | `manage-participants` |

Catatan: `/import/desa` dan `/import/kelompok` diproteksi di S2 dengan `manage-master-data`.

## Livewire Mutation Protection

Import mutation methods gated (sesuai kode aktual):

| Livewire Component | Method(s) | Ability |
|-------------------|-----------|---------|
| `Database\Peserta\ImportPeserta` | `import()` | `manage-participants` |
| `Database\Regu\ImportRegu` | — (tidak ada Gate) | — |

Catatan: Import Desa dan Import Kelompok diproteksi di S2 dengan `manage-master-data`.

## Access Matrix per Role (S5 Import)

| Ability | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
|---------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:----------:|:----:|:----:|:------:|
| `manage-participants` | ✅ | ✅ | ✅* | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

## Complete S5 Deliverables

- ✅ Import routes protected (via `manage-participants` di kode aktual; `manage-import` tersedia di engine)
- ✅ Livewire ImportPeserta gated; ImportRegu belum punya Gate (catatan gap)
- ✅ Full CAI permission matrix verified (18 abilities di kode)

---

# S6 — Sidebar Visibility (RBAC) ✅

## Status

✅ COMPLETE (2026-07-21). All sidebar menu items gated with `@can()` directives.

---

# S7 — Event-Scoped Authorization ✅

## Status

✅ COMPLETE (2026-08-04). KetuaEvent abilities are now event-scoped via User→Person→EventCommitteeAssignment chain.

## Architecture

```
User.role = ketua_event
  + User.person_id → Person
  + EventCommitteeAssignment → Event
  = event-scoped access to 7 KetuaEvent abilities
```

### S7.1 — User↔Person Foundation ✅
- Added `person_id` (nullable, UNIQUE) to `users` table
- `User::person()` BelongsTo, `Person::user()` HasOne
- Fixed F1–F4 cross-event IDOR vulnerabilities (HapusSesi, DataSesi, EditSesi, SuratIzinService)

### S7.2 — Event-Scoped Gates + EventSwitcher ✅
- Added `EventAccessService` — `isUserAssignedToEvent()`, `getAssignedEventIds()`
- 7 Gate abilities event-scoped untuk KetuaEvent: `view-dashboard`, `manage-registration`, `manage-participants`, `manage-attendance`, `manage-sessions`, `manage-secretariat`, `view-reports`
- EventSwitcher filtered to assigned events untuk non-platform user
- Server-side enforcement dalam `EventSwitcher::switchTo()` — throws `AuthorizationException` untuk unassigned events
- **Evolusi (Sprint 2 — Permission Engine):** seluruh 14 event abilities kini di-resolve via `EventPermissionService` (assignment + EventRole.permissions), bukan hanya 7 untuk KetuaEvent. `users.role` hanya menentukan hak platform.

### S7.3 — Assignment Management UI ✅
- User Create/Edit UI: searchable Person selection with uniqueness enforcement
- EventRole Manager UI: create EventRole records per Event (name, code, description, sort_order)
- Committee Management UI: list/create/delete EventCommitteeAssignment per Event with Person search + EventRole select
- All mutations gated with `manage-events` ability

## KetuaEvent Access Matrix (Event-Scoped)

| Ability | Without Assignment | With Assignment |
|---------|:-----------------:|:---------------:|
| `view-dashboard` | ❌ | ✅ |
| `manage-registration` | ❌ | ✅ |
| `manage-participants` | ❌ | ✅ |
| `manage-attendance` | ❌ | ✅ |
| `manage-sessions` | ❌ | ✅ |
| `manage-secretariat` | ❌ | ✅ |
| `view-reports` | ❌ | ✅ |

## Authorization Rule

**EventRole IS the authorization source for event abilities (sejak Permission Engine).** Any `EventCommitteeAssignment` linking the User's Person to an Event + an active `EventRole` whose `permissions` JSON contains the ability grants access. `users.role` menentukan hak platform (SuperAdmin/Admin) saja.

## Security Principles

- SuperAdmin bypass via `Gate::before()` — preserved
- Admin bypass untuk event abilities via `$eventAbility` — preserved
- Role event (ketua_event, sekretariat, dst.) — ability via EventRole.permissions (Permission Engine)
- Tanpa assignment, tanpa Person, tanpa event aktif → denied
- EventSwitcher: filtered dropdown + server-side enforcement (defense-in-depth)

## Sidebar @can Directives per Menu

The sidebar (`resources/views/components/layouts/app/sidebar.blade.php`) uses `@can()` and `@canany()` directives to control menu visibility per role.

### CAI Navigation

| Menu Item | Directive | Ability |
|-----------|-----------|---------|
| Dashboard | `@can('view-dashboard')` | `view-dashboard` |
| Scan Absensi | `@can('manage-attendance')` | `manage-attendance` |
| Sesi Absensi | `@can('manage-sessions')` | `manage-sessions` |
| Absensi group | `@canany(['manage-attendance', 'manage-sessions'])` | either ability |
| Registrasi group | `@can('manage-registration')` | `manage-registration` |
| Peserta CAI | `@can('manage-participants')` | `manage-participants` |
| Laporan group | `@can('view-reports')` | `view-reports` |
| QR & Label | `@can('manage-qr-labels')` | `manage-qr-labels` |
| Sekretariat group | `@canany(['manage-secretariat', 'view-activity-log'])` | either ability |
| Surat Izin | `@can('manage-secretariat')` | `manage-secretariat` |
| Activity Log | `@can('view-activity-log')` | `view-activity-log` |
| Kelola Event | — (no gate) | — |
| Master Data | `@can('view-master-data')` | `view-master-data` |
| User Management | `@can('manage-users')` | `manage-users` |

### Pengajian Navigation

| Menu Item | Directive | Ability |
|-----------|-----------|---------|
| Regional Report | `@can('view-reports')` | `view-reports` |
| Peserta (Daftar + Import) | `@can('manage-pengajian')` | `manage-pengajian` |
| Operasional Desa (Akses Desa) | `@can('manage-pengajian')` | `manage-pengajian` |
| Kelola Event | — (no gate) | — |

## Visibility Matrix per Role

| Menu | Super Admin | Admin | Ketua | Sekretariat | PJ Divisi | Registrasi | Scan | Juri | Viewer |
|------|:-----------:|:-----:|:-----:|:-----------:|:---------:|:----------:|:----:|:----:|:------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Scan Absensi | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Sesi Absensi | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Registrasi | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Peserta CAI | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Laporan | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| QR & Label | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Surat Izin | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Activity Log | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Master Data | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| User Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

Notes:
- **Kelola Event** is intentionally not gated — the `Event\Index` render method and CRUD mutations are server-side protected with `manage-events`. The menu item is visible to all so users can navigate, but only authorized roles can mutate.
- **EventSwitcher** remains visible to all authenticated users regardless of role — event selection is navigation UX, not a permission gate.

## Parent/Group Behavior

Parent groups use `@canany()` to group visibility:

- **Absensi group** (`@canany(['manage-attendance', 'manage-sessions'])`) — shown if user can scan OR manage sessions
- **Sekretariat group** (`@canany(['manage-secretariat', 'view-activity-log'])`) — shown if user can manage surat izin OR view activity log
- Child items within groups are independently gated with their specific `@can()`, so a user sees only permitted sub-items even when the group heading is visible.

## Defense in Depth

Sidebar visibility is a **UX convenience layer** — it does NOT replace server-side authorization. All routes and Livewire mutations remain independently protected (S3, S4, S5). Hidden menus are still server-side inaccessible. This follows the Defense in Depth principle.
