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

## QR

Status

🟡 Development

Priority

P0

Sprint

1

Features

* Internal Generator
* Batch Generate
* Print 4×4
* PDF
* Regenerate QR

Future

* QR Rotation

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

🔵 Planned

Priority

P1

Sprint

2

Features

* Surat Izin
* Print Surat
* Return Tracking
* History

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
