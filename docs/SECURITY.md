# SECURITY

> Security Standard for KJA Event Manager

---

# Purpose

Dokumen ini mendefinisikan standar keamanan yang wajib diterapkan selama pengembangan KJA Event Manager.

Semua developer, AI Assistant, dan kontributor wajib mengikuti aturan dalam dokumen ini.

---

# Security Philosophy

KJA Event Manager mengikuti prinsip:

* Secure by Default
* Least Privilege
* Defense in Depth
* Never Trust User Input
* Audit Everything

Keamanan merupakan bagian dari desain sistem, bukan fitur tambahan.

---

# Authentication

## Current

* Email
* Password

## Future

* Google Login
* OTP
* Passkey
* SSO
* Multi Organization Login

---

# Authorization

KJA Event Manager menggunakan **Role Based Access Control (RBAC)**.

Semua hak akses dikelola melalui:

* Role (`app/Enums/Role.php`) ✅ S1
* Permission (Gate abilities in `AppServiceProvider`) ✅ S1
* Gate ✅ S1 (defined and attached to routes S1–S7)
* Policy (not used — Gates used instead S1–S7)

> Lihat **PERMISSION.md** untuk detail Role & Permission Matrix.

Status implementasi:
- S1 RBAC Foundation ✅ — Role enum, User column, Gate definitions, Artisan command
- S2 Master Data Protection ✅ — Route protection, Livewire authorization, Sidebar visibility
- S3 Operational Protection ✅ — Event/CAI route & Livewire protection
- S4 Pengajian Admin Protection ✅ — Pengajian admin route & Livewire protection
- S5 CAI Module Permissions ✅ — Import routes & Livewire protection
- S6 Sidebar Visibility ✅ — All menu items gated with @can()
- S7 Event-Scoped Authorization ✅ — KetuaEvent event-scoped gates + EventSwitcher filtering

Tidak diperbolehkan:

* Hardcode Role
* Hardcode Permission
* Menampilkan menu tanpa validasi permission

---

# Attendance Security

Attendance merupakan fitur paling kritikal.

QR Code **TIDAK BOLEH** berisi:

* NIP
* Universal ID
* Database ID
* Email
* Nomor HP

QR hanya boleh berisi:

* Attendance Code

Contoh:

```text
KEM-7F92QKX
```

Attendance Code harus:

* Random
* Unique
* Tidak dapat ditebak
* Dapat diregenerate

QR lama harus dapat dinonaktifkan apabila kartu hilang.

---

# Password Policy

Minimal:

* 8 karakter

Disarankan:

* 12+ karakter

Password wajib:

* Hash menggunakan Laravel Hash
* Tidak boleh disimpan plain text
* Tidak boleh dikirim melalui API Response

---

# Session Security

Menggunakan Laravel Session.

Future:

* Remember Device
* Device Management
* Session Monitoring
* Remote Logout

---

# Input Validation

Seluruh request wajib divalidasi.

Gunakan:

* Form Request
* Livewire Validation

Tidak boleh mempercayai input dari user.

Validasi minimal:

* Required
* Type
* Length
* Exists
* Unique
* Authorization

---

# SQL Injection Protection

Gunakan:

* Eloquent
* Query Builder

Hindari:

* Raw Query
* String Concatenation SQL

Raw Query hanya diperbolehkan apabila benar-benar diperlukan.

---

# XSS Protection

Gunakan Blade Template.

Escape seluruh output.

Hindari:

* Raw HTML
* Unescaped Content

---

# CSRF Protection

Semua Form wajib menggunakan CSRF Protection bawaan Laravel.

Tidak boleh dinonaktifkan.

---

# File Upload Security

Allowed:

* PDF
* Excel
* Image

Semua upload wajib:

* Validasi MIME Type
* Validasi ukuran
* Rename menggunakan UUID
* Disimpan di Storage

Jangan pernah menggunakan nama file asli sebagai nama penyimpanan.

