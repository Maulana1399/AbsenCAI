# DEAD CODE REPORT

> Code that is unused, obsolete, or has no active production callers.
> **NOTE: No code has been deleted. This is a report only.**

---

## 1. Services with No Production Callers

| Service | File | Status | Notes |
|---------|------|--------|-------|
| `PersonDuplicateDetectionService` | `app/Services/Person/PersonDuplicateDetectionService.php` | 🟡 Available | Implemented, tested, but has zero production callers. Created for future self-registration flow |
| `AttendanceReadService` | `app/Services/Attendance/AttendanceReadService.php` | 🟡 Unknown | Exists but no clear production caller found in Livewire/routes |
| `AttendanceBackfillService` | `app/Services/Attendance/AttendanceBackfillService.php` | ⚠️ Active | Retained as active runtime service (not historical tooling) |
| `SuratIzinBackfillService` | `app/Services/Attendance/SuratIzinBackfillService.php` | ⚠️ Active | Retained as active runtime service |

## 2. Models with Potentially Unused Relationships

| Model | Relationship | Status | Notes |
|-------|-------------|--------|-------|
| `peserta` | `legacyPesertaMapping()` | ✅ Active | Active compatibility bridge |
| `peserta` | `legacyParticipationMappings()` | ✅ Active | Active compatibility bridge |
| `peserta::nextAutoParticipantNumber()` | Static method | 🟡 Unclear | May still be called from import flow. Check callers before removing |
| `Event::sesiAbsensis()` | HasMany | ✅ Active | Active for CAI sessions |

## 3. Stub / Incomplete Services

| Item | File | Status | Notes |
|------|------|--------|-------|
| `QRService::generateSvg()` | `app/Services/QR/QRService.php` | 🟡 Stub | Returns empty string. SVG generation deferred |
| `PrintEngine` SVG support | `app/Services/Print/PrintEngine.php` | 🟡 Stub | Only has `label4x4()` implemented |

## 4. Empty Directories

| Directory | Notes |
|-----------|-------|
| `app/Actions/` | Empty — planned for action pattern |
| `app/Helpers/` | Empty — planned for helper functions |
| `app/Exceptions/` | Empty — no custom exceptions yet |

## 5. Config Files — Potentially Obsolete Keys

| Config | Key | Status | Notes |
|--------|-----|--------|-------|
| `config/kjam.php` | `event_name`, `event_logo`, `org_logo` | 🟡 Mostly unused | Event name comes from `Event::name` model now. Config used as fallback |
| `config/feature.php` | Feature flags | 🟡 Unknown | Check if any feature flags are actively read |
| `config/features.php` | Feature flags | 🟡 Unknown | Exists alongside `feature.php` — check for duplication |

## 6. Blade Views — Potentially Unused

| View | Status | Notes |
|------|--------|-------|
| `resources/views/welcome.blade.php` | ✅ Active | Landing page for guest users |
| `resources/views/bkpwelcome.blade.php` | 🟡 Backup | `bkp` prefix suggests backup/obsolete |
| `resources/views/components/placeholder-pattern.blade.php` | 🟡 Unknown | May be unused template pattern |

## 7. Migration Files — Historical Backups

| File | Status | Notes |
|------|--------|-------|
| `database/database-before-*.sqlite` files | 🟡 Backup | 15+ SQLite backup files in database/ directory for various pre-operation snapshots |
| `database/database.sqlite.broken.*` | 🟡 Backup | Broken database backup |

## 8. Documentation — Obsolete/Stale Files

| File | Status | Notes |
|------|--------|-------|
| `docs/ARCHITECTURE_REVIEW_PHASE1.md` | 🟡 Historical | Phase 1 architecture review — describes old service architecture |
| `docs/IDENTITY_REFACTOR_PLAN.md` | 🟡 Historical | Identity refactor plan — superseded by S3 implementation |
| `docs/REFACTOR_PLAN.md` | 🟡 Historical | Old refactor plan — superseded by actual architecture |
| `docs/SERVICE_PLAN.md` | 🟡 Historical | Service plan — superseded by actual implementation |
| `docs/EXTRACTION_PLAN.md` | 🟡 Historical | Extraction plan — mostly superseded |
| `docs/ENUM_PLAN.md` | 🟡 Future planning | Enum plan — only Role enum is implemented |
| `docs/DATABASE_V2.md` | 🟡 Future planning | Future database design — not implemented |
| `docs/PRODUCT.md` | 🟡 Product vision | Product strategy doc — not implementation reference |
| `docs/API.md` | ⚪ Future | API design — not implemented (web only) |
| `docs/runbooks/ATTENDANCE_MIGRATION.md` | 🔴 Obsolete | Marked OBSOLETE in content (PGM.18 Sprint 1) |
| `docs/ARCHITECTURE.md` | 🟡 Partially stale | Infrastructure diagram not current |
| `docs/DATAFLOW.md` | 🟡 Partially stale | May not reflect all current flows |

## 9. Routes — All Active

| Route File | Status | Notes |
|------------|--------|-------|
| `routes/web.php` | ✅ All routes have handlers | No dead routes found |
| `routes/auth.php` | ✅ All routes have handlers | No dead routes found |
| `routes/console.php` | ✅ Command registered | `kja:identity-backfill` still usable |

## 10. Previously Removed (PGM.18 Sprint 1)

The following were removed in PGM.18 Sprint 1 — listed for reference:

| Removed Item | Type | Reason |
|-------------|------|--------|
| `BackfillLegacyPeserta` | Command | Zero production callers |
| `BackfillLegacyParticipation` | Command | Zero production callers |
| `BackfillPersonKelompok` | Command | Zero production callers |
| `RebuildLegacyMappings` | Command | Zero production callers |
| `AttendanceBackfill` | Command | Zero production callers |
| `SuratIzinBackfill` | Command | Zero production callers |
| `LegacyPesertaBackfillService` | Service | Zero production callers |
| `LegacyParticipationBackfillService` | Service | Zero production callers |
| `BackfillReport` | Service | Zero production callers |
| `BackfillReportItem` | Service | Zero production callers |
| 4 test files | Tests | Pure historical tooling |

## 11. Retired Components (PGM.19-20)

| Component | Retirement | Notes |
|-----------|-----------|-------|
| `pesertas.regu_id` column | PGM.19 Sprint 8B | Dropped physically |
| `pesertas.nip` column | PGM.20 Phase 4 | Dropped physically |
| `people.nip` column | PGM.20 Phase 4 | Dropped physically |
| `peserta::regu()` relationship | PGM.19 Sprint 8A | Removed |
| `regu::peserta()` relationship | PGM.19 Sprint 8A | Removed |
| `legacyNextNip()` methods | PGM.20 | Removed from model/service |
| Regu dual-write to peserta | PGM.19 Sprint 8A | Stopped |

## 12. Test Baseline Dead Code

| File | Status | Notes |
|------|--------|-------|
| All tests reference active production code | ✅ | No dead test references detected |
| 1574 tests pass with 3745 assertions | ✅ | All tests executing against live code |
