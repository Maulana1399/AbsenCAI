# CURRENT STATE

> Current Development Status

---

# Project

## Name

KJA Event Manager

Current MVP:

CAI Operational + Pengajian Desa MVP

---

# Current Version

Version:

v1.5

Stage:

MVP Development + Pilot

---

# Current Sprint

Sprint 3 — Multi Event Architecture + Pengajian Desa MVP (COMPLETE)

Status:

✅ PGM.12–PGM.17 COMPLETE
✅ S01–S04 Foundation COMPLETE 100%
✅ S3.0–S3.10 all COMPLETE/VERIFIED
✅ UI Bug Fix Sprint — COMPLETE

Target: August 2026 pilot release.

Focus:

Multi Event Foundation completed and verified. S3.0–S3.10 all COMPLETE/VERIFIED.

**Pengajian Desa MVP (PGM series)** implemented on top of S3 architecture:
- PGM.12 Functional Fix ✅ COMPLETE
- PGM.13 Token Management UI ✅ COMPLETE
- PGM.14 Event Context/Isolation ✅ COMPLETE
- PGM.14.1 Security & Reliability Closure ✅ COMPLETE
- PGM.14.5 Manual Participant Entry ✅ COMPLETE
- PGM.15 Dashboard & Report Optimization ✅ COMPLETE
- PGM.16 Pengajian UX, Contextual Navigation & Bulk Import ✅ COMPLETE
  - event_type architecture (cai / pengajian)
  - Contextual sidebar (CAI vs Pengajian menus)
  - KJA Event Manager branding
  - Pengajian bulk import (CSV/Excel with preview)
  - Regional/Desa report filter fixes
  - Responsive Regional Report and Desa Dashboard
  - Event switcher redirect/reload fix
  - Migration: kelompok_id to people table, event_type to events table
- PGM.17 Pilot Release ✅ COMPLETE — UI interaction remediation (invisible controls, modal close, dark mode, mobile responsiveness). Full suite: 1570 passed / 3803 assertions / 0 failures. Design C diagnostic: problem_total = 0.
- **PGM.18 Sprint 1 ✅ COMPLETE** — Legacy historical tooling removal (6 commands, 4 services, 4 test files, 2 partial test refactors). Design C diagnostic contract fixed (NULL participation_id no longer counted as broken reference). Full suite: 1494 passed / 3581 assertions / 0 failures. Design C diagnostic: problem_total = 0. Sprint 1 removed 76 tests (historical tooling coverage intentionally deleted) + added 1 regression test = net -75 tests from PGM.17 baseline. Not a regression.
- **PGM.18 Sprint 2 ✅ COMPLETE** — Legacy mapping contract refactoring. LegacyPesertaMapping contract narrowed to peserta↔Person only. LegacyParticipationMapping confirmed as sole event-specific bridge. Full suite: 1499 passed / 3592 assertions / 0 failures. Design C: problem_total = 0. Fixed 6 regression failures, 1 parse error, 3 stale contract references. Genuine production regression found and fixed (QR label single print route).
 
 **UI Bug Fix Sprint** — All batches RESOLVED VERIFIED ✅.
 
 **Runtime architecture unchanged for legacy compatibility** — `pesertas` and `LegacyPesertaMapping` remain intentional compatibility bridges.
 Sprint 3 proposed scope: Drop legacy columns, remove deprecated relationships, finalize PGM.18 completion.

---

# Current Goal

Pengajian Desa MVP pilot is feature-complete and UI-remediated. PGM.17 CLOSED.

Completed foundation work:

* S01 Foundation completed.
* S02 Registration and Placement foundation completed.
* S03 Attendance architecture completed.
* S04 Identity & QR completed.
* S05 Document & Certificate deferred / skipped for now.
* S3.0 Architecture & Database Audit completed.
* S3.1 Event Foundation completed.
* S3.2 Universal Person completed.
* S3.3 Participation Foundation completed.
* S3.4 Active Event Context Hardening completed.
* S3.5 Legacy Data Backfill COMPLETE — PRODUCTION BACKFILL EXECUTED 2026-07-17.
* S3.6 Attendance Event Scoping COMPLETE.
* S3.7 Participant/QR Migration COMPLETE.
* S3.8 Dashboard & Report Scoping COMPLETE.
* S3.9A–S3.9E Multi Role/Venue/Category COMPLETE.
* S3.10 Regression & Production Readiness COMPLETE.
* PGM.12–PGM.17 Pengajian Desa MVP COMPLETE.