---

# Export Security

Semua Export harus dicatat.

Minimal mencatat:

* User
* Event
* Jenis Export
* Waktu

Future:

Export Approval untuk data sensitif.

---

# Audit Log

Minimal aktivitas berikut harus dicatat:

* Login
* Logout
* Registrasi
* Scan QR
* Manual Attendance
* Edit Data
* Delete Data
* Import
* Export
* Generate QR
* Print
* Generate Certificate (V2 — Certificate Engine)

Audit Log tidak boleh dapat diubah oleh user biasa.

---

# Sensitive Data

Data berikut dianggap sensitif:

* Password
* Attendance Code
* Parent Phone
* Email
* Authentication Token
* API Key

Data sensitif tidak boleh:

* Ditampilkan tanpa permission
* Masuk ke log
* Dikirim ke client apabila tidak diperlukan

---

# Storage Security

Database hanya menyimpan metadata file.

File besar disimpan di:

* Nextcloud
* TrueNAS

Storage harus memiliki backup rutin.

---

# Backup Policy

Minimal:

* Database: 1x sehari
* Storage: 1x sehari
* Configuration: setiap perubahan besar

Backup harus dapat direstore.

---

# API Security (Future)

Semua API akan menggunakan:

* API Key
* Access Token
* Rate Limiting
* OAuth
* HTTPS Only

---

# Infrastructure Security

Development:

* Mini PC
* Proxmox

Production:

* VPS / Dedicated Server

Server wajib:

* Firewall aktif
* HTTPS
* Backup
* Monitoring
* Automatic Update (Security Patch)

---

# Logging & Monitoring

Minimal monitoring:

* Error Log
* Login Failed
* Storage Usage
* CPU
* RAM
* Database

Future:

* Health Check Dashboard
* Alert Notification

---

# AI Development Rules

AI MUST NOT:

* Disable Validation
* Disable Authorization
* Store Plain Password
* Expose Attendance Code
* Expose API Key
* Remove Security Middleware

AI MUST ALWAYS:

* Add Validation
* Follow Laravel Best Practice
* Use Eloquent
* Escape Output
* Keep Audit Logging
* Reuse Existing Security Components

---

# Security Checklist

Before Release:

* Password Hashed
* Authorization Checked
* Validation Complete
* CSRF Enabled
* Export Logged
* Audit Enabled
* Backup Configured
* HTTPS Enabled
* APP_KEY Configured
* APP_DEBUG Disabled
* .env Not Committed

---

# Future Security Roadmap

Sprint 1

* Attendance Code
* Internal QR
* Audit Export

Sprint 2

* QR Regeneration
* Operator Scan Token

Sprint 3

* Login Notification
* Device Management

Sprint 4

* Two Factor Authentication
* Offline Scan Encryption
* Attendance Code Rotation
* IP Restriction
* Geo Restriction

---

# S3 — Event Management & CAI Operational Protection ✅

**Status:** COMPLETE (2026-08-04).

## Protected Modules

### Event Management (`manage-events`)
- Route: `/events` (Livewire component)
- Livewire: `Event\Index` (render, archive, activate), `Event\EditStatus` (update)
- Roles: super_admin, admin

### Registration (`manage-registration`)
- Route: `/registrasi`, `/registrasi/ulang`
- Livewire: `Registrasi\Ulang` (registrasiUlang, updatePeserta)
- Roles: super_admin, admin, ketua_event, sekretariat, operator_registrasi

### Participant Management (`manage-participants`)
- Route: `/database`
- Livewire: `Database\Peserta\TambahPeserta`, `EditPeserta`, `HapusPeserta`, `ImportPeserta`
- Roles: super_admin, admin, ketua_event, sekretariat

### Attendance (`manage-attendance`)
- Route: `/absensi`
- Livewire: `Dashboard\Scan` (scanQR, manualHadir, manualIzin)
- Roles: super_admin, admin, ketua_event, sekretariat, pj_divisi, operator_scan

