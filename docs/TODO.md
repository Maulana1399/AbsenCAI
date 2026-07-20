# Backlog

## S01 Step 2 (Foundation Service Tests)

- [x] Audit existing tests for service behavior coverage
- [x] Add direct service tests for PlacementService
- [x] Add direct service tests for RegistrationService
- [x] Add direct service tests for AttendanceService
- [x] Add direct service test for implemented QRService PNG output
- [x] Verify new service tests in PHP runtime

Status: VERIFIED.

---

## S01 Step 1 (Config Runtime Audit)

- [x] Audit config/feature.php status and runtime usage
- [x] Audit config/kjam.php status and safe runtime usage
- [x] Sync relevant documentation to codebase reality
- [x] Keep behavior unchanged

Status: VERIFIED.

---

## Sprint 1 (CAI Operational)

Status: CLOSED / COMPLETED FOR CURRENT OPERATIONAL SCOPE

### Attendance

- [x] Attendance Code
- [x] Internal QR Generator
- [x] QR Regeneration
- [x] Manual Attendance
- [x] Manual Hadir
- [x] Manual Izin
- [x] Attendance Status
- [x] Hadir summary
- [x] Izin summary
- [x] Alfa derivation
- [x] Hadir ↔ Izin conflict protection
- [x] Attendance History

### QR

- [x] Generate QR
- [x] Batch Generate
- [ ] QR PDF Export (Deferred)
- [x] Print 4x4 cm
- [x] Batch Print
- [x] QR regeneration via attendance_code

### Report

- [x] Export Excel
- [ ] Report PDF Export (Deferred)
- [x] Rekap Per Regu
- [x] Rekap Per Desa
- [x] Rekap Per Kelompok
- [x] Rekap Belum Hadir
- [x] Rekap Hadir / Izin / Alfa

### Dashboard

- [x] Dashboard Divisi
- [ ] Dashboard PJ Regu (Deferred)
- [x] Progress Registrasi
- [x] Progress Absensi
- [ ] Live Monitoring (Deferred)

### UI

- [x] Dark Mode
- [x] Responsive Mobile
- [x] Menu Refactor
- [x] Reusable Components foundation

### Verification

- [x] Manual Attendance verified
- [x] Izin attendance flow verified
- [x] Hadir/Izin/Alfa summary verified
- [x] Full regression suite verified

Last verified test suite (archived):

70 tests passed, 198 assertions, 0 failures.

Latest verified regression: **908 passed, 2192 assertions** (UI Bug Fix Sprint Batch 1, 2026-07-21).

### Deferred Backlog

- [ ] QR PDF Export
- [ ] Report PDF Export
- [ ] Dashboard PJ Regu
- [ ] Live Monitoring

Deferred reason:

These features are not currently required for CAI operational use and do not block Sprint 1 closure.

### Current Next Task

- [ ] Start Sprint 2 according to `docs/ROADMAP.md`
---

## Sprint 2.5.1 (QR & Label UI)

- [ ] Create QR & Label menu
- [ ] Individual QR search and download
- [ ] Batch QR export UI
- [ ] Print label UI
- [ ] Keep existing modules unchanged

---

## Sprint Bugfix (Identity Repair)

- [ ] Backfill legacy participant_number placeholder values
- [ ] Backfill legacy attendance_code placeholder values
- [ ] Keep valid records unchanged
- [ ] Verify command summary output

---

## Sprint 2.8 (Print Foundation)

- [ ] Create print engine
- [ ] Create label 4x4 template
- [ ] Use QRService in print flow
- [ ] Prepare printable participant labels

---

## Sprint 2.7 (Batch QR Export Foundation)

- [ ] Create batch QR export service
- [ ] Reuse QRService
- [ ] Support PNG and SVG output
- [ ] Return export summary

---

## Sprint 2.6 (QR Foundation)

- [ ] Create reusable QR service
- [ ] Support SVG generation
- [ ] Support PNG generation
- [ ] Keep service reusable for future modules

---

## Sprint 2.5 (Attendance Identity Transition)

