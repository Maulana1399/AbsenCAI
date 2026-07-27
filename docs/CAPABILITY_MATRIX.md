# CAPABILITY MATRIX

> Undocumented or lesser-known capabilities found in the codebase that are not explicitly documented elsewhere.

---

## 1. Birth Date Validation During Attendance

| Attribute | Value |
|-----------|-------|
| **Description** | When doing self-attendance in Pengajian, participants must enter their birth date (YYYY-MM-DD) to verify identity before recording attendance |
| **File** | `app/Services/Pengajian/PengajianIdentityService.php::verifyBirthDate()` |
| **Used By** | `app/Livewire/Pengajian/SelfAttendance.php` — during self-attendance flow |
| **Flow** | 1. Person searches their name → 2. Selects their identity → 3. System asks for birth date verification → 4. On correct match, attendance is recorded |
| **Why hidden** | Documented in PENGAJIAN_MVP_OPERATIONAL.md but not widely referenced in FEATURE.md |
| **Status** | ✅ Active |

## 2. Duplicate Detection for Person Identity

| Attribute | Value |
|-----------|-------|
| **Description** | `PersonDuplicateDetectionService` matches persons by normalized name (exact + fuzzy/similar_text) and optional birth date disambiguation |
| **File** | `app/Services/Person/PersonDuplicateDetectionService.php` — `detect()` and `normalizeName()` |
| **Used By** | (Currently unused in production flow — service exists but no caller found) |
| **Flow** | normalizeName() → compare exact match → if no exact, use similar_text → with optional birth date disambiguation |
| **Why hidden** | Service exists but has no active production callers. Created as reusable capability for future self-registration flow |
| **Status** | 🟡 Available but unused |

## 3. Attendance Code — Globally Unique Format

| Attribute | Value |
|-----------|-------|
| **Description** | `attendance_code` format is `KJA-XXXXXXXX` (8 random uppercase alphanumeric). Globally unique across all events. Primary QR payload |
| **File** | `app/Services/Registration/RegistrationService.php::generateAttendanceCode()` |
| **Used By** | RegistrationService::createParticipant(), also used by QRIdentityResolver for scanning |
| **Flow** | Generate random 8 chars → check uniqueness globally → loop if collision → assign |
| **Why hidden** | Partially documented but the format specification and uniqueness guarantee are not centralized |
| **Status** | ✅ Active |

## 4. Participant Number System

| Attribute | Value |
|-----------|-------|
| **Description** | Participant numbers follow format `KL001` (male) / `KP001` (female) with 3-digit zero-padded sequential numbering. Per-event uniqueness |
| **File** | `app/Services/Placement/PlacementService.php::generateParticipantNumber()` |
| **Used By** | RegistrationService, SelfRegister, TambahPeserta |
| **Flow** | Determine gender prefix → query max existing number in event → increment → format |
| **Why hidden** | Format documented but the per-event scoping (not global) is an important detail |
| **Status** | ✅ Active |

## 5. Auto Placement — Least Filled Regu Algorithm

| Attribute | Value |
|-----------|-------|
| **Description** | When auto-placing participants into regu (teams), the system picks the regu with the fewest participants of the same gender. Event-scoped |
| **File** | `app/Services/Placement/PlacementService.php::leastFilledRegu()` |
| **Used By** | RegistrationService::createParticipant(), SelfRegister |
| **Flow** | 1. Get all regus with matching gender → 2. Count participants per regu in event → 3. Sort by count ASC → 4. Pick first (least filled) |
| **Why hidden** | Not documented as a named algorithm |
| **Status** | ✅ Active |

## 6. Import Row-Level Validation (Regu Import)

| Attribute | Value |
|-----------|-------|
| **Description** | Regu import catches Excel validation exceptions and converts them to row-level error messages with attribute mapping |
| **File** | `app/Http/Controllers/ImportDataController.php::regu()` |
| **Used By** | Regu import route |
| **Why hidden** | Only regu import has detailed error handling; other imports (desa, kelompok, peserta) have basic validation |
| **Status** | ✅ Active |

## 7. Attendance Parity Audit

| Attribute | Value |
|-----------|-------|
| **Description** | Compares legacy Absensi/IzinAbsensi records with canonical EventAttendance records to find discrepancies |
| **File** | `app/Services/Attendance/AttendanceParityService.php` — `audit()`, `auditAll()` |
| **Used By** | Artisan command `attendance:parity` |
| **Flow** | For each event: load legacy attendance → find matching canonical → report matched/missing/orphaned/conflict |
| **Why hidden** | CLI-only capability, no UI |
| **Status** | ✅ Active |

## 8. Design C Diagnostics

| Attribute | Value |
|-----------|-------|
| **Description** | Comprehensive diagnostic checking all canonical architecture invariants: misaligned PKs, orphaned mappings, null FKs, missing relationships |
| **File** | `app/Console/Commands/DesignCDiagnostics.php` |
| **Used By** | Artisan command `kja:design-c-diagnostics` |
| **Metrics** | 8 metrics: participants without people, people without participants, broken mappings, orphaned mappings, duplicate relationships, etc. |
| **Why hidden** | CLI-only, operational/debugging tool |
| **Status** | ✅ Active |

## 9. Legacy Data Audit

| Attribute | Value |
|-----------|-------|
| **Description** | Full audit of legacy peserta data: person duplicates, missing identities, relationship integrity |
| **File** | `app/Console/Commands/AuditLegacyData.php` |
| **Used By** | Artisan command `audit:legacy-data` |
| **Why hidden** | CLI-only, one-time audit tool |
| **Status** | ✅ Active |

