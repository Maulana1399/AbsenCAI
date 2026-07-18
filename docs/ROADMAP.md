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

Next checkpoint:
- S3.9 Multi Role/Venue/Category (deferred design only)

### S3.9 Multi Role/Venue/Category

Status: 🔴 DEFERRED (design only — not needed for August 2026)

### S3.10 Regression & Production Readiness

Status: 📋 PENDING

---

# Sprint 4

## Goal

Identity & QR.

## Features

* Attendance Code finalization
* QR identity hardening
* participant_number compatibility rules
* legacy NIP fallback maintenance
* scan identifier normalization

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

* S01 Foundation completed.
* S02 Registration/Placement foundation completed.
* S03 Attendance architecture completed.
* S04 Identity & QR completed.
* Sprint 1 CAI Operational closed for current operational scope.
* Manual Attendance is implemented and verified.
* Attendance status Hadir/Izin/Alfa is implemented and verified.
* QR operational scope is complete.
* Sprint 2 Permission feature group completed: Surat Izin, Print Surat, Return Tracking.
* Sprint 2 Activity Log Foundation completed: model, service, read-only UI, Surat Izin lifecycle integration, tests.
* Sprint 2 Print Log completed: Surat Izin print, QR label single/batch/A4 print views integrated with ActivityLogService (action: print_viewed).
* Sprint 2 Export Log completed: participant data Excel export integrated with ActivityLogService (action: exported).
* Sprint 2 QR Log completed: single QR PNG download and batch QR export to storage integrated with ActivityLogService (action: downloaded / batch_exported).
* Latest verified baseline: 186 tests, 432 assertions.
* Sprint 2 remaining scope: Riwayat Izin, Scoring, Storage — **DEFERRED to 2027**.
* **Sprint 3 ACTIVE — HIGHEST PRIORITY.** Multi Event required for August 2026.
* S3.0 Architecture & Database Audit: COMPLETE.
* S3.1 Event Foundation: COMPLETE. Event model, events table, Legacy CAI bootstrap, ActiveEventContext service, event switcher UI, event management CRUD. Existing app backward compatible.
* S3.2 Universal Person: COMPLETE. People table, Person model. Clean foundational table — no participation wiring.
* S3.3 Participation Foundation: COMPLETE. Participations table, Participation model, Person↔Event relationships. No legacy backfill.
* S3.4 Active Event Context Hardening: COMPLETE. requireCurrent(), resolveDefault(), stale/inactive event safety. No legacy module scoping.
* S3.5 Legacy Data Backfill: COMPLETE — Production backfill executed 2026-07-17. 144 Person, 144 Participation, 144 Mapping created. 0 conflicts. Idempotency verified. Runtime architecture unchanged — peserta table remains active source.

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

# Current Priority

```
1. Multi Event (Sprint 3) — HIGHEST PRIORITY, August 2026 target
2. Event Foundation ✅ Complete (S3.1)
3. Universal Person ✅ Complete (S3.2)
4. Participation Foundation ✅ Complete (S3.3)
5. Active Event Context Hardening ✅ Complete (S3.4)
6. Legacy Data Backfill ✅ Complete — PRODUCTION BACKFILL EXECUTED (S3.5)
7. Attendance Event Scoping ✅ Complete (S3.6)
8. Participant/QR Migration (S3.7)
9. Dashboard & Report Scoping (S3.8)
10. Regression & Production Readiness (S3.10)
11. Competition (future sprint)
12. Commercial (future sprint)
```

---

# Product Vision

CAI bukan tujuan akhir.

CAI adalah MVP.

KJA Event Manager adalah platform Event Management yang dapat digunakan oleh sekolah, organisasi, komunitas, universitas, hingga penyelenggara kejuaraan dan festival dengan arsitektur modular yang siap dikembangkan dalam jangka panjang.
