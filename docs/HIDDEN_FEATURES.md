# HIDDEN FEATURES

> Features that exist in the code but are not visible in the UI, not documented, or require special knowledge to use.

---

## 1. Person Duplicate Detection Service

| Attribute | Value |
|-----------|-------|
| **Feature** | Fuzzy name matching + duplicate person detection |
| **File** | `app/Services/Person/PersonDuplicateDetectionService.php` |
| **Why Hidden** | Service is implemented and tested but has **zero production callers**. No UI exposes it. |
| **How to Use** | Inject `PersonDuplicateDetectionService` and call `detect(string $name, ?int $desaId, ?string $birthDate)` — returns array of potential duplicates with match type (`exact` or `possible`) |
| **Status** | 🟡 Available, not integrated |

## 2. QR SVG Generation (Stub)

| Attribute | Value |
|-----------|-------|
| **Feature** | QR code generation in SVG format |
| **File** | `app/Services/QR/QRService.php::generateSvg()` |
| **Why Hidden** | Method exists but returns empty string (stub). Marked as "deferred" in docs |
| **How to Use** | Not functional yet. PNG generation works via `generatePng()` |
| **Status** | 🟡 Stub only |

## 3. Attendance Parity Audit (CLI)

| Attribute | Value |
|-----------|-------|
| **Feature** | Compares legacy attendance with canonical EventAttendance records |
| **File** | `app/Services/Attendance/AttendanceParityService.php` |
| **Why Hidden** | CLI-only command `attendance:parity`. No UI |
| **How to Use** | `php artisan attendance:parity` — reports matched, missing, orphaned, conflict records |
| **Status** | ✅ Active (CLI only) |

## 4. Design C Diagnostics (CLI)

| Attribute | Value |
|-----------|-------|
| **Feature** | Comprehensive canonical architecture health check (8 metrics) |
| **File** | `app/Console/Commands/DesignCDiagnostics.php` |
| **Why Hidden** | CLI-only command `kja:design-c-diagnostics` |
| **How to Use** | `php artisan kja:design-c-diagnostics` — checks Person ↔ Participation ↔ LegacyPesertaMapping integrity |
| **Status** | ✅ Active (CLI only) |

## 5. Legacy Data Audit (CLI)

| Attribute | Value |
|-----------|-------|
| **Feature** | Full legacy data integrity audit |
| **File** | `app/Console/Commands/AuditLegacyData.php` |
| **Why Hidden** | CLI-only `audit:legacy-data` |
| **How to Use** | `php artisan audit:legacy-data` — scans all legacy peserta for issues |
| **Status** | ✅ Active (CLI only) |

## 6. Reset Event Data (CLI)

| Attribute | Value |
|-----------|-------|
| **Feature** | Resets all event-scoped data for testing/cleanup |
| **File** | `app/Console/Commands/ResetEventData.php` |
| **Why Hidden** | CLI-only `kja:reset-event-data` |
| **How to Use** | `php artisan kja:reset-event-data {event}` — removes participations, attendances, etc. |
| **Status** | ✅ Active (CLI only) |

## 7. Token Encryption at Rest

| Attribute | Value |
|-----------|-------|
| **Feature** | Desa access tokens encrypted using Laravel encryption (double security: bcrypt hash + Laravel encrypt) |
| **File** | `database/migrations/2026_07_28_000002_add_encrypted_token_to_desa_access_grants.php` |
| **Why Hidden** | Only the bcrypt hash is documented. The encrypted_token column for potential decryption is not mentioned |
| **How to Use** | Automatic — `DesaAccessService` handles encryption transparently |
| **Status** | ✅ Active (undocumented) |

## 8. `manage-import` Permission (Implicit)

| Attribute | Value |
|-----------|-------|
| **Feature** | Separate `manage-import` ability for import operations (S5) |
| **Files** | `routes/web.php` (import routes), `AppServiceProvider` (Gate definition) |
| **Why Hidden** | Not visible in UI — users with `manage-participants` or `manage-master-data` might not realize import is a separate permission |
| **How to Use** | Assignable via `user:set-role`. Only admin and sekretariat have it |
| **Status** | ✅ Active |

