# KJA Event Manager Roadmap

> Product Roadmap

---

# Product Evolution

```
AbsenCAI (v1.0)
     │
     ▼
CAI Operational (v1.5)
     │
     ▼
KJA Event Manager Foundation (V1 — COMPLETED)
     │
     ▼
Event Operating System (V2 — PLANNED)
     │
     ▼
Commercial Platform (Future)
```

**Roadmap V1** = ✅ **100% COMPLETE** — Foundation, Multi Event, RBAC, CAI, Pengajian, Documentation.

**Roadmap V2 — Competition V1** = ✅ **COMPLETE** — Competition Management, Match Status, Ready Detection, Match Center, Viewer Integration, Match Result Dialog, Officials Assignment, Single Elimination Bracket, Public Portal, Event Dashboard.

**Remaining Roadmap V2** = 📋 **PLANNED** — Blueprint Event, Venue Management (V2 hierarchy), Certificate Engine, Public API, Mobile. Lihat `VISION_V2.md` untuk detail.

---

# Current Status

> **Catatan penomoran sprint:** Bagian "# Sprint 0–6" di bawah adalah sprint *historis* roadmap CAI/Multi Event (sudah closed/complete). **Sprint series saat ini** (Sprint 1, 2, 3.1, 3.2 — track pengembangan pasca-Competition V1) tercantum pada tabel di bawah dan di `docs/CHANGELOG.md` [Unreleased].

| Item | Status |
|------|--------|
| Roadmap V1 (Foundation, CAI, Pengajian) | ✅ 100% COMPLETE |
| Competition V1 (Sprint 7–10) | ✅ COMPLETE |
| Competition Foundation (Teams + Formats + Status) | ✅ COMPLETE — teams event-scoped, auto formation, 5 format, status. Lihat `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md` |
| Sprint 1 (Platform Consolidation — MariaDB, Permission Engine, Competition V1) | ✅ COMPLETE 100% |
| Sprint 2 (RBAC & Permission Engine — Design C) | ✅ COMPLETE 100% |
| Sprint 3.1 (technical debt cleanup) | ✅ COMPLETE 100% |
| Sprint 3.2 (architecture hardening) | ✅ COMPLETE 100% |
| Sprint 3.3 (legacy retirement prep & UAT readiness) | ✅ COMPLETE 100% |
| Heat Manager + Heat Format Builder | ✅ COMPLETE — menu Heat operator (format peserta/heat + lolos/heat per babak, auto-generate heat & babak berikutnya) untuk Individual Heat & Team Heat; `competition_heat_formats` + `CompetitionHeatManagerService` + UI `competition.heat.index`. Lihat `docs/audit/SPRINT-HEAT-MANAGER.md` & `AUDIT-HEAT-MANAGER.md` |
| Sprint 4 | 🔲 NOT STARTED |
| User without Person + Event Membership + Guest + Event Chair | ✅ COMPLETE — `event_committee_assignments.user_id` (User-based membership), Guest/Event Chair account roles, Register→Guest, Profile/ConfirmPassword null-email safe. Lihat `docs/audit/USER-EVENT-MEMBERSHIP-IMPLEMENTATION.md` |
| Import Framework (IF series) | 🔲 **IF-01 AUDIT** — IF-02 ENGINE — IF-03 DESA — IF-04 KELOMPOK — IF-05 REGU — IF-06 PERSON — IF-07 PARTICIPATION — IF-08 PENGAJIAN — IF-09 PESERTA — **IF-10 CLEANUP (STABLE v1.0)** — IF-11+ NOT STARTED (lihat `docs/import-framework.md`) |
| Test Baseline | ✅ **2436 passed / 6543 assertions / 0 failures / 0 skipped** (2026-08-26, `-d memory_limit=1G`) |
| Stage | **Pre-UAT** |

---

# Vision

Membangun platform **Event Management** yang modular, scalable, dan dapat digunakan oleh berbagai organisasi.

KJA Event Manager dikembangkan secara bertahap.

Target pertama adalah menyelesaikan seluruh kebutuhan operasional **CAI**, kemudian dikembangkan menjadi platform Event Management yang mendukung berbagai jenis event dan akhirnya menjadi produk komersial.

