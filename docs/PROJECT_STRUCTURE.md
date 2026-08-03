# PROJECT STRUCTURE

> Complete mapping of KJA Event Manager architecture, modules, and components.

---

## 1. HIGH-LEVEL ARCHITECTURE

```
Internet
  │
  Cloudflare Tunnel
  │
  Mini PC → Proxmox → Rocky Linux (Laravel + SQLite/MariaDB)
  │
  └── KJA Event Manager (Laravel 12 + Livewire v3 + Flux UI + Tailwind v4)
```

### Canonical Data Flow

```
Person (master identity)
  │ nama, jenis_kelamin, tanggal_lahir, desa_id, kelompok_id
  ▼
Participation (event-scoped membership)
  │ participant_number, attendance_code, regu_id, jenis_peserta, status_registrasi
  ▼
EventAttendance (canonical attendance fact)
  │ status (hadir/izin), method (scan/manual/surat_izin)
  ▼
ActivityRegistration (optional event activity enrollment)
```

### Legacy Compatibility Layer

```
peserta (legacy table) ←── LegacyPesertaMapping ──→ Person
peserta ←── LegacyParticipationMapping ──→ Participation
Absensi (legacy historical reads only — no longer written)
IzinAbsensi (legacy + canonical — still written for legacy path)
```

---

## 2. DIRECTORY STRUCTURE

```
├── app/
│   ├── Actions/            (empty — planned for future)
│   ├── Console/Commands/   (10 Artisan commands)
│   ├── Enums/              (Role.php — 9 roles)
│   ├── Exceptions/         (empty)
│   ├── Exports/            (4 export classes)
│   ├── Helpers/            (empty)
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Auth/       (VerifyEmailController)
│   │       ├── ImportDataController.php
│   │       └── PublicEventController.php
│   ├── Imports/            (4 import classes)
│   ├── Livewire/           (96+ components, 16 subdirectories)
│   ├── Models/             (39 models)
│   ├── Providers/          (AppServiceProvider)
│   ├── Services/           (43 services, 14 subdirectories)
│   └── Support/            (ActiveEventContext, EventOwnership, EventRolePermissionDefaults)
│
├── bootstrap/
├── config/                 (13 config files)
├── database/
│   ├── factories/
│   ├── migrations/         (84 migration files)
│   └── seeders/            (10 seeders + DatabaseSeeder)
│
├── docs/                   (80+ documentation files)
│   ├── ai/                 (27 AI-related docs)
│   ├── design/             (9 design system docs)
│   ├── ui-blueprint/       (11 UI blueprint files)
│   └── runbooks/           (1 runbook)
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/              (134 Blade templates)
│       ├── components/
│       ├── livewire/       (component-specific views)
│       └── partials/
│
├── routes/
│   ├── web.php             (main routes — 355 lines)
│   ├── auth.php            (auth routes — 36 lines)
│   └── console.php         (Artisan commands — 55 lines)
│
├── tests/
│   ├── Feature/
│   ├── Unit/
│   ├── diagnostic/
│   ├── Pest.php
│   └── TestCase.php
│
└── public/
    └── storage/
```

---

## 3. MODULE MAP

### 3.1 Authentication & User Management
| Component | Path | Status |
|-----------|------|--------|
| Login | `Livewire/Auth/Login.php` | ✅ Stable |
| Register (User) | `Livewire/Auth/Register.php` | ✅ Stable |
| Self Register (Public) | `Livewire/Registrasi/SelfRegister.php` | ✅ Stable |
| Forgot Password | `Livewire/Auth/ForgotPassword.php` | ✅ Stable |
| Reset Password | `Livewire/Auth/ResetPassword.php` | ✅ Stable |
| Confirm Password | `Livewire/Auth/ConfirmPassword.php` | ✅ Stable |
| Verify Email | `Livewire/Auth/VerifyEmail.php` | ✅ Stable |
| Logout | `Livewire/Actions/Logout.php` | ✅ Stable |
| User CRUD | `Livewire/MasterData/User/*` (5 components) | ✅ Stable |
| User Management Service | `Services/User/UserManagementService.php` | ✅ Stable |

