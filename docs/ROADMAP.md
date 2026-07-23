# KJA Event Manager Roadmap

> Product Roadmap

---

# Vision

Membangun platform **Event Management** yang modular, scalable, dan dapat digunakan oleh berbagai organisasi.

KJA Event Manager dikembangkan secara bertahap.

Target pertama adalah menyelesaikan seluruh kebutuhan operasional **CAI**, kemudian dikembangkan menjadi platform Event Management yang mendukung berbagai jenis event dan akhirnya menjadi produk komersial.

---

# Development Principles

Seluruh pengembangan mengikuti prinsip:

* CAI First
* No Code Before Design
* Documentation First
* Modular Development
* Backward Compatible
* Scalable Architecture

---

# Product Timeline

```
AbsenCAI
     │
     ▼
CAI Operational
     │
     ▼
KJA Event Manager
     │
     ▼
Competition Module
     │
     ▼
Commercial Platform
```

---

# Sprint 0

## Status

✅ Current

## Goal

Merapikan pondasi proyek sebelum pengembangan besar dimulai.

## Target

* Dokumentasi
* Arsitektur
* Database Design
* Security
* Permission
* Roadmap
* Development Standard

## Deliverables

* AGENTS.md
* INDEX.md
* CONTEXT.md
* DATABASE.md
* DATAFLOW.md
* FEATURE.md
* SECURITY.md
* PERMISSION.md
* ROADMAP.md
* CHANGELOG.md

---

# Sprint 1

## Status

✅ Closed / Completed for Current Operational Scope

Sprint 1 ditutup untuk kebutuhan operasional CAI saat ini.

Deferred backlog untuk implementasi masa depan:

* QR PDF Export
* Report PDF Export
* Dashboard PJ Regu
* Live Monitoring

## Goal

Menyelesaikan seluruh kebutuhan operasional absensi CAI.

## Priority

Highest

## Features

### Attendance

* [x] Attendance Code
* [x] Internal QR Generator
* [x] QR Regeneration
* [x] Manual Attendance
* [x] Attendance History
* [x] Attendance Status
* [x] Izin
* [x] Alfa

Notes:

* Hadir disimpan melalui `Absensi`.
* Izin disimpan melalui `IzinAbsensi`.
* Alfa dihitung secara derived dari peserta yang tidak memiliki status Hadir maupun Izin pada sesi.
* Manual Attendance mendukung pencatatan Hadir dan Izin.
* Konflik Hadir ↔ Izin dicegah oleh attendance service layer.


---

### QR

* [x] Generate QR
* [x] Batch Generate
* [ ] PDF Export — Deferred
* [x] Print 4×4 cm
* [x] Batch Print

---

### Report

* [x] Export Excel
* [ ] Export PDF — Deferred
* [x] Rekap Per Regu
* [x] Rekap Per Desa
* [x] Rekap Per Kelompok
* [x] Rekap Belum Hadir
---

### Dashboard

* [x] Dashboard Divisi
* [ ] Dashboard PJ Regu — Deferred
* [x] Progress Registrasi
* [x] Progress Absensi
* [ ] Live Monitoring — Deferred

---

### UI

* [x] Dark Mode
* [x] Responsive Mobile
* [x] Menu Refactor
* [x] Reusable Components

Notes:

* Reusable Components dinyatakan selesai pada level foundation karena aplikasi menggunakan Flux UI dan centralized layouts.
* QR regeneration dilakukan on-demand dari `attendance_code`; tidak membutuhkan file QR persisten terpisah.
* QR PDF Export dan Report PDF Export adalah scope berbeda. QR PDF Export ditunda; QR PNG generation/export dan print flow tetap operasional.

---

# Sprint 2

## Status

🟢 Operational Stable / Partially Deferred

---

# Phase 4 — RBAC (Security & Permission)

## Status

🟢 **S1 RBAC Foundation**: Complete. Role enum, users.role migration, Gate definitions, Artisan command `user:set-role`.
✅ **S3**: Complete — Event Management & CAI Operational Protection.
🟡 **S4–S7**: Not yet implemented.

## Goal

Menerapkan Role-Based Access Control (RBAC) di seluruh aplikasi sesuai permission matrix di `docs/PERMISSION.md`.

## Phases

### S1 — RBAC Foundation ✅
- [x] `Role` enum (9 roles)
- [x] `users.role` migration (nullable string)
- [x] User model: role cast, hasRole(), hasAnyRole()
- [x] Gate definitions (15 abilities) + Super Admin bypass
- [x] Artisan command `php artisan user:set-role`

### S2 — Master Data Protection ✅
- [x] Route protection: `/master-data`, `/person`, `/desa`, `/kelompok` via `can:view-master-data`
- [x] Import protection: `/import/desa`, `/import/kelompok` via `can:manage-master-data`
- [x] Livewire mutation authorization: Person, Desa, Kelompok CRUD + Import
- [x] Sidebar visibility: `@can('view-master-data')` on Master Data menu
- [x] Regu explicitly excluded from Master Data protection
- [x] 36 dedicated tests

