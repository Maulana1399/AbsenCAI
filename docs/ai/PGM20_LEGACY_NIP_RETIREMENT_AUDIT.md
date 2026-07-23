# PGM.20 LEGACY NIP RETIREMENT AUDIT

**Status:** PHASE 1 RUNTIME CUTOVER COMPLETE
**Date:** 2026-07-23
**Previous Sprint:** PGM.19 Sprint 8B — Physical Regu Retirement
**Next Sprint:** PGM.21 — Physical NIP Column Retirement (proposed)

**Phase 1 changes:** See `docs/ai/PGM20_NIP_RUNTIME_CUTOVER_REPORT.md`

---

## 1. EXECUTIVE SUMMARY

NIP (Nomor Induk Peserta) is a legacy CAI participant identifier inherited from the original architecture. It was designed for the old single-event CAI flow where peserta was the master record. Under the modern multi-event architecture, NIP has become a compatibility layer with zero architectural necessity.

**Audit result: NIP is safe to retire in phases.**

### Key metrics
| Category | Count |
|----------|-------|
| NIP columns in schema | 5 (3 tables + 2 snapshot columns) |
| Production NIP reads | ~50 distinct call sites |
| Production NIP writes | ~15 distinct call sites |
| NIP generation callers | 2 (autoPlacement, PesertaImport) |
| Attendance NIP fallbacks | 5 service paths |
| QR NIP dependencies | **0** |
| Legacy_nip reads (runtime) | **0** — write-only |
| Test NIP references | 593 across 86 files |
| Test files where NIP is the primary contract | 4 files |

### Immediate finding
NIP is **already removable from QR codes** (none embed NIP) and **already optional for Person** (CreatePerson allows null). The remaining dependencies are concentrated in:
1. Legacy attendance resolution (`absensis.nip` → `pesertas.nip` chain)
2. Registration flow (auto-generated NIP for new peserta)
3. UI display/search (NIP shown everywhere as participant identifier)
4. Diagnostic commands (attendance:diagnose, audit:legacy-data)
5. Schema constraints (UNIQUE on `pesertas.nip` and `people.nip`)

---

## 2. FINAL ARCHITECTURE DECISION

```
people
├── nama
├── desa_id
├── kelompok_id
└── tanggal_lahir            # NO nip

participations
├── person_id
├── event_id
├── regu_id                  # CAI only / nullable
├── status_registrasi        # target event-scoped
├── jenis_peserta            # target event-scoped
├── participant_number
└── attendance_code          # NO nip

event_attendances
└── attendance records linked to participation  # NO nip
```

**NIP is not part of the final architecture.**
**participant_number** is the human-facing event participant number.
**attendance_code** is the canonical machine/QR attendance identifier.
No global participant identifier replaces NIP.

---

## 3. EVERY NIP COLUMN IN SCHEMA

| # | Table | Column | Type | Nullable | Unique | Default | Purpose |
|---|-------|--------|------|----------|--------|---------|---------|
| 1 | `pesertas` | `nip` | integer | NO | YES | — | Legacy primary identifier; auto-generated in gender ranges |
| 2 | `people` | `nip` | integer | YES | YES | — | Carried over from legacy peserta at migration time |
| 3 | `absensis` | `nip` | integer | NO | NO | — | Denormalized legacy FK; Absensi model uses `belongsTo(peserta::class, 'nip', 'nip')` |
| 4 | `legacy_peserta_mappings` | `legacy_nip` | integer | YES | NO | — | Snapshot of peserta.nip at mapping creation time |
| 5 | `cai_participant_replacements` | `legacy_nip` | integer | YES | NO | — | Snapshot of peserta.nip at replacement time |

---

## 4. PRODUCTION NIP READS (50+ call sites)

### 4A. Attendance Resolution (REQUIRES_CUTOVER)