### 3.2 Event Management
| Component | Path | Status |
|-----------|------|--------|
| Event Model | `Models/Event.php` | ✅ Stable |
| Active Event Context | `Support/ActiveEventContext.php` | ✅ Stable |
| Event Index/CRUD | `Livewire/Event/Index.php` | ✅ Stable |
| Event Dashboard | `Livewire/Event/Dashboard.php` | ✅ Stable |
| Event Switcher | `Livewire/Event/EventSwitcher.php` | ✅ Stable |
| Edit Status | `Livewire/Event/EditStatus.php` | ✅ Stable |
| Event Role Manager | `Livewire/Event/EventRoleManager.php` | ✅ Stable |
| Committee Management | `Livewire/Event/CommitteeManagement.php` | ✅ Stable |
| Event Access Service | `Services/Event/EventAccessService.php` | ✅ Stable |

### 3.3 Master Data (Global)
| Component | Path | Status |
|-----------|------|--------|
| Person CRUD | `Livewire/MasterData/Person/*` (4 components) | ✅ Stable |
| Desa CRUD | `Livewire/Database/Desa/*` (5 components) | ✅ Stable |
| Kelompok CRUD | `Livewire/Database/Kelompok/*` (5 components) | ✅ Stable |
| Regu CRUD | `Livewire/Database/Regu/*` (5 components) | ✅ Stable (Legacy CAI) |
| Person Model | `Models/Person.php` | ✅ Stable |
| Person Legacy Sync | `Services/Person/PersonLegacySyncService.php` | ✅ Stable |
| Person Duplicate Detection | `Services/Person/PersonDuplicateDetectionService.php` | ✅ Stable |

### 3.4 CAI Registration
| Component | Path | Status |
|-----------|------|--------|
| Registration (Peserta) | `Livewire/Database/Peserta/TambahPeserta.php` | ✅ Stable |
| Re-registration | `Livewire/Registrasi/Ulang.php` | ✅ Stable |
| Self Register | `Livewire/Registrasi/SelfRegister.php` | ✅ Stable |
| Registration Service | `Services/Registration/RegistrationService.php` | ✅ Stable |
| Placement Service | `Services/Placement/PlacementService.php` | ✅ Stable |
| Import Peserta | `Livewire/Database/Peserta/ImportPeserta.php` | ✅ Stable |
| Import Controller | `Http/Controllers/ImportDataController.php` | ✅ Stable |

### 3.5 CAI Attendance
| Component | Path | Status |
|-----------|------|--------|
| Scan Dashboard | `Livewire/Dashboard/Scan.php` | ✅ Stable |
| Session CRUD | `Livewire/Database/Sesi/*` (4 components) | ✅ Stable |
| Attendance Service | `Services/Attendance/AttendanceService.php` | ✅ Stable |
| Attendance Read Service | `Services/Attendance/AttendanceReadService.php` | ✅ Stable |
| Attendance Parity | `Services/Attendance/AttendanceParityService.php` | ✅ Stable |
| Legacy Participation Resolver | `Services/Attendance/LegacyParticipationResolver.php` | ✅ Stable |
| Participation Resolver | `Services/Attendance/ParticipationResolver.php` | ✅ Stable |
| Attendance Exception Service | `Services/Attendance/AttendanceExceptionService.php` | ✅ Stable |

### 3.6 Surat Izin (Permission Letter)
| Component | Path | Status |
|-----------|------|--------|
| Surat Izin Index | `Livewire/SuratIzin/Index.php` | ✅ Stable |
| Surat Izin Create | `Livewire/SuratIzin/Create.php` | ✅ Stable |
| Surat Izin Service | `Services/Attendance/SuratIzinService.php` | ✅ Stable |
| Surat Izin Backfill | `Services/Attendance/SuratIzinBackfillService.php` | ✅ Stable |
| Print View | `resources/views/surat-izin/print.blade.php` | ✅ Stable |