### S3 — Route/Livewire Protection ✅
- [x] Event Management protection (manage-events)
- [x] CAI module permissions: registration, participants, attendance, sessions, QR labels, secretariat, reports, activity log, dashboard

### S3 — Livewire Action Authorization ✅
- [x] Authorize Livewire mutations per component (16 components, 25+ mutation methods)

### S4 — Pengajian Admin Protection 🔲
- [ ] Separate admin Pengajian routes from role-based access

### S5 — CAI Module Permissions 🔲
- [ ] Registration, attendance, QR, session, import permissions

### S6 — Sidebar Visibility ✅
- [x] `@can()` directives for all menu items

### S7 — Security Regression Tests 🔲
- [ ] Full role × module × allowed/denied test matrix

---

Permission feature group completed.
Activity Log foundation + Export/Print/QR Log completed.
Riwayat Izin, Scoring, Storage **deferred** to 2027.

## Reason for Deferral

Multi Event (Sprint 3) is required for operational use in **August 2026**.
Next CAI operational use is planned for 2027.
All CAI-specific Sprint 2 features are deferred until CAI is next needed.

## Goal

Melengkapi kebutuhan operasional sekretariat.

## Features

### Permission

* [x] Surat Izin — create, submit, approve, reject, cancel, return, print
* [x] Print Surat — A5 landscape template with Kop Surat and logos, browser-native print
* [x] Return Tracking — mark returned with selectable date, attendance cleanup, IzinAbsensi end_time update
* [ ] Riwayat Izin — **DEFERRED** to 2027

Notes:

* Surat Izin has full CRUD with service layer (`SuratIzinService`), Livewire UI (`KelolaSuratIzin`, `TambahSuratIzin`), database migrations, authorization gates, and test coverage.
* Return Tracking is integrated into the Surat Izin flow with a date picker modal.
* `jenis_izin` (`pulang`/`keluar`) support added as a migration.

Known Limitation:

* `EditSesi` page currently references `sesi_id` from `SuratIzin` as editable session data. This needs to be reviewed: the `sesi_id` on a Surat Izin should record the *session the izin applies to*, not allow arbitrary session editing.

---

### Scoring

* [ ] Master Point — **DEFERRED** to 2027
* [ ] Bonus — **DEFERRED** to 2027
* [ ] Penalty — **DEFERRED** to 2027
* [ ] Leaderboard — **DEFERRED** to 2027
* [ ] Riwayat Penilaian — **DEFERRED** to 2027

---

### Audit

* [x] Activity Log Foundation — ActivityLog model, ActivityLogService, read-only UI (ActivityLogIndex), Surat Izin lifecycle integration, tests
* [x] Export Log — participant data Excel export via RekapPeserta::exportExcel()
* [x] Print Log — Surat Izin print, QR label single/batch/A4 print views (print_viewed action)
* [x] QR Log — single QR download (downloaded), batch QR export to storage (batch_exported)

Notes:

* Activity Log Foundation is built as a shared audit infrastructure for all audit modules.
* `ActivityLogService::log()` accepts action, module, description, optional subject (polymorphic), optional properties (JSON), and optional user.
* Logged Surat Izin events: created, submitted, approved, rejected, returned.
* Logged Print events: print_viewed (surat_izin, qr_label_single, qr_label_filtered, qr_label_a4).
* Logged Export events: exported (peserta xlsx).
* Logged QR events: downloaded (single PNG), batch_exported (batch to storage).
* Logs are written after successful business operations — failed/rolled-back operations never produce false logs.
* User fallback: "Sistem" when user_id is null or user deleted (FK uses nullOnDelete).
* Schema includes ip_address and user_agent only when HTTP request exists (null-safe for CLI/tests).
* Full test coverage: service unit tests, Surat Izin lifecycle integration, Activity Log UI, Print Log, Export Log, QR Log.
* Physical print limitation: print_viewed records print-page generation/access, not guaranteed physical printer completion (server cannot detect window.print completion).
* Latest verified baseline: 186 tests, 432 assertions.

---

### Storage

* [ ] Nextcloud Integration — **DEFERRED** to 2027
* [ ] TrueNAS Integration — **DEFERRED** to 2027

---

# Sprint 3

## Status

🟢 ACTIVE — HIGHEST PRIORITY

Target: August 2026 operational use for Multi Event.

## Goal

Transformasi menuju KJA Event Manager — Multi Event Architecture.

## Phases

### S3.0 Architecture & Database Audit

Status: ✅ COMPLETE

See `docs/SPRINT3_MULTI_EVENT_AUDIT.md` for the full audit report.

Deliverables:
* Current database map (all tables, constraints, relationships)
* Current identity model audit (NIP, participant_number, attendance_code)
* Single-event coupling map (10 CRITICAL, 10 HIGH, 5 MEDIUM, 5 LOW findings)
* Proposed target domain (Person → Participation → Event)
* Field ownership matrix
* Multi Event attendance architecture
* Active event context recommendation
* Backward compatibility & migration strategy (Stages A-F)
* Universal Person deduplication strategy
* Test migration strategy
* Sprint 3 breakdown (S3.0—S3.10)