| File | Line | Code | Purpose |
|------|------|------|---------|
| `app/Services/Attendance/AttendanceService.php` | 87 | `Absensi::where('nip', $identity->nip)` | Duplicate scan check via legacy NIP |
| `app/Services/Attendance/AttendanceService.php` | 188 | `peserta::where('nip', $identifier)->first()` | Last-resort identity resolution fallback |
| `app/Services/Attendance/AttendanceReadService.php` | 51 | `$p->person?->legacyPesertaMapping?->peserta?->nip` | Read legacy NIP for attendance cross-reference |
| `app/Services/Attendance/AttendanceReadService.php` | 59 | `->whereIn('nip', $legacyNips)` | Query absensis by NIP |
| `app/Services/Attendance/AttendanceReadService.php` | 75 | `$nip = $legacyPeserta?->nip ?? $person?->nip` | Derive NIP per participation |
| `app/Services/Attendance/ParticipationResolver.php` | 21 | `peserta::where('nip', $nip)->first()` | Resolve participation by legacy NIP |
| `app/Services/Attendance/LegacyParticipationResolver.php` | 68 | `peserta::where('nip', (string) $nip)->value('id')` | Resolve legacy peserta ID by NIP |
| `app/Services/Attendance/AttendanceBackfillService.php` | 366 | `$q->where('nip', $nip)` | Backfill resolution by NIP |
| `app/Services/Attendance/AttendanceParityService.php` | 34 | `$resolver->resolveByNip((int) $absensi->nip, $eventId)` | Parity audit |
| `app/Services/Attendance/AttendanceExceptionService.php` | 51 | `Absensi::where('nip', $peserta->nip)` | Duplicate hadir check |
| `app/Services/Attendance/SuratIzinService.php` | 119 | `Absensi::where('nip', $surat->peserta->nip)` | Surat izin — duplicate check |
| `app/Services/Attendance/SuratIzinService.php` | 304 | `Absensi::where('nip', $surat->peserta->nip)` | Surat izin — session sync |
| `app/Services/Cai/CaiParticipantReplacementService.php` | 44 | `Absensi::where('nip', $peserta->nip)->exists()` | Pre-replacement attendance check |
| `app/Services/Cai/CaiParticipantReplacementService.php` | 146 | `$legacyNip = $peserta->nip` | Save legacy NIP before replacement |
| `app/Services/Attendance/AttendanceIdentity.php` | 25 | `$this->nip = $this->peserta?->nip ?? $this->person?->nip` | Resolve identity NIP |

### 4B. Search/Display (SAFE_TO_REMOVE — UI only)

| File | Lines | Purpose |
|------|-------|---------|
| `app/Livewire/Dashboard/Scan.php` | 76, 86, 97, 112, 140, 154, 228, 282, 312 | Search participants by NIP, display NIP in scan results |
| `app/Livewire/Registrasi/Ulang.php` | 129, 139 | Search by person NIP, display NIP |
| `app/Livewire/Database/Peserta/Database.php` | 43, 55 | Search by NIP, display NIP |
| `app/Livewire/Database/Peserta/TambahPeserta.php` | 170, 181, 197 | Search existing persons by NIP |
| `app/Livewire/SuratIzin/Create.php` | 35, 44, 54, 63, 88, 101, 111 | Search by NIP, display NIP |
| `app/Livewire/MasterData/Person/IndexPerson.php` | 26 | Search persons by NIP |
| `app/Livewire/Rekap/Absensi/RekapAbsensi.php` | 63 | Display NIP in attendance rekap |
| `app/Livewire/Rekap/Peserta/RekapPeserta.php` | 75 | Display NIP in peserta rekap |

### 4C. Diagnostic Commands (LEGACY_ONLY)

| File | Lines | Purpose |
|------|-------|---------|
| `app/Console/Commands/AttendanceDiagnose.php` | 61, 86, 95, 100, 105, 117, 131, 143 | Legacy attendance diagnosis — NIP is the key lookup |
| `app/Console/Commands/AuditLegacyData.php` | 29-47 | Audit null/duplicate/range of NIP |
| `app/Console/Commands/ResetEventData.php` | 198, 281, 293 | Snapshot and validate peserta.nip during reset |

---

## 5. PRODUCTION NIP WRITES (15+ call sites)

