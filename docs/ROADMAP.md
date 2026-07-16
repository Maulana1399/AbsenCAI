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

## Goal

Melengkapi kebutuhan operasional sekretariat.

## Features

### Permission

* Surat Izin
* Print Surat
* Return Tracking
* Riwayat Izin

---

### Scoring

* Master Point
* Bonus
* Penalty
* Leaderboard
* Riwayat Penilaian

---

### Audit

* Activity Log
* Export Log
* Print Log
* QR Log

---

### Storage

* Nextcloud Integration
* TrueNAS Integration

---

# Sprint 3

## Goal

Transformasi menuju KJA Event Manager.

## Features

* Universal Person Database
* Multi Event
* Multi Role
* Multi Venue
* Multi Category
* Participation History
* Dashboard Universal
* Attendance integration coverage hardening

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
* Full regression suite verified: 70 tests passed, 198 assertions, 0 failures.
* Current next priority: Sprint 2.

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
1. Stabilkan CAI
2. Rapikan Arsitektur
3. Refactor
4. Multi Event
5. Competition
6. Commercial
```

---

# Product Vision

CAI bukan tujuan akhir.

CAI adalah MVP.

KJA Event Manager adalah platform Event Management yang dapat digunakan oleh sekolah, organisasi, komunitas, universitas, hingga penyelenggara kejuaraan dan festival dengan arsitektur modular yang siap dikembangkan dalam jangka panjang.
