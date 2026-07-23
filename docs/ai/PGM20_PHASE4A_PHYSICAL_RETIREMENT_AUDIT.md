# PGM.20 PHASE 4A — LEGACY NIP PHYSICAL RETIREMENT AUDIT

**Status:** AUDIT COMPLETE — NO CHANGES MADE
**Date:** 2026-07-23
**Baseline:** 1576 passed / 3760 assertions / 0 failures
**Design C:** problem_total = 0

---

## 1. EXECUTIVE SUMMARY

Phase 4A audits all remaining legacy NIP dependencies to determine what blocks physical column retirement.

**Key finding:** NIP is safe to retire from `people` immediately but cannot be retired from `pesertas` or `absensis` until:
1. `pesertas.nip` is made nullable (schema migration needed)
2. `legacyNextNip()` internal generation is stopped
3. `Absensi` legacy read bridge is adapted or the table is archived

**No dead code (Class F) found.** All remaining references are active schema compatibility (B), diagnostic (C), display-only (D), or legacy bridge (E).

---

## 2. REMAINING NIP REFERENCE COUNT

| Class | Count | Description |
|:------|:-----:|-------------|
| **A — Canonical Person dependency** | **0** | ✅ Fully eliminated in Phases 1–3 |
| **B — Schema compatibility** | ~40 | Reads/writes on `pesertas.nip`, `people.nip`, `absensis.nip` (current DB columns) |
| **C — Diagnostic/migration** | ~10 | `AuditLegacyData`, `AttendanceDiagnose`, `AttendanceParityService` |
| **D — Display-only** | ~25 | Blade views showing legacy NIP; read-only form fields |
| **E — Legacy bridge** | ~8 | `legacyNextNip()`, `legacy_nip` snapshots, RegistrationService bridge logic |
| **F — Dead/obsolete** | **0** | None identified |
| **G — Test-only** | Pending | Not audited in this report (separate task) |

---

## 3. PEOPLE.NIP DECISION

### Current state
- **Created by:** `2026_07_20_000001_create_people_table.php:16` — `$table->integer('nip')->nullable()->unique()`
- **Nullable:** YES
- **Unique:** YES (UNIQUE constraint)
- **FK/Index:** No FK. No explicit index (UNIQUE creates an index implicitly).
- **Incoming FKs:** None (no foreign key references `people.nip`)

### Production dependencies

| Type | Count | Details |
|------|:-----:|---------|
| **Writes** | 3 | `RegistrationService::createParticipant()` — `'nip' => null` (line 105); `ManualParticipantRegistrationService` — `'nip' => null` (line 139); `PengajianImportService` — `'nip' => null` (line 192). ALL write null. |
| **Canonical reads** | 0 | ✅ No production code reads `people.nip` as canonical identity |
| **Display reads** | ~10 | Views use `$person?->nip` with `?? '-'` fallback. Always shows '-' since all new Persons have null nip. |
| **Search reads** | ~4 | `Database.php:43`, `IndexPerson.php:26`, `Ulang.php:129`, `SuratIzin/Create.php:35` — search by `people.nip`. Always returns no results since all new Persons have null nip. |
| **Model fillable** | **0** | ✅ Removed from Person `$fillable` in Phase 3 |
| **Legacy mapping** | 0 | `LegacyPesertaMapping.legacy_nip` reads from `peserta.nip`, not `people.nip` |
| **Diagnostic** | 0 | `AuditLegacyData`/`AttendanceDiagnose` audit `pesertas.nip` and `absensis.nip`, not `people.nip` |

### Recommendation

**DROP UNIQUE — MAKE NULLABLE PASSIVE — RETAIN COLUMN**

- `people.nip` UNIQUE constraint can be dropped immediately (no reads depend on uniqueness).
- The column can remain as a nullable, non-unique column for now. All new writes set it to null.
- Physical column removal is LOW priority. The column is nullable and never used, so it adds zero operational cost. Removal simplifies the schema but provides no runtime benefit.