## 9. Event-Scoped Attendance (KetuaEvent Limitation)

| Attribute | Value |
|-----------|-------|
| **Feature** | KetuaEvent role only works when User has linked Person AND EventCommitteeAssignment |
| **Files** | `EventAccessService.php`, `AppServiceProvider.php` |
| **Why Hidden** | Without person_id + assignment, KetuaEvent has ZERO access despite having a valid role |
| **How to Use** | 1. Link user to Person (User CRUD) → 2. Create EventCommitteeAssignment (Committee Management) → 3. KetuaEvent can now access assigned event |
| **Status** | ✅ Active (must be configured) |

## 10. `PersonImportTemplateExport`

| Attribute | Value |
|-----------|-------|
| **Feature** | Downloadable Excel template for person import |
| **File** | `app/Exports/PersonImportTemplateExport.php` |
| **Why Hidden** | Route exists (`/pengajian/admin/import-massal/template`) but no UI button links to it in the import view |
| **How to Use** | Navigate to `/pengajian/admin/import-massal/template` directly |
| **Status** | ✅ Active (no UI link) |

## 11. `regu` Route — No Gate Protection

| Attribute | Value |
|-----------|-------|
| **Feature** | `/regu` is the only operational route without explicit gate middleware |
| **File** | `routes/web.php` line 62-64 |
| **Why Hidden** | Regu is classified as Legacy CAI Operational, excluded from RBAC S2 Master Data. Only `auth` + `verified` middleware |
| **How to Use** | Any authenticated user can access regu management |
| **Status** | ✅ Active (intentional) |

## 12. Activity / Venue / Category CRUD — No UI

| Attribute | Value |
|-----------|-------|
| **Feature** | Models and migrations exist for ActivityGroup, Activity, ActivityRegistration, Venue, Rundown, CategoryDefinition, EventRole, EventCommitteeAssignment |
| **Files** | All in `app/Models/` with corresponding migrations |
| **Why Hidden** | Only EventRole and EventCommitteeAssignment have Livewire UI. Venue, Rundown, Category have NO CRUD UI yet |
| **How to Use** | Programmatically via the service classes: `ActivityRegistrationService`, `ActivityScheduleService` |
| **Status** | 🟡 Domain ready, UI missing |

## 13. Parallel Identity Correction Paths

| Attribute | Value |
|-----------|-------|
| **Feature** | Two parallel identity correction submission paths exist: `IdentityCorrectionService` (public) and `PengajianIdentityService` (older path) |
| **Files** | Both services in `app/Services/Pengajian/` |
| **Why Hidden** | Known P2 issue documented in TODOs. Both paths exist but may conflict |
| **How to Use** | Current recommended: use `IdentityCorrectionService` for new implementation |
| **Status** | 🟡 Duplicate (known issue) |

## 14. View all events regardless of type

| Attribute | Value |
|-----------|-------|
| **Feature** | The EventSwitcher in the sidebar and `/events` page list ALL events regardless of type |
| **Why Hidden** | Users may not realize they can see both CAI and Pengajian events simultaneously |
| **How to Use** | Sidebar dropdown shows all active events. Switching changes the context |
| **Status** | ✅ Active |

## 15. CAI Routes Still Accessible in Pengajian Context

| Attribute | Value |
|-----------|-------|
| **Feature** | All CAI operational routes remain server-side accessible even when a Pengajian event is active. The sidebar hides them but routes are not blocked |
| **Files** | `routes/web.php` — no event-type route filtering |
| **Why Hidden** | Contextual sidebar hides CAI menus in Pengajian mode, but `/absensi`, `/registrasi`, etc. are still reachable by URL |
| **How to Use** | Navigate directly to any CAI URL while Pengajian event is active |
| **Status** | ✅ Active (intentional — "Hidden navigation is NOT authorization") |