| File | Line | Code | Purpose |
|------|------|------|---------|
| `app/Services/Registration/RegistrationService.php` | 89 | `'nip' => $data['nip']` | Write to peserta on first-time create (Case A) |
| `app/Services/Registration/RegistrationService.php` | 101 | `'nip' => $data['nip']` | Dual-write to person on first-time create (Case A) |
| `app/Services/Registration/RegistrationService.php` | 119 | `'legacy_nip' => $peserta->nip` | Snapshot to legacy_peserta_mappings |
| `app/Services/Cai/CaiParticipantReplacementService.php` | 165 | `'nip' => null` | Null old Person's NIP on replacement |
| `app/Services/Cai/CaiParticipantReplacementService.php` | 182 | `'nip' => $legacyNip` | Assign legacy NIP to new Person |
| `app/Services/Cai/CaiParticipantReplacementService.php` | 247 | `'legacy_nip' => $legacyNip` | Snapshot to cai_participant_replacements |
| `app/Services/Registration/ManualParticipantRegistrationService.php` | 139 | `'nip' => null` | Create Person with null NIP |
| `app/Services/Pengajian/PengajianImportService.php` | 192 | `'nip' => null` | Create Person with null NIP |
| `app/Livewire/MasterData/Person/EditPerson.php` | 65 | `'nip' => $resolvedNip` | Persist resolved NIP on edit |
| `app/Livewire/MasterData/Person/CreatePerson.php` | 49 | `'nip' => $this->nip ?: null` | Write optional NIP |
| `app/Services/Attendance/AttendanceService.php` | 112 | `'nip' => $identity->nip` | Write to absensis during legacy attendance scan |

---

## 6. NIP GENERATION PATHS

### 6A. legacyNextNip — Definition

**File:** `app/Services/Placement/PlacementService.php:27-54`

```php
public static function legacyNextNip(?string $jenisKelamin = null): int
{
    // Male range: 1001+ (1000-1999)
    // Female range: 2001+ (2000-2999)
    // Fallback: max(nip) + 1
}
```

Queries `pesertas` table for `max('nip')` in gender range. This is the **only** NIP generation path.

### 6B. Callers of legacyNextNip

| Caller | File | Line | Context |
|--------|------|------|---------|
| `PlacementService::autoPlacement()` | `app/Services/Placement/PlacementService.php` | 82 | Returns `['nip' => (string) self::legacyNextNip(...)]` |
| `peserta::nextAutoNip()` | `app/Models/peserta.php` | 50 | **Dead code** — defined but never called |

### 6C. Callers of autoPlacement

| Caller | File | Line | Receives NIP |
|--------|------|------|-------------|
| `PesertaImport::model()` | `app/Imports/PesertaImport.php` | 48 | `$autoPlacement['nip']` → peserta.nip |
| `SelfRegister::generateAutoFields()` | `app/Livewire/Registrasi/SelfRegister.php` | 56 | `$this->nip = $autoPlacement['nip']` |
| `TambahPeserta::generateAutoFields()` | `app/Livewire/Database/Peserta/TambahPeserta.php` | 56 | `$this->nip = $autoPlacement['nip']` |

### 6D. Why NIP is still generated

1. **pesertas.nip is NOT NULL** (schema constraint). Every peserta record must have a NIP.
2. **UNIQUE constraint** on `pesertas.nip` — auto-generation ensures uniqueness.
3. RegistrationService Case A (new Person) writes `$data['nip']` to both `pesertas.nip` and `people.nip`.
4. The form flows (SelfRegister, TambahPeserta) auto-fill a generated NIP and require it.

---

## 7. NIP DATABASE CONSTRAINTS

| Table | Column | Constraint | Impact on removal |
|-------|--------|-----------|-------------------|
| `pesertas` | `nip` | NOT NULL | Must drop NOT NULL before making NIP optional |
| `pesertas` | `nip` | UNIQUE | Must drop UNIQUE before allowing null |
| `pesertas` | `nip` | (implicit) Used in unique composite index | Must check composite index |
| `people` | `nip` | NULLABLE | Already optional |
| `people` | `nip` | UNIQUE | Must drop UNIQUE |
| `absensis` | `nip` | NOT NULL | Hard dependency — absensi records coupled to NIP |

---

## 8. ATTENDANCE NIP DEPENDENCIES

### The Absensi NIP Coupling

The `absensis` table uses `nip` as its participant identifier:

```php
// app/Models/Absensi.php
protected $fillable = ['nip', 'nama', 'jam_scan', 'sesi_id'];

public function peserta()
{
    return $this->belongsTo(peserta::class, 'nip', 'nip');
}
```