**Risk:** LOW. Dropping UNIQUE may affect schema-dependent tooling but no runtime code depends on `people.nip` uniqueness.

**Prerequisite:** Migration to drop UNIQUE constraint: `$table->dropUnique(['nip'])` on `people` table.

---

## 4. PESERTAS.NIP DECISION

### Current state
- **Created by:** `2025_06_16_071812_create_peserta_table.php:17` — `$table->integer('nip')` (NOT NULL)
- **Rebuilt in:** `2026_07_01_000002` (table rebuild, still NOT NULL)
- **Nullable:** **NO** — `integer NOT NULL`
- **Unique:** **YES** — added by `2026_07_02_000001_add_unique_constraints_to_tables.php:12`
- **FK/Index:** No FK on `pesertas.nip`. Unique constraint creates implicit index.
- **Incoming FKs:** `absensis.nip` is NOT a FK but `Absensi::peserta()` uses `belongsTo(peserta::class, 'nip', 'nip')` — a logical join, not a physical FK.

### Production dependencies

| Type | Count | Details |
|------|:-----:|---------|
| **Canonical writes** | 2 | `RegistrationService::createParticipant()` — `'nip' => $internalNip` (line 93); `PesertaImport` — through `createParticipant()`. Internal only. |
| **Canonical reads** | 0 | ✅ No runtime code reads `pesertas.nip` for canonical identity |
| **Legacy generation** | 2 | `PlacementService::legacyNextNip()` — generates NIP for NOT NULL column; called by `RegistrationService::createParticipant()` |
| **Legacy bridge reads** | ~7 | `AttendanceReadService` (NIP cross-ref with absensis), `LegacyParticipationResolver::resolveByLegacyNip()`, `ParticipationResolver::resolveByNip()`, `SuratIzinService` (duplicate check), `AttendanceExceptionService` (duplicate check), `CaiParticipantReplacementService` (pre-replacement check) |
| **Diagnostic reads** | ~12 | `AttendanceDiagnose` (NIP lookup), `AuditLegacyData` (NIP audit), `ResetEventData` (NIP validation) |
| **Display reads** | ~8 | Views showing `$legacyPeserta?->nip` with fallback |
| **Snapshot writes** | 2 | `RegistrationService::createParticipant()` — `'legacy_nip' => $peserta->nip` (line 123); `CaiParticipantReplacementService` — `'legacy_nip' => $peserta->nip` (line 238) |
| **Model fillable** | 1 | `peserta.php:20` — `'nip'` in `$fillable` |

### Blockers (physical column drop)

| # | Blocker | Impact | Prerequisite |
|---|---------|--------|--------------|
| 1 | `pesertas.nip` is NOT NULL | Any `peserta::create()` without `'nip'` fails | Migration to make nullable + drop unique |
| 2 | `legacyNextNip()` still called | Generates unique NIP for NOT NULL column | Can only stop after column made nullable |
| 3 | Legacy bridge reads `pesertas.nip` | 7 services still search/match by NIP | Must migrate to participation_id or retire |
| 4 | Diagnostic commands read `pesertas.nip` | 3 commands audit NIP values | Must adapt or retire tools |
| 5 | Absensi model joins on `pesertas.nip` | `belongsTo(peserta::class, 'nip', 'nip')` | Must refactor or archive Absensi table |
| 6 | `legacy_nip` snapshots reference it | 2 models store `$peserta->nip` as snapshot | Can remain (snapshot is historical) |

### Recommendation

**MAKE NULLABLE FIRST — DROP UNIQUE — DROP COLUMN LATER**

**Step 1 (immediate):** Make `pesertas.nip` nullable and drop UNIQUE. This is safe because:
- No production runtime depends on `pesertas.nip` as canonical identity
- The column is internally generated and never user-facing
- `legacyNextNip()` can be called less frequently (only when peserta created)

**Step 2 (after Step 1):** Stop calling `legacyNextNip()`. The column is now nullable, so empty NIP is allowed.