- [x] Lookup attendance by attendance_code first
- [x] Keep legacy NIP fallback
- [x] Preserve duplicate attendance prevention
- [x] Preserve session validation
- [x] Add Livewire attendance orchestration coverage
- [x] Close S03 as complete
- [x] Close S04 as complete
- [x] Defer SVG QR as technical debt

---

## Sprint 2.4 (Registration Identity Transition)

- [ ] Generate participant_number on new registration
- [ ] Generate attendance_code on new registration
- [ ] Keep legacy nip flow for backward compatibility
- [ ] Verify unique participant_number and attendance_code

---

## Sprint 2.3 (Participant Number Foundation)

- [x] Generate participant_number with KL/KP prefix
- [x] Keep legacy nip wrapper for backward compatibility
- [x] Prepare participant number transition docs
- [x] Verify deterministic running sequence

Status: VERIFIED.

---

## Sprint 2.5 (Registration Foundation)

- [x] Centralize participant create/update persistence in RegistrationService
- [x] Refactor database participant create/update callers
- [x] Refactor import participant persistence path
- [x] Add regression tests for registration service

Status: VERIFIED.

S02 status: COMPLETED.

---

## Sprint 2.4 (Placement Foundation)

- [x] Centralize auto placement logic in PlacementService
- [x] Centralize least-filled regu selection in PlacementService
- [x] Update callers to use PlacementService as source of truth
- [x] Add regression tests for placement service

Status: VERIFIED.

---

## Sprint 2.2 (Identity Foundation)

- [ ] Add participant_number column
- [ ] Add attendance_code column
- [ ] Keep legacy nip compatibility
- [ ] Prepare identity transition docs
- [ ] Defer identity cleanup to S04

---

## Sprint 2

Status: 🟢 Operational Stable — remaining features **DEFERRED to 2027**

### Permission

- [x] Surat Izin — create, submit, approve, reject, cancel, return
- [x] Print Surat — A5 landscape template with Kop Surat
- [x] Return Tracking — selectable return date, attendance cleanup
- [ ] Riwayat Izin — **DEFERRED to 2027**

### Scoring

- [ ] Master Point — **DEFERRED to 2027**
- [ ] Bonus — **DEFERRED to 2027**
- [ ] Penalty — **DEFERRED to 2027**
- [ ] Leaderboard — **DEFERRED to 2027**
- [ ] Riwayat Penilaian — **DEFERRED to 2027**

### Audit

- [x] Activity Log Foundation
- [x] Print Log
- [x] Export Log
- [x] QR Log

### Storage

- [ ] Nextcloud Integration — **DEFERRED to 2027**
- [ ] TrueNAS Integration — **DEFERRED to 2027**

### Verification

- [x] Full regression suite: 186 tests passed, 432 assertions, 0 failures.

---

## UI Bug Fix Sprint

Status: 🟡 Backlog — NOT STARTED

Priority: Critical/High

### Background

Berdasarkan audit dokumentasi dan codebase pada 2026-07-20, teridentifikasi 11 area perbaikan UI. Berikut hasil audit terhadap implementasi aktual:

### Actual Active Bug Backlog