This is a **non-standard Eloquent relationship** using `nip` as both local and foreign key, completely bypassing `participations` and `people`.

### Attendance resolution chain

```
QR scan (attendance_code)
  └─ AttendanceService::processScan()
       ├─ resolveByAttendanceCode (CANONICAL)
       ├─ resolveByNip (LEGACY FALLBACK — peserta::where('nip', $identifier))
       └─ resolveByLegacyNip (LEGACY FALLBACK — LegacyParticipationResolver)
            └─ peserta::where('nip', $nip)
```

### Legacy attendance write

```
AttendanceService::processScan()
  └─ if config('features.attendance_legacy_write'):
       └─ Absensi::create(['nip' => $identity->nip, ...])
```

Currently guarded by feature flag. Default: `true`.

### All attendance NIP paths

| # | Path | Classification | Notes |
|---|------|---------------|-------|
| 1 | AttendanceService canonical resolve by attendance_code | SAFE_TO_REMOVE | No NIP in canonical path |
| 2 | AttendanceService fallback resolve by NIP | REQUIRES_CUTOVER | Last resort after attendance_code fails |
| 3 | AttendanceReadService legacy cross-reference | REQUIRES_CUTOVER | Reads absensis by NIP for legacy attendance display |
| 4 | AttendanceBackfillService resolve by NIP | LEGACY_ONLY | Migration/backfill only |
| 5 | AttendanceParityService NIP resolve | LEGACY_ONLY | Audit tool only |
| 6 | AttendanceService legacy absensis write | LEGACY_ONLY | Guarded by feature flag |
| 7 | AttendanceExceptionService NIP duplicate check | LEGACY_ONLY | Reads absensis by NIP |
| 8 | SuratIzinService NIP duplicate check | LEGACY_ONLY | Reads absensis by NIP |
| 9 | CaiParticipantReplacement pre-replacement check | REQUIRES_CUTOVER | Checks absensis for attendance history |
| 10 | AttendanceDiagnose command | LEGACY_ONLY | Diagnostic tool |

---

## 9. QR NIP DEPENDENCIES

**NIP is NOT embedded in any QR code.** All QR variants:

| QR Type | Payload | Tech |
|---------|---------|------|
| Scan attendance | `attendance_code` | `QRService::generatePng(string $attendanceCode)` |
| CAI label print | `attendance_code` | `BatchQRExportService::renderContent($attendanceCode)` |
| Label4x4 print | `attendance_code` | `Label4x4Template::qrService->generatePng($attendanceCode)` |
| Pengajian self-attendance | URL with random nonce | `QrPrint.php ::route('pengajian.hadir', ['nonce' => $grant->nonce])` |

**QR NIP dependencies: 0 (ZERO).**

---

## 10. REGISTRATION DEPENDENCIES

| Registration flow | NIP generated? | NIP required? | NIP stored where? |
|------------------|----------------|---------------|-------------------|
| SelfRegister (Case A) | Yes — autoPlacement | Yes — validation `'required', 'integer'` | peserta.nip + person.nip |
| SelfRegister (Case B — existing Person) | No — uses existing person NIP | Yes — read from person | Not written (preserves existing) |
| TambahPeserta (Case A) | Yes — autoPlacement | Yes — validation `'required', 'integer'` | peserta.nip + person.nip |
| TambahPeserta (Case B — existing Person) | No — uses existing person NIP | Yes — read from person | Not written |
| PesertaImport | Yes — autoPlacement | Yes (auto-generated) | peserta.nip + person.nip |
| ManualParticipantRegistrationService | No | No — `'nip' => null` | person.nip = null |
| PengajianImportService | No | No — `'nip' => null` | person.nip = null |

**Registration would need the most changes to stop NIP generation.**

---

## 11. IMPORT DEPENDENCIES

| Import | NIP source | Required? | Notes |
|--------|-----------|-----------|-------|
| `PesertaImport` | `autoPlacement['nip']` | Yes (auto-generated) | NIP is **not** read from import file. Always auto-generated. |
| `PengajianImportService` | None | No | Creates Person with null NIP |
| Attendance imports | None | N/A | No attendance import files found |

