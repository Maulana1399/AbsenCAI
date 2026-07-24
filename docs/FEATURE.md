# FEATURE

> Feature Catalog for KJA Event Manager

---

# Feature Status

## Master Data

Status

🟢 Landing page + all global CRUD complete.

Priority

P1

Sprint

Feature

- Master Data landing page at `/master-data` with navigation cards
- Sidebar single link "Master Data" → `/master-data`
- Person CRUD
- Desa CRUD (existing)
- Kelompok CRUD (existing)

Notes:
- Regu dikeluarkan dari Master Data (Legacy CAI Operational — route `/regu` tetap ada)
- Master Data accessible without active event context
- Appears for all authenticated users (no RBAC yet)

Future

- Venue CRUD
- CategoryDefinition CRUD
- RBAC for Master Data

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

🟢 Stable

Priority

P0

Sprint

1

Features

* Dashboard Admin
* Dashboard Divisi
* Dashboard PJ Regu (Deferred)
* Live Progress
* Statistics
* Attendance summary (Hadir / Izin / Alfa)

Notes:

* Dashboard Admin stabil dan event-scoped.
* Dashboard PJ Regu dan Live Monitoring — DEFERRED.
* DashboardService belum diimplementasikan — stats dihitung inline di Livewire.

Future

* TV Dashboard
* Public Dashboard

---

## Person

Status

🟢 Stable

Priority

P0

Sprint

1

Features

* CRUD Person (via RegistrationService + Participation)
* **Dedicated Person master data page** — `/person` (IndexPerson Livewire)
* Create Person (modal form, global identity only, no Participation created)
* Edit Person (modal form, preserves relationships)
* Delete Person (safety-guarded: blocks if has Participations/LegacyPesertaMapping)
* Search by name
* Pagination
* Person sidebar menu in Master Data group
* Import Excel (via RegistrationService)
* Universal Person Database (Person model + People table)

Notes:

* Person adalah canonical identity — satu identitas per orang.
* Person dihubungkan ke Event via Participation.
* Legacy peserta compatibility melalui LegacyPesertaMapping.
* Person CRUD tidak membuat Participation, tidak melakukan auto-placement, tidak generate NIP.
* Person adalah global master data — tidak bergantung pada ActiveEventContext.

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

🟢 Stable

Priority

P0

Sprint

1

Features

* QR Scan (via AttendanceService)
* Manual Input (manual hadir/izin)
* Attendance Code (primary QR payload)
* Attendance History
* Hadir / Izin / Alfa summary
* Session (SesiAbsensi)
* Live Status
* Surat Izin integration (IzinAbsensi)

Future

* Offline Mode
* Face Verification
* GPS Validation

---

## QR & Label

Status

🟢 Stable

Priority

P0

Sprint

2.5.1

Features

* Generate Individual QR (QRService + QRIdentityResolver)
* Download PNG
* Batch QR Export (BatchQRExportService)
* Print Label 4×4 (PrintEngine + Label4x4Template)
* QR Log (ActivityLog integration)

Notes:

* QR content menggunakan attendance_code.
* SVG generation — deferred (stub exists).
* PDF Export — deferred.

Future

* ID Card
* Canva Export
* PDF Layout

---

## Report

Status

🟢 Stable

Priority

P0

Sprint

1

Features

* Export Excel (PesertaExport, ActivityRegistrationExport)
* Rekap Per Regu / Per Desa / Per Kelompok
* Rekap Belum Hadir
* Rekap Absensi event-scoped
* Regional Report (Pengajian)
* Desa-level Report (Pengajian)

Future

* Export PDF
* Scheduled Report

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
* [x] Auditable commands — CreateDesaGrant (BackfillLegacyPeserta dihapus di PGM.18 Sprint 1 karena command sudah tidak memiliki production caller)

Notes:

* `ActivityLogService::log()` provides a single entry point for all audit logging with automatic user/IP/user-agent detection.
* Logged modules: `surat_izin` (created/submitted/approved/rejected/returned), `print` (print_viewed), `export` (exported), `qr` (downloaded/batch_exported), `grant`, `backfill`.
* Logs are written after successful business operations only — no false logs on failure/forbidden.
* User fallback: "Sistem" when user_id is null or user deleted.
* Schema is extensible: properties JSON column, polymorphic subject, nullable ip_address/user_agent.
* Physical print limitation: `print_viewed` action records print-page generation/access, not guaranteed physical printer completion.
* Low-level `QRService::generatePng()` is intentionally NOT logged to prevent duplicate logs from internal rendering.
* Latest documented baseline: 459 tests, 1140 assertions (S3.9E). Actual count needs verification.

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

🟢 Stable

Priority

P2

Sprint

3

Features

* Multi Event (Event model, events table, ActiveEventContext, event switcher UI)
* Event CRUD (create, edit, archive/activate)
* Event Type (cai / pengajian)
* Event Role / Committee (EventRole + EventCommitteeAssignment)
* Activity Groups & Activities

Future

* Event Template
* Event Archive

---

## Category

Status

🟢 Stable (CategoryDefinition + ActivityCategory)

Priority

P2

Sprint

3

Features

* Category Definitions (event-scoped)
* Activity Categories (link activities to allowed categories)
* requires_category flag on activities

---

## Venue

Status

🟢 Stable (Venue + Rundown + RundownItem)

Priority

P2

Sprint

3

Features

* Multi Venue (event-scoped Venue model)
* Rundown management (Rundown + RundownItem)
* Time validation (ends_at > starts_at)
* Parallel activities support

Future

* Venue Dashboard
* Schedule conflict detection

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

Target MVP (CAI Operational + Pengajian Desa)

✅ Authentication

✅ Registration (CAI + Pengajian)

✅ Attendance (CAI QR scan + Pengajian self/operator)

✅ Dashboard

✅ Report (Excel export, Rekap Peserta/Absensi, Regional Report)

✅ QR (QRService, BatchQRExport, PrintEngine)

✅ Permission (Surat Izin — created/submit/approve/reject/return)

✅ Audit (Activity Log, Print Log, Export Log, QR Log)

✅ UI (Dark Mode, Responsive, Flux UI)

✅ Multi Event (Event model, ActiveEventContext, event switcher)

✅ Event Role/Committee (EventRole + EventCommitteeAssignment)

✅ Venue/Rundown (Venue, Rundown, RundownItem)

✅ Category (CategoryDefinition, ActivityCategory)

✅ Pengajian Desa MVP (Token access, Self-attendance, Regional Report, Bulk Import)

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

**UI Bug Fix Sprint** — 9 active bugs (Branding, Navigation, Access Token, Filters, Responsive)

Prioritas tertinggi saat ini:

1. Perbaikan branding CAI → KJA Event Manager (landing page + login page)
2. Perbaikan navigasi default KJA logo (event-aware routing)
3. Hard-delete untuk revoked access token
4. Audit/masking raw token di modal creation
5. Verifikasi filter Regional Report
6. Menu "Pengajian" hanya muncul di event Pengajian

Semua fitur di luar Sprint aktif masuk ke Backlog hingga Sprint berjalan selesai.

---

## User Management

Status

🟢 Stable

Priority

P1

Sprint

0

Features

* User Index — daftar user dengan pencarian
* User Create — tambah user baru (nama, email, password, role)
* User Edit — ubah profil dan role user
* User Reset Password — reset password user oleh admin
* User Delete — hapus user dengan safety rules

Notes:

* Hanya Super Admin yang dapat mengakses (`manage-users`)
* Delete safety: tidak bisa hapus diri sendiri
* Delete safety: tidak bisa hapus Super Admin terakhir
* Password selalu di-hash menggunakan Laravel Hash
* Password tidak pernah diekspos di response/view/log
* Semua aksi tercatat di Activity Log (created, updated, role_changed, password_reset, deleted)
* Service layer: `UserManagementService`
* Route: `/users` (middleware `can:manage-users`)

Future

* Bulk user operations
* User import/export
* User activity history