| # | Kategori | Deskripsi | Prioritas | File Utama | Status Verifikasi |
|---|----------|-----------|-----------|------------|-------------------|
| 1 | Branding & Navigation | Landing page (`/`) masih branding CAI: logo "CAI", judul "CINTA ALAM INDONESIA 2025" | Medium | `resources/views/welcome.blade.php` | **RESOLVED — VERIFIED** ✅ 908 tests passed, runtime verification 1-5 OK |
| 2 | Branding & Navigation | Login page masih branding CAI: logo "CAI", judul "Cinta Alam Indonesia" | Medium | `resources/views/livewire/auth/login.blade.php` | **RESOLVED — VERIFIED** ✅ 908 tests passed, runtime verification 1-5 OK |
| 6 | Branding & Navigation | KJA logo di sidebar mengarah ke dashboard CAI (`route('dashboard')`), bukan global/KJA dashboard | High | `resources/views/components/layouts/app/sidebar.blade.php` | **RESOLVED — VERIFIED** ✅ 908 tests passed, runtime verification 1-5 OK |
| 4 | Branding & Navigation | Menu "Pengajian" (Akses Desa, Regional Report) muncul di event CAI | Low | `resources/views/components/layouts/app/sidebar.blade.php` | **RESOLVED — VERIFIED** ✅ 908 tests passed, runtime verification 1-5 OK |
| 5 | Access Token UI | Tidak ada tombol delete/hard-delete untuk access token yang sudah di-revoke | Medium | `AccessIndex.php`, `DesaAccessService.php` | **IMPLEMENTED — RUNTIME FIX REQUIRED** ⏳ Delete confirmation primary button tidak terlihat (layout flex dan warna). Diperbaiki dengan `font-bold`, `py-3`, layout flex-col gap-2 |
| 7 | Access Token UI & Security | Raw token ditampilkan penuh di modal creation (sekali saja) | High | `access-index.blade.php` | **SECURITY AUDIT COMPLETE** ✅ DB hanya hash, one-time reveal. **RUNTIME FIX REQUIRED** ⏳ Modal overflow diperbaiki dengan `<textarea readonly>` |
| 8 | Access Token UI | Token overflow/UI kurang rapi | Medium | `access-index.blade.php` | **OPEN — RUNTIME FIX REQUIRED** ⏳ `break-all` + `min-w-0` tidak cukup. Diganti dengan `<textarea readonly rows="3">` untuk wrapping native + select-all + copy |
| 10 | Dark Mode | Heading dan table text masih gelap di dark mode | Medium | Multiple files | **OPEN — RUNTIME CONFIRMED** ⏳ Diperbaiki: heading → `dark:text-white`, table name → `dark:text-white`. Butuh runtime verifikasi |
| 3 | Functional/UI Logic | Stat "Peserta Belum Absen" — label "Alfa", nilai selalu integer, tidak pernah "-" | Medium | `Dashboard.php`, `dashboard.blade.php` | **IMPLEMENTED — PENDING RUNTIME VERIFICATION** ⏳ Root cause: tabel mengakses properti legacy peserta pada Participation. Fix: person chain |
| 9 | Filter/Regional Report | Filter Hadir/Tidak + Metode di Regional Report | Medium | `RegionalReport.php`, `regional-report.blade.php` | **IMPLEMENTED — PENDING RUNTIME VERIFICATION** ⏳ Service layer PGM.16 tests pass. Added `wire:model.live` + Livewire component filter tests |
| 10 | Dark Mode | Dark mode text contrast pada Akses Desa & Kelola Event | - | `access-index.blade.php`, `event/index.blade.php` | **Resolved** — audit shows all elements have proper `dark:text-*` classes |
| 11 | Responsive Layout | `/pengajian` (enter-token) layout on desktop | Low | `enter-token.blade.php` | **Partially addressed** — uses `flex flex-col gap-6`, Flux responsive. Minor: fixed logo size |

---

## Sprint 3

Status: ✅ COMPLETE / VERIFIED

### S3.0 Architecture & Database Audit

- [x] Current database map
- [x] Current identity model audit
- [x] Single-event coupling map
- [x] Target domain design
- [x] Field ownership matrix
- [x] Multi Event attendance architecture
- [x] Active event context recommendation
- [x] Backward compatibility & migration strategy
- [x] Universal Person deduplication strategy
- [x] Test migration strategy
- [x] Sprint 3 breakdown & roadmap proposal

Status: COMPLETE

### S3.1 Event Foundation

- [x] Event model + events table migration
- [x] Legacy CAI Event bootstrap (idempotent)
- [x] ActiveEventContext service (session-based singleton)
- [x] Event selection UI (sidebar switcher)
- [x] Event management CRUD (index, create, edit, archive/activate)
- [x] Event routes (`/events`)
- [x] Registered ActiveEventContext in AppServiceProvider
- [x] Tests: Event creation, validation, bootstrap, context, UI
- [x] Backward compatibility: existing app unchanged
- [x] Documentation: ROADMAP, TODO, CHANGELOG, CURRENT_STATE

