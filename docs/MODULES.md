# KJA Event Manager Modules

**Roadmap V1** = ✅ **100% COMPLETE** — Semua modul bertanda 🟢 adalah V1.

**Roadmap V2** = Competition V1 ✅ **COMPLETE** (Sprint 7–10), Public Portal ✅ (Sprint 9.0), Event Dashboard ✅ (Sprint 10.0). Competition Engine generic masih 📋 **Planned**. Lihat `VISION_V2.md`.

Status Legend

🟢 Stable (V1 — Complete)

🟡 Development

🔴 Planned (V1 deferred)

📋 Planned (V2)

⚪ Future (Beyond V2)

---

# Core

🟢

- Authentication
- User
- Role
- Permission

---

# Master Data

🟢 Landing page + all global master data CRUD complete.

| Entity | Scope | Route | UI Status |
|--------|-------|-------|-----------|
| **Person** | Global (no event_id) | `/person` | Full CRUD ✅ |
| **Desa** | Global (no event_id) | `/desa` | Full CRUD ✅ |
| **Kelompok** | Global (no event_id) | `/kelompok` | Full CRUD ✅ |

Master Data landing page at `/master-data` serves as navigation hub with clickable cards.

Notes:
- Menu Master Data di sidebar adalah single link menuju `/master-data`
- Person, Desa, Kelompok adalah global master data (reusable lintas event)
- Person CRUD hanya mengelola data identitas global — tidak membuat Participation
- Person Delete dilindungi safety guard
- Regu sudah dikeluarkan dari Master Data — reklasifikasi sebagai Legacy CAI Operational
- Venue dan CategoryDefinition bersifat event-scoped — tidak masuk Master Data

RBAC:
- Route protection S2: `can:view-master-data` / `can:manage-master-data`
- Sidebar: `@can('view-master-data')`
- Hanya **super_admin** (sejak Permission Engine — ability platform dibatasi ke SuperAdmin; Admin bypass hanya untuk event abilities; sebelumnya admin & sekretariat)

---

# User Management

🟢

- User Index (list users)
- User Create (new user)
- User Edit (profile + role)
- User Reset Password (admin reset)
- User Delete (with safety rules)
- Activity Log integration (created, updated, role_changed, password_reset, deleted)

Notes:
- Hanya Super Admin yang dapat mengakses (`manage-users`)
- Service layer: `UserManagementService`
- Route: `/users` (middleware `can:manage-users`)
- Delete safety: cannot delete self, cannot delete last Super Admin

---

# Event

🟢

- Event (CRUD, event_type, ActiveEventContext)
- Event Role / Committee
- Venue
- Rundown
- Category (CategoryDefinition + ActivityCategory)
- Activity Group / Activity
- Activity Registration

Current (V2 Roadmap Progress)

- ✅ **Competition V1** (Sprint 7–10) — Module-based competition implementation (Match Status, Ready Detection, Match Center, Viewer, Result Dialog, Officials, Bracket)
- ✅ **Public Portal** (Sprint 9.0) — Public homepage, event detail, schedule, bracket, announcements
- ✅ **Event Dashboard** (Sprint 10.0) — Overview cards, live matches, today's schedule, quick actions

Future (V2 Roadmap Remaining)

- Blueprint Event (V2)
- Scoring Engine (V2)
- Venue Management (V2)
- Event Template
- Event Archive

# Registration

🟢

- Registrasi (CAI + Pengajian)
- Registrasi Ulang
- Search Person
- Self Registration
- Import Excel (CAI + Pengajian bulk import)
- Manual Participant Entry (Pengajian)

---

# Import Framework

🔲 **IF-01 (audit & desain) COMPLETE** — ✅ **IF-02 (engine infrastructure) COMPLETE** — IF-03+ NOT STARTED.

Standard tunggal untuk seluruh import aplikasi (UI/UX, lifecycle, validation, preview, summary, commit, testing). Golden standard = Import Massal Pengajian. IF-02 menghidupkan skeleton `app/Services/Import/*` (stage pipeline nyata, runner dispatch, DI, exceptions, konsolidasi DTO) — **belum dipakai modul mana pun, perilaku tidak berubah**.

| Import | Status | Route |
|--------|--------|-------|
| **Desa** | 🟡 legacy form POST — migrasi IF-05 | `POST /import/desa` |
| **Kelompok** | 🟡 legacy form POST — migrasi IF-06 | `POST /import/kelompok` |
| **Regu** | 🟡 legacy form POST — migrasi IF-07 | `POST /import/regu` |
| **Peserta** | 🟡 legacy form POST — migrasi IF-08 | `POST /import/peserta` |
| **Pengajian (Import Massal)** | 🟢 golden standard — migrasi ke framework IF-04 | `/pengajian/admin/import-massal` |

Kandidat baru (IF-11+): Person, Competition, Kategori, Kelas, Venue, Schedule, Committee, Attendance, Activity/Rundown, Access Grant.

Detail: `docs/import-audit.md`, `docs/import-framework.md`.

