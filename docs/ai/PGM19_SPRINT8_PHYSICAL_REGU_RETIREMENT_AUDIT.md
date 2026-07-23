# PGM.19 SPRINT 8 — PHYSICAL LEGACY REGU RETIREMENT AUDIT

**MODE:** READ-ONLY AUDIT
**Date:** 2026-07-23
**Branch:** feature/database-v2-design-c
**Verification:** 1549 passed / 3716 assertions / 0 failures / Design C problem_total = 0

---

## 1. Executive Summary

Sprint 7 successfully cut over all runtime read paths from `pesertas.regu_id` to `participations.regu_id`, hardened 10 fallback patterns, and stopped 5 dual-write UPDATE paths. However, Sprint 8 audit reveals that `pesertas.regu_id` is **NOT SAFE TO DROP**.

**Verdict: NOT SAFE TO DROP**

Four categories of blocker remain:

| Category | Count | Blocking? |
|---|---|---|
| Production CREATE writes → `pesertas.regu_id` | 4 paths | YES |
| Production READ from `pesertas.regu_id` | 4 locations | YES |
| Model relationship `peserta::regu()` with runtime caller | 1 location | YES |
| Test fixture dependency (`peserta::create` with `regu_id`) | 20+ files, 40+ writes | YES |

---

## 2. Current Architecture

```
Person
  ↓
Participation (event-scoped)
  ↓
Participation.regu_id  ← CANONICAL source (Sprint 5+)
```

`pesertas.regu_id` is a legacy global field. After Sprint 7:
- All event-scoped reads go through `Participation.regu_id`
- All UPDATE dual-write to `pesertas.regu_id` is stopped
- 4 CREATE paths still write `regu_id` to `pesertas`
- `pesertas.regu_id` is nullable (FK `regus.id` CASCADE ON DELETE)

---

## 3. Production Read Inventory

### Reads of `pesertas.regu_id` (BUKAN Participation.regu_id)

| # | File | Line | Code | Type | Risk |
|---|---|---|---|---|---|
| R1 | `RegistrationService.php` | 62 | `$data['regu_id'] ?? $legacyPeserta?->regu_id` | Fallback read for reguId | MEDIUM — callers always pass regu_id, but fallback creates latent dependency |
| R2 | `CaiParticipantReplacementService.php` | 196 | `$oldParticipation->regu_id ?? $peserta->regu_id` | Fallback read for new Participation create | MEDIUM — if legacy null, new Participation gets null regu_id (acceptable) |
| R3 | `CaiParticipantReplacementService.php` | 253 | `'regu_id' => $peserta->regu_id` | Audit snapshot read | LOW — cosmetic for replacement history |
| R4 | `GantiPeserta.php` | 65 | `$participation->regu?->regu ?? $peserta->regu?->regu ?? '-'` | Cosmetic display fallback | LOW — just shows regu name in modal |

### Confirmed ZERO reads (all canonical):

- `Database.php` blade: `$peserta->regu` is mapped from `$participation->regu` (line 61)
- `Ulang.php` blade: `$peserta->regu` is mapped from `$p->regu` (line 142)
- `RekapPeserta.php` blade: `$p->regu` is mapped from `$participation->regu` (line 82)
- `RekapAbsensi.php` blade: `$peserta->regu` is mapped from `$participation->regu` (line 64)
- `Dashboard.php` blade: `$entry->participation->regu` — canonical
- `PesertaExport.php`: query filter uses `Participation::where('regu_id', ...)`
- Routes `web.php:153,341`: filter uses `Participation::where('regu_id', ...)`
- `AttendanceReadService.php:25`: filter uses `Participation::where('regu_id', ...)`

**Production READ count: 4 (all fallback/display, not functional)**

---

## 4. Production Write Inventory

### CREATE writes to `pesertas.regu_id` (BLOCKERS)

| # | File | Line | Code | Flow |
|---|---|---|---|---|
| W1 | `RegistrationService.php` | 96 | `'regu_id' => $data['regu_id']` in `peserta::create()` | CASE A: New Person registration |
| W2 | `PesertaImport.php` | 52 | Passes `'regu_id' => $autoPlacement['regu_id']` to `createParticipant()` | Import |
| W3 | `SelfRegister.php` | 138 | Passes `'regu_id' => $this->regu_id` to `createParticipant()` | Self-registration |
| W4 | `TambahPeserta.php` | 134 | Passes `'regu_id' => $this->regu_id` to `createParticipant()` | Manual add |

