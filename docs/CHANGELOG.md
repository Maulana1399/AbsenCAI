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
* `SuratIzinService` now depends on `ActivityLogService` via constructor injection — logs are written after successful business operations only
* `routes/web.php`: all 4 print route closures now inject `ActivityLogService::log()` after authorization/validation
* `App\Livewire\QRLabel\Index`: `downloadPng()` and `generateBatchExport()` now inject `ActivityLogService::log()` after successful generation
* `App\Livewire\Rekap\Peserta\RekapPeserta`: `exportExcel()` now injects `ActivityLogService::log()` before returning download

## Planned

Belum ada perubahan.

Semua rencana pengembangan dicatat pada:

* ROADMAP.md
* TODO.md

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