## 10. Event Data Reset

| Attribute | Value |
|-----------|-------|
| **Description** | Resets all event-scoped data (participations, attendances, sessions) for testing/cleanup |
| **File** | `app/Console/Commands/ResetEventData.php` |
| **Used By** | Artisan command `kja:reset-event-data` |
| **Why hidden** | CLI-only, operational tool |
| **Status** | ✅ Active |

## 11. Token Encryption at Rest

| Attribute | Value |
|-----------|-------|
| **Description** | Desa access tokens are encrypted (not just hashed) using Laravel's encryption. The raw token is encrypted and stored in `encrypted_token` column |
| **File** | `database/migrations/2026_07_28_000002_add_encrypted_token_to_desa_access_grants.php` |
| **Used By** | DesaAccessService |
| **Why hidden** | Not documented — the token security model only documents hashing, not encryption |
| **Status** | ✅ Active |

## 12. Person→Legacy Sync (Two-Way)

| Attribute | Value |
|-----------|-------|
| **Description** | Person edits synchronize to legacy peserta AND vice versa (when editing legacy peserta through CAI Database UI). Syncs: nama, jenis_kelamin, desa_id, kelompok_id |
| **File** | `app/Services/Person/PersonLegacySyncService.php` (Person→peserta) and `RegistrationService::updateParticipant()` (peserta→Person) |
| **Used By** | EditPerson (Master Data), EditPeserta (CAI Database) |
| **Why hidden** | The bi-directional sync is not explicitly documented |
| **Status** | ✅ Active |

## 13. Jenis Peserta System

| Attribute | Value |
|-----------|-------|
| **Description** | Participants have a `jenis_peserta` field: `Wajib`, `Kiriman`, `Person`. Pengajian uses `Pengajian Desa`. Default is `Wajib` |
| **File** | `app/Models/peserta.php` (constants), `database/migrations/2026_07_07_125113_add_jenis_peserta_to_pesertas_table.php` |
| **Used By** | Registration flow |
| **Why hidden** | Not documented except in code |
| **Status** | ✅ Active |

## 14. Status Registrasi System

| Attribute | Value |
|-----------|-------|
| **Description** | Participants have `status_registrasi`: `Belum Registrasi`, `Self Register`, `Registrasi Ulang`. Pengajian uses different statuses managed through Participation |
| **File** | `app/Models/peserta.php`, `database/migrations/2026_08_15_000001_add_status_registrasi_to_participations_table.php` |
| **Used By** | Registration flow, dashboard |
| **Why hidden** | Not documented |
| **Status** | ✅ Active |

## 15. CaiParticipantReplacement

| Attribute | Value |
|-----------|-------|
| **Description** | Mechanism to replace a participant in CAI event: creates new Person + Participation for replacement, marks original as replaced |
| **File** | `app/Models/CaiParticipantReplacement.php`, `app/Services/Cai/CaiParticipantReplacementService.php` |
| **Used By** | `Livewire/Database/Peserta/GantiPeserta.php` |
| **Why hidden** | Specialty feature for CAI only |
| **Status** | ✅ Active |

## 16. Multi-Format Scan Identifier Resolution

| Attribute | Value |
|-----------|-------|
| **Description** | QR scan can resolve attendance codes from multiple formats: canonical `attendance_code`, legacy `peserta` codes, with event-scoped and global fallback |
| **File** | `app/Services/QR/QRIdentityResolver.php::resolve()` |
| **Used By** | Scan Livewire, QR scan flow |
| **Why hidden** | Not documented as a multi-format resolution |
| **Status** | ✅ Active |

## 17. Selective Attendance Method (First Wins)

| Attribute | Value |
|-----------|-------|
| **Description** | First attendance method recorded is never overwritten. `method` field: `self` (participant), `operator` (assisted), `scan` (QR), `manual`, `surat_izin` |
| **File** | `app/Models/EventAttendance.php`, PengajianAttendanceService |
| **Used By** | Attendance recording |
| **Why hidden** | The "first wins" contract is documented but not the specific method values |
| **Status** | ✅ Active |

## 18. Legacy Participation Resolver Chain

| Attribute | Value |
|-----------|-------|
| **Description** | Three-tier resolution chain for attendance: 1) Canonical Participation by attendance_code, 2) LegacyParticipationMapping, 3) Legacy peserta fallback |
| **File** | `app/Services/Attendance/LegacyParticipationResolver.php` |
| **Used By** | AttendanceService, QRIdentityResolver |
| **Why hidden** | Internal resolution chain |
| **Status** | ✅ Active |

## 19. Parallel Activity Support

| Attribute | Value |
|-----------|-------|
| **Description** | Multiple activities can run at the same time as long as venue/data structure is valid. No schedule conflict detection engine yet |
| **File** | `ActivityScheduleService` |
| **Used By** | S3.9 activity/rundown system |
| **Why hidden** | The domain model supports it but there is no enforcement UI |
| **Status** | ✅ Active (domain only) |

## 20. Desa-Scoped Kelompok Lookup

| Attribute | Value |
|-----------|-------|
| **Description** | When resolving kelompok during Pengajian import, the lookup is scoped to the resolved desa (deterministic, no name-only `first()`). Same kelompok name in different desa resolves correctly |
| **File** | `PengajianImportService` |
| **Used By** | Pengajian import flow |
| **Why hidden** | Important design detail not in feature docs |
| **Status** | ✅ Active |