### S3.1 Event Foundation

Status: ✅ COMPLETE

Deliverables:
* Event model + events table migration
* Legacy CAI Event bootstrap (idempotent seeder)
* ActiveEventContext service (session-based)
* Event selection UI in sidebar
* Event management CRUD (index, create, edit, archive/activate)
* Event routes (`/events`)
* Registered ActiveEventContext as singleton
* 30+ dedicated tests
* Full backward compatibility — existing app unchanged
* Architecture source: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`

### S3.2 Universal Person

Status: ✅ COMPLETE

Deliverables:
* `people` table migration (id, nama, jenis_kelamin (L/P), desa_id FK, nip nullable unique)
* `Person` model with desa() relationship and jenis_kelamin_label accessor
* 15+ dedicated tests
* Clean table — person identity foundation only
* No changes to existing peserta or other tables

### S3.3 Participation Foundation

Status: ✅ COMPLETE

Deliverables:
* `participations` table migration (person_id FK, event_id FK, participant_number nullable, attendance_code nullable globally unique, jenis_peserta default 'Wajib')
* `Participation` model with `person()` and `event()` belongsTo relationships
* `UNIQUE(event_id, person_id)` — one participation per person per event
* `UNIQUE(event_id, participant_number)` — per-event participant number
* Globally unique `attendance_code` — unambiguous QR scanning
* `Person` model: `participations()` hasMany, `events()` belongsToMany
* `Event` model: `participations()` hasMany, `people()` belongsToMany
* 20+ dedicated tests
* No peserta backfill — legacy architecture unchanged
* No runtime migration — CAI operational still on peserta architecture

### S3.4 Active Event Context Hardening

Status: ✅ COMPLETE

Deliverables:
* Hardened `ActiveEventContext` — centralized session-based singleton
* `requireCurrent(): Event` — throws `RuntimeException` when no active event available
* `resolveDefault(): ?Event` — returns first active event, no session mutation
* Stale session handling — deleted/archived event IDs are automatically cleared
* Inactive event enforcement — archived events cannot become active context
* 25+ dedicated tests including context isolation, stale state, inactive policy, Participation isolation, and legacy compatibility
* Route middleware deferred — no concrete multi-event routes exist yet to apply it to
* No legacy module scoping — switching active event has zero effect on peserta/attendance/QR/surat-izin/reports

### S3.5 Legacy Data Backfill

Status: ✅ COMPLETE — PRODUCTION BACKFILL EXECUTED

**S3.5A Legacy Data Quality Audit:** ✅ COMPLETE
**S3.5B Legacy Mapping Infrastructure:** ✅ COMPLETE
**S3.5C Safe Backfill Engine:** ✅ COMPLETE
**S3.5D Copy Database Execute Verification:** ✅ COMPLETE
**S3.5E Production Backfill:** ✅ COMPLETE

Deliverables:
- `legacy_peserta_mappings` table with FK constraints (restrictOnDelete)
- `LegacyPesertaMapping` model with belongsTo relationships to Peserta, Person, Participation, Event
- Inverse relationships on Peserta, Person, Participation, Event
- UNIQUE(peserta_id), UNIQUE(participation_id) — identity contracts enforced
- Snapshot columns for legacy data at backfill time (legacy_nip, legacy_participant_number, legacy_attendance_code)
- `LegacyPesertaBackfillService` — per-peserta analysis, NIP-based Person matching, identity signal validation, Participation conflict detection, dry-run projection, transactional execution
- `BackfillLegacyPeserta` Artisan command — `php artisan backfill:legacy-peserta` (default dry-run), `--dry-run` (explicit), `--execute` (writes), `--event` (slug)
- 57+ combined dedicated tests for mapping infrastructure and backfill engine

Production execute results (2026-07-17):
- People Created: 144
- Participations Created: 144
- Mappings Created: 144
- Database Writes: 432
- Conflicts: 0 | Review Required: 0 | Broken Mapping: 0 | Drift Detected: 0 | Errors: 0
- Idempotency verified — second dry-run shows Already Mapped: 144

Current migration state:
```
pesertas (legacy runtime)
        |
        | LegacyPesertaMapping
        v