---

## 12. EXPORT / DISPLAY / SEARCH DEPENDENCIES

### Export

| File | Column | Data source |
|------|--------|-------------|
| `app/Exports/PesertaExport.php` | `'NIP' => $person?->nip` | Person model (canonical) |

### Display (views)

| View file | Line | Data source |
|-----------|------|-------------|
| `dashboard/scan.blade.php` | 85 | `$nip` (Livewire property) |
| `dashboard/dashboard.blade.php` | 115, 155 | `$entry->person->nip ?? ($lp->nip ?? '-')` |
| `database/peserta/database.blade.php` | 31 | `$peserta->nip` (mapped object) |
| `database/peserta/tambah-peserta.blade.php` | 99, 117 | `$result['nip'] ?? '-'` |
| `rekap/absensi/rekap-absensi.blade.php` | 74, 110, 150 | `$entry->person?->nip ?? ($lp->nip ?? '-')` |
| `rekap/peserta/rekap-peserta.blade.php` | 110 | `$p->nip` |
| `master-data/person/index-person.blade.php` | 29 | `$person->nip ?? '-'` |
| `master-data/person/edit-person.blade.php` | 33-40 | `wire:model="nip"` form field |
| `master-data/person/create-person.blade.php` | 33 | NIP input (optional) |
| `registrasi/ulang.blade.php` | 29 | `$peserta->nip` |
| `registrasi/self-register.blade.php` | 20 | `wire:model="nip"` form field |
| `registrasi/self-register-success.blade.php` | 14 | `$registrasi['nip'] ?? '-'` |
| `surat-izin/index.blade.php` | 63 | `$surat->peserta->nip ?? ''` |
| `surat-izin/create.blade.php` | 27 | `$p->nip ?? '-'` |

### Search

- **By NIP**: Scan (person + peserta), Database (person), Ulang (person), TambahPeserta (person), SuratIzin (person + peserta), IndexPerson (person)

---

## 13. REPLACEMENT / DELETION DEPENDENCIES

| Operation | NIP dependency | Details |
|-----------|---------------|---------|
| CAI Participant Replacement | Yes — reads/writes | Saves `$peserta->nip`, nulls old Person's nip, assigns to new Person |
| HapusPeserta (delete) | No | Uses `peserta_id` for deletion |
| GantiPeserta (UI replacement) | Yes — display only | Shows `$peserta->nip` in form |

---

## 14. LEGACY_PESERTA_MAPPINGS.LEGACY_NIP DEPENDENCIES

**Runtime reads: ZERO.** The `legacy_nip` column on both `legacy_peserta_mappings` and `cai_participant_replacements` is **write-only** at runtime.

| Write | File | Purpose |
|-------|------|---------|
| `'legacy_nip' => $peserta->nip` | `RegistrationService.php:119` | Snapshot at mapping creation |
| `'legacy_nip' => $legacyNip` | `CaiParticipantReplacementService.php:247` | Snapshot at replacement time |

**Classification:** LEGACY_ONLY / ARCHIVAL. Can be removed at any time without affecting runtime behavior. Recommend removing with peserta table retirement (archival preference C).

---

## 15. TEST DEPENDENCIES

| Category | Files | Count | Description |
|----------|-------|-------|-------------|
| NIP is primary contract | 4 | ~67 refs | `PersonFoundationTest`, `PersonMasterDataTest`, `OtomatisasiRegistrasiTest`, `PlacementServiceTest` |
| NIP is semi-essential | 7 | ~93 refs | Scan, Attendance, QR identity tests |
| NIP is incidental fixture | ~75 | ~433 refs | All other test files — any unique identifier would work |
| legacy_nip tests | 2 | ~8 refs | Schema existence tests only |
| **TOTAL** | **86** | **593** | |

---

## 16. SAFE-TO-REMOVE DEPENDENCIES

These can be removed immediately without affecting any runtime behavior:

