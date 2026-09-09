# FEATURE INVENTORY

> Complete inventory of all implemented features based on actual code. Not based on roadmap.
>
> **Roadmap V1** = ✅ **100% COMPLETE** — Semua fitur di bawah adalah V1.
> **Roadmap V2** = 🟡 **Partial** — Competition V1 COMPLETE; generic engine Planned — Lihat bagian "V2 Planned Features" di bawah.

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
| Role-Based Access | ✅ | RBAC S1–S7 + Permission Engine | 9 roles, 18 gates, event-scoped |
| Super Admin Bypass | ✅ | `AppServiceProvider::boot()` | Gate::before() |
| Admin Bypass (event) | ✅ | `AppServiceProvider` | `$eventAbility` — Admin bypass event abilities |
| Permission Engine | ✅ | `EventPermissionService` | User→Person→EventCommitteeAssignment→EventRole.permissions |
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
| Manual Attendance (Hadir) | ✅ | `Scan::manualAttend()` | Manual entry |
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
| Export Competition | ✅ | `Exports/CompetitionExport.php` | Registration/Schedule/Outcome CSV |
| Export Log | ✅ | ActivityLog logged | |
| Regional Report (Pengajian) | ✅ | `PengajianRegionalReportService` | |
| Desa Report (Pengajian) | ✅ | `PengajianDesaReportService` | |
| Activity Registration Report | ✅ | `Livewire/Rekap/Activity/ActivityRegistrationReport.php` | |
| ~~Committee Report~~ | ❌ Dihapus | — | Dihapus di Sprint 3.1 cleanup (render view tidak ada) |
| ~~Rundown Report~~ | ❌ Dihapus | — | Dihapus di Sprint 3.1 cleanup (render view tidak ada) |

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
| Venue CRUD | ✅ | `Livewire/Competition/Venue/Index.php` | Create/edit/toggle — route `competition.venue.index` |
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

## Competition V1 (Sprint 7–10)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Match Status (Scheduled/Ready/Playing/Waiting Result/Finished) | ✅ | `CompetitionSchedule` model | |
| Automatic Ready Detection | ✅ | Competition services | based on required_participants |
| Match Center | ✅ | `Livewire/Competition/MatchCenter.php` | Start/finish, official assignment |
| Viewer (public display) | ✅ | `Livewire/Competition/Viewer.php` | Playing + Next |
| Match Result Dialog | ✅ | MatchCenter | Winner, finish reason, notes |
| Match Officials | ✅ | `CompetitionMatchOfficial` + `OfficialPanel` | referee/judge/scorer/supervisor |
| Single Elimination Bracket | ✅ | `CompetitionBracket` + `BracketManager` | 4/8/16/32, auto-advance |
| Public Portal | ✅ | `PublicEventController` + views | homepage, detail, schedule, bracket, announcements |
| Event Dashboard | ✅ | `Livewire/Event/Dashboard.php` | overview cards, live matches, today's schedule |
| Competition Dashboard | ✅ | `Livewire/Competition/Dashboard.php` | Presenter-factory based |
| Competition Export | ✅ | `Exports/CompetitionExport.php` | Registration/Schedule/Outcome CSV |
| Competition Gates | ✅ | `manage-matches`, `manage-officials`, `submit-result` | 3 gate tambahan (total 18) |
| Venue CRUD | ✅ | `Livewire/Competition/Venue/Index.php` | event-scoped |

## Competition Heat Manager + Heat Format Builder (2026-08-26)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Heat Format Builder (per class + round) | ✅ | `CompetitionHeatFormat` + `competition_heat_formats` | `participants_per_heat` + `qualifiers_per_heat`; unique `(competition_class_id, round)`; validated (0/0, qualifiers>participants, format unsupported) |
| Menu Heat (operator) | ✅ | `Livewire/Competition/Heat/Index` + route `competition.heat.index` | Sidebar Operasional, `can:manage-events`; only `individual_heat` / `team_heat` |
| Auto-Generate Heat (Round 1) | ✅ | `CompetitionHeatManagerService::generateRound` | **`participants_per_heat` = source of truth** → `required_participants` + entries per heat; chunk per format; idempotent (`round_exists`) |
| Auto-Generate Round Berikutnya | ✅ | `generateNextRound` | Hitung **qualified pool** (top-N per heat yang selesai) → bangun heat round berikutnya HANYA bila `pool >= participants_per_heat`; reject `no_next_format`/`next_round_exists`/`qualified_pool_insufficient` (tanpa fabrikasi babak) |
| Qualification Per-Heat | ✅ | `CompetitionMultiRoundHeatService::qualifyHeat` | Heat yang selesai langsung menentukan top-N qualified tanpa menunggu sibling heat; reject `heat_incomplete`; dipanggil tombol "Advance Top 2/3" (`OutcomeManager`) |
| Rebuild Existing Round dari Format | ✅ | `CompetitionHeatManagerService::rebuildRound` | Perbaiki heat legacy/misconfigured (mis. 2/heat) → hapus heat belum-dimulai + generate ulang dari format; guard `round_started` & `has_results` |
| Deteksi `needs_rebuild` | ✅ | `Heat/Index` render + blade | Round ditandai bila ada heat yang `required_participants` ≠ `participants_per_heat` → banner amber + tombol **Generate Ulang Babak Ini** |
| Hapus Heat Babak Ini | ✅ | `removeRoundSchedules` | Reset round; reject `round_started` |
| Rank/Input Hasil/Podium | ✅ | `rankHeat`, `OutcomeManager`, `aggregateRoundResults`, `finalizePodium` (R4H) | Ranking, `result_type`, identity tidak diubah |

