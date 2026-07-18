# CURRENT STATE

> Current Development Status

---

# Project

## Name

KJA Event Manager

Current MVP:

CAI Operational

---

# Current Version

Version:

v1.0

Stage:

MVP Development

---

# Current Sprint

Sprint 3 — Multi Event Architecture

Status:

✅ COMPLETE / VERIFIED

Target: August 2026 operational use.

Focus:

Multi Event Foundation completed and verified. S3.0 Architecture Audit COMPLETE. S3.1 Event Foundation COMPLETE. S3.2 Universal Person COMPLETE. S3.3 Participation Foundation COMPLETE. S3.4 Active Event Context Hardening COMPLETE. S3.5 Legacy Data Backfill COMPLETE — PRODUCTION BACKFILL EXECUTED 2026-07-17 (144 people, participations, mappings created. 0 conflicts. Idempotency verified). S3.6 Attendance Event Scoping COMPLETE. S3.7 Participant/QR Migration COMPLETE. S3.8 Dashboard & Report Scoping COMPLETE / VERIFIED. **Runtime architecture unchanged for legacy compatibility** — `pesertas` and `LegacyPesertaMapping` remain intentional compatibility bridges.
Sprint 2 remaining scope (Riwayat Izin, Scoring, Storage) **DEFERRED to 2027**.
S3.9 Multi Role/Venue/Category is **ACTIVE / IN PROGRESS**.
S3.9A Domain Foundation is **COMPLETE / VERIFIED**.
S3.10 Regression & Production Readiness remains **PENDING** as future work.

---

# Current Goal

Sprint 3 Multi Event Architecture telah ditutup secara resmi. Fokus berikutnya mengikuti roadmap pada fase S3.9 yang masih design-only.

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
* S3.5 Legacy Data Backfill COMPLETE — PRODUCTION BACKFILL EXECUTED 2026-07-17. 144 Person, 144 Participation, 144 LegacyPesertaMapping created. 0 conflicts, 0 errors. Idempotency verified. Pre-backfill backup saved. **Runtime architecture unchanged** — peserta table remains active source. People/participations populated but NOT yet runtime-migrated.

Current Sprint 1 attendance state:

* Attendance Code implemented.
* Internal QR Generator implemented.
* QR Regeneration supported through on-demand generation from `attendance_code`.
* Manual Attendance implemented.
* Manual Hadir implemented.
* Manual Izin implemented.
* Attendance Status Hadir/Izin/Alfa implemented.
* Hadir remains stored in `Absensi`.
* Izin is stored separately in `IzinAbsensi`.
* Alfa is derived and is not persisted as a database row.
* Hadir ↔ Izin conflicts are prevented.
* Rekap Hadir/Izin/Alfa implemented.
* Dashboard Hadir/Izin/Alfa summary implemented.
* `attendance_code` remains the primary scan and QR identifier.
* Legacy NIP fallback remains supported.

Deferred Sprint 1 backlog:

* QR PDF Export.
* Report PDF Export.
* Dashboard PJ Regu.
* Live Monitoring.

Verification:

* Full test suite passed.
* 186 tests passed.
* 432 assertions.
* 0 failures.
* Sprint 3 Event Foundation: 30+ dedicated Event tests added.

Sprint 2 Activity Log integration verified:

| Module | Actions | Boundaries |
|---|---|---|
| `surat_izin` | created, submitted, approved, rejected, returned | SuratIzinService |
| `print` | print_viewed | 4 route closures (surat-izin.print, qr-label.print.*) |
| `export` | exported | RekapPeserta::exportExcel() |
| `qr` | downloaded, batch_exported | QRLabel\Index::downloadPng(), generateBatchExport() |
| UI | search, filter, paginate | ActivityLogIndex Livewire |

---

# Current Priority

Priority saat ini:

1. **Sprint 3: Multi Event Architecture** — ✅ COMPLETE / VERIFIED.
2. S3.0 Architecture & Database Audit ✅ COMPLETE.
3. S3.1 Event Foundation ✅ COMPLETE — Event model, events table, Legacy CAI bootstrap, ActiveEventContext, event switcher UI, event management CRUD.
4. S3.2 Universal Person ✅ COMPLETE — People table, Person model.
5. S3.3 Participation Foundation ✅ COMPLETE — Participations table, Participation model, Person↔Event relationships. No legacy backfill yet.
6. S3.4 Active Event Context Hardening ✅ COMPLETE — requireCurrent(), resolveDefault(), stale/inactive safety. Route middleware deferred.
7. S3.5 Legacy Data Backfill ✅ COMPLETE — PRODUCTION BACKFILL EXECUTED 2026-07-17 (144 people, participations, mappings created; 0 conflicts). Runtime architecture unchanged — peserta remains active source.
8. S3.6 Attendance Event Scoping ✅ COMPLETE.
9. S3.7 Participant/QR Migration ✅ COMPLETE.
10. S3.8 Dashboard & Report Scoping ✅ COMPLETE / VERIFIED.
11. S3.9 Multi Role/Venue/Category — DEFERRED DESIGN ONLY.
12. S3.10 Regression & Production Readiness — PENDING / future work.
13. Sprint 2 remaining features (Riwayat Izin, Scoring, Storage) **DEFERRED to 2027**.

