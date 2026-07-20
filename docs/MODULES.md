# KJA Event Manager Modules

Status Legend

🟢 Stable

🟡 Development

🔴 Planned

⚪ Future

---

# Core

🟢

- Authentication
- User
- Role
- Permission

---

# Master Data

🟢

- Person
- Desa
- Kelompok
- Group

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

Future

- Competition
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

# Scoring

🔴

- Master Point

- Bonus

- Penalty

- Leaderboard

---

# Competition

⚪

- Jadwal

- Bracket

- Arena

- Match

- Judge

- Winner

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

# Certificate

⚪

- Generate

- Verification QR

- Print

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

# Planned Add-on

Attendance Plus

Competition

Certificate

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