**Behavior format 5/2** (verifikasi unit test UAT Case A–D):

| Competitors | Heats | `required_participants` per heat | Entries per heat |
|---|---|---|---|
| 5 | 1 | [5] | [5] |
| 9 | 2 | [5, 5] | [5, 4] |
| 10 | 2 | [5, 5] | [5, 5] |
| 4 | 1 | [5] | [4] (bukan 2+2) |

Top-N qualifier per heat selalu `qualifiers_per_heat` (5 → 2 lolos; 9 → 2+2 = 4 lolos). Qualification bersifat **per-heat**: heat yang selesai bisa qualify tanpa menunggu heat lain; round berikutnya dibangun hanya saat pool qualified cukup (pool 2 < kapasitas 4 → tunggu; pool 4 ≥ 4 → 1 heat 4/4). Rebuild tidak pernah otomatis (`round_started`/`has_results` menolak; murni aksi operator).

## Competition Bracket — Perebutan Juara 3 (Bronze Match — 2026-08-27)

| Feature | Status | Location | Notes |
|---------|--------|----------|-------|
| Bronze Match Option | ✅ | `competition_brackets.third_place_match` (boolean, default false) | Migrasi `2026_08_27_000001`; backward-compatible, existing = OFF |
| Bronze Match Flag | ✅ | `competition_bracket_matches.is_third_place` (boolean, default false) | Migrasi `2026_08_27_000002`; `round=1` `position=2`, source = SF1 & SF2 |
| Generate Bronze Match | ✅ | `BracketManager::generate()` + checkbox blade | Hanya saat ON & `totalRounds >= 2`; section "Perebutan Juara 3" di render |
| SF Winner → Final | ✅ | `CompetitionWorkflowService::advanceWinner` / `advanceWinnerTeam` | Lookup next-match exclude `is_third_place`; Bronze winner tidak advance |
| SF Loser → Bronze | ✅ | `advanceLoser` / `advanceLoserTeam` | Hanya bila `third_place_match`; idempotent; slot dari source side; Bronze auto-Ready saat penuh |
| Bronze Podium 3/4 | ✅ | `CompetitionBracketPodiumService::finalizePodiumForSchedule` / `finalizeTeamPodiumForSchedule` | `is_third_place`: winner 3 / loser 4; Final 1/2; OFF: SF losers tied 3; tanpa duplikasi |
| Podium limit 4 | ✅ | `CompetitionResultService::podiumForClass` / `podiumForTeams` `$limit = 3` | Bronze ON memakai limit 4; default 3 tidak berubah |
| Rollback loser | ✅ | `rollbackLoserAdvancement` / `rollbackLoserTeamAdvancement` | Reset semifinal → losernya keluar dari Bronze (bila Bronze belum dimainkan) |
| Played Bronze Protection | ✅ | `playedBronzeEntries()` + `resetMatch` | Bronze `Playing`/`Waiting Result`/`Finished`: entry + outcome Juara 3/4 dilindungi |
| Test | ✅ | `CompetitionBracketBronzePodiumTest` (12 test / 89 assertions) | Bronze OFF & ON (Individual + Team), urutan selesai bebas, re-finalization, rollback, idempotency |

## V2 Planned Features (Event Operating System)

Fitur berikut adalah bagian dari **Roadmap V2**. Semua masih **Planned**, belum diimplementasikan.

| Feature | Status | Notes |
|---------|--------|-------|
| Blueprint Event | 📋 Planned | Konfigurasi awal event — Pengajian, Silat, Olahraga, Festival, Seminar, Custom |
| Competition Engine | 🟡 Partial | Competition V1 (module) COMPLETE; generic engine Planned |
| Scoring Engine | 📋 Planned | Generic — Versus, Score, Time, Distance, Ranking, Pass/Fail |
| Venue Management (Reusable) | 🟡 Partial | Venue CRUD V1 ada; Master Venue → Event Venue → Arena/Room Planned |
| Live Schedule Engine | 🟡 Partial | Jadwal + status match ada; estimasi realtime Planned |
| Public Dashboard | ✅ COMPLETE | Sprint 9.0 — Public Portal tanpa login |
| Announcement Engine | 🟡 Partial | Competition announcements live; generic engine Planned |
| Certificate Engine | 📋 Planned | Generate sertifikat otomatis |
| Mobile App | 📋 Planned | Android/iOS |
| Public API | 📋 Planned | REST API untuk integrasi |

Lihat `docs/VISION_V2.md` untuk dokumentasi lengkap.

---

## Console Commands

| Feature | Status | Location |
|---------|--------|----------|
| user:set-role | ✅ | `Console/Commands/UserSetRole.php` |
| attendance:diagnose | ✅ | `Console/Commands/AttendanceDiagnose.php` |
| attendance:parity | ✅ | `Console/Commands/AttendanceParity.php` |
| attendance:status | ✅ | `Console/Commands/AttendanceStatus.php` |
| audit:legacy-data | ✅ | `Console/Commands/AuditLegacyData.php` |
| pengajian:create-desa-grant | ✅ | `Console/Commands/CreateDesaGrant.php` |
| db:info | ✅ | `Console/Commands/DatabaseInfo.php` |
| diagnose:design-c | ✅ | `Console/Commands/DesignCDiagnostics.php` |
| app:reset-event-data | ✅ | `Console/Commands/ResetEventData.php` |
| event-roles:audit | ✅ | `Console/Commands/AuditEventRoles.php` |
| kja:identity-backfill | ✅ | `routes/console.php` (inline) |