| # | Dependency | Removal action | Risk |
|---|-----------|---------------|------|
| 1 | All QR code generation | None — already NIP-free | None |
| 2 | UI NIP display (~20 views) | Remove NIP columns from tables, remove form fields | Low — UI only |
| 3 | NIP search in Livewire components | Remove `orWhere('nip', ...)` from search queries | Low — search by nama remains |
| 4 | Export NIP column | Remove from PesertaExport collection/headings | Low — optional column |
| 5 | `peserta::nextAutoNip()` | Remove dead method | None |
| 6 | `AttendanceDiagnose` command | Retain until legacy absensis retired | Low — diagnostic only |
| 7 | `AuditLegacyData` command NIP section | Remove NIP-specific audit sections | Low — audit only |
| 8 | PersonLegacySyncService resolveNip/canChangeNip | Remove NIP enforcement | Low — returns existing DB value |

---

## 17. DEPENDENCIES REQUIRING CUTOVER

These cannot be removed until cutover logic is implemented:

| # | Dependency | Required cutover | Priority |
|---|-----------|-----------------|----------|
| 1 | `pesertas.nip` NOT NULL | Make column nullable, update RegistrationService Case A to not require nip | HIGH |
| 2 | `pesertas.nip` UNIQUE | Drop unique constraint | HIGH |
| 3 | `people.nip` UNIQUE | Drop unique constraint | MEDIUM |
| 4 | `legacyNextNip()` generation | Remove from autoPlacement; SelfRegister/TambahPeserta no longer need NIP | HIGH |
| 5 | RegistrationService Case A writes `'nip'` | Remove from peserta::create() and Person::create() | HIGH |
| 6 | AttendanceService NIP fallback (line 188) | Verify attendance_code lookup always succeeds | HIGH |
| 7 | AttendanceReadService legacy cross-reference | Switch entirely to event_attendances | HIGH |
| 8 | Absensi::peserta() relationship | Retire absensi table entirely | HIGH |
| 9 | CAI replacement NIP transfer | Use person identity match instead of NIP | MEDIUM |
| 10 | Form validation requiring NIP (SelfRegister, TambahPeserta) | Remove validation rules | MEDIUM |
| 11 | NIP uniqueness check in SelfRegister/TambahPeserta | Remove | LOW |
| 12 | Scan search by NIP | Remove from search queries | LOW |
| 13 | ResetEventData NIP snapshot validation | Remove from integrity check | LOW |

---

## 18. EXACT MIGRATION ORDER

```
PHASE 1: NIP Runtime Retirement (PGM.21 proposed)
├── Schema: Make pesertas.nip nullable (+ drop UNIQUE)
├── Schema: Drop people.nip UNIQUE
├── Code: Remove legacyNextNip from autoPlacement
├── Code: Update RegistrationService Case A — don't write/require nip
├── Code: Remove nip validation from SelfRegister/TambahPeserta
├── Code: Remove NIP search from all Livewire components
├── Code: Remove NIP display from all views
├── Code: Remove NIP column from PesertaExport
├── Code: Remove PersonLegacySyncService NIP enforcement
└── Code: Remove AuditLegacyData NIP section

PHASE 2: Attendance Cutover (PGM.22 proposed)
├── Set ATTENDANCE_LEGACY_WRITE=false in .env (default config change)
├── Code: Remove legacy absensis write from AttendanceService
├── Code: Remove NIP fallback from AttendanceService::resolveIdentity
├── Code: Remove legacy attendance cross-reference from AttendanceReadService
├── Code: Remove LegacyParticipationResolver resolveByLegacyNip
├── Code: Remove ParticipationResolver resolveByNip
└── Code: Update CAI replacement to avoid NIP transfer

PHASE 3: Legacy Table Retirement (PGM.23 proposed)
├── Code: Remove absensis table + Absensi model
├── Code: Remove attendance:diagnose command
├── Code: Remove AttendanceBackfillService
├── Code: Remove AttendanceParityService
└── Code: Remove AttendanceExceptionService legacy paths

PHASE 4: Column Removal (PGM.24 proposed)
├── Migration: Drop pesertas.nip
├── Migration: Drop people.nip
├── Migration: Drop legacy_peserta_mappings.legacy_nip
├── Migration: Drop cai_participant_replacements.legacy_nip
└── Migration: Drop absensis.nip (if table remains)

PHASE 5: Peserta Table Retirement (PGM.25+ proposed)
└── Full peserta table retirement (separate plan needed)
```

---