---

# Person-Legacy Sync

## Status

🟢 Complete (2026-07-21).

### What was done
- **PersonLegacySyncService** — one-way sync from Person → legacy peserta within a DB transaction
- **Sync boundary**: nama, jenis_kelamin, desa_id, kelompok_id ONLY
- **NIP locked** for mapped Persons to protect attendance history and legacy compatibility
- **Server-side NIP enforcement**: `resolveNip()` method in service layer overrides submitted NIP with database value for mapped Persons, preventing Livewire state manipulation
- **No-sync guarantee**: participant_number, attendance_code, regu_id, status_registrasi never change
- **Gender normalization**: L → 'Laki - Laki', P → 'Perempuan' via PlacementService::normalizePersonGender()
- **RegistrationService fix**: `createParticipant()` and `updateParticipant()` now sync `kelompok_id` to Person (was missing, causing two-way identity inconsistency)
- **26 new tests** covering sync behavior, NIP enforcement, RegistrationService kelompok_id consistency, delete guard regression

---

# Person Master Data CRUD

## Status

🟢 Complete (2026-07-21).

### What was done
- **Person CRUD page** at `/person` with index, create, edit, and delete
- **Sidebar** — Person added as first submenu under Master Data
- **Global data** — Person page works without active event; does not modify ActiveEventContext
- **Safe delete** — Person with participations, legacy mapping, or committee assignments cannot be deleted
- **No Participation** — Create Person only creates a Person record; does not create Participation, generate NIP, or perform auto-placement
- **26 dedicated tests** for Person CRUD, sidebar visibility, search, empty state, delete safety, and regression

### Next (for Person domain)
- RBAC for Master Data (currently all authenticated users can access)
- Person → sync with legacy peserta on edit (currently RegistrationService handles this for event-registered persons only)

---

# Master Data Module

## Status

🟢 Landing page + navigation complete (2026-07-21).

### What was done
- **Master Data landing page** at `/master-data` with responsive navigation cards
- **Sidebar**: single "Master Data" link → `/master-data` (replaces old expandable group with sub-items)
- **Cards**: Person, Desa, Kelompok — each with icon, description, named route
- **Regu removed** from Master Data — reclassified as Legacy CAI Operational
- Regu route `/regu`, components, and model preserved for backward compatibility
- Master Data is **global** — accessible without active event context
- ActiveEventContext is not modified by Master Data navigation

### Next
- **Person CRUD** — Person is global master data but lacks dedicated management page
- **Venue CRUD** — event-scoped, needs UI
- **CategoryDefinition CRUD** — event-scoped, needs UI
- **RBAC** for Master Data menus

---

# RBAC (Phase 4)

## Status

🟢 S1–S7 RBAC COMPLETE (2026-08-04). Full RBAC implementation including event-scoped authorization for KetuaEvent.

### S1 — RBAC Foundation ✅
- Role enum (`app/Enums/Role.php`) — 9 roles
- Migration `2026_08_03_000001` — nullable `role` column on users
- User model: role cast, `hasRole()`, `hasAnyRole()`
- 15 Gate abilities defined in `AppServiceProvider` with Super Admin bypass via `Gate::before()`
- Artisan command: `php artisan user:set-role {email} {role}`
- 35 tests covering Role enum, User model, all Gate permissions, null-role safety, Artisan command, regression

### S2 — Master Data Protection ✅
- Route protection: `/master-data`, `/person`, `/desa`, `/kelompok` via `middleware('can:view-master-data')`
- Import protection: `/import/desa`, `/import/kelompok` via `middleware('can:manage-master-data')`
- Livewire mutation protection: 12 mutation methods across Person/Desa/Kelompok components gated with `Gate::authorize('manage-master-data')`
- Sidebar visibility: `@can('view-master-data')` on Master Data menu item
- 36 dedicated tests for route access matrix, sidebar, mutations, regression
- `/regu` explicitly excluded from Master Data protection

### S3 — Event Management & CAI Operational Protection ✅
- **Route protection**: 11 routes protected with `can:*` middleware:
  - `view-dashboard` → `/dashboard`
  - `manage-registration` → `/registrasi`, `/registrasi/ulang`
  - `manage-participants` → `/database`
  - `manage-sessions` → `/sesi-absensi`
  - `view-reports` → `/rekap-peserta`, `/rekap-absensi`
  - `manage-qr-labels` → `/qr-label`
  - `manage-attendance` → `/absensi`
  - `manage-secretariat` → `/surat-izin`
  - `view-activity-log` → `/activity-log`