**Step 3 (after Step 2):** Drop `pesertas.nip` column. This requires all bridge services to be migrated first.

**Risk:** MEDIUM. Making `pesertas.nip` nullable could affect:
- `AuditLegacyData` command (expects NIP to exist)
- `AttendanceDiagnose` command (joins on NIP)
- `ResetEventData` command (validates NIP unchanged)
- Legacy bridge services that search by NIP
- Tests that expect `peserta->nip` to be non-null

---

## 5. ABSENSIS LEGACY TABLE DECISION

### Current state
- **Created by:** `2025_06_23_031629_create_absensis_table.php`
- **Columns:** `id`, `nip` (NOT NULL), `nama`, `jam_scan`, `sesi_id` (added by `2026_07_06_000001`)
- **FK:** `sesi_id` → `sesi_absensis(id)` (nullable, nullOnDelete)
- **Relationship:** `Absensi::peserta()` uses `belongsTo(peserta::class, 'nip', 'nip')` — NO physical FK to `pesertas`
- **Model:** `app/Models/Absensi.php` — `$fillable = ['nip', 'nama', 'jam_scan', 'sesi_id']`

### Runtime dependencies

| Type | Count | Details |
|------|:-----:|---------|
| **Canonical writes** | **0** | ✅ `AttendanceService::processScan()` no longer writes to `absensis` (removed Phase 1) |
| **Canonical reads** | **0** | ✅ No runtime code uses `absensis` as source of truth |
| **Legacy read bridge** | 5 | `AttendanceReadService` (dashboard/rekap display), `SuratIzinService` (duplicate check), `AttendanceExceptionService` (duplicate check), `CaiParticipantReplacementService` (pre-replacement check) |
| **Diagnostic** | 3 | `AttendanceDiagnose` (scans absensis for unmappable records), `AttendanceParityService` (parity audit), `AttendanceBackfillService` (backfill tool) |
| **Dashboard display** | 2 | `DashboardTest` (legacy attendance display), `RekapAbsensi` (legacy attendance cross-reference) |

### Recommendation

**DEPRECATE — RETAIN TABLE — STOP ALL DEPENDENT CODE**

The `absensis` table is no longer written to (canonical writes stopped in Phase 1). All remaining dependencies are:
- Read-only legacy display (dashboard shows "legacy" attendance alongside canonical)
- Surat izin / exception duplicate checking (reads historical records)
- Diagnostic tooling (backfill, parity, diagnose)

**The table can be archived once these read paths are migrated to `event_attendances`.**

**Blockers:**
1. `AttendanceReadService` reads `absensis` for dashboard "sudah absen" display
2. `SuratIzinService` checks `absensis` for duplicate attendance
3. `AttendanceExceptionService` checks `absensis` for duplicate attendance
4. `AttendanceDiagnose` command reads `absensis`
5. `AttendanceBackfillService` reads `absensis`
6. `AttendanceParityService` reads `absensis`

**Risk:** MEDIUM. Archiving `absensis` before migrating read paths would break:
- Dashboard legacy attendance display
- Surat izin duplicate validation for legacy participants
- Diagnostic/backfill tools

---

## 6. LEGACY BRIDGE SERVICE MATRIX

