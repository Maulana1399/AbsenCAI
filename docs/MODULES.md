# KJA Event Manager Modules

**Roadmap V1** = ✅ **100% COMPLETE** — Semua modul bertanda 🟢 adalah V1.

**Roadmap V2** = 📋 **Planned** — Lihat `VISION_V2.md`. Competition, Certificate, dan Scoring masuk V2.

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
- Hanya super_admin, admin, sekretariat

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

Future (V2 Roadmap)

- Blueprint Event (V2)
- Competition Engine (V2)
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
- Venue Dashboard
- Live Monitoring
- DashboardService (business logic extraction)

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

Competition Engine

Scoring Engine

Venue Management

Live Schedule Engine

Public Dashboard

Announcement Engine

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