---

# Attendance

🟢

- QR Scan (via AttendanceService + QRIdentityResolver)
- Attendance Code (KJA-XXXXXXXX format)
- Session (SesiAbsensi)
- Manual Input (Hadir + Izin via Scan Livewire)
- Izin / Alfa / Hadir summary
- History

---

# Pengajian Attendance

🟢

- Token-based desa operator access (DesaAccessGrant)
- Self-attendance via QR (SelfAttendance)
- Operator-assisted attendance (DesaDashboard)
- Identity correction workflow
- Regional Report (event-scoped)
- Desa-level Report

---

# Dashboard

🟢

Current

- Statistik (total peserta, desa, kelompok, regu)
- Attendance summary (Hadir/Izin/Alfa)
- Event-scoped (via ActiveEventContext)
- Regu filter
- Session management

Future

- Universal Dashboard
- Division Dashboard
- Live Monitoring
- DashboardService (business logic extraction) — presenter layer `app/Services/Dashboard/*` sudah ada

---

# Report

🟢

- Excel Export (PesertaExport, ActivityRegistrationExport)
- Rekap Peserta (event-scoped, Participation-based)
- Rekap Absensi (event-scoped, session-based)
- Regional Report (Pengajian, with filters)
- Desa-level Report (Pengajian)

Future

- PDF Export
- Scheduled Report
- Violation Report
- Score Report

# Permission

🟢

- Surat Izin (create, submit, approve, reject, cancel, return)
- Print Surat (A5 landscape template with Kop Surat)
- Return Tracking (selectable return date, attendance cleanup)
- Surat Izin Activity Logging

Future

- Riwayat Izin History

---

# Audit

🟢

- Activity Log Foundation (model, service, read-only UI)
- Print Log (Surat Izin, QR label print logging)
- Export Log (participant Excel export logging)
- QR Log (single download + batch export logging)

---

# Scoring (V2 — Scoring Engine)

📋

- Scoring Engine — generic (Versus, Score, Time, Distance, Ranking, Pass/Fail)
- Template Penilaian dapat dibuat tanpa coding
- Multiple komponen dengan bobot berbeda

---

# Competition V1 (Sprint 7–10)

🟢

- Match Status (Scheduled / Ready / Playing / Waiting Result / Finished)
- Automatic Ready Detection
- Match Center — operator controls Start/Finish
- Viewer — public display, auto shows Playing + Next Ready
- Match Result Dialog — winner, reason, notes
- Match Officials — assign officials, permission layer
- Single Elimination Bracket — 4/8/16/32 participants, auto-advance
- Public Portal — homepage, event detail, schedule, bracket, announcements
- Event Dashboard — overview cards, live matches, quick actions

---

# Competition (V2 — Competition Engine)

📋

- Competition Engine — generic engine untuk semua format kompetisi
- Scoring Engine — generic scoring (Versus, Score, Time, Distance, Ranking, Pass/Fail)
- Blueprint Event — konfigurasi awal event

V2 tidak akan membuat modul khusus per jenis lomba. Semua dikonfigurasi melalui engine.

---

# QR & Label

🟢

- QRService (PNG generation via BaconQrCode)
- QRIdentityResolver (resolves attendance_code → Participation)
- BatchQRExportService (bulk export to storage)
- PrintEngine + Label4x4Template (4×4 cm QR labels)
- Single + Batch + A4 print views
- QR Log integration (ActivityLog)

Future

- SVG generation
- PDF Export
- ID Card

---

# Certificate (V2 — Certificate Engine)

📋

- Generate sertifikat otomatis berdasarkan hasil kompetisi
- QR Verification
- Template dapat dikonfigurasi

---

# Notification

⚪

- Broadcast

- Announcement

- Reminder

---

# Document

⚪

Integrasi Nextcloud

---

# Media

⚪

- Gallery

- Event Album

- Documentation

---

# API

⚪

REST API

---

# Mobile

⚪

Android

iOS

---

# Commercial

⚪

- License

- White Label

- Marketplace

- Add-on Module

---

# V2 Roadmap (Planned)

> Lihat `VISION_V2.md` untuk detail.

Blueprint Event

✅ Competition V1 (Sprint 7–10) — Module-based competition implementation

Scoring Engine

🟡 Venue Management — Venue CRUD V1 ada; hierarki Master/Event/Arena Planned

🟡 Live Schedule Engine — jadwal + status match ada; estimasi realtime Planned

✅ Public Dashboard (Sprint 9.0) — Public portal with event detail, schedule, bracket

🟡 Announcement Engine — Competition announcements live; generic engine Planned

Certificate Engine

Mobile App

Public API

---

# Future Add-on (Beyond V2)

Attendance Plus

Finance

Inventory

Medical

Transportation

Accommodation

Volunteer

Media Center

Analytics

AI Assistant

---

# Dependency

Core

↓

Master Data

↓

Event

↓

Registration

↓

Attendance

↓

Scoring

↓

Competition

↓

Report

↓

Certificate

↓

Commercial