| Service | Current role | Caller(s) | NIP dependency | Recommended action |
|---------|-------------|-----------|----------------|-------------------|
| `AttendanceReadService` | Composite attendance list (canonical + legacy) | `RekapAbsensi`, `Dashboard` | Reads `peserta.nip` + `absensis.nip` | **REFACTOR:** Use `participation_id` for canonical; retire legacy absensis cross-reference |
| `LegacyParticipationResolver::resolveByLegacyNip()` | Resolve NIP to Participation via peserta | `QRIdentityResolver` (removed Phase 2), tests | Reads `pesertas.nip` | **RETIRE:** No production callers remain |
| `ParticipationResolver::resolveByNip()` | Resolve NIP to Participation | `AttendanceParityService`, tests | Reads `pesertas.nip` | **KEEP temporarily:** Called by diagnostic + backfill |
| `SuratIzinService` | Duplicate attendance check before permit | `SuratIzinController` | Reads `pesertas.nip` + `absensis.nip` | **REFACTOR:** Switch to `peserta_id` or `participation_id` |
| `AttendanceExceptionService` | Duplicate check before izin | `Scan` component (manualIzin) | Reads `pesertas.nip` + `absensis.nip` | **REFACTOR:** Switch to `peserta_id` or `participation_id` |
| `CaiParticipantReplacementService` | Pre-replacement attendance check; legacy_nip snapshot | Replacement workflow | Reads `pesertas.nip` + `absensis.nip` | **REFACTOR:** Use `participation_id` for attendance check (legacy_nip snapshot is write-only, keep) |
| `AttendanceIdentity` | Identity resolution (retired Phase 1) | `AttendanceService` | Was `$this->nip = $peserta?->nip ?? $person?->nip` | ✅ ALREADY REFACTORED (Phase 1) |
| `AttendanceService::resolveIdentity()` | Identity resolution (retired Phase 1) | `processScan` | Was NIP fallback | ✅ ALREADY REFACTORED (Phase 1) |

---

## 7. DIAGNOSTIC/BACKFILL TOOL MATRIX

| Tool | Purpose | Runtime use? | Clean-state relevance | Recommended action |
|------|---------|:------------:|:---------------------:|-------------------|
| `AttendanceDiagnose` | Diagnose legacy absensis→peserta mapping health | CLI diagnostic | LOW (no data in clean DB) | **ADAPT:** Make NIP check optional/conditional on data existence |
| `AuditLegacyData` | S3.5 pre-migration data quality audit | CLI diagnostic | NONE (clean DB has no legacy data) | **RETIRE:** Was useful for S3.5 migration. Clean DB has nothing to audit. |
| `ResetEventData` | Reset event operational data, validate master data | CLI operational | HIGH (resets events with data) | **ADAPT:** Keep NIP validation in comparison but make it not fail if NIP changes (NIP is no longer canonical) |
| `AttendanceBackfillService` | Backfill legacy absensis→event_attendances | CLI backfill | NONE (clean DB has no absensis) | **ARCHIVE:** Keep code for potential recovery. No longer needed for clean deployments. |
| `AttendanceParityService` | Compare canonical vs legacy attendance | CLI diagnostic | LOW (no legacy data in clean DB) | **ADAPT:** Make NIP-based comparison conditional; primary comparison should be `participation_id`-based |

---

## 8. DISPLAY-ONLY NIP CLEANUP

| File/View | Current display | Replacement | Recommended action |
|-----------|----------------|-------------|-------------------|
| `database/peserta/database.blade.php:31` | `{{ $peserta->nip }}` | `participant_number` | **REPLACE** with `participant_number` |
| `database/peserta/edit-peserta.blade.php:5` | `wire:model="nip"` (readonly) | Remove field | **REMOVE** — no longer needed |
| `database/peserta/ganti-peserta.blade.php:23` | `{{ $nip }}` (peserta.nip) | `participant_number` | **REPLACE** with `participant_number` |
| `registrasi/ulang.blade.php:29` | `{{ $peserta->nip }}` | `participant_number` | **REPLACE** with `participant_number` |
| `rekap/absensi/rekap-absensi.blade.php` (3 lines) | `$entry->person?->nip ?? ($lp->nip ?? '-')` | `participant_number` | **REPLACE** with `participant_number` |
| `rekap/peserta/rekap-peserta.blade.php:110` | `{{ $p->nip }}` | `participant_number` | **REPLACE** with `participant_number` |
| `dashboard/dashboard.blade.php:115,155` | `$entry->person->nip ?? ($lp->nip ?? '-')` | `participant_number` | **REPLACE** with `participant_number` |
| `surat-izin/create.blade.php:27` | `{{ $p->nip ?? '-' }}` | Remove or use name only | **REMOVE** from search results |
| `surat-izin/index.blade.php:63` | `{{ $surat->peserta->nip ?? '' }}` | `participant_number` | **REPLACE** with `participant_number` |