Person -> Participation -> Event
```

Safety guarantees:
- Default is dry-run (zero writes)
- `--dry-run` + `--execute` rejected together
- Per-peserta transaction in execute mode
- NIP is the ONLY automatic Person match key (name never auto-matches)
- Identity signal conflicts (gender, name, desa) block execution
- Participant_number and attendance_code conflicts detected before creation

**Important — Runtime architecture unchanged:**
- `pesertas` table is still the active runtime source for existing modules (attendance, QR, surat izin, reports, registration/import)
- People/participations are populated with real production data but are NOT yet the runtime source
- `legacy_peserta_mappings` is the compatibility bridge between legacy and normalized domains
- Legacy NIP compatibility is preserved
- `pesertas` is NOT deprecated or removed
- Runtime event-scoping (S3.6+) will incrementally adopt the normalized domain

### S3.6 Attendance Event Scoping

Status: ✅ COMPLETE

### S3.7 Participant/QR Migration

Status: ✅ COMPLETE

Deliverables:
- RegistrationService write-path now normalizes participant identifiers through Participation
- participant_number generation is event-scoped
- attendance_code remains globally unique
- LegacyPesertaMapping preserves compatibility between legacy peserta, Person, and Participation
- QR runtime remains Participation-based
- Legacy NIP/regu behavior remains supported
- Full suite verified: 395 tests passed, 1013 assertions

### S3.8 Dashboard & Report Scoping

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- RekapPeserta uses Participation as the active-event runtime source
- Person provides identity fields
- LegacyPesertaMapping/peserta remain compatibility-only for required legacy fields
- PesertaExport is event-scoped and uses Participation/Person without cross-event mixing
- Dashboard totalPeserta is event-scoped; totalDesa, totalKelompok, and totalRegu remain global master-data metrics
- Attendance summary remains bounded by active event and session scope
- RekapAbsensi is event-safe with ActiveEventContext boundary and event-scoped sessions
- Same Person in multiple events stays isolated by Participation boundary
- Full suite verified: 401 passed, 1035 assertions, 7.11s

### S3.9 Multi Role/Venue/Category

Status: ✅ COMPLETE / VERIFIED

#### S3.9A Domain Foundation

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- `activity_groups` table + `ActivityGroup` model
- `activities` table + `Activity` model
- `activity_registrations` table + `ActivityRegistration` model
- `ActivityRegistrationService` domain service for safe creation
- Event-scoped invariants enforced:
  - `activity_registration.event_id == participation.event_id == activity.event_id`
  - `activity.event_id == activity_group.event_id`
- One Participation can join many Activities within the same Event
- Duplicate registration for the same Participation + Activity is rejected
- Cross-event activity registration is blocked
- Legacy `pesertas` + `LegacyPesertaMapping` remain compatibility bridge and are not source of truth for new activity domain
- Verified with full regression suite: 411 passed, 1057 assertions

#### S3.9B Category Foundation

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- `category_definitions` table + `CategoryDefinition` model
- `activity_categories` table + `ActivityCategory` model
- `requires_category` support on `activities`
- nullable `category_definition_id` on `activity_registrations`
- Event-scoped invariants enforced:
  - `activity_category.event_id == activity.event_id == category_definition.event_id`
  - `activity_registration.event_id == participation.event_id == activity.event_id`
  - when category is used: `activity_registration.event_id == category_definition.event_id`
  - category must be available for the activity via `ActivityCategory`
- `requires_category=true` rejects registrations without category
- `requires_category=false` allows registration without category
- Same category name can be reused in different events
- Duplicate category and duplicate activity-category assignment are rejected
- Legacy `pesertas` + `LegacyPesertaMapping` remain compatibility bridge and are not source of truth for new category domain
- Verified with full regression suite: 425 passed, 1086 assertions, 7.24s

#### S3.9C Venue + Rundown Foundation

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- `venues` table + `Venue` model
- `rundowns` table + `Rundown` model
- `rundown_items` table + `RundownItem` model
- `ActivityScheduleService` for safe venue/rundown creation
- Event-scoped invariants enforced:
  - `venue.event_id == event.id`
  - `rundown.event_id == event.id`
  - `rundown_item.event_id == rundown.event_id == activity.event_id`
  - optional venue on rundown item must belong to same event
- Parallel activities at same time are supported when data structure is valid
- Time validation prevents `ends_at <= starts_at`
- Conflict detection / scheduling engine remains future enhancement
- Legacy `pesertas` + `LegacyPesertaMapping` remain compatibility bridge and are not affected
- Verified with full regression suite: 432 passed, 1103 assertions, 7.46s

#### S3.9D Event Role / Committee Foundation

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- `event_roles` table + `EventRole` model
- `event_committee_assignments` table + `EventCommitteeAssignment` model
- `EventCommitteeService` domain service for safe committee assignment
- Event-scoped invariants enforced:
  - `event_committee_assignment.event_id == event_role.event_id`
  - if participation is used: `assignment.event_id == participation.event_id`
  - if participation is used: `assignment.person_id == participation.person_id`
  - if activity_group is used: `assignment.event_id == activity_group.event_id`
  - if activity is used: `assignment.event_id == activity.event_id`
  - if venue is used: `assignment.event_id == venue.event_id`
- Person is the canonical identity for committee assignments
- participation_id is nullable and contextual only
- Optional targets supported: ActivityGroup, Activity, Venue
- Duplicate identical assignment is rejected
- Person may hold multiple roles in one event and across multiple events
- EventRole does not grant application authorization
- Legacy `pesertas` + `LegacyPesertaMapping` remain compatibility bridge and are not source of truth for committee domain
- Verified with full regression suite: 452 passed, 1129 assertions, 7.62s

#### S3.9E Reporting / Export Integration

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- `ActivityRegistrationExport` foundation for event-safe activity registration export
- event-scoped activity registration reporting query foundation
- committee and rundown reporting query foundations
- Existing `PesertaExport` and `RekapPeserta` remain compatible and event-scoped
- Event isolation preserved for participant, activity, category, committee, rundown, and venue reporting paths
- Export logging follows existing audit convention
- Verified with full regression suite: 459 passed, 1140 assertions, 7.69s

#### Future Checkpoint: Recurring Event Self-Registration & Identity Correction

Status: FUTURE / NOT IMPLEMENTED

Scope:
- QR publik membuka self-registration untuk event/pengajian yang dituju
- peserta mencari Person yang sudah ada
- tampilkan data pembeda seperti nama, kelompok, desa, tanggal lahir
- peserta mengonfirmasi identitas
- sistem membuat Participation untuk event bulan tersebut
- jika data Person dianggap salah, peserta dapat mengajukan koreksi
- koreksi tidak langsung mengubah Person
- koreksi berstatus pending sampai divalidasi panitia
- panitia dapat approve/reject correction
- event besar tetap menggunakan operator/panitia untuk registration

Out of scope:
- jangan implementasikan sekarang
- jangan dicampur dengan S3.9D

### S3.10 Regression & Production Readiness

Status: ✅ COMPLETE / VERIFIED

Deliverables:
- full regression suite verified at 459 passed / 1140 assertions
- no blocking regression issues found in audited production-critical paths
- event isolation, identity integrity, registration, attendance, QR, reporting/export, and legacy compatibility are stable
- production-readiness checklist confirmed in documentation
- future enhancements remain deferred and non-blocking

Next checkpoint:
- PGM.16 completed (Pengajian UX, Contextual Navigation, Bulk Import)
- PGM.17 Pilot Release — UI interaction remediation COMPLETE
- PGM.18 Sprint 1 ✅ COMPLETE — Legacy historical tooling removed, Design C diagnostic contract fixed
- PGM.18 Sprint 2 ✅ COMPLETE — Mapping contract refactored, 1499 passed / 3592 assertions / 0 failures

---

# Pengajian Desa MVP (PGM Series)

## Status

✅ PGM.12–PGM.17 COMPLETE. Pilot end-to-end functional, UI/UX refined, bulk import ready, UI interaction fully remediated.

## Goal

Desa-level attendance module for Pengajian (religious study) events.
Built on S3 Person→Participation→Attendance architecture.
Separate from CAI Operational; no legacy peserta/LegacyPesertaMapping dependency.

## Completed

### PGM.14.1 — Security & Reliability Closure
- EnterToken rate limiting (IP-based, 5 failed attempts/min, reset on successful entry)
- SelfAttendance duplicate handling database-agnostic (domain RuntimeException, no MySQL error codes)
- QrPrint session/grant integrity check (same pattern as DesaDashboard/ManualEntry)
- P0/P1 reclassified: no true P0 blockers remain on SQLite production
- 9 new tests

### PGM.12 — Functional Fix
- ActiveEventContext no longer silently falls back to arbitrary/default events
- Missing event context fails closed
- Cross-event data isolation hardened

### PGM.13 — Token Management UI
- DesaAccessGrant CRUD (admin)
- Token generation, validation, revocation
- Nonce rotation for QR session security
- Token entry UI for operators

### PGM.14 — Event Context / Isolation
- QR Label individual print migrated to Participation-based flow
- QR Print All Filtered and Print All A4 migrated to event-scoped Participation data
- Pengajian participants appear in QR & Label without legacy peserta IDs
- 789 passed (1865 assertions)

### PGM.14.5 — Manual Participant Entry
- ManualParticipantRegistrationService (shared service, no ActiveEventContext dependency)
- Operator Desa manual entry with server-resolved grant context
- Admin manual entry with event/desa selection
- Conservative Person matching (name+desa+dob)
- Timggal lahir required; Kelompok selection scoped to Desa
- Grant/session security — revalidateGrant() compares DB against session
- 38 tests (25 service + 7 grant consistency + 6 kelompok/tanggal_lahir)

### PGM.15 — Dashboard & Report Optimization
- [x] N+1 query optimization in PengajianDesaReportService and PengajianRegionalReportService (PGM.15)
- [x] Operator search by name and participant_number
- [x] Attendance status indicators (Hadir/Belum badge)
- [x] Operator confirmation flow
- [x] Datetime raw-join Carbon regression fix
- [x] Regional/Desa report filter fixes (PGM.16 Phase D):
  - [x] Search + Status + Method combine correctly
  - [x] Hadir + Self shows only self attendance
  - [x] Hadir + Operator shows only operator attendance
  - [x] Belum Hadir ignores/clears method filter
  - [x] Method filter disabled when status is Belum Hadir
  - [x] Debounced search (300ms) prevents N+1 per keystroke
  - [x] No stale Livewire state between filter changes
  - [x] Filter combination regression tests (12 tests)

### PGM.16 — Pengajian UX, Contextual Navigation & Bulk Import

#### Event Type Architecture
- Added `event_type` column (`cai` / `pengajian`) to events table (migration: `2026_08_02_000001`)
- `Event` model: `isCai()`, `isPengajian()`, `scopeCai()`, `scopePengajian()`
- `ActiveEventContext`: `currentEventType()`, `isCurrentCai()`, `isCurrentPengajian()`
- Event creation form allows selecting event type (CAI/Pengajian)
- Event index table shows event type badge

#### Contextual Sidebar Navigation
- Sidebar is now event-type-aware via `ActiveEventContext::current()`
- **CAI active** — full CAI operational menu (Absensi, Registrasi, Database, Laporan, QR & Label, Pengajian, Event, Sekretariat)
- **Pengajian active** — clean Pengajian nav:
  - Pengajian → Regional Report
  - Peserta → Daftar Peserta, Import Massal
  - Operasional Desa → Akses Desa
  - Event → Kelola Event
- CAI-only menus (Absensi, Regu, Registrasi Ulang, QR & Label, etc.) hidden
- **Hidden navigation is NOT authorization** — all routes remain server-side accessible
- Event switcher now redirects to context-appropriate landing page after switching:
  - CAI events → dashboard
  - Pengajian events → pengajian.report

#### KJA Branding
- App logo shows "KJA Event Manager" instead of "CAI"
- Branding consistent with KJA product direction

#### Pengajian Bulk Import (`/pengajian/admin/import-massal`)
- Dedicated import for Pengajian participants
- Fields: nama, jenis_kelamin, tanggal_lahir, desa, kelompok
- Preview/validate before final import
- Row-level validation errors with clear messages
- Desa resolved case-insensitively
- Kelompok scoped to Desa during lookup (deterministic — no name-only `first()`)
- Person identity matching reuses canonical `ManualParticipantRegistrationService` pattern:
  - Match by normalized nama + desa_id + PHP-level tanggal_lahir comparison
  - Existing Person reused when identity matches
  - Different birth date → new Person (no false matches)
  - Same identity in different Desa → no cross-Desa match
- Participation created with `jenis_peserta='Pengajian Desa'`
- No Regu assignment, no CAI PlacementService
- Duplicate Participation (person_id + event_id) prevention
- Import summary: created_persons, matched_persons, created_participations, skipped_duplicates, failed_rows
- Desa isolation: same identity in different Desa does not match
- Same kelompok name in different Desa resolves to correct kelompok
- 25+ dedicated tests

#### Responsive UI
- **Regional Report**: responsive stat grid (2→4 cols), desa breakdown in multi-column grid, stacked filters on mobile, scrollable tabs
- **Desa Dashboard**: max-w-4xl container, responsive header/KJA logo, 3-col stat grid, QR image responsive sizing
- **Sidebar**: mobile drawer preserved, event-type context aware

#### Migration: `2026_08_01_000001_add_kelompok_id_to_people_table.php`
- Adds `kelompok_id` FK (nullable, nullOnDelete) to `people` table — enables Pengajian peserta group assignment

---

# Database V2 — Part 5 Closure

## Status

✅ CLOSED

## Scope

- 5A EditPeserta + Ulang: COMPLETE
- 5B PesertaImport: COMPLETE
- 5C CaiParticipantReplacement: COMPLETE
- 5D HapusPeserta: COMPLETE

## Final Design C

- Person = global canonical identity
- Participation = event-scoped membership
- peserta = global legacy mirror
- LegacyPesertaMapping = single global compatibility mapping
- LegacyParticipationMapping = event-aware compatibility bridge

## Deletion Limitation

Last-membership hard deletion remains intentionally blocked because `legacy_peserta_mappings.participation_id` is NOT NULL + `restrictOnDelete`.

## Verified Baseline

1533 passed / 3677 assertions / 0 failures (user/server verified)

## Test Delta Note

Historical numerical delta cannot be reconstructed exactly because relevant Part 5 test files were untracked during the development sequence. Current semantic coverage has been audited; no known critical Design C coverage is missing.

---

# Sprint 4

## Status

✅ COMPLETE 100% — Identity & QR

## Goal

Identity & QR.

## Features

* [x] Attendance Code finalization
* [x] QR identity hardening
* [x] participant_number compatibility rules
* [x] legacy NIP fallback maintenance
* [x] scan identifier normalization

---

# Sprint 5

## Goal

Document & Certificate Module.

## Status

Deferred / Skipped for now

## Reason

Fitur belum dibutuhkan pada tahap pengembangan saat ini.

## Completion

0%

## Features

* Sertifikat Otomatis
* QR Verification
* Arsip Dokumen
* Template Sertifikat
* Piagam

---

# Sprint 6

## Goal

Commercial Preparation.

## Features

* White Label
* Theme
* Branding
* Organization Management
* License Management
* Billing Preparation

---

# Version Roadmap

## v1.0

AbsenCAI

Status:

Released

---

## v1.5

CAI Operational

Status:

Operational Scope Completed

Target:

Seluruh operasional CAI dapat dijalankan menggunakan sistem.

Current work:

Sprint 2 — Secretariat Operational

Current progress:

* S01 Foundation: COMPLETE 100%.
* S02 Registration/Placement: COMPLETE 100%.
* S03 Attendance: COMPLETE 100%.
* S04 Identity & QR: COMPLETE 100%.
* Sprint 1 CAI Operational closed for current operational scope.
* Manual Attendance is implemented and verified.
* Attendance status Hadir/Izin/Alfa is implemented and verified.
* QR operational scope is complete.
* Sprint 2 Permission feature group completed: Surat Izin, Print Surat, Return Tracking.
* Sprint 2 Activity Log Foundation completed: model, service, read-only UI, Surat Izin lifecycle integration, tests.
* Sprint 2 Print Log completed: Surat Izin print, QR label single/batch/A4 print views integrated with ActivityLogService (action: print_viewed).
* Sprint 2 Export Log completed: participant data Excel export integrated with ActivityLogService (action: exported).
* Sprint 2 QR Log completed: single QR PNG download and batch QR export to storage integrated with ActivityLogService (action: downloaded / batch_exported).
* Latest documented baseline: 459 tests, 1140 assertions (S3.9E). Actual count needs verification via `php artisan test`.
* Sprint 2 remaining scope: Riwayat Izin, Scoring, Storage — **DEFERRED to 2027**.
* **Sprint 3 (Multi Event)** ✅ COMPLETE/VERIFIED. Required for August 2026.
* S3.0 Architecture & Database Audit: COMPLETE.
* S3.1 Event Foundation: COMPLETE.
* S3.2 Universal Person: COMPLETE.
* S3.3 Participation Foundation: COMPLETE.
* S3.4 Active Event Context Hardening: COMPLETE.
* S3.5 Legacy Data Backfill: COMPLETE — Production backfill executed 2026-07-17. 144 Person, 144 Participation, 144 Mapping created. 0 conflicts.
* S3.6 Attendance Event Scoping: COMPLETE.
* S3.7 Participant/QR Migration: COMPLETE.
* S3.8 Dashboard & Report Scoping: COMPLETE.
* S3.9A–S3.9E Multi Role/Venue/Category: COMPLETE/VERIFIED.
* S3.10 Regression & Production Readiness: COMPLETE/VERIFIED.

Notes:

* Sprint 1 attendance status summary is implemented for Hadir/Izin/Alfa.
* participant_number contract preserved.
* attendance_code is the primary QR payload.
* Legacy NIP compatibility preserved.
* QR integration verified.
* Deferred Sprint 1 backlog: QR PDF Export, Report PDF Export, Dashboard PJ Regu, Live Monitoring.

---

## v2.0

KJA Event Manager

Status:

Planning

Target:

Platform Event Management Multi Event.

---

## v2.5

Competition Module

Status:

Planning

---

## v3.0

Commercial Edition

Status:

Future

---

# Long Term Features

## Event

* Multi Event
* Event Template
* Event Archive

---

## Organization

* Multi Organization
* Multi Branch
* Organization Dashboard

---

## Attendance

* Offline Mode
* QR Rotation
* Face Verification (Optional)
* GPS Validation (Optional)

---

## Competition

* Judge Panel
* Live Score
* Live Ranking
* Medal Table

---

## Certificate

* Auto Generate
* QR Verification
* Online Verification

---

## Dashboard

* TV Dashboard
* Public Dashboard
* Mobile Dashboard

---

## Infrastructure

* API
* Mobile App
* Cloud Storage
* Queue
* Notification
* Email
* WhatsApp Integration

---

# Success Criteria

Sprint dianggap selesai apabila:

* Semua fitur selesai.
* Tidak ada bug kritikal.
* Dokumentasi diperbarui.
* Database diperbarui bila diperlukan.
* CHANGELOG diperbarui.
* Testing selesai.

---

# Master Data Module

## Status

🟢 Navigation infrastructure complete — submenu CRUD pages pre-existing.

## Scope

Master Data adalah pusat pengelolaan data referensi/master aplikasi yang bersifat GLOBAL (reusable lintas event).

### Global Master Data (no event_id)
| Entity | Table | Route | UI Status |
|--------|-------|-------|-----------|
| **Person** | `people` | `/person` | Full CRUD ✅ |
| **Desa** | `desas` | `/desa` | Full CRUD ✅ |
| **Kelompok** | `kelompoks` | `/kelompok` | Full CRUD ✅ |

### Legacy CAI Operational (not global master data)
| Entity | Table | Route | Status |
|--------|-------|-------|--------|
| **Regu** | `regus` | `/regu` | Full CRUD ✅ (legacy CAI only) |

Regu tidak termasuk Master Data karena merupakan struktur operasional CAI.
Regu tidak memiliki event_id saat ini — akan dipindahkan ke event-scoped config pada refactor terpisah.

All entities accessible via Master Data landing page at `/master-data`.

### Event-Scoped Data (NOT in Master Data)
| Entity | Reason |
|--------|--------|
| Venue | Has `event_id` — event configuration |
| CategoryDefinition | Has `event_id` — event configuration |
| Participation | Transactional bridge between Person and Event |
| SesiAbsensi | Event-scoped session management |

## Navigation
Master Data is a single sidebar link pointing to the landing page at `/master-data`. The landing page contains navigation cards for Person, Desa, and Kelompok. The sidebar link has active state for `/master-data`, `/person`, `/desa`, and `/kelompok`. Regu is excluded from Master Data and remains accessible only via its dedicated route.

Master Data appears in sidebar for all authenticated users regardless of event context (CAI, Pengajian, or no active event).

## Authorization Gap
Master Data is currently visible to all authenticated users. RBAC has not been implemented yet. See `docs/PERMISSION.md` for the planned permission matrix.

---

# User Management

## Status

✅ COMPLETE (2026-07-21).

## Goal

Super Admin manages user accounts (create, edit, reset password, delete).

## Deliverables

- [x] Route `/users` protected with `manage-users` ability
- [x] Livewire components: Index, Create, Edit, ResetPassword, Delete
- [x] `UserManagementService` for centralized user business logic
- [x] Delete safety: cannot delete self, cannot delete last Super Admin
- [x] Super Admin only access
- [x] Activity Log integration (created, updated, role_changed, password_reset, deleted)
- [x] Password hashing and security compliance

---

# Current Priority

```
1. **UI Bug Fix Sprint — Batch 1** ✅✅ Branding & Navigation (bugs #1, #2, #4, #6) — RESOLVED VERIFIED (908 tests/2192 assertions)
2. **UI Bug Fix Sprint — Batch 2** ✅✅ Access Token UI & Security (bugs #5, #7, #8) — RESOLVED VERIFIED
3. **UI Bug Fix Sprint — Batch 3** ✅✅ Functional/UI Logic (bugs #3, #9) — RESOLVED VERIFIED
4. **UI Bug Fix Sprint — Batch 4** ✅ Final UI Polish (bugs #10, #11, #12) — RESOLVED VERIFIED
5. **PGM.17 — Pilot Release** ✅ COMPLETE — UI interaction remediation, mobile/dark mode audit, modal close controls standardized
6. **Pengajian Desa MVP** — PGM.12–PGM.17 ✅ Complete.
7. **Multi Event (Sprint 3)** ✅ Complete (S3.0–S3.10)
8. **S01–S04 Foundation** ✅ Complete
9. **PGM.18 Sprint 2** ✅ COMPLETE — Mapping contract refactored, fully verified (1499/3592/0)
10. **PGM.18 Sprint 3** 📋 PROPOSED — Drop legacy columns, remove deprecated relationships, finalize PGM.18 completion
11. **Competition** (future sprint)
12. **Commercial** (future sprint)
```

---

# Product Vision

CAI bukan tujuan akhir.

CAI adalah MVP.

KJA Event Manager adalah platform Event Management yang dapat digunakan oleh sekolah, organisasi, komunitas, universitas, hingga penyelenggara kejuaraan dan festival dengan arsitektur modular yang siap dikembangkan dalam jangka panjang.

---

# S4 Completion — Pengajian Admin Protection ✅

**Status:** COMPLETE (2026-07-21).

Previously tracked as:
```
### S4 — Pengajian Admin Protection 🔲
- [ ] Separate admin Pengajian routes from role-based access
```

**Completed:**
- ✅ 4 Pengajian admin routes protected with `can:manage-pengajian` middleware:
  - `/koreksi-data` — Identity Correction Review
  - `/pengajian/admin/access` — Access Token Management
  - `/pengajian/admin/manual-entry` — Manual Participant Entry
  - `/pengajian/admin/import-massal` — Bulk Import
- ✅ 4 Livewire components gated with `Gate::authorize('manage-pengajian')`
- ✅ Access matrix verified per role (super_admin, admin, sekretariat)
- ✅ Public token flow unchanged — remains accessible without authentication
- ✅ Pre-S4 audit documented: routes previously accessible by ALL authenticated users

**Phase 4 RBAC updated status:**
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 **S4 Pengajian Admin Protection: ✅ Complete**
- 🟢 **S5 CAI Module Permissions: ✅ Complete**
- 🟢 **S6 Sidebar Visibility: ✅ Complete**
- 🟡 S7: Not yet implemented

### S5 — CAI Module Permissions ✅ COMPLETE (2026-07-21)

**Previously tracked as:**
```
### S5 — CAI Module Permissions 🔲
- [ ] Registration, attendance, QR, session, import permissions
```

**Completed:**
- ✅ `manage-import` ability applied to `/import/peserta` and `/import/regu` routes
- ✅ Livewire ImportPeserta and ImportRegu mutations gated with `Gate::authorize('manage-import')`
- ✅ Full CAI permission matrix verified (all 15 abilities)
- ✅ `manage-import` access matrix verified per role (super_admin, admin, sekretariat)

**Phase 4 RBAC updated status:**
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 S4 Pengajian Admin Protection: ✅ Complete
- 🟢 **S5 CAI Module Permissions: ✅ Complete**
- 🟢 **S6 Sidebar Visibility: ✅ Complete**
- 🟡 S7: Not yet implemented