### 3.7 QR & Labels
| Component | Path | Status |
|-----------|------|--------|
| QR Label Index | `Livewire/QRLabel/Index.php` | ✅ Stable |
| QR Service | `Services/QR/QRService.php` | ✅ Stable |
| QR Identity Resolver | `Services/QR/QRIdentityResolver.php` | ✅ Stable |
| Batch QR Export | `Services/QR/BatchQRExportService.php` | ✅ Stable |
| Print Engine | `Services/Print/PrintEngine.php` | ✅ Stable |
| Label 4x4 Template | `Services/Print/Templates/Label4x4Template.php` | ✅ Stable |

### 3.8 Reports
| Component | Path | Status |
|-----------|------|--------|
| Rekap Peserta | `Livewire/Rekap/Peserta/RekapPeserta.php` | ✅ Stable |
| Rekap Absensi | `Livewire/Rekap/Absensi/RekapAbsensi.php` | ✅ Stable |
| Peserta Export | `Exports/PesertaExport.php` | ✅ Stable |
| Activity Registration Report | `Livewire/Rekap/Activity/ActivityRegistrationReport.php` | ✅ Stable |
| ~~Committee Report~~ | — | ❌ Dihapus di Sprint 3.1 cleanup |
| ~~Rundown Report~~ | — | ❌ Dihapus di Sprint 3.1 cleanup |

### 3.9 Pengajian Desa Module
| Component | Path | Status |
|-----------|------|--------|
| Enter Token | `Livewire/Pengajian/EnterToken.php` | ✅ Stable |
| Desa Dashboard | `Livewire/Pengajian/DesaDashboard.php` | ✅ Stable |
| Manual Entry | `Livewire/Pengajian/ManualEntry.php` | ✅ Stable |
| Self Attendance | `Livewire/Pengajian/SelfAttendance.php` | ✅ Stable |
| QR Print | `Livewire/Pengajian/QrPrint.php` | ✅ Stable |
| Regional Report | `Livewire/Pengajian/RegionalReport.php` | ✅ Stable |
| Identity Correction Review | `Livewire/Pengajian/IdentityCorrectionReview.php` | ✅ Stable |
| Admin — Access Index | `Livewire/Pengajian/Admin/AccessIndex.php` | ✅ Stable |
| Admin — Manual Entry | `Livewire/Pengajian/Admin/ManualEntry.php` | ✅ Stable |
| Admin — Import Massal | `Livewire/Pengajian/Admin/ImportMassal.php` | ✅ Stable |
| Desa Access Service | `Services/Pengajian/DesaAccessService.php` | ✅ Stable |
| Attendance Service | `Services/Pengajian/PengajianAttendanceService.php` | ✅ Stable |
| Identity Service | `Services/Pengajian/PengajianIdentityService.php` | ✅ Stable |
| Correction Service | `Services/Pengajian/IdentityCorrectionService.php` | ✅ Stable |
| Import Service | `Services/Pengajian/PengajianImportService.php` | ✅ Stable |
| Desa Report Service | `Services/Pengajian/PengajianDesaReportService.php` | ✅ Stable |
| Regional Report Service | `Services/Pengajian/PengajianRegionalReportService.php` | ✅ Stable |

### 3.10 Activity & Committee (S3.9)
| Component | Path | Status |
|-----------|------|--------|
| Activity Group Model | `Models/ActivityGroup.php` | ✅ Stable |
| Activity Model | `Models/Activity.php` | ✅ Stable |
| Activity Registration Model | `Models/ActivityRegistration.php` | ✅ Stable |
| Category Definition Model | `Models/CategoryDefinition.php` | ✅ Stable |
| Activity Category Model | `Models/ActivityCategory.php` | ✅ Stable |
| Venue Model | `Models/Venue.php` | ✅ Stable |
| Rundown Model | `Models/Rundown.php` | ✅ Stable |
| Rundown Item Model | `Models/RundownItem.php` | ✅ Stable |
| Event Role Model | `Models/EventRole.php` | ✅ Stable |
| Event Committee Model | `Models/EventCommitteeAssignment.php` | ✅ Stable |
| Activity Registration Service | `Services/Activity/ActivityRegistrationService.php` | ✅ Stable |
| Activity Schedule Service | `Services/Activity/ActivityScheduleService.php` | ✅ Stable |
| Event Committee Service | `Services/Activity/EventCommitteeService.php` | ✅ Stable |

