# FEATURE INVENTORY

> Complete inventory of all implemented features based on actual code. Not based on roadmap.

---

## Authentication & Security

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Login | ✅ | `Livewire/Auth/Login.php` | Email + password |
| Logout | ✅ | `Livewire/Actions/Logout.php` | POST-based |
| Session Management | ✅ | Laravel Session | Standard session-based |
| Remember Me | ✅ | Login component | "remember" checkbox |
| Forgot Password | ✅ | `Livewire/Auth/ForgotPassword.php` | Email link |
| Reset Password | ✅ | `Livewire/Auth/ResetPassword.php` | Token-based |
| Confirm Password | ✅ | `Livewire/Auth/ConfirmPassword.php` | For sensitive actions |
| Email Verification | ✅ | `Livewire/Auth/VerifyEmail.php` + Controller | Route-level verified middleware |
| Role-Based Access | ✅ | RBAC S1–S7 | 9 roles, 15 gates, event-scoped |
| Super Admin Bypass | ✅ | `AppServiceProvider::boot()` | Gate::before() |
| Rate Limiting | ✅ | EnterToken (5/min), SelfAttendance (30/min) | Throttle middleware |

## Dashboard

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Platform Dashboard | ✅ | `Livewire/Dashboard/PlatformDashboard.php` | Global dashboard |
| Event Dashboard | ✅ | `Livewire/Event/Dashboard.php` | Per-event dashboard |
| Scan Dashboard | ✅ | `Livewire/Dashboard/Scan.php` | QR + manual attendance |
| Attendance Summary | ✅ | Dashboard | Hadir / Izin / Alfa counts |
| Registration Progress | ✅ | Event Dashboard | Peserta counts |
| Session Management | ✅ | Dashboard | Set active session |
| Event Switcher | ✅ | `Livewire/Event/EventSwitcher.php` | Switch active event |

## Event Management

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Event CRUD | ✅ | `Livewire/Event/Index.php` | Create, edit, archive, activate |
| Event Type | ✅ | `Event::isCai() / isPengajian()` | `event_type` column |
| Active Event Context | ✅ | `Support/ActiveEventContext.php` | Session-based singleton |
| Event Dashboard | ✅ | `Livewire/Event/Dashboard.php` | Event-specific |
| Event Role Manager | ✅ | `Livewire/Event/EventRoleManager.php` | Committee roles |
| Committee Management | ✅ | `Livewire/Event/CommitteeManagement.php` | Person → Role → Event |
| Event Access Service | ✅ | `Services/Event/EventAccessService.php` | Assignment-based access |

## Master Data (Global)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Master Data Landing | ✅ | `resources/views/master-data/index.blade.php` | Navigation hub |
| Person CRUD | ✅ | `Livewire/MasterData/Person/*` | 4 components |
| Person Search | ✅ | IndexPerson | Name search |
| Person Delete Guard | ✅ | DeletePerson | Blocks if has participations |
| Person→Legacy Sync | ✅ | `PersonLegacySyncService` | Identity sync to peserta |
| Desa CRUD | ✅ | `Livewire/Database/Desa/*` | 5 components |
| Kelompok CRUD | ✅ | `Livewire/Database/Kelompok/*` | 5 components |
| Regu CRUD | ✅ | `Livewire/Database/Regu/*` | 5 components (Legacy CAI) |
| User CRUD | ✅ | `Livewire/MasterData/User/*` | 5 components |
| Person Duplicate Detection | ✅ | `PersonDuplicateDetectionService` | Name + desa matching |

## CAI Registration

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Participant Registration | ✅ | `Livewire/Database/Peserta/TambahPeserta.php` | Manual create |
| Self Registration | ✅ | `Livewire/Registrasi/SelfRegister.php` | Public registration |
| Re-registration | ✅ | `Livewire/Registrasi/Ulang.php` | Update existing |
| Auto Placement | ✅ | `PlacementService::autoPlacement()` | Least-filled regu |
| Participant Number Generation | ✅ | `PlacementService::generateParticipantNumber()` | KL/KP prefix |
| Attendance Code Generation | ✅ | `RegistrationService::generateAttendanceCode()` | KJA-XXXXXXXX |
| Import Peserta (Excel/CSV) | ✅ | `Livewire/Database/Peserta/ImportPeserta.php` | Bulk import |
| Import Regu | ✅ | `ImportDataController::regu()` | Excel/CSV |
| Edit Peserta | ✅ | `Livewire/Database/Peserta/EditPeserta.php` | Update |
| Hapus Peserta | ✅ | `Livewire/Database/Peserta/HapusPeserta.php` | Delete |
| Ganti Peserta (Replacement) | ✅ | `Livewire/Database/Peserta/GantiPeserta.php` | Participant swap |
| Registration Service | ✅ | `Services/Registration/RegistrationService.php` | Centralized |
| Manual Registration (Pengajian) | ✅ | `ManualParticipantRegistrationService.php` | For Pengajian |