### Session Management (`manage-sessions`)
- Route: `/sesi-absensi`
- Livewire: `Database\Sesi\TambahSesi`, `EditSesi`, `HapusSesi`; `Dashboard\Dashboard` (setSesiAktif)
- Roles: super_admin, admin, ketua_event, sekretariat

### QR Labels (`manage-qr-labels`)
- Route: `/qr-label`
- Livewire: `QRLabel\Index` (downloadPng, printSelected, generateBatchExport, exportBatch)
- Roles: super_admin, admin, sekretariat

### Surat Izin / Secretariat (`manage-secretariat`)
- Route: `/surat-izin`
- Livewire: `SuratIzin\Index` (submit, approve, reject, cancel, return), `SuratIzin\Create` (simpan, submit)
- Roles: super_admin, admin, ketua_event, sekretariat

### Reports / Export (`view-reports`)
- Route: `/rekap-peserta`, `/rekap-absensi`
- Livewire: `Rekap\Peserta\RekapPeserta` (exportExcel)
- Roles: super_admin, admin, ketua_event, sekretariat, viewer

### Activity Log (`view-activity-log`)
- Route: `/activity-log`
- Roles: super_admin, admin, sekretariat

### Dashboard (`view-dashboard`)
- Route: `/dashboard`
- Roles: super_admin, admin, ketua_event, sekretariat, pj_divisi, viewer

## Implementation Detail
- All routes use `middleware('can:{ability}')` for declarative route-level security
- All Livewire mutation methods use `Gate::authorize('{ability}')` before any database write
- Authorization always occurs BEFORE state mutation — unauthorized invocation returns 403
- Sidebar visibility (`@can()` directives) for S3 modules implemented (S6)

---

# S4 — Pengajian Admin Protection ✅

**Status:** COMPLETE (2026-07-21).

## Protected Modules

### Pengajian Admin Route Protection (`manage-pengajian`)
- Routes protected with `can:manage-pengajian`:
  - `/koreksi-data` — Identity Correction Review
  - `/pengajian/admin/access` — Access Token Management
  - `/pengajian/admin/manual-entry` — Manual Participant Entry
  - `/pengajian/admin/import-massal` — Bulk Participant Import
- Previously accessible by ALL authenticated users — now gated to super_admin, admin, sekretariat
- `/pengajian/report` protected separately with `view-reports` (S3)

### Access Token Management Protection
- `DesaAccessGrant` CRUD operations (create, revoke, delete) protected with `Gate::authorize('manage-pengajian')`
- Token deletion only allowed for revoked grants (active grants cannot be deleted)
- Raw token one-time reveal design verified secure (DB stores hash only, token never persisted in logs/session)

### Import Authorization
- Pengajian bulk import (`/pengajian/admin/import-massal`) requires `manage-pengajian`
- Import identity matching is server-side validated (same as ManualParticipantRegistrationService pattern)
- Duplicate Participation prevention enforced at service layer

### Identity Correction Review Protection
- `/koreksi-data` route protected with `can:manage-pengajian`
- `IdentityCorrectionReview::approve()` and `reject()` gated with `Gate::authorize('manage-pengajian')`
- Known issue: two parallel identity correction submission paths (PengajianIdentityService vs IdentityCorrectionService)

### Public Token Flow Unchanged
- The public token entry flow (`/pengajian`, `/pengajian/enter-token`) remains **unchanged** — no authentication required
- Self-attendance (`/pengajian/hadir/{nonce}`) remains publicly accessible
- Rate limiting (5 failed attempts/min, IP-based) on EnterToken remains in place
- Public flows are not affected by S4 RBAC changes

---

# S0 — User Management Authorization

## Status

✅ COMPLETE (2026-07-21).

## Authorization

User Management is restricted to **Super Admin only** via the `manage-users` ability:
- Route `/users` uses `middleware('can:manage-users')`
- All Livewire mutations use `Gate::authorize('manage-users')`
- Sidebar menu item gated with `@can('manage-users')`