### 3.10b Competition V1 (Sprint 7–10)
| Component | Path | Status |
|-----------|------|--------|
| Match Center | `Livewire/Competition/MatchCenter.php` | ✅ Stable |
| Bracket Manager | `Livewire/Competition/BracketManager.php` | ✅ Stable |
| Viewer | `Livewire/Competition/Viewer.php` | ✅ Stable |
| Official Panel | `Livewire/Competition/OfficialPanel.php` | ✅ Stable |
| Operator Dashboard | `Livewire/Competition/OperatorDashboard.php` | ✅ Stable |
| Competition Dashboard | `Livewire/Competition/Dashboard.php` | ✅ Stable |
| Participant List | `Livewire/Competition/ParticipantList.php` | ✅ Stable |
| Registration | `Livewire/Competition/Registration.php` | ✅ Stable |
| Schedule | `Livewire/Competition/Schedule/*` | ✅ Stable |
| Report | `Livewire/Competition/Report/*` | ✅ Stable |
| Venue CRUD | `Livewire/Competition/Venue/Index.php` | ✅ Stable |
| Category CRUD | `Livewire/Competition/Category/Index.php` | ✅ Stable |
| Class CRUD | `Livewire/Competition/Class/Index.php` | ✅ Stable |
| Competition Export | `Exports/CompetitionExport.php` | ✅ Stable |
| Public Event Controller | `Http/Controllers/PublicEventController.php` | ✅ Stable |

### 3.11 Audit
| Component | Path | Status |
|-----------|------|--------|
| Activity Log Index | `Livewire/Audit/ActivityLogIndex.php` | ✅ Stable |
| Activity Log Service | `Services/Audit/ActivityLogService.php` | ✅ Stable |
| Activity Log Model | `Models/ActivityLog.php` | ✅ Stable |

### 3.12 Settings
| Component | Path | Status |
|-----------|------|--------|
| Profile | `Livewire/Settings/Profile.php` | ✅ Stable |
| Password | `Livewire/Settings/Password.php` | ✅ Stable |
| Appearance | `Livewire/Settings/Appearance.php` | ✅ Stable |
| Delete User Form | `Livewire/Settings/DeleteUserForm.php` | ✅ Stable |

---

## 4. DATABASE TABLES (84 Migrations)

| Table | Type | Scope | Status |
|-------|------|-------|--------|
| `users` | Core | Global | Active |
| `people` | Core | Global (master identity) | Active |
| `events` | Core | Global | Active |
| `participations` | Core | Event-scoped | Active |
| `pesertas` | Legacy | Global (legacy CAI) | Active (read/write) |
| `desas` | Master Data | Global | Active |
| `kelompoks` | Master Data | Global | Active |
| `regus` | Legacy CAI | Global (event-scoped planned) | Active |
| `sesi_absensis` | CAI | Event-scoped (has event_id) | Active |
| `absensis` | Legacy | Global (historical only) | Active (read-only writes stopped) |
| `izin_absensis` | CAI | Event-scoped (has peserta_id) | Active |
| `event_attendances` | Canonical | Event-scoped | Active |
| `surat_izins` | CAI | Event-scoped (has event_id) | Active |
| `activity_logs` | Core | Global | Active |
| `activity_groups` | S3.9 | Event-scoped | Active |
| `activities` | S3.9 | Event-scoped | Active |
| `activity_registrations` | S3.9 | Event-scoped | Active |
| `category_definitions` | S3.9 | Event-scoped | Active |
| `activity_categories` | S3.9 | Event-scoped | Active |
| `venues` | S3.9 | Event-scoped | Active |
| `rundowns` | S3.9 | Event-scoped | Active |
| `rundown_items` | S3.9 | Event-scoped | Active |
| `event_roles` | S3.9 | Event-scoped | Active |
| `event_committee_assignments` | S3.9 | Event-scoped | Active |
| `desa_access_grants` | Pengajian | Event-scoped | Active |
| `legacy_peserta_mappings` | Legacy Bridge | Global | Active |
| `legacy_participation_mappings` | Legacy Bridge | Event-scoped | Active |
| `cai_participant_replacements` | CAI | Global | Active |
| `identity_correction_requests` | Pengajian | Event-scoped | Active |
| `competition_categories` | Competition | Event-scoped | Active |
| `competition_classes` | Competition | Event-scoped | Active |
| `competition_registrations` | Competition | Event-scoped | Active |
| `competition_schedules` | Competition | Event-scoped | Active |
| `competition_schedule_entries` | Competition | Event-scoped | Active |
| `competition_outcomes` | Competition | Event-scoped | Active |
| `competition_match_officials` | Competition | Event-scoped | Active |
| `competition_brackets` | Competition | Event-scoped | Active |
| `competition_bracket_matches` | Competition | Event-scoped | Active |
| `competition_announcements` | Competition | Event-scoped | Active |