## CAI Attendance

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| QR Scan Attendance | ✅ | `AttendanceService::processScan()` | Attendance code based |
| Manual Attendance (Hadir) | ✅ | `Scan::manualHadir()` | Manual entry |
| Manual Attendance (Izin) | ✅ | `Scan::manualIzin()` | Manual entry |
| Active Session | ✅ | SesiAbsensi.aktif (per-event) | Session management |
| Session CRUD | ✅ | `Livewire/Database/Sesi/*` | 4 components |
| Duplicate Prevention | ✅ | AttendanceService | Per (participation, session) |
| Hadir/Izin/Alfa Summary | ✅ | Dashboard + Reports | Derived status |
| Attendance History | ✅ | Scan Livewire + Rekap | |
| Event Attendance Canonical | ✅ | `EventAttendance` model | New canonical table |
| Identity Resolution | ✅ | `QRIdentityResolver` | attendance_code → Participation |
| Legacy Participation Resolver | ✅ | `LegacyParticipationResolver` | Legacy fallback |
| Attendance Parity | ✅ | `AttendanceParityService` | Legacy vs canonical |

## Surat Izin (Permission Letter)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Create Surat Izin | ✅ | `Livewire/SuratIzin/Create.php` | Draft creation |
| Submit Surat Izin | ✅ | `SuratIzinService::submit()` | Change status to pending |
| Approve Surat Izin | ✅ | `SuratIzinService::approve()` | Auto-create izin records |
| Reject Surat Izin | ✅ | `SuratIzinService::reject()` | |
| Cancel Surat Izin | ✅ | SuratIzin Index | Cancel from draft/pending |
| Return Tracking | ✅ | `SuratIzinService::markReturned()` | Return date + cleanup |
| Print Surat Izin | ✅ | `resources/views/surat-izin/print.blade.php` | A5 landscape |
| Jenis Izin (Pulang/Keluar) | ✅ | `jenis_izin` column | |
| Sync New Session | ✅ | `SuratIzinService::syncNewSession()` | New session → izin |

## QR & Labels

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| QR PNG Generation | ✅ | `QRService::generatePng()` | BaconQrCode |
| QR SVG Generation (stub) | ✅ | `QRService::generateSvg()` | Stub only |
| QR Identity Resolution | ✅ | `QRIdentityResolver::resolve()` | Attendance code → Participation |
| Individual QR Download | ✅ | `QRLabel Index::downloadPng()` | |
| Batch QR Export | ✅ | `BatchQRExportService::export()` | To storage |
| Print Label 4x4 (Single) | ✅ | `PrintEngine::label4x4()` | Single label |
| Print Label Batch (Filtered) | ✅ | Route `qr-label.print/filtered` | Filtered batch |
| Print Label A4 (35/page) | ✅ | Route `qr-label.print/a4` | Grid layout |
| QR Activity Log | ✅ | downloadPng, exportBatch logged | |

## Reports & Exports

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Rekap Peserta | ✅ | `Livewire/Rekap/Peserta/RekapPeserta.php` | Event-scoped |
| Rekap Absensi | ✅ | `Livewire/Rekap/Absensi/RekapAbsensi.php` | Event-scoped |
| Export Excel (Peserta) | ✅ | `Exports/PesertaExport.php` | |
| Export Excel (Activity Registration) | ✅ | `Exports/ActivityRegistrationExport.php` | S3.9 |
| Export Log | ✅ | ActivityLog logged | |
| Regional Report (Pengajian) | ✅ | `PengajianRegionalReportService` | |
| Desa Report (Pengajian) | ✅ | `PengajianDesaReportService` | |
| Activity Registration Report | ✅ | `Livewire/Rekap/Activity/ActivityRegistrationReport.php` | |
| Committee Report | ✅ | `Livewire/Rekap/Activity/CommitteeReport.php` | |
| Rundown Report | ✅ | `Livewire/Rekap/Activity/RundownReport.php` | |

## Pengajian Desa Module

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Token Entry | ✅ | `Livewire/Pengajian/EnterToken.php` | Public, rate-limited |
| Token Management | ✅ | `Livewire/Pengajian/Admin/AccessIndex.php` | CRUD + revoke |
| Token Generation | ✅ | `DesaAccessService::createGrant()` | Hash + encrypt |
| Token Validation | ✅ | `DesaAccessService::validateToken()` | Hash check |
| Nonce Rotation | ✅ | `DesaAccessService::rotateNonce()` | QR session security |
| Self Attendance (Public QR) | ✅ | `Livewire/Pengajian/SelfAttendance.php` | QR → nonce → attendance |
| Operator Attendance | ✅ | `DesaDashboard` | Search + confirm |
| Manual Participant Entry | ✅ | `ManualParticipantRegistrationService` | |
| Bulk Import (CSV/Excel) | ✅ | `Livewire/Pengajian/Admin/ImportMassal.php` | Preview + execute |
| Person Identity Matching | ✅ | PengajianImportService | Name + desa + dob |
| Identity Correction (Public) | ✅ | `IdentityCorrectionService::submitFromPublicContext()` | |
| Identity Correction (Review) | ✅ | `Livewire/Pengajian/IdentityCorrectionReview.php` | Approve/reject |
| Desa Dashboard | ✅ | `Livewire/Pengajian/DesaDashboard.php` | |
| QR Print (Pengajian) | ✅ | `Livewire/Pengajian/QrPrint.php` | |
| Regional Report | ✅ | `Livewire/Pengajian/RegionalReport.php` | Filters + stats |
| Filter Kombinasi Report | ✅ | Regional/Desa | Status + Method filters |
| Token Revocation | ✅ | `DesaAccessService::revokeGrant()` | |
| Token Deletion (Revoked only) | ✅ | `DesaAccessService::deleteGrant()` | |