- **Livewire mutation protection**: 16 components gated with `Gate::authorize()`:
  - `manage-events`: Event\Index (render, archive, activate), Event\EditStatus (update)
  - `manage-participants`: TambahPeserta, EditPeserta, HapusPeserta, ImportPeserta, Registrasi\Ulang
  - `manage-sessions`: TambahSesi, EditSesi, HapusSesi, Dashboard\Dashboard (setSesiAktif)
  - `manage-attendance`: Dashboard\Scan (scanQR, manualHadir, manualIzin)
  - `manage-qr-labels`: QRLabel\Index (downloadPng, printSelected, generateBatchExport, exportBatch)
  - `view-reports`: Rekap\Peserta\RekapPeserta (exportExcel)
  - `manage-secretariat`: SuratIzin\Index (submit, approve, reject, cancel, return), SuratIzin\Create (simpan, submit)
- **Sidebar visibility**: ✅ Implemented (S6)

### S7 — Event-Scoped Authorization ✅
- **S7.1** — User↔Person Foundation: `person_id` on users, model relationships, cross-event IDOR fixes (HapusSesi, DataSesi, EditSesi, SuratIzinService)
- **S7.2** — Event-scoped gates for KetuaEvent (7 abilities), EventSwitcher filtering + server-side enforcement, EventAccessService
- **S7.3** — Assignment management UI, EventRole creation UI, User↔Person linking in User Management
- 95 new tests across S7.1–S7.3

### Remaining Backlog (Non-Blocking)
- Assignment role edit UI (delete+recreate workaround exists)
- EventRole edit/delete UI
- ActivityGroup/Activity/Venue assignment UI
- Advanced Person search in User forms

---

# Current Priority

Priority saat ini:

1. **PGM.17 — Pilot Release** ✅ COMPLETE — UI interaction remediation. Full suite: 1570 passed / 3803 assertions / 0 failures. Design C: problem_total = 0.
2. **Database V2 Part 5 CLOSED** ✅ — Documentation synchronized; semantic coverage audited.
3. **PGM.18 Sprint 1 ✅ COMPLETE** — Legacy historical tooling removed, Design C diagnostic contract fixed. Verified: 1494 passed / 3581 assertions / 0 failures, Design C: problem_total = 0.
4. Remaining P2/P3 technical debt items (non-blocking RBAC backlog).
5. **PGM.18 Sprint 2 ✅ COMPLETE** — Mapping contract refactored, fully verified (1499/3592/0).
6. **PGM.18 Sprint 3 📋 PROPOSED** — Drop legacy columns, remove deprecated relationships, finalize PGM.18 completion.

## Database V2 Part 5 Closure

- 5A EditPeserta + Ulang: COMPLETE
- 5B PesertaImport: COMPLETE
- 5C CaiParticipantReplacement: COMPLETE
- 5D HapusPeserta: COMPLETE
- Final Design C: Person = global canonical identity; Participation = event-scoped membership; peserta = global legacy mirror; LegacyPesertaMapping = single global compatibility mapping; LegacyParticipationMapping = event-aware compatibility bridge
- Deletion limitation remains intentional because `legacy_peserta_mappings.participation_id` is NOT NULL + `restrictOnDelete` (NOTE: Sprint 2 refactored contract — `LegacyPesertaMapping` no longer depends on `participation_id`, but model and DB column are retained; drop requires Sprint 3)
- Technical debt remains: legacy participation pointer redesign/nullability, status_registrasi event-scoping, regu_id event-scoping, legacy Absensi/IzinAbsensi event ambiguity, legacy participant_number mirror dependency
- Test delta note: exact historical -2 tests / +16 assertions cannot be reconstructed because relevant Part 5 tests were untracked during development; current semantic coverage audited and no known critical Design C coverage is missing

---

# Project Status

## Documentation

🟢 Stable — updated for PGM.16

## Architecture

🟢 Stable — Multi Event architecture complete with event_type discriminator

## Database

🟢 Stable — 2 new migrations in PGM.16 (kelompok_id on people, event_type on events)

## Core Feature

🟢 Stable — all CAI + Pengajian features operational