---

## 5. ROLE & PERMISSION (18 Gate Abilities)

See `docs/PERMISSION.md` and `docs/ROLE_MATRIX.md` for full matrix.

**4 platform abilities** (`users.role`): `view-master-data`, `manage-master-data`, `manage-events`, `manage-users` — SuperAdmin (via `Gate::before` bypass) + Admin (`manage-events`).

**14 event-scoped abilities** (Permission Engine — `User → Person → EventCommitteeAssignment → EventRole.permissions`): `view-dashboard`, `manage-registration`, `manage-participants`, `manage-attendance`, `manage-sessions`, `manage-qr-labels`, `manage-secretariat`, `manage-import`, `view-reports`, `manage-pengajian`, `view-activity-log`, `manage-matches`, `manage-officials`, `submit-result`.

| Role | Code | 
|------|------|
| Super Admin | `super_admin` — full access |
| Admin | `admin` — manage all events (platform) |
| Ketua Event | `ketua_event` — event-scoped (via EventRole assignment) |
| Sekretariat | `sekretariat` — via EventRole permissions |
| PJ Divisi | `pj_divisi` — via EventRole permissions |
| Operator Registrasi | `operator_registrasi` — via EventRole permissions |
| Operator Scan | `operator_scan` — via EventRole permissions |
| Juri | `juri` — scoring (V2) |
| Viewer | `viewer` — read-only reports |

> **PENTING:** Sejak Permission Engine (Design C), ability event-scoped di-resolve dari **EventRole permissions**, bukan dari `users.role`. Role selain SuperAdmin/Admin harus punya `EventCommitteeAssignment` + `EventRole` aktif dengan ability yang sesuai.

---

## 6. EVENT FLOW

### CAI Event Flow
```
Login → Dashboard (Platform) → Select/Pick CAI Event → Event Dashboard
  ├── Registrasi Peserta (manual/import/self)
  ├── Manage Sesi Absensi
  ├── Scan QR / Manual Attendance
  ├── Surat Izin (create/approve/reject)
  ├── Reports (Rekap Peserta/Absensi, Export Excel)
  ├── QR & Label (print/download/batch)
  └── Activity Log
```

### Pengajian Event Flow
```
Public: /pengajian → Enter Token → Desa Dashboard
  ├── QR Attendance (public self-scan)
  ├── Operator Attendance (search & confirm)
  ├── Identity Correction (submit/review)
  └── Reports (Desa-level + Regional)

Admin: /pengajian/admin/access → Manage Access Tokens
       /pengajian/admin/manual-entry → Manual Participant Entry
       /pengajian/admin/import-massal → Bulk Import
       /koreksi-data → Identity Correction Review
```

---

## 7. KEY SERVICE DEPENDENCIES

```
ActivityLogService
  ├── used by: SuratIzinService, UserManagementService, print routes, QR export
  ├── used by: RekapPeserta (exportExcel), QRLabel Index

RegistrationService
  ├── used by: ManualParticipantRegistrationService, PengajianAttendanceService
  ├── used by: PengajianImportService
  └── uses: PlacementService (static)

PlacementService
  └── used by: RegistrationService, PersonLegacySyncService, peserta model

QRService
  ├── used by: BatchQRExportService, PrintEngine
  └── used by: route closures (QR label print)

SuratIzinService
  ├── uses: AttendanceExceptionService, ActivityLogService
  └── used by: SuratIzin Livewire components

EventAccessService
  └── used by: AppServiceProvider (Gate definitions), EventSwitcher

DesaAccessService
  └── used by: AccessIndex Livewire, grant route closures

IdentityCorrectionService
  └── used by: IdentityCorrectionReview Livewire

PengajianAttendanceService
  ├── uses: RegistrationService
  └── used by: SelfAttendance Livewire, DesaDashboard Livewire
```