## Activity & Committee (S3.9)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Activity Groups | ✅ | `ActivityGroup` model | Event-scoped |
| Activities | ✅ | `Activity` model | Event-scoped |
| Activity Registration | ✅ | `ActivityRegistration` model | Event-scoped |
| Category Definitions | ✅ | `CategoryDefinition` model | Event-scoped |
| Activity Category Link | ✅ | `ActivityCategory` model | |
| Requires Category Flag | ✅ | `activities.requires_category` | Validation |
| Venue CRUD | ✅ | `Venue` model (migration only) | No UI yet |
| Rundown | ✅ | `Rundown` model | Event-scoped |
| Rundown Items | ✅ | `RundownItem` model | Time slots |
| Parallel Activities | ✅ | Supported | Same time, diff venues |
| Event Role (Operational) | ✅ | `EventRole` model | Not RBAC |
| Committee Assignment | ✅ | `EventCommitteeAssignment` model | Person → Role → Event |
| Activity Registration Service | ✅ | `ActivityRegistrationService` | |
| Activity Schedule Service | ✅ | `ActivityScheduleService` | |
| Event Committee Service | ✅ | `EventCommitteeService` | |

## Audit

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Activity Log | ✅ | `ActivityLog` model + Service | Polymorphic |
| Activity Log UI | ✅ | `Livewire/Audit/ActivityLogIndex.php` | Search, filter, paginate |
| Print Log | ✅ | Print routes | print_viewed logged |
| Export Log | ✅ | RekapPeserta::exportExcel() | exported logged |
| QR Log | ✅ | QRLabel Index | downloaded/batch_exported |
| User Audit Log | ✅ | UserManagementService | created/updated/role_changed/password_reset/deleted |
| Surat Izin Audit | ✅ | SuratIzinService | created/submitted/approved/rejected/returned |

## Settings

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Profile Settings | ✅ | `Livewire/Settings/Profile.php` | |
| Password Settings | ✅ | `Livewire/Settings/Password.php` | |
| Appearance Settings | ✅ | `Livewire/Settings/Appearance.php` | Dark mode toggle |
| Delete Account | ✅ | `Livewire/Settings/DeleteUserForm.php` | |

## Imports

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Desa Import (Excel/CSV) | ✅ | `Imports/DesaImport.php` | |
| Kelompok Import (Excel/CSV) | ✅ | `Imports/KelompokImport.php` | |
| Regu Import (Excel/CSV) | ✅ | `Imports/ReguImport.php` | With validation |
| Peserta Import (Excel/CSV) | ✅ | `Imports/PesertaImport.php` | CAI |
| Person Import Template | ✅ | `Exports/PersonImportTemplateExport.php` | |
| Pengajian Bulk Import | ✅ | `Livewire/Pengajian/Admin/ImportMassal.php` | Preview/validate/execute |

## UI Features

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Dark Mode | ✅ | Global | Theme toggle |
| Responsive Mobile | ✅ | All layouts | Breakpoint-aware |
| Contextual Sidebar | ✅ | sidebar.blade.php | CAI vs Pengajian |
| Event Switcher Dropdown | ✅ | EventSwitcher | |
| SPA Navigation | ✅ | wire:navigate | |
| Flux UI Components | ✅ | Buttons, modals, tables, forms | Pro v2 |
| Tailwind v4 | ✅ | Global | |
| Modal Close Controls | ✅ | All modals | PGM.17 fix |
| User Management UI | ✅ | 5 components | Super Admin only |
| Master Data Landing | ✅ | Navigation cards | Person, Desa, Kelompok |

## Console Commands

| Feature | Status | Location |
|---------|--------|----------|
| user:set-role | ✅ | `Console/Commands/UserSetRole.php` |
| attendance:diagnose | ✅ | `Console/Commands/AttendanceDiagnose.php` |
| attendance:parity | ✅ | `Console/Commands/AttendanceParity.php` |
| attendance:status | ✅ | `Console/Commands/AttendanceStatus.php` |
| audit:legacy-data | ✅ | `Console/Commands/AuditLegacyData.php` |
| kja:create-desa-grant | ✅ | `Console/Commands/CreateDesaGrant.php` |
| kja:database-info | ✅ | `Console/Commands/DatabaseInfo.php` |
| kja:design-c-diagnostics | ✅ | `Console/Commands/DesignCDiagnostics.php` |
| kja:reset-event-data | ✅ | `Console/Commands/ResetEventData.php` |
| kja:identity-backfill | ✅ | `routes/console.php` (inline) |