## Security

🟢 S1–S7 RBAC complete — Role enum, 15 Gate abilities, event-scoped KetuaEvent authorization, User↔Person↔Assignment chain, defense-in-depth

---

# Current Technical Stack

Backend: Laravel 12
Frontend: Livewire, Flux UI, Tailwind CSS
Database: SQLite

---

# Current Risks

## High
- SQLite not suitable for concurrent large-scale access
- Multi Event is context-only — modules must be migrated one by one
- Raw access token ditampilkan penuh di modal creation — potensi security issue jika pengguna tidak menyalin token dengan aman

## Medium
- P2: Admin ManualEntry no RBAC
- P2: Two parallel identity correction submission paths
- P2: No XLSX template download for Pengajian import
- Landing page dan login page masih menggunakan branding CAI — membingungkan pengguna baru KJA Event Manager

## Low
- API not yet needed
- Mobile App still planning
- KJA logo navigasi ke dashboard CAI — perlu event-aware routing

---

# S1+S2 Known Technical Debt (RBAC)

- **Master Data route/Livewire/sidebar protection**: ✅ COMPLETE
- **Event, CAI, Pengajian Admin, Reports route protection**: ✅ COMPLETE (S3 Operational Protection).
- **Livewire actions di Event/CAI/Pengajian module**: ✅ COMPLETE (S3 Operational Protection).
- **Sidebar untuk modul non-Master-Data**: ✅ COMPLETE (S6 Sidebar Visibility).
- **Ketua Event global**: `manage-events` hanya diberikan ke Super Admin + Admin. Ketua Event belum memiliki event-scoped authorization (memerlukan EventRole/EventCommitteeAssignment integration).
- **Viewer read-only**: Viewer memiliki `view-dashboard` dan `view-reports` tetapi belum ada enforcement read-only di level UI/action.

# Current Technical Debt

- Legacy `pesertas` table remains operational — intentional compatibility bridge
- `RegistrationService` still creates legacy `peserta` records alongside Person/Participation
- `PlacementService` has mixed responsibilities (legacy NIP + participant_number generation)
- Two parallel identity correction paths (PengajianIdentityService vs IdentityCorrectionService)
- Sidebar is static Blade — doesn't live-render on event switch (page navigation resolves)
- RBAC implemented S1–S7 — no known gaps in CAI Operational routes
- Pengajian import template XLSX not yet downloadable from UI
- Event edit form does not allow changing event_type after creation
- No dedicated Pengajian admin dashboard (Regional Report serves as landing)
- Landing page (`/`) still uses CAI branding — needs KJA Event Manager rebrand
- Login page still uses CAI branding — needs KJA Event Manager rebrand
- No hard-delete for revoked DesaAccessGrants — only soft revocation
- `DashboardService` not yet implemented — dashboard stats computed inline in Livewire
- Test suite: 1499 passed, 3592 assertions, 0 failures (post-PGM.18 Sprint 2). Increase from Sprint 1: +5 tests, +11 assertions (refactored LegacyPesertaMapping test coverage added back). Baseline Sprint 1: 1494/3581/0. Baseline PGM.17: 1570/3803/0 (difference expected — Sprint 1 intentionally removed 75 tests).

---

# Next Work

**PGM.18 — Database V2 Part 6: Legacy Dependency Remediation + CAI Participant Architecture**

Priority: **HIGH**

Status: **✅ COMPLETE (Sprint 1 + Sprint 2)**

## Sprint 1 ✅ COMPLETE

Scope: Legacy Historical Tooling Removal + Design C Diagnostic Contract Fix

Delivered:
- Removed 6 historical backfill/migration commands (zero production callers)
- Removed 4 historical service classes (zero production callers)
- Removed 4 pure-historical-tooling test files (1954 lines)
- Refactored 2 mixed test files (removed 4 backfill-specific tests)
- Fixed Design C diagnostic contract — NULL participation_id no longer counted as broken reference
- Added regression test for diagnostic contract
- No production runtime behavior changed
- No database migration
- No database mutation

Verified:
- Full suite: 1494 passed / 3581 assertions / 0 failures
- Design C: problem_total = 0
- Design C details: all 8 metrics 0

Database snapshot during Sprint 1 audit (2026-07-22):
- 143 pesertas (real legacy import data, July 7-9, 2026)
- 144 people
- 2 participations
- 143 legacy_peserta_mappings (all with participation_id = NULL — valid forward-reference state)
- 2 legacy_participation_mappings
- 1 event (CAI 27)