Status: COMPLETE / VERIFIED

### S3.2 Universal Person

- [x] Create `people` migration + Person model
- [x] Person model with desa() relationship and jenis_kelamin_label accessor
- [x] Tests: person creation, schema, NIP uniqueness, desa FK, jenis_kelamin L/P format
- [x] Design deduplication matching strategy

Status: COMPLETE / VERIFIED

### S3.3 Participation Foundation

- [x] Create `participations` migration + Participation model
- [x] `UNIQUE(event_id, person_id)` — one participation per person per event
- [x] `UNIQUE(event_id, participant_number)` — per-event participant number
- [x] Globally unique `attendance_code`
- [x] `Person` model: participations() + events() relationships
- [x] `Event` model: participations() + people() relationships
- [x] Tests: schema, relationships, uniqueness, cascade, Multi Event identity contract
- [x] No peserta backfill — legacy architecture unchanged

Status: COMPLETE / VERIFIED

### S3.4 Active Event Context Hardening

- [x] ActiveEventContext: requireCurrent(), resolveDefault(), stale/inactive safety
- [x] Inactive event enforcement — archived events rejected
- [x] Stale session handling — deleted/archived IDs auto-cleared with fallback to first active event
- [x] ActiveEventContext cache removed — no stale event retention
- [x] `id()` delegates to `current()?->id`, `hasActiveEvent()` checks `current() !== null`
- [x] `$cleared` flag prevents fallback after explicit `clear()`
- [x] 25+ tests: basic context, stale state, inactive policy, Participation isolation, legacy compatibility
- [x] Route middleware deferred — no concrete multi-event routes yet
- [x] Event-scoped query scopes deferred — S3.6+ scope

Status: COMPLETE / VERIFIED

### S3.5 Legacy Data Backfill

#### S3.5A Legacy Data Quality Audit ✅
- [x] Audit legacy peserta data quality (144 peserta, NIP range 1001–2063, gender distribution, duplicate analysis)

#### S3.5B Legacy Mapping Infrastructure ✅
- [x] `legacy_peserta_mappings` migration — FK constraints with restrictOnDelete, UNIQUE(peserta_id), UNIQUE(participation_id), snapshot columns
- [x] `LegacyPesertaMapping` model — belongsTo relationships to Peserta, Person, Participation, Event
- [x] Inverse relationships on Peserta (`hasOne`), Person (`hasOne`), Participation (`hasOne`), Event (`hasMany`)
- [x] Tests — schema, creation, nullable fields, belongs-to relationships, inverse relationships, UNIQUE constraints, restrictOnDelete (all 4 parents), cascade-free guarantee

#### S3.5C Safe Backfill Engine ✅
- [x] `LegacyPesertaBackfillService` — execute(), per-peserta analysis, NIP matching, identity signal validation, conflict detection, dry-run projection, transactional writes
- [x] `BackfillLegacyPeserta` Artisan command — `--dry-run` (default), `--execute`, `--event`, mutual exclusion validation, event validation (exists + active)
- [x] 37+ dedicated tests: command contract, dry-run, execute, NIP matching, conflict detection, participation resolution, idempotency, bulk determinism, domain safety

#### S3.5D Copy Database Execute Verification ✅
- [x] Isolated test on `database.s3.5d-test.sqlite` copy
- [x] First execute: 144 people, participations, mappings created. 0 conflicts. 432 writes
- [x] Second dry-run: Already Mapped: 144. 0 writes
- [x] Second execute: Already Mapped: 144. 0 writes. Idempotency verified

#### S3.5E Production Backfill ✅
- [x] Pre-backfill SQLite backup created: `database/database.pre-s3.5e-backfill-20260717-172802.sqlite`
- [x] Production dry-run: 144 peserta, 0 conflicts, 0 writes
- [x] Production execute: 144 People, 144 Participations, 144 Mappings created. 432 writes. 0 errors
- [x] Post-execute audit: people=144, participations=144, events=1, peserta unchanged
- [x] Final idempotency dry-run: Already Mapped: 144. 0 writes. 0 projection
- [x] **Runtime architecture unchanged** — peserta table remains active source