**Visi baru:** KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi. KJA Event Manager adalah **Event Operating System** — platform yang dapat mengelola semua jenis event melalui engine generic, bukan modul khusus.

Lihat `docs/VISION_V2.md` untuk detail.

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
KJA Event Manager (V1 Foundation — COMPLETED)
     │
     ▼
Event Operating System (V2 — PLANNED)
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

> **Catatan:** Sprint 2 di bagian ini adalah sprint *historis* roadmap CAI (Secretariat Operational). Beda dengan **Sprint 2 series saat ini** (RBAC & Permission Engine) di bagian "Current Status".

## Status

🟢 Operational Stable / Partially Deferred

---

# Phase 4 — RBAC (Security & Permission)

## Status

🟢 **S1 RBAC Foundation**: Complete. Role enum, users.role migration, Gate definitions, Artisan command `user:set-role`.
✅ **S3**: Complete — Event Management & CAI Operational Protection.
✅ **S4–S7**: ALL COMPLETE.

## Goal

Menerapkan Role-Based Access Control (RBAC) di seluruh aplikasi sesuai permission matrix di `docs/PERMISSION.md`.

## Phases

### S1 — RBAC Foundation ✅
- [x] `Role` enum (9 roles)
- [x] `users.role` migration (nullable string)
- [x] User model: role cast, hasRole(), hasAnyRole()
- [x] Gate definitions (18 abilities) + Super Admin bypass
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

### S4 — Pengajian Admin Protection ✅
- [x] Separate admin Pengajian routes from role-based access

### S5 — CAI Module Permissions ✅
- [x] Registration, attendance, QR, session, import permissions

### S6 — Sidebar Visibility ✅
- [x] `@can()` directives for all menu items

### S7 — Event-Scoped Authorization ✅
- [x] User↔Person Foundation + IDOR fixes
- [x] Event-scoped gates for KetuaEvent + EventSwitcher filtering
- [x] Assignment management UI + EventRole UI

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

> **Catatan:** Sprint 3 di bagian ini adalah sprint *historis* roadmap Multi Event (S3.0–S3.10, sudah complete). Beda dengan **Sprint 3.1 / 3.2 series saat ini** (cleanup & hardening) di bagian "Current Status".

## Status

✅ COMPLETE / VERIFIED

All Multi Event phases S3.0–S3.10 implemented and verified.
PGM.18 Sprint 3 (physical mapping cleanup) also COMPLETE.

Target: August 2026 operational use for Multi Event — on track.

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
- **PGM.18 Sprint 3 ✅ COMPLETE** — Physical mapping cleanup: columns dropped, model cleaned, 1508 passed / 3624 assertions / 0 failures

### PGM.18 Closure

PGM.18 **fully complete**:
- Sprint 1: Legacy historical tooling removed ✅
- Sprint 2: Mapping contract refactored ✅
- Sprint 3: Physical column drop + model cleanup ✅

Final baseline: **1508 passed / 3624 assertions / 0 failures**
Design C: **problem_total = 0**

**PGM.19 Sprint 8A + 8B ✅ COMPLETE** — Regu retirement: runtime dependency eliminated, column dropped. Final baseline: **1581 passed / 3774 assertions / 0 failures**.
**PGM.20 Legacy NIP Retirement ✅ COMPLETE** — All 4 phases done. NIP retired from Person/Participation/attendance/QR. `people.nip` and `pesertas.nip` columns dropped. `legacyNextNip()` removed.

---

### PGM.19 Sprint 8A — Legacy Regu Dependency Elimination ✅

**Status:** ✅ COMPLETE / VERIFIED

Eliminated all runtime dependency on `pesertas.regu_id`:
- RegistrationService dual-write to `pesertas.regu_id` **stopped**
- `PlacementService::leastFilledRegu` now requires `int $eventId` (no global fallback)
- `peserta::regu()` relationship **removed**
- `regu::peserta()` relationship **removed**
- All production callers forward `$eventId` to `leastFilledRegu`
- 15 Sprint 8A contract tests created and verified

### PGM.19 Sprint 8B — Physical Regu Retirement ✅

**Status:** ✅ COMPLETE / VERIFIED

