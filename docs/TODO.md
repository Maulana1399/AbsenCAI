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

Last verified test suite:

70 tests passed, 198 assertions, 0 failures.

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

## Sprint 3

Status: 🟢 ACTIVE — HIGHEST PRIORITY

Target: August 2026 operational use for Multi Event.

Architecture source: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`

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
