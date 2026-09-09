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

🔲 **IF-01 (audit) COMPLETE** — ✅ **IF-02 (engine) COMPLETE** — ✅ **IF-03 (Desa) COMPLETE** — ✅ **IF-04 (Kelompok) COMPLETE** — ✅ **IF-05 (Regu) COMPLETE** — ✅ **IF-06 (Person) COMPLETE** — ✅ **IF-07 (Participation) COMPLETE** — ✅ **IF-08 (Pengajian) COMPLETE** — ✅ **IF-09 (Peserta) COMPLETE** — ✅ **IF-10 (Cleanup — STABLE v1.0) COMPLETE** — IF-11+ NOT STARTED.

**Import Framework v1.0 STABLE.** SELURUH 7 modul import memakai SATU Import Framework (backend + frontend). Tidak ada lagi Maatwebsite import, `Excel::import`, manual coordinator/registry/pipeline, maupun bypass `ImportAdapter`. Dead code orphan telah dihapus.

| Import | Status | Route |
|--------|--------|-------|
| **Desa** | 🟢 **FRAMEWORK** (IF-03) | `POST /import/desa` + `GET /import/desa/template` |
| **Kelompok** | 🟢 **FRAMEWORK** (IF-04 — parameter desa) | `POST /import/kelompok` + `GET /import/kelompok/template` |
| **Regu** | 🟢 **FRAMEWORK** (IF-05) | `POST /import/regu` + `GET /import/regu/template` |
| **Person** | 🟢 **FRAMEWORK** (IF-06 — Design C) | `POST /import/person` + `GET /import/person/template` |
| **Participation** | 🟢 **FRAMEWORK** (IF-07 — parameter event) | wizard di halaman Registrasi + `GET /events/{event}/registrasi/import-participation/template` |
| **Pengajian (Import Massal)** | 🟢 **FRAMEWORK** (IF-08 — behavior-preserving) | `/pengajian/admin/import-massal` (+ template route tetap) |
| **Peserta** | 🟢 **FRAMEWORK** (IF-09 — via `RegistrationService`) | `POST /import/peserta` (form partial, UI identik) |

Kandidat baru (IF-12+): Competition, Kategori, Kelas, Venue, Schedule, Committee, Attendance, Activity/Rundown, Access Grant.

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

# Competition Foundation (Event Membership — 2026-08)

🟢

- **5 format lomba** pada `competition_classes.format` — `individual_heat`, `individual_mass`, `team_vs_team`, `team_mass`, `individual_vs_individual` (`App\Support\CompetitionFormat`).
- **Status lomba** pada `competition_classes.status` — draft / registration_open / registration_closed / ready / running / finished / cancelled (`App\Support\CompetitionStatus`).
- **Teams** — `competition_teams` (event-scoped, satu kelompok = satu team per lomba) + `competition_team_members` (players + substitutes via `competition_registration_id`).
- **Auto team formation** — `CompetitionTeamFormationService` (ukuran team = kelompok terkecil; sisa = cadangan; transactional; TIDAK memakai regus).
- **Team member management** — `CompetitionTeamService` (tambah/hapus/pindah player↔cadangan/shuffle; validasi kelompok & kelas & satu-team).
- UI `Competition/Team/Index` + route `competition.teams` (gate `manage-registration`).

---

# Competition Heat Manager + Heat Format Builder (2026-08-26)

🟢

- **Format tersimpan per babak** — `competition_heat_formats` (per `competition_class_id` + `round`): `participants_per_heat` + `qualifiers_per_heat` (auto-generate tanpa hardcode).
- **Orchestrator** — `CompetitionHeatManagerService`: `validateFormat`/`upsertFormat`/`deleteFormat`, `computeHeatCount`, `generateRound` (idempoten, event-scoped), `generateNextRound` (via `advanceRound` + `isRoundCompleteForAdvancement`), `removeRoundSchedules` (reject `round_started`) — Individual Heat & Team Heat saja.
- **Helper additive R4H** — `CompetitionMultiRoundHeatService::isRoundCompleteForAdvancement(classId, round, isTeam)`.
- **UI** — menu Heat di sidebar (grup Operasional, `can:manage-events`), `App\Livewire\Competition\Heat\Index`, route `competition.heat.index` (`events/{event}/competition/heat`): daftar kelas heat, kartu heat dengan badge status + daftar peserta, aksi Generate Heat / Generate Round Berikutnya / Hapus Round, lalu Peserta / Match Center / Input Hasil (reuse `EntryManager`, `MatchCenter`, `OutcomeManager`).
- Menggunakan service R4H apa adanya (`rankHeat`, `advanceRound`, `roundSchedules`, `isFinalRound`) + hasil `competition_heat_results` — tanpa mengubah Bracket, Mass, VS, atau `result_type`.

---

# Competition (V2 — Competition Engine)

📋

- Competition Engine — generic engine untuk semua format kompetisi
- Scoring Engine — generic scoring (Versus, Score, Time, Distance, Ranking, Pass/Fail)
- Blueprint Event — konfigurasi awal event

V2 tidak akan membuat modul khusus per jenis lomba. Semua dikonfigurasi melalui engine.

> **Catatan domain:** `CompetitionClass` = lomba (contoh "Tarik Tambang Putra"); `CompetitionCategory` = pengelompokan; format/team/result/schedule melekat pada `CompetitionClass`. Competition **Team** berbeda dari **Regu** (Regu adalah master data operasional CAI lama — tidak dipakai untuk competition).

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