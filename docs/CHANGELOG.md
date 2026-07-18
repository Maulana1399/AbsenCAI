# CHANGELOG

Semua perubahan penting pada KJA Event Manager dicatat pada dokumen ini.

Format changelog mengikuti prinsip **Keep a Changelog**.

---

# [Unreleased]

## Added

* QR & Label module with individual QR, batch export preview, and label 4x4 UI
* Sprint 1 CAI Operational closed for current operational scope
* **Surat Izin module** — full create/submit/approve/reject/cancel/return flow with service layer, Livewire UI, database migrations, authorization gates, and test coverage
* **Print Surat** — A5 landscape template with Kop Surat (KJA/CAI logos) and `jenis_izin` (pulang/keluar), browser-native print
* **Return Tracking** — selectable return date via modal, attendance cleanup on return, IzinAbsensi end_time update
* `jenis_izin` column (`pulang`/`keluar`) on `surat_izins` table
* **Activity Log Foundation** — custom audit infrastructure with `ActivityLog` model, `ActivityLogService`, and read-only Livewire UI
* **Surat Izin Activity Logging** — lifecycle events (created, submitted, approved, rejected, returned) integrated through `SuratIzinService`
* **Activity Log UI** — `GET /activity-log` route with newest-first list, pagination, search, module/action filters, expandable properties, user fallback "Sistem"
* **Print Log** — 4 print views logged via `ActivityLogService`: Surat Izin print, single QR label, batch QR filtered, batch QR A4 (module: `print`, action: `print_viewed`)
* **Export Log** — participant data Excel export logged via `ActivityLogService` through `RekapPeserta::exportExcel()` (module: `export`, action: `exported`)
* **QR Log** — single QR PNG download and batch QR export to storage logged via `ActivityLogService` through `QRLabel\Index` (module: `qr`, actions: `downloaded`, `batch_exported`)

## Changed

* Existing QR and print services are now connected to user-facing screens without new business logic
* Deferred non-critical Sprint 1 backlog: QR PDF Export, Report PDF Export, Dashboard PJ Regu, and Live Monitoring
* Test suite expanded: 186 tests, 432 assertions (up from 70 tests, 198 assertions)
* Sprint 2 status changed to Operational Stable / Partially Deferred — Riwayat Izin, Scoring, Storage deferred to 2027
* Sprint 3 promoted to ACTIVE / HIGHEST PRIORITY
* `SuratIzinService` now depends on `ActivityLogService` via constructor injection — logs are written after successful business operations only
* `routes/web.php`: all 4 print route closures now inject `ActivityLogService::log()` after authorization/validation
* `App\Livewire\QRLabel\Index`: `downloadPng()` and `generateBatchExport()` now inject `ActivityLogService::log()` after successful generation
* `App\Livewire\Rekap\Peserta\RekapPeserta`: `exportExcel()` now injects `ActivityLogService::log()` before returning download
* **Event model and `events` table** — foundation for Multi Event architecture
* **Legacy CAI Event seeder** — idempotent bootstrap creates `cai-operational` event
* **ActiveEventContext service** — session-based active event management with singleton binding
* **Event switcher UI** — sidebar dropdown to select active event
* **Event management CRUD** — index, create, edit, archive/activate via `/events` Livewire page
* **Sprint 3 ACTIVE** — Multi Event highest priority for August 2026
* **Sprint 2 remaining features deferred** — Riwayat Izin, Scoring, Storage deferred to 2027
* `docs/SPRINT3_MULTI_EVENT_AUDIT.md` — full architecture audit and migration plan
* 30+ dedicated tests for Event Foundation
* **Person model and `people` table** — Universal Person foundation (S3.2)
* `Person` model with `desa()` relationship and `jenis_kelamin_label` accessor
* 15+ dedicated tests for Person Foundation
* Sprint 3.2 status updated to COMPLETE
* **Participation model and `participations` table** — Participation Foundation (S3.3)
* `Participation` model with `person()` and `event()` relationships
* `Person` model: `participations()` hasMany, `events()` belongsToMany
* `Event` model: `participations()` hasMany, `people()` belongsToMany
* `UNIQUE(event_id, person_id)` — one participation per person per event
* `UNIQUE(event_id, participant_number)` — participant number unique within event
* Globally unique `attendance_code` — unambiguous QR code resolution
* `jenis_peserta` default `'Wajib'` — matches existing peserta convention
* 20+ dedicated tests for Participation Foundation
* Sprint 3.3 status updated to COMPLETE
* **ActiveEventContext hardened** — requireCurrent(), resolveDefault(), stale/inactive event safety
* 25+ dedicated tests for ActiveEvent Context Hardening (S3.4)
* Route middleware deferred — no concrete multi-event routes yet
* Sprint 3.4 status updated to COMPLETE
* **ActiveEventContext cache removed** — stale `$cached` property caused deleted/archived event retention; `current()` now queries DB every call
* **Fallback behavior added** — `current()` falls back to first active event when session is stale, `id()` delegates to `current()?->id`, `hasActiveEvent()` checks `current() !== null`
* **`$cleared` flag added** — explicit `clear()` prevents fallback, preserving "no event" state
* **6 test assertions updated** — 3 stale-session tests realigned with fallback contract, `requireCurrent` test changed to expect default instead of throw, `resolveDefault` id() assertion corrected, stale-session-with-other-active test renamed and updated
* **LegacyPesertaMapping model and `legacy_peserta_mappings` table** — S3.5 mapping infrastructure foundation
* `LegacyPesertaMapping` model with `peserta()`, `person()`, `participation()`, `event()` belongsTo relationships
* `UNIQUE(peserta_id)` — one mapping per legacy peserta
* `UNIQUE(participation_id)` — one mapping per participation
* Snapshot columns: `legacy_nip`, `legacy_participant_number`, `legacy_attendance_code`, `migrated_at`, `backfill_batch_id`
* All FKs use `restrictOnDelete` — prevents cascade deletion of mapped entities
* Inverse relationships: `Peserta.legacyPesertaMapping()`, `Person.legacyPesertaMapping()`, `Participation.legacyPesertaMapping()`, `Event.legacyPesertaMappings()`
* 20+ dedicated tests for S3.5 mapping infrastructure
* S3.5 status updated to IN PROGRESS — mapping infrastructure complete, backfill command remaining
* **LegacyPesertaBackfillService** — S3.5C backfill engine with per-peserta analysis, NIP-based Person matching, identity signal validation, conflict detection, dry-run projection, and transactional execute mode
* **BackfillLegacyPeserta Artisan command** — `php artisan backfill:legacy-peserta` with `--dry-run` (default), `--execute` (writes), `--event` (slug target). `--dry-run` + `--execute` mutual exclusion. Event existence and active status validation
* `BackfillReport` and `BackfillReportItem` value objects for structured service output
* 37+ dedicated tests for backfill engine: command contract, dry-run guarantees, execute mode, NIP matching rules, conflict detection (name/gender/desa/participant_number/attendance_code), Participation resolution, idempotency, bulk determinism, transaction isolation
* S3.5 status updated to COMPLETE. **Real backfill NOT YET EXECUTED** — pending manual `--dry-run` review against 144 real peserta records
* **S3.5C backfill engine fixes** — dry-run reporting contract (Total Legacy Peserta, Database Writes), BROKEN_MAPPING test (valid FK with logical inconsistency), transaction isolation test (real conflict fixture), actual write counters (peopleCreated/participationsCreated/mappingsCreated). Test suite: 368 passed, 964 assertions
* **S3.5E Production Backfill EXECUTED 2026-07-17** — 144 Person, 144 Participation, 144 LegacyPesertaMapping created. 0 conflicts, 0 errors. Full idempotency verified. Pre-backfill backup: `database/database.pre-s3.5e-backfill-20260717-172802.sqlite`
* **S3.5 status updated** — ALL sub-phases COMPLETE (A=Audit, B=Mapping, C=Engine, D=Copy verification, E=Production). **Runtime architecture unchanged** — `pesertas` table remains active source. People/participations populated but not yet runtime-migrated