All 4 flow through `RegistrationService::createParticipant()` which calls `peserta::create([..., 'regu_id' => ..., ...])`.

**TambahPeserta::tambahkanKeEvent()** (line 271) creates Participation directly with `'regu_id' => $placement['regu_id']` — this is canonical-only, NO `pesertas.regu_id` write.

### UPDATE writes to `pesertas.regu_id`

**ZERO.** All dual-write UPDATE paths stopped per Sprint 7.

---

## 5. CREATE Path Matrix

| Flow | Creates peserta? | Writes peserta.regu_id? | Creates Participation? | Writes Participation.regu_id? | Safe without peserta.regu_id? |
|---|---|---|---|---|---|
| RegistrationService CASE A | YES | YES (line 96) | YES (line 114) | YES | NO — create would fail |
| RegistrationService CASE B | NO (existing) | NO (dual-write stopped) | YES (line 70) | YES | YES |
| PesertaImport | YES (via CASE A) | YES (indirect) | YES (indirect) | YES | NO |
| SelfRegister | YES (via CASE A) | YES (indirect) | YES (indirect) | YES | NO |
| TambahPeserta (baru) | YES (via CASE A) | YES (indirect) | YES (indirect) | YES | NO |
| TambahPeserta (tambahkanKeEvent) | NO | NO | YES (line 271) | YES | YES |
| Ulang | NO | NO | NO | YES (line 99) | YES |
| EditPeserta | NO | NO | NO | YES (line 102) | YES |
| CaiParticipantReplacement | NO (new Person only) | NO (dual-write stopped) | YES (line 196) | YES | YES (reads legacy fallback line 196 but not required) |

### Detailed CREATE path analysis

**RegistrationService CASE A (lines 87-98):**
1. Apakah peserta masih dibuat? **YES** — `peserta::create(...)` at line 87
2. Apakah regu_id wajib pada INSERT peserta? **NO** — column is nullable, but present in `$fillable`
3. Apakah database column nullable? **YES**
4. Apakah model validation mewajibkan regu_id? **NO** — no validation in model
5. Apakah factory/default membutuhkan regu_id? **NO**
6. Apakah Participation dibuat pada flow yang sama? **YES** — line 108
7. Apakah Participation.regu_id sudah selalu ditulis? **YES** — line 114
8. Apa yang terjadi jika regu_id dihapus dari peserta CREATE? SQL error: `unknown column regu_id` or `not null constraint failed` depending on schema
9. Apakah runtime downstream masih membaca peserta.regu_id? **YES** — 4 read locations
10. Apakah flow tetap berjalan jika pesertas.regu_id tidak ada? **NO** — create would fail

---

## 6. Model Relationship Audit

**File:** `app/Models/peserta.php`

```php
protected $fillable = [
    'regu_id',    // LINE 27 — in fillable
];

public function regu()    // LINE 75
{
    return $this->belongsTo(regu::class);
}
```

### Callers of `$peserta->regu` / `$peserta->regu()`:

| File | Line | Usage | Safe to remove? |
|---|---|---|---|
| `GantiPeserta.php` | 65 | `$peserta->regu?->regu` as display fallback | YES — replace with `$participation->regu?->regu` only |

All blade template `$peserta->regu` accesses are actually mapped from `$participation->regu` in the Livewire component (see Phase 3). The only direct model access is GantiPeserta.

**Verdict:** `peserta::regu()` relationship CAN be removed after GantiPeserta cosmetic fix, but is NOT a blocker for physical column drop since removing it is a separate code change.

---

## 7. Import Audit

**File:** `app/Imports/PesertaImport.php`

- Calls `RegistrationService::createParticipant()` with `'regu_id' => $autoPlacement['regu_id']`
- Creates both peserta and Participation in same transaction
- Participation.regu_id is set correctly via RegistrationService
- **BLOCKER:** createParticipant writes `regu_id` to `pesertas` table