---

## 9. PROPOSED PHASE 4 IMPLEMENTATION ORDER

### Step 1 — Make `pesertas.nip` nullable + drop UNIQUE
- **Files affected:** New migration (`make_pesertas_nip_nullable`)
- **Schema affected:** `pesertas.nip` → `integer nullable` (drop NOT NULL + UNIQUE)
- **Risk:** LOW-MEDIUM
- **Prerequisite:** None (all current code handles null)
- **Tests affected:** None expected (tests already expect null in many places)

### Step 2 — Stop `legacyNextNip()` generation
- **Files affected:** `PlacementService.php`, `RegistrationService.php`
- **Schema affected:** None
- **Risk:** LOW (column is now nullable)
- **Prerequisite:** Step 1
- **Tests affected:** `RegistrationServiceTest`, `PlacementServiceTest`
- **Action:** Remove `$internalNip = PlacementService::legacyNextNip(...)` from RegistrationService; remove `legacyNextNip()` from PlacementService

### Step 3 — Migrate legacy bridge reads to canonical paths
- **Files affected:** `SuratIzinService`, `AttendanceExceptionService`, `CaiParticipantReplacementService`
- **Risk:** MEDIUM
- **Prerequisite:** Step 2
- **Action:** Replace `Absensi::where('nip', ...)` with `EventAttendance::where('participation_id', ...)` or appropriate canonical check

### Step 4 — Adapt/archive diagnostic tools
- **Files affected:** `AuditLegacyData`, `AttendanceDiagnose` (partially), `ResetEventData` (partially)
- **Risk:** LOW (CLI-only tools)
- **Prerequisite:** Step 2
- **Action:** Remove NIP-specific sections from `AuditLegacyData`; make NIP checks in `AttendanceDiagnose` conditional; remove NIP from `ResetEventData` validation loop

### Step 5 — Remove `pesertas.nip` column
- **Files affected:** New migration (`drop_nip_from_pesertas`)
- **Schema affected:** `pesertas.nip` column removed
- **Risk:** HIGH (final removal)
- **Prerequisite:** Steps 1–4
- **Tests affected:** All tests accessing `peserta->nip` must be updated

### Step 6 — Drop `people.nip` UNIQUE (or full column)
- **Files affected:** New migration (`drop_nip_unique_from_people` or `drop_nip_from_people`)
- **Schema affected:** `people.nip` UNIQUE removed or column dropped
- **Risk:** LOW
- **Prerequisite:** Steps 1–5
- **Tests affected:** `PersonFoundationTest` schema tests

### Step 7 — Archive `absensis` table
- **Files affected:** New migration (`archive_absensis` or `drop_absensis`)
- **Schema affected:** `absensis` table archived/dropped
- **Risk:** HIGH
- **Prerequisite:** Steps 1–6 (all read paths migrated)
- **Tests affected:** All attendance tests referencing `Absensi` model

---

## 10. MIGRATION PLAN (NEW MIGRATIONS NEEDED)

| Order | Migration name | Action | Risk |
|:-----:|---------------|--------|:----:|
| 1 | `2026_08_12_000001_make_pesertas_nip_nullable` | Make `pesertas.nip` nullable; drop UNIQUE | LOW |
| 2 | `2026_08_13_000001_drop_unique_from_people_nip` | Drop UNIQUE from `people.nip` | LOW |
| 3 | `2026_08_14_000001_drop_nip_from_pesertas` | Drop `pesertas.nip` column | HIGH |
| 4 | `2026_08_15_000001_drop_nip_from_people` | Drop `people.nip` column | LOW |
| 5 | `2026_08_16_000001_drop_absensis_tables` | Drop `absensis` table | HIGH |