Physical column drop + full regression recovery:
- **Migration**: `pesertas.regu_id` column dropped (FK, index, column)
- **SQLite regression 1**: Index name mismatch after table rebuild (`pesertas_new_regu_id_index` vs `pesertas_regu_id_index`) — removed explicit `dropIndex`
- **SQLite regression 2**: FK definition blocks `DROP COLUMN` — explicit table rebuild via `CREATE+INSERT+DROP+RENAME`
- **Runtime regression**: null `eventId` in `autoPlacement` when no active event context — null-guard added
- **Test leak**: Missing `Str::createRandomStringsNormally()` in `afterEach` — 50 cascading slug collisions fixed
- **2 stale test bugs**: `peserta_id` lookup on participations (wrong column), gender null on mount
- 17 Sprint 8B contract tests created and verified
- Zero production code regressions

**Final baseline: 1581 passed / 3774 assertions / 0 failures**
**Design C: problem_total = 0**

### Canonical assignment
```
Participation.regu_id — PRESERVED (event-scoped) ✓
pesertas.regu_id — RETIRED (column dropped) ✓
peserta::regu() — REMOVED ✓
regu::peserta() — REMOVED ✓
regu dual-write — STOPPED ✓
global regu fallback — REMOVED ✓
```

### PGM.20 Legacy NIP Retirement ✅

**Status:** ✅ COMPLETE / VERIFIED (all 4 phases)

Retired NIP (Nomor Induk Peserta) as canonical identifier from the entire system:

**Phase 1 — NIP Audit & Assessment:**
- Audited all NIP usage across codebase (models, services, controllers, Livewire components, tests, Blade templates, routes, config)
- Classified findings into Person (4), Participation (2), Attendance (5), QR (2), Reports (2), Config/Services (3)
- Confirmed NIP is NOT used as a hard FK in any table (no cascade risk)
- Recommended full retirement

**Phase 2 — Runtime NIP Elimination:**
- `RegistrationService::createParticipant()` — removed `legacyNextNip()` call, `resolveNip()` from RegistrationRequest
- `RegistrationService::updateParticipant()` — removed NIP enforcement/assignment
- `PersonLegacySyncService` — removed NIP lock enforcement, removed NIP from sync boundary
- `PlacementService` — removed NIP generation responsibility
- `Person` model — removed `legacyNextNip()`, `scopeWithNip()`, `nip` from casts/append
- `peserta` model — removed `legacyNextNip()`, `nextAutoNip()`, `nip` from casts
- `Scan.php` — removed NIP fallback in QR scan path
- `AttendanceService` — removed NIP fallback in `recordAttendance()`, `findParticipant()`, attendance lookup no longer reads NIP
- `Absensi` model — removed `pesertaByNip()` scope, NIP attendance lookup
- `PesertaExport` — removed NIP column from export
- `RekapPeserta` — removed NIP from display
- 13 service/model files, 6 Livewire components, 2 test files, 4 config/routes/blade files — all NIP references removed
- 11 deployment checkpoints verified: zero NIP references remain in runtime code

**Phase 3 — NIP Removal from Person CRUD UI:**
- `CreatePerson` form — removed NIP field
- `EditPerson` form — removed NIP field
- `IndexPerson` table — removed NIP column from table and search
- `PersonController` import — removed NIP column from import
- `PersonImport` — removed NIP column mapping

**Phase 4 — Physical Column Drop:**
- **Migration**: `people.nip` column dropped
- **Migration**: `pesertas.nip` column dropped
- Both tables cleaned via SQLite-compatible rebuild migration
- `legacyNextNip()` method permanently removed from codebase
- All model casts, fillable arrays, and form requests cleaned

**Final baseline: 1574 passed / 3745 assertions / 0 failures**

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

# Sprint 4 (HISTORIS — Identity & QR)

> **Catatan:** Sprint 4 di bagian ini adalah sprint *historis* roadmap v1/v1.5 yang sudah selesai. Jangan dicampur dengan **Sprint 4 series saat ini** (track pasca-Competition V1, masih 🔲 NOT STARTED) yang tercantum di bagian "Current Status".

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
* Latest baseline saat ini: **1944 tests, 4648 assertions** (pasca Sprint 3.1 + 3.2).
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

KJA Event Manager — Event Operating System

Status:

📋 Planned

Target:

Event Operating System dengan Competition Engine, Scoring Engine, dan Blueprint Event.

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