---

## 8. Registration Audit

**File:** `app/Services/Registration/RegistrationService.php`

- `createParticipant()` — 2 paths:
  - CASE A (new Person): `peserta::create([..., 'regu_id' => $data['regu_id'], ...])` — writes legacy field
  - CASE B (existing Person): does NOT write `pesertas.regu_id` (dual-write stopped per Sprint 7), but reads `$legacyPeserta?->regu_id` as fallback at line 62
- `updateParticipant()` — does NOT write `pesertas.regu_id` ✅

**BLOCKER:** CASE A still writes to legacy field. Fallback read at line 62 creates latent dependency.

---

## 9. SelfRegister Audit

**File:** `app/Livewire/Registrasi/SelfRegister.php`

- Validation at line 84: `'regu_id' => ['required', Rule::exists('regus', 'id')]` — form REQUIRES regu_id
- Passes `'regu_id' => $this->regu_id` to `createParticipant()` at line 138
- **BLOCKER:** writes via RegistrationService, form validation requires field

---

## 10. TambahPeserta Audit

**File:** `app/Livewire/Database/Peserta/TambahPeserta.php`

- Two flows:
  1. `simpan()` (line 66): validates `'regu_id' => 'required|exists:regus,id'` at line 84, passes to `createParticipant()` at line 134 — **BLOCKER**
  2. `tambahkanKeEvent()` (line 207): creates Participation directly at line 271 with `'regu_id' => $placement['regu_id']` — does NOT write `pesertas.regu_id` ✅
- **BLOCKER:** simpan() flow still writes legacy field

---

## 11. CaiParticipantReplacement Audit

**File:** `app/Services/Cai/CaiParticipantReplacementService.php`

- Line 196: `'regu_id' => $oldParticipation->regu_id ?? $peserta->regu_id` — fallback read for new Participation
- Line 253: `'regu_id' => $peserta->regu_id` — audit snapshot (CaiParticipantReplacement table, not pesertas)
- Does NOT write to `pesertas.regu_id` ✅
- **BLOCKER (minor):** fallback read at line 196 creates dependency; line 253 reads for snapshot

---

## 12. Reset Command Audit

**File:** `app/Console/Commands/ResetEventData.php`

| Line | Usage | Impact if column dropped |
|---|---|---|
| 164 | Comment: `'pesertas.regu_id -> regus.id'` | Documentation only — needs update |
| 198, 281 | `->select(..., 'regu_id')` in snapshot | SQL error — column doesn't exist |
| 293 | `foreach (['regu_id', ...] as $field)` validation | SQL error — column doesn't exist |
| 300 | Output: `'✅ All peserta fields unchanged (... regu_id)'` | Cosmetic — needs update |
| 325-326 | `whereNotNull('regu_id')` and `whereNotIn('regu_id', ...)` | SQL error — column doesn't exist |
| 331 | Output: `'✅ No orphan regu_id'` | Cosmetic — needs update |

**BLOCKER:** 4 validation/read operations would fail.

---

## 13. Schema Audit

### Source migrations:

1. `2025_06_16_071812_create_peserta_table.php`
   - `$table->foreignId('regu_id')->constrained('regus')->onDelete('cascade')->nullable();`
   - **nullable, FK → regus(id), CASCADE on delete**

2. `2026_07_01_000002_make_jenis_kelamin_nullable_on_pesertas_table.php`
   - Recreates table with same schema (SQLite rebuild)
   - `$table->foreignId('regu_id')->nullable()->constrained('regus')->cascadeOnDelete();`

### Schema properties:

| Property | Value |
|---|---|
| Nullable? | YES |
| FK → | `regus.id` |
| onDelete | CASCADE |
| Index? | Implicit from FK (SQLite auto-index) |
| Unique? | NO |

### Proposed drop migration requirements:

```sql
-- Step 1: Drop FK
ALTER TABLE pesertas DROP FOREIGN KEY pesertas_regu_id_foreign;

-- Step 2: Drop column
ALTER TABLE pesertas DROP COLUMN regu_id;
```

### SQLite considerations:

- **SQLite < 3.35.0** does not support `DROP COLUMN`. Requires `CREATE TABLE AS` rebuild.
- **SQLite >= 3.35.0** supports `ALTER TABLE DROP COLUMN` but requires:
  - `PRAGMA foreign_keys = OFF`
  - Drop FK first (or recreate table)
- Laravel's `Schema::dropColumns()` handles SQLite rebuild automatically
- `doctrine/dbal` may be needed for complex column drops (Laravel 11+ uses native SQLite DDL)

### Migration safety:

| Scenario | Safe? | Notes |
|---|---|---|
| Fresh migration (migrate:fresh) | YES | New table won't have the column |
| Existing DB upgrade | YES, with rebuild | SQLite will recreate table |
| Rollback | N/A | Forward-only migration |

---

## 14. Test Dependency Inventory

### Test files that CREATE peserta with `regu_id` (fixture dependency):

| # | Test File | Fixture Count | Lines |
|---|---|---|---|
| 1 | `Sprint2MappingContractTest.php` | 1 create + 1 assertion | 31, 241 |
| 2 | `ExportLogTest.php` | 1 | 69 |
| 3 | `AttendanceStatusSummaryTest.php` | 2 | 31, 32 |
| 4 | `HapusPesertaSafetyTest.php` | 2 | 44, 61 |
| 5 | `ImportDataTest.php` | 1 | 84 |
| 6 | `Sprint7ReguRuntimeCutoverTest.php` | 4 fixture creates + 8 assertions on peserta.regu_id | 44, 60, 77, 94; 206, 219, 220, 227, 239, 253, 269, 270 |
| 7 | `Sprint5ReguReadPathCutoverTest.php` | 3 fixture creates + 1 update | 35, 51, 69; 85 |
| 8 | `MasterDataProtectionTest.php` | 1 | 418 |
| 9 | `PersonMasterDataTest.php` | 5 fixture creates + 2 assertions | 339, 489, 498, 669, 695, 705 |
| 10 | `CaiParticipantReplacementDesignCTest.php` | 1 | 47 |
| 11 | `CaiParticipantReplacementTest.php` | 1 create + 1 assertion | 59, 177 |
| 12 | `LegacyParticipationBridgeFoundationTest.php` | 4 | 56, 71, 137, 151 |
| 13 | `Sprint4ReguEventScopingTest.php` | 1 | 48 |
| 14 | `DesignCDiagnosticsTest.php` | 1 (helper `dc_peserta`) | 30 |
| 15 | `IdentityContractHardeningTest.php` | 3 (passes regu_id to updateParticipant) | 85, 115, 140 |
| 16 | `RegistrationServiceTest.php` | 3 (passes regu_id to createParticipant) | 65, 103, 132 |
| 17 | `PlacementServiceTest.php` | 4 | 69, 76, 92, 97 |
| 18 | `EventCaiProtectionTest.php` | 1 (sets regu_id on Livewire) | 202 |
| 19 | `DatabaseSeeder.php` | 2 (seed data) | 103, 121 |

**Totals:**
- **20 files** with fixture dependency
- **~42 fixture creates** that write `regu_id` to `pesertas` table
- **~12 assertions** that verify `$peserta->regu_id` value

### Test files that require `pesertas.regu_id` column to exist:

All 20 files above — `peserta::create(['regu_id' => ...])` would throw `Column not found` SQL error.

---

## 15. Data Audit

**PHP runtime/data audit not performed.** No production database connection available. Static audit only.

---

## 16. Safe Drop Criteria Assessment