Status: COMPLETE / VERIFIED

### S3.6 Attendance Event Scoping

- [x] Session event ownership added to `sesi_absensis`
- [x] Active session resolution scoped to active event
- [x] Participant resolution made event-safe via mapping bridge
- [x] Cross-event attendance persistence blocked
- [x] Legacy CAI attendance compatibility preserved
- [x] Full regression suite verified

Status: COMPLETE / VERIFIED

### S3.7 Participant/QR Migration

- [x] Migrate QR scan/label flows to participation runtime
- [x] Add event-scoped QR lookup paths
- [x] Preserve legacy NIP/attendance_code compatibility during transition
- [x] RegistrationService normalized write-path to Participation
- [x] participant_number generation is event-scoped
- [x] Full suite verified (395 passed, 1013 assertions)

Status: COMPLETE / VERIFIED

### S3.8 Dashboard & Report Scoping

- [x] Migrate dashboard queries to event scope
- [x] Migrate report queries to event scope (RekapPeserta + PesertaExport)
- [x] Preserve legacy compatibility where required
- [x] Migrate dashboard counters and statistics to event scope
- [x] Verify RekapAbsensi event-safe normalization and cross-event leakage

Status: COMPLETE / VERIFIED

Completed verification: 401 passed, 1035 assertions, 7.11s

Target: August 2026 operational use for Multi Event.

Architecture source: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`

Next roadmap checkpoint: S3.9B Category Foundation

### S3.9 Multi Role/Venue/Category

Status: ✅ COMPLETE / VERIFIED

#### S3.9A Domain Foundation ✅ VERIFIED
- [x] Create `activity_groups` table + `ActivityGroup` model
- [x] Create `activities` table + `Activity` model
- [x] Create `activity_registrations` table + `ActivityRegistration` model
- [x] Add `ActivityRegistrationService` for safe domain creation
- [x] Enforce event isolation for ActivityGroup / Activity / ActivityRegistration
- [x] Enforce duplicate registration protection for Participation + Activity
- [x] Keep LegacyPesertaMapping + peserta compatibility bridge intact
- [x] Full regression suite verified: 411 passed, 1057 assertions

#### S3.9B Category Foundation ✅ VERIFIED
- [x] Create `category_definitions` table + `CategoryDefinition` model
- [x] Create `activity_categories` table + `ActivityCategory` model
- [x] Add `requires_category` support on `activities`
- [x] Add nullable `category_definition_id` on `activity_registrations`
- [x] Enforce event isolation for CategoryDefinition / ActivityCategory / ActivityRegistration category usage
- [x] Enforce availability rules via ActivityCategory
- [x] Enforce `requires_category` behavior on registration
- [x] Keep LegacyPesertaMapping + peserta compatibility bridge intact
- [x] Full regression suite verified: 425 passed, 1086 assertions, 7.24s

#### S3.9C Venue + Rundown Foundation ✅ VERIFIED
- [x] Create `venues` table + `Venue` model
- [x] Create `rundowns` table + `Rundown` model
- [x] Create `rundown_items` table + `RundownItem` model
- [x] Add `ActivityScheduleService` for safe venue/rundown creation
- [x] Enforce event isolation for Venue / Rundown / RundownItem
- [x] Enforce time validation (`ends_at > starts_at`)
- [x] Support parallel activities at the same time when venue/rundown data is valid
- [x] Keep LegacyPesertaMapping + peserta compatibility bridge intact
- [x] Full regression suite verified: 432 passed, 1103 assertions, 7.46s

#### S3.9D Event Role / Committee Foundation ✅ VERIFIED
- [x] Create `event_roles` table + `EventRole` model
- [x] Create `event_committee_assignments` table + `EventCommitteeAssignment` model
- [x] Add `EventCommitteeService` for safe committee assignment
- [x] Enforce event isolation for EventRole / EventCommitteeAssignment
- [x] Support optional ActivityGroup / Activity / Venue assignment targets
- [x] Enforce Person as canonical committee identity
- [x] Keep participation as optional contextual bridge only
- [x] Preserve authorization separation (`EventRole` != app roles)
- [x] Keep LegacyPesertaMapping + peserta compatibility bridge intact
- [x] Full regression suite verified: 452 passed, 1129 assertions, 7.62s

#### S3.9E Reporting / Export Integration ✅ VERIFIED
- [x] Add event-safe activity registration reporting/export foundation
- [x] Keep PesertaExport and RekapPeserta compatible and event-scoped
- [x] Add committee/reporting query foundation
- [x] Add rundown/reporting query foundation
- [x] Preserve export logging convention
- [x] Full regression suite verified: 459 passed, 1140 assertions, 7.69s

#### S3.9 Closure
- [x] S3.9A Domain Foundation
- [x] S3.9B Category Foundation
- [x] S3.9C Venue + Rundown Foundation
- [x] S3.9D Event Role / Committee Foundation
- [x] S3.9E Reporting / Export Integration

#### Future checkpoint (not implemented)
- [ ] Recurring Event Self-Registration & Identity Correction

### S3.10 Regression & Production Readiness ✅ VERIFIED

- [x] Full test suite verification
- [x] Manual QA on critical flows
- [x] Performance sanity review
- [x] Deployment readiness checklist documented

Status: COMPLETE / VERIFIED

### S3.0 Architecture & Database Audit

- [x] Current database map
- [x] Current identity model audit
- [x] Single-event coupling map
- [x] Target domain design
- [x] Field ownership matrix
- [x] Multi Event attendance architecture
- [x] Active event context recommendation
- [x] Backward compatibility & migration strategy
- [x] Universal Person deduplication strategy
- [x] Test migration strategy
- [x] Sprint 3 breakdown & roadmap proposal

Deliverable: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`