The 143 LegacyPesertaMapping records with NULL participation_id are NOT Design C violations. This state is valid according to current schema (nullable FK with nullOnDelete). Database tidak benar-benar kosong saat Sprint 1 dieksekusi.

## Sprint 2 ✅ COMPLETE

Scope: Legacy Mapping Contract Refactoring — narrowing LegacyPesertaMapping to peserta↔Person only.

Delivered:
- **Contract refactored**: LegacyPesertaMapping = global bridge peserta↔Person only
- **LegacyParticipationMapping confirmed**: sole event-specific bridge (event_id + peserta_id ↔ participation)
- **Foundation test refactored**: 22 → 14 tests (10 kept, 8 removed, 4 refactored). Factory/fixture now defaults to peserta_id + person_id only.
- **6 regression failures fixed** (4 Activity Foundation tests, CaiParticipantReplacementTest, PrintLogTest)
- **1 parse error fixed** in CaiParticipantReplacementTest (missing semicolon)
- **3 stale contract references fixed** (PersonReuseTest, MultiEventValidationRoutingTest, DesignCDiagnosticsTest)
- **1 genuine production regression found and fixed**: QR label single print route — handler depended on `participation_id` via `$participant->legacyPesertaMapping()`, which returned NULL after Sprint 2 fixture refactor. Fixed by resolving via `LegacyParticipationMapping`.
- **Model audit completed**: `participation()`, `event()` relationships and `participation_id`, `event_id`, `backfill_batch_id` in `$fillable` are DEPRECATED but retained (Sprint 3 target)
- **Production zero-reference audit**: All LegacyPesertaMapping deprecated columns — 0 explicit production references ✅
- **6 implicit references via `Participation::legacyPesertaMapping()`** documented as Sprint 3 blockers (4 Livewire files)

Verified externally:
- Full suite: 1499 passed / 3592 assertions / 0 failures (increase from Sprint 1: +5 tests, +11 assertions)
- Design C: problem_total = 0

## Sprint 3 📋 PROPOSED SCOPE

Scope: Drop legacy columns, remove deprecated relationships, finalize PGM.18 completion.

Proposed deliverables:
1. **Column drop**: `participation_id`, `event_id`, `backfill_batch_id` from `legacy_peserta_mappings` table
2. **Model cleanup**: Remove deprecated `participation()`, `event()` relationships and `$fillable` entries from LegacyPesertaMapping model
3. **Production cleanup**: Migrate 6 implicit references via `Participation::legacyPesertaMapping()` to `LegacyParticipationMapping` (4 Livewire files: CaiParticipantReplacement, HapusPeserta, PesertaImport, EditPeserta)
4. **Database migration**: One migration to drop 3 columns + update any code referencing them
5. **Repeat full suite verification**: Expect same baseline (1499/3592/0) — no test removal this sprint

Blockers before Sprint 3 can begin:
- Must wait for user to accept proposed scope
- Must be executed on machine with PHP runtime (not available here)

Architecture source: `docs/DATABASE_V2.md`, `docs/DATABASE.md`, `docs/DECISION.md`.

---

# Development Rules

* Follow `docs/ROADMAP.md` as the primary roadmap.
* Audit existing functionality before implementing new functionality.
* Do not duplicate features that already exist.
* Keep backward compatibility unless an explicit migration is designed.
* Business logic should remain centralized in service layers.
* Update documentation after verified changes.
* Run the full test suite before closing a feature scope.

---

# Success Criteria

PGM.17 dianggap selesai apabila:
- Pilot data verified
- End-to-end simulation passes
- Production deployment successful
- Operator training materials complete

---

# Notes

CURRENT_STATE.md adalah snapshot kondisi proyek.
Dokumen ini akan diperbarui setiap kali sprint selesai.

---

# S4 — Pengajian Admin Protection ✅

## Status

✅ COMPLETE (2026-07-21).

### What was done
- **Route protection**: 4 Pengajian admin routes protected with `can:manage-pengajian` middleware:
  - `/koreksi-data` — Identity Correction Review
  - `/pengajian/admin/access` — Access Token Management
  - `/pengajian/admin/manual-entry` — Manual Participant Entry
  - `/pengajian/admin/import-massal` — Bulk Import