**NOTE:** Migration 3+ depends on production code changes first.

---

## 11. RISK ANALYSIS

| Step | Risk | Rationale |
|:----:|:----:|-----------|
| 1 | **LOW** | Making nullable is additive. No code breaks from having nulls. |
| 2 | **LOW** | Dropping UNIQUE on a column that is always written as null. |
| 3 | **MEDIUM** | Removing column affects all services that read/write `pesertas.nip`. Must ensure bridge services are migrated first. |
| 4 | **LOW** | Column is already nullable and always written as null. |
| 5 | **MEDIUM-HIGH** | Legacy read paths (dashboard, rekap, surat izin) still depend on `absensis`. Must migrate first. |

---

## 12. ROLLBACK STRATEGY

Each migration must have a `down()`:
- **Step 1 down:** `$table->integer('nip')->nullable(false)->change(); $table->unique('nip');`
- **Step 2 down:** `$table->unique('nip');`
- **Step 3 down:** `$table->integer('nip')->nullable();` — data loss risk (cannot restore individual values)
- **Step 4 down:** `$table->integer('nip')->nullable()->unique();` — data loss risk
- **Step 5 down:** `Schema::create('absensis', ...)` — data loss risk

---

## 13. TEST IMPACT

| Affected area | Tests affected | Impact |
|--------------|---------------|--------|
| `PersonFoundationTest` | Schema tests (column exists, unique) | Low — update assertions |
| `PlacementServiceTest` | `legacyNextNip()` tests | Medium — remove NIP generation tests |
| `RegistrationServiceTest` | Participant creation assertions | Medium — update NIP assertions |
| `AttendanceCutoverTest` | Absensi-related assertions | Low — already migrated |
| `AttendanceDualWriteTest` | Already migrated (Phase 1) | None |
| `CaiParticipantReplacementTest` | Person.nip assertions | Low — already expecting null |
| `DashboardTest` | Legacy absensis fixtures | Low — update fixture |

---

## 14. FINAL RECOMMENDATION

**CONDITIONAL GO** for Phase 4B (physical retirement) — with the following conditions:

1. **Immediate (Phase 4B Step 1):** Create migration to make `pesertas.nip` nullable + drop UNIQUE. This is safe and removes the primary physical blocker.
2. **Immediate (Phase 4B Step 2):** Drop UNIQUE from `people.nip`. Safe — column is always null.
3. **Deferred (Phase 4B Steps 3-5):** Delay `pesertas.nip` column removal, `people.nip` column removal, and `absensis` table retirement until the 5 legacy bridge services are refactored (Phase 4B Steps 3A-3E).
4. **Deferred:** Keep `legacyNextNip()` until after column is made nullable (then remove — it becomes unnecessary).

### Go/No-Go Matrix

| Phase 4B sub-phase | GO? | Blocker |
|:------------------|:---:|---------|
| Make `pesertas.nip` nullable + drop UNIQUE | **GO** ✅ | None |
| Drop `people.nip` UNIQUE | **GO** ✅ | None |
| Refactor SuratIzinService | **GO** ✅ | None (canonical path available) |
| Refactor AttendanceExceptionService | **GO** ✅ | None (canonical path available) |
| Refactor CaiParticipantReplacementService | **GO** ✅ | None (canonical path available) |
| Adapt diagnostic tools | **GO** ✅ | Clean DB — no data to audit |
| Drop `pesertas.nip` column | **NO-GO** ❌ | Bridge services still read it |
| Drop `people.nip` column | **NO-GO** ❌ | Bridge services still read it via mapping |
| Archive `absensis` table | **NO-GO** ❌ | Legacy read paths still depend on it |
| Retire `legacyNextNip()` | **NO-GO** ❌ | Column still NOT NULL (fix Step 1 first) |

**Recommendation:** Start Phase 4B with Steps 1-2 (schema changes) + Steps 3A-3E (service refactoring) in parallel. These have no blockers and provide immediate value.