---

## 8. AUTHORIZATION CHAIN

```
Route Middleware (web.php)
  └── middleware('can:{ability}')
      ├── applied to: 20+ routes
      └── returns 403 if unauthorized

Livewire Gate Authorization
  └── Gate::authorize('{ability}')
      ├── applied to: 30+ mutation methods
      └── returns 403 if unauthorized

Sidebar Visibility
  └── @can('{ability}') / @canany([...])
      ├── applied to: all sidebar menu items
      └── UI-only convenience layer

Event-Scoped (KetuaEvent)
  └── EventAccessService::canAccess(user, event)
      ├── checks: role=ketua_event + person_id + EventCommitteeAssignment
      └── enforced in: EventSwitcher::switchTo(), Gate closures
```

---

## 9. FRONTEND ARCHITECTURE

```
Layout Hierarchy:
  auth/           → login, register, forgot/reset password
  auth/split      → split-screen layout
  auth/simple     → simple centered layout (Pengajian)
  auth/card       → card-centered layout
  app/            → main authenticated layout
  app/sidebar     → sidebar navigation (event-type aware)
  app/header      → top bar
  platform        → platform-level layout (no sidebar)
  pengajian       → Pengajian-specific layout

Component Framework:
  Flux UI (pro)   → navlist, button, input, select, modal, badge, table
  Tailwind v4     → utility classes, dark mode
  Alpine.js       → interactive elements (modal, dropdown)
  Livewire v3     → reactive components, form handling
```

---

## 10. ARTISAN COMMANDS

| Command | Purpose | File |
|---------|---------|------|
| `user:set-role` | Set user role | `Console/Commands/UserSetRole.php` |
| `attendance:diagnose` | Diagnose attendance issues | `Console/Commands/AttendanceDiagnose.php` |
| `attendance:parity` | Check attendance parity (legacy vs canonical) | `Console/Commands/AttendanceParity.php` |
| `attendance:status` | Show attendance status | `Console/Commands/AttendanceStatus.php` |
| `audit:legacy-data` | Audit legacy data integrity | `Console/Commands/AuditLegacyData.php` |
| `event-roles:audit` | Audit EventRole codes/permissions | `Console/Commands/AuditEventRoles.php` |
| `pengajian:create-desa-grant` | Create desa access grant | `Console/Commands/CreateDesaGrant.php` |
| `db:info` | Show database info | `Console/Commands/DatabaseInfo.php` |
| `diagnose:design-c` | Design C diagnostics | `Console/Commands/DesignCDiagnostics.php` |
| `app:reset-event-data` | Reset event data | `Console/Commands/ResetEventData.php` |
| `kja:identity-backfill` | (in console.php) Legacy identity repair | `routes/console.php` |

---

## 11. EXPORTS & IMPORTS

| Class | Type | Purpose |
|-------|------|---------|
| `PesertaExport` | Export | Participant data Excel |
| `ActivityRegistrationExport` | Export | Activity registration Excel |
| `PersonImportTemplateExport` | Export | Person import template Excel |
| `CompetitionExport` | Export | Competition registration/schedule/outcome CSV |
| `DesaImport` | Import | Desa data import (Excel/CSV) |
| `KelompokImport` | Import | Kelompok data import |
| `ReguImport` | Import | Regu data import |
| `PesertaImport` | Import | CAI participant import |

---

## 12. TEST SUITE

| Type | Count | 
|------|-------|
| Unit Tests | (In Feature + Unit dirs) |
| Feature Tests | ~1944 tests / 4648 assertions |
| Pest Framework | PHPUnit + Pest |
| Coverage | Authorization, Services, Livewire, Integration, Regression |

Test baseline: **1944 passed / 4648 assertions / 0 failures**