## 19. RECOMMENDED IMPLEMENTATION SPRINT

| Sprint | Phase | Effort estimate | Risk | Dependencies |
|--------|-------|----------------|------|-------------|
| PGM.21 | Phase 1 — NIP Runtime Retirement | Medium (schema + code) | Low-Medium | Stop generating NIP; verify registration works without it |
| PGM.22 | Phase 2 — Attendance Cutover | Medium | Medium | Depends on PGM.21; needs production attendance_code coverage verification |
| PGM.23 | Phase 3 — Legacy table retirement | High | High | Depends on PGM.22; removes absensis table entirely |
| PGM.24 | Phase 4 — Column removal | Low | Low | Depends on PGM.23; pure schema migration |
| PGM.25 | Phase 5 — Peserta table retirement | High | High | Full peserta retirement (separate plan needed) |

---

## 20. GO/NO-GO FOR NIP RUNTIME RETIREMENT

### Prerequisites for GO
- [ ] All AttendanceService canonical paths verified to resolve by attendance_code only
- [ ] Test environment confirms no regression when `legacyNextNip` returns 0 or null
- [ ] All production callers of `autoPlacement['nip']` tolerate null/absent NIP
- [ ] Form flows (SelfRegister, TambahPeserta) validated without NIP requirement

### GO Condition
**GO** if RegistrationService Case A tests pass with optional/null NIP AND attendance resolution tests pass without NIP fallback.

### Recommendation
**GO for Phase 1 (Runtime Retirement) in PGM.21.** NIP generation can be safely removed because:
- ManualParticipantRegistrationService and PengajianImportService already create Persons with null NIP
- QR codes use attendance_code (not NIP)
- The canonical attendance path resolves by attendance_code (not NIP)
- Person.nip is already nullable

---

## 21. GO/NO-GO FOR PHYSICAL NIP COLUMN REMOVAL

### Prerequisites for GO
- [ ] Phase 1 completed (runtime NIP retired)
- [ ] Phase 2 completed (attendance cutover done)
- [ ] Schema no longer has any NOT NULL or UNIQUE constraints depending on NIP
- [ ] All NIP display/search/export removed from UI

### GO Condition
**NO-GO until Phases 1-3 complete.** Physical column removal requires:
1. No runtime code writes to `pesertas.nip` or `people.nip`
2. No attendance code reads `pesertas.nip` or `absensis.nip`
3. `Absensi::peserta()` relationship removed (or absensis table retired)

---

## 22. IMPACT ON EVENTUAL PESERTA TABLE RETIREMENT

NIP retirement is a **prerequisite** for peserta table retirement because:
1. `pesertas.nip` is the anchor column for the legacy `absensis` join
2. `legacyNextNip()` queries `pesertas` table — must stop before dropping table
3. `RegistrationService` Case A creates both `peserta` + `Person` — NIP remove simplifies estopping peserta::create()
4. `PesertaImport` always creates `peserta` records — NIP removal means it becomes a Participation-only import

**NIP retirement removes one of the biggest dependencies keeping the peserta table alive.**

---

## 23. EXACT METRICS SUMMARY

| Metric | Count |
|--------|-------|
| Production NIP reads | ~50 distinct call sites across 25 files |
| Production NIP writes | ~15 distinct call sites across 8 files |
| NIP generation callers | 2 (autoPlacement, PesertaImport) |
| Attendance NIP fallbacks (runtime) | 5 service paths |
| QR NIP dependencies | 0 |
| Import NIP dependencies | 1 (auto-generated, not read from file) |
| Export/UI NIP dependencies | ~25 display + 6 search paths |
| Legacy_nip runtime reads | 0 (write-only) |
| Schema NIP columns | 5 (3 data + 2 snapshot) |
| UNIQUE constraints on NIP | 2 (pesertas.nip, people.nip) |
| NOT NULL constraints on NIP | 2 (pesertas.nip, absensis.nip) |
| Test NIP references | 593 across 86 files |
| Test files NIP is primary contract | 4 (PersonFoundationTest, PersonMasterDataTest, OtomatisasiRegistrasiTest, PlacementServiceTest) |
| Files needing zero NIP changes | ~75 test files (NIP is incidental fixture) |