Development follows `docs/ROADMAP.md` as the primary product roadmap.
Architecture source: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`.

# Project Status

## Documentation

🟢 Stable

---

## Architecture

🟢 Stable for CAI Operational

---

## Database

🟢 Stable

---

## Core Feature

🟢 Stable

* Import
* Registrasi
* Registrasi Ulang
* Scan QR
* Dashboard
* Rekap
* Surat Izin
* Activity Log

---

## UI

🟢 Stable for CAI Operational

Target:

* Dark Mode completed.
* Responsive Mobile completed.
* Menu Refactor completed.
* Reusable Components foundation completed through Flux UI and centralized layouts.

---

## Security

🟢 Stable

---

## Permission

🟢 Stable

---

# Current Technical Stack

Backend

* Laravel 12

Frontend

* Livewire
* Flux UI
* Tailwind CSS

Database

Current

* SQLite

Future

* MariaDB

Infrastructure

* Rocky Linux
* Proxmox
* TrueNAS
* Nextcloud

---

# Current Risks

## High

* SQLite belum cocok untuk concurrent access dalam skala besar.
* QR masih menggunakan NIP sebagai legacy fallback.
* Multi Event S3.1 Event Foundation is context-only — no existing queries are yet event-scoped. Modules must be migrated one by one.

---

## Medium

* QR PDF Export deferred.
* Report PDF Export deferred.
* Dashboard PJ Regu deferred.
* Live Monitoring deferred.
* EditSesi page references sesi_id from SuratIzin as editable data — needs architectural review.
* Riwayat Izin belum diimplementasikan.
* Batch mode "Print All Filtered" via Livewire `printAllFiltered()` is not logged (blade in label mode uses route which is logged; batch mode uses Livewire method directly — minor gap).

---

## Low

* API belum dibutuhkan.
* Mobile App masih tahap perencanaan.

---

# Current Technical Debt

* SVG QR generation remains deferred technical debt; active PNG QR runtime uses the internal QR service.
* Attendance masih menggunakan NIP sebagai legacy fallback, sementara attendance_code menjadi identifier utama scan.
* Struktur database masih berorientasi pada CAI.
* EditSesi page treats SuratIzin.sesi_id as editable session data — this needs architectural review.
* Activity Log's "failed submit" test has a dead assertion after `expectException`.
* Event Foundation is context infrastructure only — no existing queries are yet event-scoped.
* Person table is populated with 144 production records — NOT yet runtime source.
* Participation is populated with 144 production records — runtime integration in QR/registration write-path is in progress; legacy peserta architecture remains operational.
* LegacyPesertaMapping populated with 144 production records — compatibility bridge between legacy peserta and normalized Person→Participation→Event domain. Production backfill executed 2026-07-17.
* ActiveEventContext hardening complete — stale cache removed, fallback to first active event added, clear() prevents fallback. Route middleware and legacy module scoping are not yet implemented. Switching active event has zero effect on operational modules.
* S3.6 complete: attendance sessions are event-scoped, participant resolution is event-safe, and cross-event persistence is blocked.
* S3.7D verified: RegistrationService now writes normalized participant identifiers through Participation, with LegacyPesertaMapping preserving compatibility.
* S3.8 COMPLETE and VERIFIED — Dashboard and report scoping now follow active-event boundaries across RekapPeserta, PesertaExport, Dashboard, and RekapAbsensi.
* Architecture contract:
  * Event = event boundary
  * Person = canonical identity
  * Participation = event-scoped participation and runtime identifiers
  * Event -> Participation -> Person
  * Attendance = Event -> SesiAbsensi -> Absensi
  * LegacyPesertaMapping + peserta = intentional backward-compatibility bridge
* Remaining legacy dependencies are intentional compatibility, not Sprint 3 blockers, and may be reduced in later roadmap phases if required.
* Full suite verified: 401 tests passed, 1035 assertions, duration 7.11s.

---

# Next Work

Current next task:

**Sprint 3.8 — Dashboard & Report Scoping.** COMPLETE and VERIFIED. Dashboard counters and attendance/report views are now event-scoped with required legacy compatibility preserved.

S3.5 Legacy Data Backfill is COMPLETE — production backfill executed 2026-07-17. 144 people, participations, mappings created. 0 conflicts. Idempotency verified. Runtime architecture unchanged — peserta remains active source.

S3.8 complete: RekapPeserta + PesertaExport scope to the active event via Participation, and Dashboard + RekapAbsensi are now event-safe as well.

After S3.8: move to S3.9 only if a future design checkpoint is explicitly reactivated; otherwise maintain S3.8 as closed and verified.

After S3.1 Event Foundation completion:

Continue development according to `docs/ROADMAP.md` and `docs/SPRINT3_MULTI_EVENT_AUDIT.md`.

Deferred Sprint 1 items remain in backlog until operationally required.
Sprint 2 remaining scope (Riwayat Izin, Scoring, Storage) **deferred to 2027**.

Architecture source: `docs/SPRINT3_MULTI_EVENT_AUDIT.md`.
Latest verified baseline: 397 tests, 1019 assertions.
Event Foundation: 30+ dedicated tests added.
Person Foundation: 15+ dedicated tests added.
Participation Foundation: 20+ dedicated tests added.
Active Event Context Hardening: 25+ dedicated tests added (3 stale-session tests and 2 collateral tests updated for new fallback contract).
LegacyPesertaMapping infrastructure: 20+ dedicated tests added. LegacyPesertaBackfill: 37+ dedicated tests added.

Sprint 5 Document & Certificate remains deferred until required.

---

# Development Rules

# Development Rules

Current development rules:

* Follow `docs/ROADMAP.md` as the primary roadmap.
* Audit existing functionality before implementing new functionality.
* Do not duplicate features that already exist.
* Keep backward compatibility unless an explicit migration is designed.
* Business logic should remain centralized in service layers.
* Update documentation after verified changes.
* Run the full test suite before closing a feature scope.

---

# Success Criteria

Sprint 0 dianggap selesai apabila:

* Dokumentasi lengkap.
* Struktur proyek konsisten.
* Roadmap final.
* Security final.
* Permission final.
* AI dapat memahami proyek hanya dengan membaca dokumentasi.

---

# Notes

CURRENT_STATE.md adalah snapshot kondisi proyek.

Dokumen ini akan diperbarui setiap kali sprint selesai.

Dokumen ini **bukan** tempat mencatat roadmap, changelog, atau keputusan desain.

Gunakan dokumen lain sesuai fungsinya:

* ROADMAP.md → Rencana pengembangan.
* CHANGELOG.md → Riwayat perubahan.
* DECISION.md → Keputusan arsitektur.
* TODO.md → Pekerjaan aktif.