- **Livewire mutation protection**: 4 components gated with `Gate::authorize('manage-pengajian')`:
  - `AccessIndex` (create, revoke, delete)
  - `ManualEntry` (submit, confirmMatch, createNewPerson)
  - `ImportMassal` (preview, executeImport)
  - `IdentityCorrectionReview` (approve, reject)
- **Pre-S4 audit**: Routes previously accessible by ALL authenticated users — now restricted
- **Public flow unchanged**: Token entry and self-attendance remain publicly accessible

### Updated RBAC Status
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 **S4 Pengajian Admin Protection: ✅ Complete**
- 🟡 S5–S7: Not yet started

### Remaining (S6–S7)
- S6 — Sidebar Visibility (`@can()` directives for all menu items)
- S7 — Full permission test matrix

---

# S5 — CAI Module Permissions ✅

## Status

✅ COMPLETE (2026-07-21).

### What was done
- **Route protection**: 2 CAI import routes protected with `can:manage-import` middleware:
  - `/import/peserta` — CAI Participant Import
  - `/import/regu` — CAI Regu Import
- **Livewire mutation protection**: 2 components gated with `Gate::authorize('manage-import')`:
  - `ImportPeserta::import()`
  - `ImportRegu::import()`
- **Full CAI permission matrix verification**: All 15 Gate abilities now applied across routes and/or Livewire mutations
- **`manage-import` access matrix**: super_admin, admin, sekretariat

### Updated RBAC Status
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 S4 Pengajian Admin Protection: ✅ Complete
- 🟢 **S5 CAI Module Permissions: ✅ Complete**
- 🟢 **S6 Sidebar Visibility: ✅ Complete**
- 🟡 S7: Not yet started

### Remaining (S7)
- S7 — Full permission test matrix

---

# S6 — Sidebar Visibility (RBAC) ✅

## Status

✅ COMPLETE (2026-07-21).

### What was done
- **Sidebar `@can()` directives** added to all menu items in `sidebar.blade.php`:
  - CAI navigation: Dashboard, Absensi (Scan + Sesi), Registrasi, Peserta CAI, Laporan, QR & Label, Sekretariat (Surat Izin + Activity Log), Master Data, User Management
  - Pengajian navigation: Regional Report, Peserta (Daftar + Import), Operasional Desa (Akses Desa)
- **Parent group gating**: `@canany()` for Absensi and Sekretariat groups — group heading shown when user has ANY sub-ability
- **EventSwitcher**: intentionally ungated — visible to all authenticated users
- **Kelola Event**: intentionally ungated — server-side `manage-events` protection
- **Contextual sidebar consistency**: CAI and Pengajian contexts both use same gate mechanism
- **Visibility matrix verified**: All 9 roles checked against PERMISSION.md access matrix

### Updated RBAC Status
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 S4 Pengajian Admin Protection: ✅ Complete
- 🟢 S5 CAI Module Permissions: ✅ Complete
- 🟢 **S6 Sidebar Visibility: ✅ Complete**
- 🟡 S7: Not yet started

### Remaining (S7)
- Full permission test matrix (role × module × allowed/denied)

---

# User Management

## Status

✅ COMPLETE (2026-07-21).

### What was done
- **Route `/users`** protected with `can:manage-users` middleware — Super Admin only
- **5 Livewire components** for user management:
  - `User\Index` — searchable user list with pagination
  - `User\Create` — new user form (name, email, password, role)
  - `User\Edit` — edit user profile and role
  - `User\ResetPassword` — admin-initiated password reset
  - `User\Delete` — delete user with safety guard
- **`UserManagementService`** — centralized service for user CRUD business logic
- **Delete safety rules**:
  - Cannot delete own account (self-deletion blocked)
  - Cannot delete last Super Admin (at least one must remain)
- **Super Admin only** — `manage-users` ability granted exclusively to `super_admin` role
- **Activity Log integration** — all user mutations logged:
  - `created` — new user account created
  - `updated` — user profile updated
  - `role_changed` — user role changed
  - `password_reset` — password reset by admin
  - `deleted` — user account deleted
- **Password security** — always hashed via Laravel `Hash::make()`, never exposed in responses/logs
- **Sidebar visibility** — User Management menu gated with `@can('manage-users')`
- **Route** — `/users` with `auth`, `verified`, and `can:manage-users` middleware
- **Tests** — dedicated test coverage for CRUD, delete safety, authorization, and activity logging