## Super Admin Safety Rules

- **Cannot delete self** — user is prevented from deleting their own account
- **Cannot delete last Super Admin** — system enforces at least one Super Admin exists

## Password Handling

- Passwords are **always hashed** using Laravel `Hash::make()` before storage
- Passwords are **never returned** in any API response, JSON, or view
- Password reset generates a new hashed password — the old hash is overwritten
- No plain-text password is ever logged, stored in session, or exposed

## Activity Log Integration

User Management actions are recorded in the Activity Log:

| Action | Module | Description |
|--------|--------|-------------|
| `created` | `user` | New user account created |
| `updated` | `user` | User profile updated |
| `role_changed` | `user` | User role changed |
| `password_reset` | `user` | Password reset by admin |
| `deleted` | `user` | User account deleted

## Implementation Detail
- 4 Pengajian admin routes now use `middleware('can:manage-pengajian')`
- 4 Livewire components gated with `Gate::authorize('manage-pengajian')`: AccessIndex, ManualEntry, ImportMassal, IdentityCorrectionReview
- Public Pengajian flows (token entry, self-attendance) remain accessible without authentication

---

# S5 — CAI Module Permissions ✅

**Status:** COMPLETE (2026-07-21).

## Protected Modules

### Import Management (`manage-import`)
- Routes protected with `can:manage-import`:
  - `/import/peserta` — CAI Participant Import (Excel/CSV)
  - `/import/regu` — CAI Regu Import (Excel/CSV)
- Note: `/import/desa` and `/import/kelompok` protected separately with `manage-master-data` (S2)
- Livewire: `ImportPeserta::import()`, `ImportRegu::import()` gated with `Gate::authorize('manage-import')`
- Roles: super_admin, admin, sekretariat

### Full CAI Permission Matrix Verification
All 10 operational abilities from S3 plus `manage-import` from S5 have been verified per role against the permission matrix at `docs/PERMISSION.md`. No authorization gaps remain in CAI operational routes.

## Authorization Summary (All 15 Abilities)

| # | Ability | Applied In | Status |
|---|---------|-----------|--------|
| 1 | `view-dashboard` | S3 | ✅ |
| 2 | `view-master-data` | S2 | ✅ |
| 3 | `manage-master-data` | S2 | ✅ |
| 4 | `manage-events` | S3 | ✅ |
| 5 | `manage-registration` | S3 | ✅ |
| 6 | `manage-participants` | S3 | ✅ |
| 7 | `manage-attendance` | S3 | ✅ |
| 8 | `manage-sessions` | S3 | ✅ |
| 9 | `manage-qr-labels` | S3 | ✅ |
| 10 | `manage-secretariat` | S3 | ✅ |
| 11 | `manage-import` | S5 | ✅ |
| 12 | `view-reports` | S3 | ✅ |
| 13 | `manage-pengajian` | S4 | ✅ |
| 14 | `view-activity-log` | S3 | ✅ |
| 15 | `manage-users` | S0 | ✅ |

All 15 Gate abilities have been applied to routes and/or Livewire mutations. Sidebar visibility implemented in S6.

---

# S6 — Sidebar UX Layer (RBAC Visibility) ✅

**Status:** COMPLETE (2026-07-21).

## Overview

Sidebar menu visibility is now synchronized with backend Gate abilities. Every menu item in the sidebar is wrapped with `@can()` or `@canany()` directives matching the same ability used for route/Livewire protection.

## Key Design Decisions

### EventSwitcher Remains Accessible to All
The EventSwitcher component (`livewire:event.event-switcher`) is intentionally **not gated** — it remains visible to all authenticated users regardless of role. Event selection is a navigation feature, not an authorization gate. Server-side protection on event-scoped routes/Livewire mutations ensures unauthorized users cannot act on events they lack permission for.