| # | Criterion | Status | Notes |
|---|---|---|---|
| 1 | Production READ from peserta.regu_id = 0 | ❌ FAIL | 4 reads (R1-R4) |
| 2 | Production FILTER using peserta.regu_id = 0 | ✅ PASS | All filters use Participation.regu_id |
| 3 | Production UPDATE peserta.regu_id = 0 | ✅ PASS | All dual-write UPDATE paths stopped Sprint 7 |
| 4 | Production CREATE tidak membutuhkan peserta.regu_id | ❌ FAIL | 4 CREATE paths write legacy field |
| 5 | All event-scoped assignment via Participation.regu_id | ✅ PASS | Sprint 7 completed |
| 6 | peserta::regu() not needed at runtime | ❌ FAIL | Used by GantiPeserta display fallback |
| 7 | Import runs without peserta.regu_id | ❌ FAIL | PesertaImport passes regu_id to createParticipant |
| 8 | Registration runs without peserta.regu_id | ❌ FAIL | RegistrationService CASE A writes regu_id |
| 9 | Replacement runs without peserta.regu_id | ❌ FAIL (minor) | CaiParticipantReplacement reads $peserta->regu_id |
| 10 | Reset commands not dependent on FK | ❌ FAIL | ResetEventData validates regu_id field |
| 11 | Test fixtures refactorable | ❌ FAIL | 20+ files, 42+ creates, 12+ assertions |
| 12 | Migration safe for SQLite | ✅ PASS | Rebuild required but feasible |
| 13 | Existing DB upgrade safe | ✅ PASS | Forward-only migration |
| 14 | Design C not dependent on field | ✅ PASS | DC diagnostic ignores pesertas.regu_id |

**Criteria met: 7/14 — NOT SAFE TO DROP**

---

## 17. Risk Matrix

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| CREATE path writes to missing column → SQL error | CERTAIN | HIGH — registration broken | Must stop writes before drop |
| ResetEventData validation reads missing column | CERTAIN | MEDIUM — command broken | Must update validation before drop |
| Test suite fails on column drop | CERTAIN | HIGH — CI broken | Must refactor fixtures before drop |
| CaiParticipantReplacement reads null | HIGH | LOW — only affects audit snapshot | Acceptable or fix before drop |
| GantiPeserta display shows wrong regu | MEDIUM | LOW — cosmetic only | Fix to canonical-only before drop |

---

## 18. Sprint 8A Prerequisite Plan (NOT SAFE TO DROP)

Since verdict is **NOT SAFE TO DROP**, create a Sprint 8A implementation sprint to resolve all blockers. Then Sprint 8B for the physical drop.

### Sprint 8A scope — Remove all runtime dependencies:

**A. Stop CREATE writes to `pesertas.regu_id` (4 paths)**

1. `RegistrationService.php:96` — Remove `'regu_id'` from `peserta::create()` array in CASE A
2. Remove `'regu_id'` from `peserta::$fillable` array

**B. Remove fallback reads (3 locations)**

1. `RegistrationService.php:62` — Remove `?? $legacyPeserta?->regu_id` fallback. Since all callers pass `regu_id` in data, the fallback is dead code.
2. `CaiParticipantReplacementService.php:196` — Change to `'regu_id' => $oldParticipation->regu_id` (remove fallback)
3. `CaiParticipantReplacementService.php:253` — Change to `'regu_id' => $oldParticipation->regu_id` (audit snapshot reads canonical)
4. `GantiPeserta.php:65` — Change to `$this->regu = $participation->regu?->regu ?? '-'` (remove legacy fallback)

**C. Remove `peserta::regu()` relationship (zero callers after A+B)**

1. Remove `regu()` method from `peserta.php`
2. Remove `'regu_id'` from `peserta::$fillable`

**D. Update ResetEventData**

1. Remove `regu_id` from select queries (lines 198, 281)
2. Remove `regu_id` from field validation (line 293)
3. Remove `regu_id` from orphan validation (lines 325-326)
4. Update output messages (lines 300, 331)
5. Remove or update FK comment (line 164)

**E. Refactor test fixtures (~20 files)**

1. Remove `'regu_id'` from all `peserta::create()` calls
2. Update assertions that check `$peserta->regu_id` to check `$participation->regu_id` instead
3. Remove tests that explicitly conflict `peserta.regu_id` vs `participation.regu_id` (Sprint5 tests)

**F. Remove form validation requiring regu_id**

1. `SelfRegister.php:84` — Remove `'regu_id' => ['required', ...]` validation rule (regu_id derived from autoPlacement)
2. `TambahPeserta.php:84` — Remove `'regu_id' => 'required|exists:regus,id'` validation rule
3. `EditPeserta.php:87` — Remove `'regu_id' => 'required'` validation rule

### Sprint 8B scope — Physical column drop:

After Sprint 8A is verified passing:

1. Remove FK: `$table->dropForeign(['regu_id'])`
2. Drop column: `$table->dropColumn('regu_id')`
3. Update `DatabaseSeeder.php` — remove `regu_id` from seed data
4. Update `PersonLegacySyncService.php:18` — remove `regu_id` from NOT-synced list comment
5. Run `migrate:fresh` + full suite + Design C diagnostic

---

## 19. Implementation Scope Estimate

| Component | Files to change | Complexity |
|---|---|---|
| Stop CREATE writes + remove fallback reads | 5 production files | LOW |
| Remove `peserta::regu()` + `$fillable` | 1 model file | LOW |
| ResetEventData updates | 1 command file | LOW |
| Form validation updates | 3 Livewire files | LOW |
| Test fixture refactoring | ~20 test files | MEDIUM |
| Sprint 8B migration | 1 migration file | LOW |
| Documentation update | 4-5 doc files | LOW |

**Total estimate:** Sprint 8A (prerequisite) + Sprint 8B (drop) = 2 sprints

---

## 20. Explicit GO/NO-GO

| Question | Answer |
|---|---|
| 1. Production READ `pesertas.regu_id` remaining? | **4** (R1-R4, all fallback/display) |
| 2. Production UPDATE writes remaining? | **0** ✅ |
| 3. Production CREATE writes remaining? | **4** (W1-W4 via RegistrationService) |
| 4. `peserta::regu()` runtime caller? | **YES** (GantiPeserta.php:65) |
| 5. `PesertaImport` safe without column? | **NO** — writes via createParticipant |
| 6. `RegistrationService` safe? | **NO** — CASE A writes, CASE B reads fallback |
| 7. `SelfRegister` safe? | **NO** — writes via createParticipant, validates regu_id |
| 8. `TambahPeserta` safe? | **NO** — simpan() flow writes, validates regu_id |
| 9. `CaiParticipantReplacement` safe? | **PARTIAL** — no write, but reads $peserta->regu_id |
| 10. `ResetEventData` depends on FK? | **YES** — 4 validation queries use regu_id |
| 11. Test files to refactor? | **~20 files**, ~42 creates, ~12 assertions |
| 12. SQLite drop column safe? | **YES** — requires table rebuild, Laravel handles it |
| 13. Existing DB upgrade safe? | **YES** — after Sprint 8A |
| 14. `migrate:fresh` safe? | **YES** — after Sprint 8A |
| 15. Design C depends on field? | **NO** ✅ |
| **16. VERDICT** | **NOT SAFE TO DROP** ❌ |
| 17. If SAFE, scope? | N/A — NOT SAFE |
| **18. Recommendation** | **Sprint 8A** — stop writes + remove reads + refactor tests. **Sprint 8B** — physical drop. |

---

## Appendix: Exact remaining `pesertas.regu_id` Reads

### R1 — RegistrationService fallback
**File:** `app/Services/Registration/RegistrationService.php:62`
```php
$reguId = $data['regu_id'] ?? $legacyPeserta?->regu_id;
```
All callers (SelfRegister, TambahPeserta, PesertaImport) always pass `'regu_id'` in `$data`. The `?? $legacyPeserta?->regu_id` is dead code.

### R2 — CaiParticipantReplacement new Participation fallback
**File:** `app/Services/Cai/CaiParticipantReplacementService.php:196`
```php
'regu_id' => $oldParticipation->regu_id ?? $peserta->regu_id,
```
Canonical-first: `$oldParticipation->regu_id` is the primary source. `$peserta->regu_id` is legacy fallback that should be stale after Sprint 7.

### R3 — CaiParticipantReplacement audit snapshot
**File:** `app/Services/Cai/CaiParticipantReplacementService.php:253`
```php
'regu_id' => $peserta->regu_id,
```
Stores legacy regu_id in `cai_participant_replacements` table for historical audit. Non-critical — snapshot is informational.

### R4 — GantiPeserta cosmetic display
**File:** `app/Livewire/Database/Peserta/GantiPeserta.php:65`
```php
$this->regu = $participation->regu?->regu ?? $peserta->regu?->regu ?? '-';
```
Only used to display regu name in modal. Cosmetic. Canonical `$participation->regu?->regu` is primary.