### S3.1 Event Foundation ✅

- [x] Event model + events table migration
- [x] Legacy CAI Event bootstrap (idempotent)
- [x] ActiveEventContext service (session-based singleton)
- [x] Event selection UI (sidebar switcher)
- [x] Event management CRUD (index, create, edit, archive/activate)
- [x] Event routes (`/events`)
- [x] Registered ActiveEventContext in AppServiceProvider
- [x] Tests: Event creation, validation, bootstrap, context, UI
- [x] Backward compatibility: existing app unchanged
- [x] Documentation: ROADMAP, TODO, CHANGELOG, CURRENT_STATE

### S3.2 Universal Person ✅

- [x] Create `people` migration + Person model
- [x] Person model with desa() relationship and jenis_kelamin_label accessor
- [x] Tests: person creation, schema, NIP uniqueness, desa FK, jenis_kelamin L/P format
- [ ] Design deduplication matching strategy (deferred to S3.5 backfill)

### S3.3 Participation Foundation ✅

- [x] Create `participations` migration + Participation model
- [x] `UNIQUE(event_id, person_id)` — one participation per person per event
- [x] `UNIQUE(event_id, participant_number)` — per-event participant number
- [x] Globally unique `attendance_code`
- [x] `Person` model: participations() + events() relationships
- [x] `Event` model: participations() + people() relationships
- [x] Tests: schema, relationships, uniqueness, cascade, Multi Event identity contract
- [x] No peserta backfill — legacy architecture unchanged

### S3.4 Active Event Context Hardening ✅

- [x] ActiveEventContext: requireCurrent(), resolveDefault(), stale/inactive safety
- [x] Inactive event enforcement — archived events rejected
- [x] Stale session handling — deleted/archived IDs auto-cleared with fallback to first active event
- [x] ActiveEventContext cache removed — no stale event retention
- [x] `id()` delegates to `current()?->id`, `hasActiveEvent()` checks `current() !== null`
- [x] `$cleared` flag prevents fallback after explicit `clear()`
- [x] 25+ tests: basic context, stale state, inactive policy, Participation isolation, legacy compatibility
- [ ] Route middleware deferred — no concrete multi-event routes yet
- [ ] Event-scoped query scopes deferred — S3.6+ scope