### Kelola Event Menu — No Gate
The "Kelola Event" sidebar link is intentionally not wrapped in `@can('manage-events')`. The `Event\Index` render method and CRUD mutations are server-side protected. The menu item is visible so all authenticated users can see event listings and navigate, but only `super_admin` and `admin` can create/edit/archive events.

### Parent/Child Group Gating
Groups use `@canany()` for the group heading (shown when user has ANY sub-ability), and child items use `@can()` for precise per-item control:
- **Absensi group**: shown if user has `manage-attendance` OR `manage-sessions`
- **Sekretariat group**: shown if user has `manage-secretariat` OR `view-activity-log`

### Event-Type Aware Contextual Gating
Sidebar gates work within both event contexts (CAI and Pengajian), providing consistent authorization UX across event types. The same ability checks apply regardless of which event type is active.

## Sidebar Coverage (All 9 Roles)

All 15 Gate abilities now have corresponding sidebar `@can()` directives where applicable:

| Ability | Sidebar Gate | Menu Item |
|---------|-------------|-----------|
| `view-dashboard` | `@can('view-dashboard')` | Dashboard |
| `manage-attendance` | `@can('manage-attendance')` | Scan Absensi |
| `manage-sessions` | `@can('manage-sessions')` | Sesi Absensi |
| `manage-registration` | `@can('manage-registration')` | Registrasi group |
| `manage-participants` | `@can('manage-participants')` | Peserta CAI |
| `view-reports` | `@can('view-reports')` | Laporan group, Regional Report |
| `manage-qr-labels` | `@can('manage-qr-labels')` | QR & Label |
| `manage-secretariat` | `@can('manage-secretariat')` | Surat Izin |
| `view-activity-log` | `@can('view-activity-log')` | Activity Log |
| `view-master-data` | `@can('view-master-data')` | Master Data |
| `manage-pengajian` | `@can('manage-pengajian')` | Peserta group + Operasional Desa |
| `manage-users` | `@can('manage-users')` | User Management |

## Implementation Detail

- No menu item is hidden solely by the sidebar gate — every route and Livewire mutation retains independent server-side protection
- Sidebar visibility follows the same permission matrix documented in `docs/PERMISSION.md`
- Gate checks are performed in Blade using Laravel's `@can()` / `@canany()` — no custom logic required
- Hidden menus return HTTP 403 if accessed directly (server-side protection), preventing privilege escalation through URL manipulation

---

# S7 — Event-Scoped Authorization ✅

## Status

**COMPLETE** (2026-08-04). KetuaEvent abilities are now event-scoped.

## Architecture

```
User.role → determines WHAT the account may do
User.person_id → links account to canonical Person
EventCommitteeAssignment → determines WHICH Event the Person/User is assigned to
EventRole → operational committee position only — NOT RBAC authorization
```

### Key Design Decisions

1. **EventRole is NOT authorization.** The `EventRole` model is operational/domain metadata. Assignment existence, not role name/code, drives authorization.
2. **User↔Person is optional.** Not all Users need a linked Person. Only KetuaEvent requires it for event-scoped access.
3. **Defense in depth.** EventSwitcher dropdown is filtered AND server-side `switchTo()` enforces assignment checks. Gate closures independently verify assignment on every check.
4. **No automatic assignment creation.** Linking User→Person does not create EventCommitteeAssignment records. Assignment must be created explicitly via the Event Management UI.

### S7 Completed Deliverables

| Sprint | Deliverable | Status |
|--------|-------------|--------|
| S7.1 | User↔Person Foundation + IDOR fixes | ✅ |
| S7.2 | Event-scoped gates + EventSwitcher | ✅ |
| S7.3 | Assignment management UI + EventRole UI | ✅ |

### Remaining Backlog (Non-Blocking)

- Assignment role edit UI (delete+recreate workaround exists)
- EventRole edit/delete UI
- ActivityGroup/Activity/Venue assignment UI
- Advanced Person search in User forms
