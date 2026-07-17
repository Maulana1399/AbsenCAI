# FEATURE

> Feature Catalog for KJA Event Manager

---

# Feature Status

| Status         | Meaning                                 |
| -------------- | --------------------------------------- |
| 🔵 Planned     | Sudah dirancang tetapi belum dikerjakan |
| 🟡 Development | Sedang dikembangkan                     |
| 🟢 Stable      | Sudah selesai dan stabil                |
| ⚪ Future       | Belum menjadi prioritas                 |
| 🔴 Deprecated  | Tidak digunakan lagi                    |

---

# Priority

| Priority | Description                            |
| -------- | -------------------------------------- |
| P0       | Critical (Harus selesai sebelum event) |
| P1       | High                                   |
| P2       | Medium                                 |
| P3       | Low                                    |
| P4       | Future                                 |

---

# Core Module

## Authentication

Status

🟢 Stable

Priority

P0

Sprint

0

Features

* Login
* Logout
* Session
* Password

Future

* Google Login
* OTP
* SSO

---

## Dashboard

Status

🟡 Development

Priority

P0

Sprint

1

Features

* Dashboard Admin
* Dashboard Divisi
* Dashboard PJ Regu
* Live Progress
* Statistics

Future

* TV Dashboard
* Public Dashboard

---

## Person

Status

🟡 Development

Priority

P0

Sprint

1

Features

* CRUD Person
* Import Excel
* Search
* History

Future

* Universal Person Database

---

## Registration

Status

🟢 Stable

Priority

P0

Sprint

0

Features

* Registrasi
* Registrasi Ulang
* Validation
* Search

Future

* Self Registration
* Online Registration

---

## Attendance

Status

🟡 Development

Priority

P0

Sprint

1

Features

* QR Scan
* Manual Input
* Attendance Code
* Attendance History
* Hadir
* Izin
* Alfa
* Session
* Live Status

Future

* Offline Mode
* Face Verification
* GPS Validation

---

## QR & Label

Status

🟡 Development

Priority

P0

Sprint

2.5.1

Features

* Generate Individual QR
* Download PNG
* Download SVG
* Batch QR Export
* Print Label 4×4

Future

* ID Card
* Canva Export
* PDF Layout

---

## Report

Status

🟡 Development

Priority

P0

Sprint

1

Features

* Export Excel
* Export PDF
* Rekap Per Regu
* Rekap Per Desa
* Rekap Per Kelompok
* Rekap Belum Hadir

Future

* Scheduled Report

---

## Permission

Status

🟢 Completed for Current Operational Scope

Priority

P1

Sprint

2

Features

* [x] Surat Izin — create, submit, approve, reject, cancel, return
* [x] Print Surat — A5 landscape template with Kop Surat
* [x] Return Tracking — selectable return date, attendance cleanup
* [ ] Riwayat Izin

Notes:

* Full implementation with service layer, Livewire UI, database migrations, authorization gates, and tests.
* `jenis_izin` (`pulang`/`keluar`) support added.
* Print uses browser-native print — no PDF library dependency.

Known Limitation:

* EditSesi page references `SuratIzin.sesi_id` as editable session data — needs architectural review.

---

## Scoring

Status

🔵 Planned

Priority

P1

Sprint

2

Features

* Bonus
* Penalty
* Leaderboard
* Ranking

Future

* Achievement

---

## Audit

Status

🟢 Activity Log Integration Completed

Priority

P1

Sprint

2

Features

* [x] Activity Log Foundation — model, service, read-only UI, Surat Izin integration, tests
* [x] Print Log — Surat Izin print, QR label print views (single/batch/A4) logged via ActivityLogService
* [x] Export Log — participant data Excel export logged via ActivityLogService
* [x] QR Log — single QR download and batch QR export logged via ActivityLogService

Notes:

* `ActivityLogService::log()` provides a single entry point for all audit logging with automatic user/IP/user-agent detection.
* Logged modules: `surat_izin` (created/submitted/approved/rejected/returned), `print` (print_viewed), `export` (exported), `qr` (downloaded/batch_exported).
* Logs are written after successful business operations only — no false logs on failure/forbidden.
* User fallback: "Sistem" when user_id is null or user deleted.
* Schema is extensible: properties JSON column, polymorphic subject, nullable ip_address/user_agent.
* Physical print limitation: `print_viewed` action records print-page generation/access, not guaranteed physical printer completion.
* Low-level `QRService::generatePng()` is intentionally NOT logged to prevent duplicate logs from internal rendering.
* Latest verified test baseline: 186 tests, 432 assertions.

---

## Competition

Status

⚪ Future

Priority

P3

Sprint

4

Features

* Jadwal
* Bracket
* Penilaian
* Juara
* Sertifikat

---

## Certificate

Status

⚪ Future

Priority

P3

Sprint

5

Features

* Auto Generate
* QR Verification
* Download

---

## Event

Status

⚪ Future

Priority

P2

Sprint

3

Features

* Multi Event
* Event Template
* Event Archive

---

## Category

Status

⚪ Future

Priority

P2

Sprint

3

Features

* Multi Category
* Multi Level
* Multi Class

---

## Venue

Status

⚪ Future

Priority

P2

Sprint

3

Features

* Multi Venue
* Venue Dashboard
* Room Management

---

## Organization

Status

⚪ Future

Priority

P3

Sprint

6

Features

* Multi Organization
* Multi Branch
* White Label

---

## Storage

Status

⚪ Future

Priority

P2

Sprint

2

Features

* Nextcloud Integration
* TrueNAS Integration
* Document Management

---

## Notification

Status

⚪ Future

Priority

P3

Sprint

6

Features

* Email
* WhatsApp
* Push Notification

---

## API

Status

⚪ Future

Priority

P4

Sprint

6

Features

* REST API
* OAuth
* API Token

---

## Mobile

Status

⚪ Future

Priority

P4

Sprint

6

Features

* Android
* iOS
* Offline Sync

---

# Current MVP

Target MVP (CAI)

✅ Authentication

✅ Registration

✅ Attendance

✅ Dashboard

✅ Report

🟡 QR

🟡 Permission

🟡 UI

---

# Future Product

Target KJA Event Manager

* Universal Person Database
* Multi Event
* Multi Venue
* Competition Module
* Certificate Module
* Commercial Platform

---

# Module Dependency

```text
Authentication
      │
      ▼
Person
      │
      ▼
Registration
      │
      ▼
Attendance
      │
      ├─────────────┐
      ▼             ▼
Report       Permission
      │             │
      └──────┬──────┘
             ▼
         Dashboard
             │
             ▼
         Competition
             │
             ▼
        Certificate
```

---

# Development Rules

Semua fitur baru wajib memiliki:

* Tujuan
* Status
* Priority
* Sprint
* Dependency
* Dokumentasi
* Database Design
* Testing Checklist

Tidak diperbolehkan membuat fitur baru tanpa memperbarui FEATURE.md terlebih dahulu.

---

# Current Development Focus

Sprint 1

P0

* Attendance Code
* Internal QR Generator
* Manual Attendance
* Attendance Status
* Print QR
* Export
* Dashboard
* UI Refactor

Semua fitur di luar Sprint aktif masuk ke Backlog hingga Sprint berjalan selesai.