## Planned

Belum ada perubahan.

Semua rencana pengembangan dicatat pada:

* ROADMAP.md
* TODO.md

---

## [Unreleased]

### Added

* **S3.6 Attendance Event Scoping** — event-scoped attendance sessions, event-safe participant resolution via LegacyPesertaMapping -> Participation -> Event, and cross-event attendance persistence prevention
* **S3.6 regression coverage** — tests for event-scoped explicit sessions, duplicate isolation, historical legacy attendance compatibility, and cross-event rejection

### Changed

* Attendance now derives event ownership through `sesi_absensis.event_id` while preserving legacy `absensis` storage (`nip`, `nama`, `jam_scan`, `sesi_id`)
* Current priority advanced to Sprint 3.7 Participant/QR Migration

---

# [v1.0.0] - 2026-07-14

## Project

### Added

* Rename project vision menjadi **KJA Event Manager**
* AbsenCAI ditetapkan sebagai MVP (Minimum Viable Product)
* Dokumentasi arsitektur awal proyek

---

## Documentation

### Added

* AGENTS.md
* API.md
* ARCHITECTURE.md
* CHANGELOG.md
* CONTEXT.md
* CURRENT_STATE.md
* DATABASE.md
* DATAFLOW.md
* DECISION.md
* FEATURE.md
* INDEX.md
* ROADMAP.md
* SECURITY.md
* TODO.md
* PERMISSION.md

---

## Architecture

### Added

* Long-term architecture planning
* Universal Person Database concept
* Multi Event architecture
* Future Multi Organization support
* Storage architecture menggunakan Nextcloud & TrueNAS

---

## Database

### Added

Concept:

* Person sebagai entitas utama
* Participation sebagai relasi Event
* Attendance terpisah dari Person
* Universal ID
* Attendance Code

---

## Decision

### Accepted

* Rename AbsenCAI menjadi KJA Event Manager
* Person sebagai entitas utama
* Attendance Code menggantikan QR berbasis NIP
* No Code Before Design
* CAI menjadi prioritas utama
* Storage menggunakan metadata + Nextcloud

---

## Security

### Added

* Security Standard
* Security Checklist
* Authentication Guideline
* Authorization Guideline
* QR Security
* Audit Logging Standard
* Backup Policy

---

## Permission

### Added

* Role Matrix
* Permission Matrix
* Future Role Planning
* Permission Naming Convention

---

## Development

### Added

Sprint Management:

* Sprint 0
* Documentation First
* Architecture First
* Planning Before Coding

---

# Versioning

Menggunakan Semantic Versioning.

Format:

MAJOR.MINOR.PATCH

Contoh:

* v1.0.0
* v1.1.0
* v1.2.3
* v2.0.0

---

# Changelog Rules

Tambahkan perubahan **hanya jika implementasi sudah selesai**.

Jangan mencatat:

* Ide
* Rencana
* Roadmap
* TODO

Semua rencana dicatat di:

* ROADMAP.md
* TODO.md

---

# Release Flow

Idea

↓

Discussion

↓

Documentation

↓

Design

↓

Development

↓

Testing

↓

Release

↓

Update CHANGELOG