## Authorization
Master Data is protected with `can:view-master-data` (route + sidebar) and `can:manage-master-data` (Livewire mutations). Only super_admin, admin, and sekretariat have access. RBAC S2 complete.

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
1. **Sprint 3.2 — Architecture Hardening** ✅ COMPLETE — Dashboard Presenter Factory, EventOwnership deduplication, Import helper, ManualEntry trait. Baseline 1944/4648/0.
2. **Sprint 3.1 — Technical Debt Cleanup** ✅ COMPLETE — dead code/views/imports removed, deduplication. Baseline 1944/4648/0.
3. **Sprint 2 — RBAC & Permission Engine** ✅ COMPLETE — Permission Engine (Design C), User Management RBAC consistency, Event Role CRUD.
4. **Sprint 1 — Platform Consolidation** ✅ COMPLETE — MariaDB Migration, Permission Engine foundation, Competition V1, Public Portal, Event Dashboard.
5. **UAT (User Acceptance Testing)** 🔜 — Competition V1, Public Portal, Event Dashboard. Awaiting sign-off.
6. **UI Bug Fix Sprint — Batch 1** ✅✅ Branding & Navigation (bugs #1, #2, #4, #6) — RESOLVED VERIFIED
7. **UI Bug Fix Sprint — Batch 2** ✅✅ Access Token UI & Security (bugs #5, #7, #8) — RESOLVED VERIFIED
8. **UI Bug Fix Sprint — Batch 3** ✅✅ Functional/UI Logic (bugs #3, #9) — RESOLVED VERIFIED
9. **UI Bug Fix Sprint — Batch 4** ✅ Final UI Polish (bugs #10, #11, #12) — RESOLVED VERIFIED
10. **Pengajian Desa MVP** — PGM.12–PGM.17 ✅ Complete.
11. **Multi Event (Sprint 3)** ✅ Complete (S3.0–S3.10)
12. **S01–S04 Foundation** ✅ Complete
13. **PGM.18 Sprint 2** ✅ COMPLETE — Mapping contract refactored, fully verified
14. **PGM.18 Sprint 3** ✅ COMPLETE — Physical mapping cleanup: columns dropped, relationships removed
15. **PGM.19 Sprint 8A + 8B** ✅ COMPLETE — Physical Regu Retirement: `pesertas.regu_id` dropped
16. **PGM.20 Legacy NIP Retirement** ✅ COMPLETE — All 4 phases done. NIP retired
17. **Sprint 3.3** ✅ COMPLETE — Legacy retirement prep & UAT readiness (legacy audit, Platform Dashboard TODOs, import Gate gaps, `UAT_CHECKLIST.md`, `LEGACY_RETIREMENT_PLAN.md`)
18. **Sprint 4** 🔲 NOT STARTED — rekomendasi di bawah
19. **Import Framework (IF series)** 🔲 IF-01 (audit) COMPLETE; IF-02 (engine) COMPLETE; IF-03 (Desa) COMPLETE; IF-04 (Kelompok) COMPLETE; IF-05 (Regu) COMPLETE; IF-06 (Person) COMPLETE; IF-07 (Participation) COMPLETE; IF-08 (Pengajian) COMPLETE; IF-09 (Peserta) COMPLETE; **IF-10 (Cleanup — STABLE v1.0) COMPLETE**; IF-11+ NOT STARTED — lihat `docs/import-framework.md`
20. **Competition** (future sprint — V2 generic engine)
21. **Commercial** (future sprint)
```

---

# Import Framework (IF Series)

## Status

🔲 **IF-01 COMPLETE (audit & desain)** — ✅ **IF-02 COMPLETE (engine)** — ✅ **IF-03 COMPLETE (Desa)** — ✅ **IF-04 COMPLETE (Kelompok)** — ✅ **IF-05 COMPLETE (Regu)** — ✅ **IF-06 COMPLETE (Person)** — ✅ **IF-07 COMPLETE (Participation)** — ✅ **IF-08 COMPLETE (Pengajian)** — ✅ **IF-09 COMPLETE (Peserta)** — ✅ **IF-10 COMPLETE (Cleanup — STABLE v1.0)** — IF-11+ NOT STARTED.

## Goal

Standarisasi seluruh fitur import aplikasi ke **satu Import Framework**:
UI/UX, lifecycle, validation, preview, summary, commit flow, dan testing yang sama.
**Import Massal Pengajian** adalah golden standard.

## Deliverables Fase IF-01 (2026-08-05)

- ✅ `docs/import-audit.md` — Inventori seluruh import, GAP analysis (Kategori A–D).
- ✅ `docs/import-framework.md` — Arsitektur, pipeline, definition, wizard, template, roadmap.

## Deliverables Fase IF-02 (2026-08-06 — Infrastructure Only)

- ✅ **Stage pipeline nyata** (8 stage), **runner dispatch**, **DTO konsolidasi**, **exception khusus**,
  **DI wiring**, **logging hook**, **version guard**. +37 unit test. Baseline hijau.

## Deliverables Fase IF-03 (2026-08-06 — First Production Migration: DESA)

- ✅ **Import Desa** = modul pertama di atas framework (adapter + definition + template + wizard).
- ✅ **Adapter reusable** (`ImportAdapter`), `FileParser`, template generator (DATA/PETUNJUK/REFERENSI).
- ✅ Wizard + komponen `<x-import.*>`. +36 test. Baseline hijau.

## Deliverables Fase IF-04 (2026-08-06 — Kelompok + Metadata + Parameter Engine)

- ✅ **Metadata definition** — `ImportDefinitionMetadata`: `displayName() description() icon() parameters() columns() rules() template() summary() parameterOptions()`.
- ✅ **Parameter engine** — Kelompok butuh **Desa (required)**; wizard membaca parameter dari definition (`$meta['parameters']` + `parameterOptions`), **tidak hardcode di blade**; parameter dikirim via `ImportContext.options['parameters']`.
- ✅ **`KelompokImportDefinition`** — collaborator nyata: parser (FileParser, kolom `kelompok`), normalizer (trim + duplicateKey `desa_id|kelompok`), validator (required + **cek Desa**), duplicate detector (**scoped per desa**), committer (create di bawah desa terpilih).
- ✅ **Template generator** — `template_import_kelompok.xlsx` (kolom `kelompok`, sheet DATA/PETUNJUK/**REFERENSI = daftar desa aktif**).
- ✅ **Wizard 5 langkah** — Upload → Preview → Validation → Import → Result, memakai komponen reusable.
- ✅ **POST route** `import.kelompok` kini butuh `desa_id` (via adapter); route `GET /import/kelompok/template`.
- ✅ **25 test baru** (11 unit + 14 feature). Baseline hijau (2063 passed; 5 failure pre-existing).

## Deliverables Fase IF-05 (2026-08-06 — Regu + Reusable Wizard Base)

- ✅ **Import Regu** = modul ketiga di atas framework (metadata + wizard + template).
- ✅ **Business rule Regu dipertahankan** — normalisasi gender (`laki laki`/`Laki - laki`/`Laki – Laki` → `Laki - Laki`; `perempuan` → `Perempuan`), duplicate **by unique regu name** (`unique:regus,regu`). Regu global (tanpa FK) → **tanpa parameter**.
- ✅ **Reusable wizard** — `app/Livewire/Import/ImportWizardBase.php` + view generik `livewire/import/import-wizard.blade.php` dipakai `ImportKelompok` & `ImportRegu` (metadata-driven).
- ✅ **`ReguImportDefinition`** — collaborator nyata (parser/normalizer/validator/duplicateDetector/committer) + metadata (displayName/description/icon/parameters/columns/rules/template/summary).
- ✅ **Template generator** — `template_import_regu.xlsx` (kolom `regu, jenis_kelamin`, sheet DATA/PETUNJUK/REFERENSI = nilai enum gender); route `GET /import/regu/template`.
- ✅ **POST route** `import.regu` via adapter; error per-field (`jenis_kelamin`); hapus `app/Imports/ReguImport.php` (Maatwebsite).
- ✅ **19 test baru** (8 unit + 11 feature). Baseline hijau (2082 passed; 5 failure pre-existing).

## Deliverables Fase IF-06 (2026-08-06 — Person Import, Design C)

- ✅ **Import Person = domain canonical pertama** di atas framework (Design C: Person → Participation → Attendance; **tanpa tabel legacy peserta sebagai entitas utama**).
- ✅ **Audit Person** — model (`people`: nama, jenis_kelamin L/P, tanggal_lahir, desa_id, kelompok_id), relasi (desa, kelompok, participations, legacyPesertaMapping), identity canonical (nama + desa + tanggal lahir); NIP retired (PGM.20) tidak dipakai; attendance_code tidak dipakai untuk identitas.
- ✅ **Duplicate reuses `PersonDuplicateDetectionService`** (name + tanggal lahir, canonical) — tidak ada algoritma baru; intra-file via duplicateKey; kandidat mirip → warning.
- ✅ **Normalisasi** — trim, multiple & unicode spaces, gender → `L`/`P` (L/P/Laki - Laki/Perempuan/variant), tanggal lahir YYYY-MM-DD, resolusi desa/kelompok (FK).
- ✅ **`PersonImportDefinition`** — metadata lengkap (displayName/description/icon/parameters[]/columns/rules/template/summary) + collaborator nyata; `PersonImportCommitter` create via `Person::create()` (jalur golden; tidak ada PersonService standalone) + duplicate via service.
- ✅ **Template generator** — `template_import_person.xlsx` (kolom nama, jenis_kelamin, tanggal_lahir, desa, kelompok; REFERENSI = enum gender + daftar desa); route `GET /import/person/template`.
- ✅ **Wizard** — `ImportPerson` extends `ImportWizardBase` (tanpa parameter); POST route `import.person` via adapter; error per-field.
- ✅ **21 test baru** (10 unit + 11 feature). Baseline hijau (2103 passed; 5 failure pre-existing).

## Deliverables Fase IF-07 (2026-08-06 — Participation Import, Design C)

- ✅ **Import Participation** = domain Design C kedua (`Person → Participation → Attendance`); **Design C lengkap untuk Person & Participation**.
- ✅ **Audit Participation** — model (`participations`: person_id, event_id, participant_number, attendance_code, jenis_peserta, status_registrasi, regu_id), relasi (person, event, regu), registrasi flow canonical (`ManualParticipantRegistrationService`), status registrasi, duplicate per (person, event).
- ✅ **Person dicari dulu** — `ManualParticipantRegistrationService::resolvePerson()` (nama + desa + tanggal lahir); Person tidak pernah dibuat sembarangan; ambiguous → warning.
- ✅ **Duplicate = Participation existing utk (person, event target)** — event lain boleh; intra-file via duplicateKey.
- ✅ **Commit via `ManualParticipantRegistrationService::register()`** (service canonical) + param opsional `jenisPeserta/statusRegistrasi/reguId` (backward-compatible).
- ✅ **Normalisasi reuse `PersonIdentityNormalizer`** (Person logic, tidak diduplikasi) + resolusi regu.
- ✅ **`ParticipationImportDefinition`** — metadata lengkap + parameter `event_id` (required); `ParticipationImportTemplateExport` (`template_import_participation.xlsx`, REFERENSI = daftar event).
- ✅ **Wizard** — `ImportParticipation extends ImportWizardBase` (parameter default = active event), dipasang di halaman Registrasi; template route event-scoped `GET /events/{event}/registrasi/import-participation/template`.
- ✅ **SummaryStage** — merge warning duplicate ke summary final (warning tampil di wizard).
- ✅ **17 test baru** (9 unit + 8 feature). Baseline hijau (2120 passed; 5 failure pre-existing).

## Deliverables Fase IF-08 (2026-08-06 — Pengajian, Behavior-Preserving)

- ✅ **Audit lengkap Pengajian** — wizard (parse/preview/executeImport/reset), view 3-langkah, `PengajianImportService` (validate/import/processRow), template `Exports/PersonImportTemplateExport`, routes, 44 test golden.
- ✅ **Wizard extends `ImportWizardBase`** — lifecycle (upload/reset/updatedFile/uploadError/preview/commit/loading/navigation) dari base; override 3-step, view custom, `importContext()` (eventId), extract/result hooks, pesan persis.
- ✅ **`PengajianImportService` → orchestrator** — public contract `validate()/import()` IDENTIK; delegasi ke `ImportAdapter → PengajianImportDefinition → Pipeline → Committer`.
- ✅ **`ImportCommit.metrics`** (backward-compatible) membawa 5 counter Pengajian.
- ✅ **Parser/Validator/Normalizer/Committer port persis** — CSV tanpa prune, Excel skip nama kosong, pesan validasi & commit persis, gender STRICT L/P, `resolvePerson()` + `PlacementService` + `RegistrationService`, `jenis_peserta='Pengajian Desa'`, tanpa regu/legacy.
- ✅ **Template IDENTIK** — `PersonImportTemplateExport` + route tetap (wrapper framework).
- ✅ **18 test baru** (8 unit definition + 10 parity OLD-vs-NEW). 45 test golden tetap hijau. Baseline (2138 passed; 5 failure pre-existing).

## Deliverables Fase IF-09 (2026-08-06 — Peserta, Final Legacy Migration)

- ✅ **Peserta dimigrasi penuh** — `PesertaImportCommitter` port persis `PesertaImport::model()` (skip nama kosong, resolve desa/kelompok case-insensitive, auto-place regu, delegasi `RegistrationService::createParticipant`) **tanpa `Excel::import`**.
- ✅ **Real collaborators** — Parser (`FileParser`), Normalizer (raw-preserving), Validator (required nama/JK), DuplicateDetector (no-op), Committer, ActivityLogger, Metadata (`PesertaImportDefinition implements ImportDefinitionMetadata`).
- ✅ **`ImportDataController::peserta()` → `ImportAdapter::commit('peserta', ...)`**; helper `executeImport()` dihapus.
- ✅ **Cleanup** — hapus `app/Imports/PesertaImport.php` (Maatwebsite), `ImportPeserta` Livewire vestigial + view → partial form (UI identik), manual `ImportCoordinator`/`ImportRegistry`/`Pipeline`/`ArrayPipelineStageRunner` untuk import.
- ✅ **Tidak ada lagi** `Excel::import()` untuk proses import (hanya `Excel::download` untuk template/export).
- ✅ **10 test baru** (7 unit definition + 3 parity route). Test Design C / Otomatisasi / Sprint8A di-rewrite ke committer. Baseline (2148 passed; 5 failure pre-existing).

## Deliverables Fase IF-10 (2026-08-06 — Final Lock & Cleanup)

- ✅ **Audit dead code** — seluruh project: dead class/interface/provider/service/definition/parser/normalizer/validator/duplicate detector/committer/logger/template/blade/route/controller method/helper/config/test.
- ✅ **Dihapus (orphan, tanpa referensi):** `Results/ImportResult.php`, `Metrics/ImportMetrics.php`, `Support/TemplateVersion.php`; direktori kosong `Definitions/`, `Version/`, `Imports/`; helper test mati `something()` & `toBeOne` (tests/Pest.php).
- ✅ **Dipertahankan (bukan orphan):** `NullImportParser/Validator/Normalizer/DuplicateDetector/ActivityLogger` (dipakai test fake), `ImportCommitException` (dipakai test exception taxonomy, reserved), `ImportVersion` (guard coordinator), `ImportDesa` wizard (dipakai, memakai adapter).
- ✅ **Import path tunggal** — seluruh 7 modul melewati `ImportAdapter → ImportCoordinator → Definition → Pipeline → Committer`; tidak ada bypass/manual coordinator/`Excel::import`.
- ✅ **Docs sinkron** — ROADMAP/TODO/MODULES/ARCHITECTURE/CHANGELOG/import-framework/import-audit.
- ✅ Baseline hijau (2148 passed; 5 failure pre-existing). **Import Framework v1.0 STABLE.**

## Ringkasan Audit

- **Seluruh 7 modul import memakai SATU Import Framework** (backend + frontend).
- **Design C:** Person ✅ → Participation ✅ → Attendance (belum dimigrasi).
- **Kandidat baru tanpa import:** Competition, Kategori, Kelas, Venue, Schedule, Committee, Attendance, Activity, Rundown, Access Grant.

## Roadmap

IF-11 Template lanjutan + retire statis → IF-12 Activity Log → IF-13+ modul baru → IF-18 Regression parity.

Detail lengkap: `docs/import-framework.md`.

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
- 🟢 **S7 Event-Scoped Authorization: ✅ Complete**

### S5 — CAI Module Permissions ✅ COMPLETE (2026-07-21)

**Previously tracked as:**
```
### S5 — CAI Module Permissions 🔲
- [ ] Registration, attendance, QR, session, import permissions
```

**Completed:**
- ✅ `manage-import` ability applied to `/import/peserta` and `/import/regu` routes
- ✅ Livewire ImportPeserta and ImportRegu mutations gated with `Gate::authorize('manage-import')`
- ✅ Full CAI permission matrix verified (all 18 abilities)
- ✅ `manage-import` access matrix verified per role (super_admin, admin, sekretariat)

**Phase 4 RBAC updated status:**
- 🟢 S1 RBAC Foundation: ✅ Complete
- 🟢 S2 Master Data Protection: ✅ Complete
- 🟢 S3 Operational Protection: ✅ Complete
- 🟢 S4 Pengajian Admin Protection: ✅ Complete
- 🟢 **S5 CAI Module Permissions: ✅ Complete**
- 🟢 **S6 Sidebar Visibility: ✅ Complete**
- 🟢 **S7 Event-Scoped Authorization: ✅ Complete**

---

# Roadmap V2 — Event Operating System

## Status

🟡 **Sebagian terimplementasi.** Competition V1 (Sprint 7–10), Public Portal (Sprint 9.0), Event Dashboard (Sprint 10.0), dan fondasi Competition announcements/venue/jadwal sudah COMPLETE. Item generic engine (Blueprint, Competition Engine generic, Scoring Engine, hierarki Venue V2, Certificate, Mobile, Public API) masih Planned.

## Filosofi

**Build Engine, Not Module.**

Jangan membuat modul khusus untuk setiap jenis event (Silat, Voli, MTQ, PAUD).

Sebagai gantinya, bangun **Competition Engine** + **Scoring Engine** + **Blueprint Event** yang bersifat generic dan dapat dikonfigurasi.

## Urutan Pengembangan

| # | Item | Status | Deskripsi |
|---|------|--------|-----------|
| 1 | Blueprint Event | 📋 Planned | Konfigurasi awal event (Pengajian, Silat, Olahraga, Festival, Seminar, Custom) |
| 2 | Competition Engine | 🟡 Partial | Competition V1 (module-based) COMPLETE; generic engine masih Planned |
| 3 | Scoring Engine | 📋 Planned | Generic scoring — Versus, Score, Time, Distance, Ranking, Pass/Fail |
| 4 | Venue Management | 🟡 Partial | Venue CRUD V1 sudah ada; hierarki Master/Event/Arena masih Planned |
| 5 | Live Schedule Engine | 🟡 Partial | Jadwal + status match (Sprint 7) sudah ada; estimasi realtime masih Planned |
| 6 | Public Dashboard | ✅ COMPLETE | Portal publik tanpa login — jadwal, bracket, hasil, pengumuman (Sprint 9.0) |
| 7 | Announcement Engine | 🟡 Partial | Competition announcements (model + route publik) live; engine generic Planned |
| 8 | Certificate Engine | 📋 Planned | Generate sertifikat otomatis berdasarkan hasil |
| 9 | Mobile | 📋 Planned | Aplikasi mobile untuk peserta dan panitia |
| 10 | Public API | 📋 Planned | REST API untuk integrasi pihak ketiga |

## Detail

Lihat `docs/VISION_V2.md` untuk dokumentasi lengkap Roadmap V2.

---

## Roadmap V2 vs V1

```
Roadmap V1 (COMPLETED)              Roadmap V2 (PLANNED)
══════════════════════              ══════════════════════
Foundation Platform                 Blueprint Event
Multi Event Architecture            Competition Engine
RBAC                                Scoring Engine
CAI Operational                     Venue Management
Pengajian Desa MVP                  Live Schedule Engine
Documentation                       Public Dashboard
                                    Announcement Engine
                                    Certificate Engine
                                    Mobile
                                    Public API
```

V1 adalah fondasi. V2 adalah transformasi menjadi Event Operating System.

---

# Product Vision (Updated)

KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi.

**KJA Event Manager adalah Event Operating System.**

Sebuah platform yang dapat mengelola semua jenis event tanpa perlu membuat modul khusus untuk setiap cabang event. Semua event dikonfigurasi melalui **Competition Engine**, **Scoring Engine**, dan **Blueprint Event** yang bersifat generic dan dapat digunakan ulang.