### S3.5 Legacy Data Backfill

#### S3.5A Legacy Data Quality Audit ✅
- [x] Audit legacy peserta data quality (144 peserta, NIP range 1001–2063, gender distribution, duplicate analysis)

#### S3.5B Legacy Mapping Infrastructure ✅
- [x] `legacy_peserta_mappings` migration — FK constraints with restrictOnDelete, UNIQUE(peserta_id), UNIQUE(participation_id), snapshot columns
- [x] `LegacyPesertaMapping` model — belongsTo relationships to Peserta, Person, Participation, Event
- [x] Inverse relationships on Peserta (`hasOne`), Person (`hasOne`), Participation (`hasOne`), Event (`hasMany`)
- [x] Tests — schema, creation, nullable fields, belongs-to relationships, inverse relationships, UNIQUE constraints, restrictOnDelete (all 4 parents), cascade-free guarantee

#### S3.5C Safe Backfill Engine ✅
- [x] `LegacyPesertaBackfillService` — execute(), per-peserta analysis, NIP matching, identity signal validation, conflict detection, dry-run projection, transactional writes
- [x] `BackfillLegacyPeserta` Artisan command — `--dry-run` (default), `--execute`, `--event`, mutual exclusion validation, event validation (exists + active)
- [x] 37+ dedicated tests: command contract, dry-run, execute, NIP matching, conflict detection, participation resolution, idempotency, bulk determinism, domain safety

#### S3.5D Copy Database Execute Verification ✅
- [x] Isolated test on `database.s3.5d-test.sqlite` copy
- [x] First execute: 144 people, participations, mappings created. 0 conflicts. 432 writes
- [x] Second dry-run: Already Mapped: 144. 0 writes
- [x] Second execute: Already Mapped: 144. 0 writes. Idempotency verified

#### S3.5E Production Backfill ✅
- [x] Pre-backfill SQLite backup created: `database/database.pre-s3.5e-backfill-20260717-172802.sqlite`
- [x] Production dry-run: 144 peserta, 0 conflicts, 0 writes
- [x] Production execute: 144 People, 144 Participations, 144 Mappings created. 432 writes. 0 errors
- [x] Post-execute audit: people=144, participations=144, events=1, peserta unchanged
- [x] Final idempotency dry-run: Already Mapped: 144. 0 writes. 0 projection
- [x] **Runtime architecture unchanged** — peserta table remains active source

### S3.6 Attendance Event Scoping

- [ ] Migrate sessions to event-scoped
- [ ] Migrate attendance to participation-based
- [ ] Migrate permits to participation-based

### S3.7 Participant/QR Migration

- [ ] QR lookup event-scoped
- [ ] Participant numbering per-event

### S3.8 Dashboard & Report Scoping

- [ ] Dashboard counts filtered by active event
- [ ] Reports filtered by active event

### S3.9 Multi Role/Venue/Category

- [ ] Design only — deferred

### S3.10 Regression & Production Readiness

- [ ] Full test suite verification
- [ ] Manual QA on critical flows
- [ ] Performance testing
- [ ] Deployment checklist

---

## Pengajian Desa MVP (PGM Series)

Status: PGM.12–PGM.16 COMPLETE. Pilot end-to-end functional. PGM.17 PENDING.

### PGM.12 Functional Fix ✅
- [x] ActiveEventContext fails closed — no arbitrary fallback
- [x] Cross-event isolation hardened

### PGM.13 Token Management UI ✅
- [x] DesaAccessGrant CRUD
- [x] Token generation, validation, revocation
- [x] Nonce rotation
- [x] Token entry UI for operators

### PGM.14 Event Context / Isolation ✅
- [x] QR & Label migrated to Participation-based flow
- [x] Event-scoped QR Print
- [x] 789 passed (1865 assertions)

