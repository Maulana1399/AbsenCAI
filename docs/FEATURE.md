# FEATURE

> Feature Catalog for KJA Event Manager
>
> **Roadmap V1** = ✅ **100% COMPLETE**
> **Roadmap V2** = 🟡 **Partial** — Competition V1, Public Portal, Event Dashboard COMPLETE — Lihat `VISION_V2.md`

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
- Protected with `can:view-master-data` / `can:manage-master-data` (S2 RBAC)
- Appears for roles with `view-master-data` ability (saat ini: **super_admin** — Permission Engine membatasi ability platform ke SuperAdmin; Admin bypass hanya untuk event abilities)

Future

- CategoryDefinition CRUD (`category_definitions` — event-scoped; UI `Competition/Category` mengelola `competition_categories`)

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
* Dashboard presenter layer (`app/Services/Dashboard/*`) sudah ada — `CaiDashboardPresenter`, `CompetitionDashboardPresenter`, `PengajianDashboardPresenter`, `DashboardPresenterFactory`. `DashboardService` klasik tetap tidak ada (stats via presenter + Livewire).

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

* ~~EditSesi page references `SuratIzin.sesi_id`~~ — sudah diverifikasi: `EditSesi` memakai `SesiAbsensi` dengan event-scoping, tidak mereferensikan `SuratIzin.sesi_id`. Limitation usang.

---

## Scoring (V2 — Scoring Engine)

> Scoring Engine adalah bagian dari Roadmap V2 — Event Operating System.
> Bersifat generic, tidak hardcode jenis penilaian.

Status

📋 Planned (V2)

Priority

P2

Roadmap V2

3

Features

* Generic Scoring Engine — Versus, Score, Time, Distance, Ranking, Pass/Fail
* Template Penilaian dapat dibuat dan diedit tanpa coding
* Multiple komponen penilaian dengan bobot berbeda
* Leaderboard dan Ranking otomatis

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
* Baseline saat ini: **1944 passed / 4648 assertions / 0 failures** (pasca Sprint 3.1 + 3.2).

---

## Competition V1 (Sprint 7–10)

> Implementasi competition module secara tradisional (per-module, bukan generic engine).
> Ini adalah fondasi operasional untuk Competition V2 (generic engine) di masa depan.

Status

🟢 Stable

Features

* Match Status (Scheduled / Ready / Playing / Waiting Result / Finished)
* Automatic Ready Detection (berdasarkan required_participants)
* Match Center — kontrol pertandingan langsung (start, finish, official assignment)
* Viewer — tampilan publik otomatis (Playing + Selanjutnya)
* Match Result Dialog — winner selection, finish reason, notes
* Match Officials — assign referee/judge/scorer/supervisor ke match
* Official Panel — official submit hasil pertandingan
* Single Elimination Bracket — 4, 8, 16, 32 participants, auto-advance
* Public Portal — homepage, event detail, schedule, bracket, announcements
* Event Dashboard — overview cards, live matches, today's schedule

## Competition Foundation (Teams + Formats + Status — 2026-08)

🟢 Stable

* **5 format lomba** — `competition_classes.format` (individual_heat, individual_mass, team_vs_team, team_mass, individual_vs_individual) via `App\Support\CompetitionFormat`.
* **Status lomba** — `competition_classes.status` (draft/registration_open/registration_closed/ready/running/finished/cancelled) via `App\Support\CompetitionStatus`.
* **Teams** — `competition_teams` (event-scoped; satu kelompok = satu team per lomba) + `competition_team_members` (players + substitutes).
* **Auto team formation** — `CompetitionTeamFormationService` (ukuran team = kelompok terkecil; sisa = cadangan; transactional; tidak memakai Regu).
* **Team management** — `CompetitionTeamService` (tambah/hapus/pindah player↔cadangan/shuffle; validasi kelompok/kelas/satu-team).
* **UI** — `Competition/Team/Index` + route `competition.teams` (gate `manage-registration`).

## Competition (V2 — Generic Engine)

> Competition Engine adalah bagian dari Roadmap V2 — Event Operating System.
> Tidak akan ada modul khusus per jenis lomba. Semua dikonfigurasi melalui engine generic.

Status

📋 Planned (V2)

Priority

P2

Roadmap V2

1

Features (V2 Scope — Competition Engine)

* Generic Competition Engine — Bracket, League, Round Robin, Double Elimination
* Scoring Engine — Versus, Score, Time, Distance, Ranking, Pass/Fail
* Template Penilaian dapat dibuat tanpa coding
* Blueprint Event sebagai konfigurasi awal

Lihat `docs/VISION_V2.md` untuk detail.

---

## Certificate (V2 — Certificate Engine)

> Certificate Engine adalah bagian dari Roadmap V2 — Event Operating System.

Status

📋 Planned (V2)

Priority

P3

Roadmap V2

8

Features

* Auto Generate berdasarkan hasil kompetisi
* QR Verification
* Download
* Template sertifikat dapat dikonfigurasi

Lihat `docs/VISION_V2.md` untuk detail.

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

### V1 Scope (Existing — Stable)

Status

🟢 Stable (Venue + Rundown + RundownItem + Competition Venue CRUD)

Priority

P2

Sprint

3

Features

* Multi Venue (event-scoped Venue model)
* Venue CRUD UI — `Livewire/Competition/Venue/Index.php` (create/edit/toggle), route `competition.venue.index`
* Rundown management (Rundown + RundownItem)
* Time validation (ends_at > starts_at)
* Parallel activities support

### V2 Scope (Planned — Venue Management)

> Venue Management adalah bagian dari Roadmap V2 — Event Operating System.

Status

📋 Planned (V2)

Roadmap V2

4

Fitur baru V2:

* Master Venue — data venue global reusable
* Event Venue — penggunaan Master Venue pada event tertentu
* Arena / Room — sub-lokasi dalam venue
* Satu venue dapat digunakan banyak event
* Data venue tidak hilang setelah event selesai

Lihat `docs/VISION_V2.md` untuk detail.

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

# V2 Target — Event Operating System

Target KJA Event Manager V2

* Universal Person Database ✅ (V1 Complete)
* Multi Event ✅ (V1 Complete)
* Multi Venue ✅ (V1 foundation, V2 reusable)
* Competition Engine (V2 — replaces old Competition Module concept)
* Scoring Engine (V2)
* Blueprint Event (V2)
* Certificate Engine (V2)
* Venue Management (V2)

# Future (Beyond V2)

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

**Pengajian Desa MVP (PGM.12–PGM.20)** — ALL COMPLETE
**Sprint series:** Sprint 1–3.3 COMPLETE. Sprint 4 — NOT STARTED.

Semua sprint utama sudah selesai:
1. PGM.12–PGM.17 Pengajian Desa MVP ✅ COMPLETE
2. PGM.18 Physical Mapping Cleanup ✅ COMPLETE
3. PGM.19 Physical Regu Retirement ✅ COMPLETE
4. PGM.20 Legacy NIP Retirement ✅ COMPLETE
5. RBAC S1–S7 ✅ COMPLETE
6. User Management ✅ COMPLETE
7. UI Bug Fix Sprint ✅ COMPLETE
8. UI Standardization ✅ COMPLETE
9. Sprint 1 — Platform Consolidation ✅ COMPLETE
10. Sprint 2 — RBAC & Permission Engine ✅ COMPLETE
11. Sprint 3.1 — Technical Debt Cleanup ✅ COMPLETE
12. Sprint 3.2 — Architecture Hardening ✅ COMPLETE

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