### PGM.14.5 Manual Participant Entry ✅
- [x] Shared service (ManualParticipantRegistrationService)
- [x] Operator Desa manual entry with grant/session security
- [x] Admin manual entry with explicit event/desa selection
- [x] Conservative Person matching (nama+desa_id+tanggal_lahir)
- [x] Tanggal lahir required; Kelompok scoped to Desa
- [x] Grant/session validated via revalidateGrant() comparing DB against session
- [x] 38 dedicated tests

### PGM.14.1 Security & Reliability Closure ✅
- [x] EnterToken rate limiting (IP-based, 5 failed attempts/min, reset on success)
- [x] SelfAttendance database-agnostic duplicate handling (domain RuntimeException, no MySQL error codes)
- [x] QrPrint session/grant integrity check (matching DesaDashboard/ManualEntry pattern)
- [x] P0/P1 severity reclassification
- [x] 9 new tests

### PGM.15 Dashboard & Report Optimization ✅
- [x] N+1 query optimization (PengajianDesaReportService, PengajianRegionalReportService)
- [x] Operator search by name and participant_number
- [x] Attendance status indicators
- [x] Operator confirmation flow
- [x] Datetime raw-join Carbon regression fix
- [x] Regression tests for attendance list formatting

### PGM.16 Pengajian UX, Contextual Navigation & Bulk Import ✅
- [x] event_type architecture (cai / pengajian)
- [x] ActiveEventContext event-type awareness
- [x] Contextual sidebar (CAI vs Pengajian menus)
- [x] KJA Event Manager branding
- [x] Pengajian bulk import (CSV/Excel with preview)
- [x] Pengajian import identity matching (PHP-level date comparison)
- [x] Desa-scoped kelompok lookup (deterministic, no name-only first())
- [x] Regional/Desa report filter fixes (Hadir+Method, Belum ignores method)
- [x] Responsive Regional Report layout
- [x] Responsive Desa Dashboard layout
- [x] Event switcher redirect/reload (CAI→dashboard, Pengajian→pengajian.report)
- [x] Migration: add kelompok_id to people table
- [x] Migration: add event_type to events table
- [x] 25+ dedicated import tests
- [x] 14+ dedicated event-type tests (sidebar, context, redirect)

### PGM.17 Pilot Release 🔲 PENDING
- [ ] Pilot data verification / end-to-end simulation
- [ ] Data quality documentation
- [ ] Final go/no-go
- [ ] Production deployment
- [ ] Operator training

### Known issues (should fix before PGM.17)
- P1: Two parallel identity correction submission paths (PengajianIdentityService vs IdentityCorrectionService)
- P2: Admin ManualEntry no RBAC (documented known limitation)
- P2: Pengajian import XLSX template download not yet implemented
- P2: Ability to edit event_type safely after event creation
- P3: Sidebar remains static until page navigation (acceptable — page navigates on event switch)

---

## Sprint 2 Activity Log — Verified Integration Points

| Module | Action(s) | User Boundaries | Tests |
|---|---|---|---|
| `surat_izin` | created, submitted, approved, rejected, returned | SuratIzinService | IntegrationTest.php (5 tests) |
| `print` | print_viewed | 4 route closures | PrintLogTest.php (16 tests) |
| `export` | exported | RekapPeserta::exportExcel() | ExportLogTest.php (10 tests) |
| `qr` | downloaded, batch_exported | QRLabel\Index::downloadPng(), generateBatchExport() | QrLogTest.php (15 tests) |
| UI | search, module/action filter | ActivityLogIndex Livewire | UiTest.php (6 tests) |
| Service | log, properties, subject, user, null-safety | ActivityLogService | ActivityLogServiceTest.php (7 tests) |

---

## Sprint 3

- [ ] Multi Event
- [ ] Multi Venue
- [ ] Multi Category
- [ ] Multi Role
- [ ] Universal Person

---

## Sprint 4

- [ ] Competition Module
- [ ] Jadwal
- [ ] Bracket
- [ ] Penilaian
- [ ] Sertifikat

---

## Future

- [ ] Mobile App
- [ ] API
- [ ] SaaS
- [ ] White Label
- [ ] Offline Mode
