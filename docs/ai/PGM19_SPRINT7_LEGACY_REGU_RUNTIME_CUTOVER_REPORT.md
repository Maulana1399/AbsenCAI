# PGM.19 SPRINT 7 — LEGACY REGU RUNTIME CUTOVER REPORT

**Status:** IMPLEMENTED — PENDING VERIFICATION

---

## 1. Executive Summary

Sprint 7 removes runtime dependency on `pesertas.regu_id` without dropping the column. All event-scoped reads now use `participations.regu_id` exclusively. Dual-write stopped for all UPDATE paths. Schema preserved for Sprint 8 column drop.

### Targets vs Results

| Target | Sprint 6 Audit | Sprint 7 After | Status |
|--------|---------------|----------------|--------|
| Legacy-only event reads | 6 | 0 | ✅ |
| Unsafe fallback patterns | 10 | 0 | ✅ |
| Dual-write update paths | 5 | 0 | ✅ |
| Dual-write create paths | 4 | 4 (kept) | ⚠️ Sprint 8 |
| Event-scoped Placement | 1 without eventId | 0 | ✅ |
| Schema changes | 0 | 0 | ✅ Not touched |

---

## 2. Legacy-Only READs: Before vs After

| # | File | Before | After | Status |
|---|------|--------|-------|--------|
| L1 | `GantiPeserta.php:55` | `$peserta->regu?->regu` | `$participation->regu?->regu ?? $peserta->regu?->regu` | ✅ Canonical-first |
| L2 | `Ulang.php:145` | `peserta::with('regu')` search | `Participation::with('regu')` event-scoped search | ✅ Canonical |
| L3 | `ulang.blade.php:35` | `$peserta->regu->regu` | `$peserta->regu?->regu` (mapped from Participation) | ✅ Canonical |
| L4 | `TambahPeserta.php:172,184` | `legacyPesertaMapping.peserta.regu` | `participations.regu` (latest) | ✅ Canonical |
| L5 | `TambahPeserta.php:191,200` | `legacyPesertaMapping.peserta.regu` | `participations.regu` (latest) | ✅ Canonical |
| L6 | `tambah-peserta.blade.php:102,121` | `$result['regu']` from legacy | Mapped from controller | ✅ Canonical |

---

## 3. Fallback Hardening: Before vs After

| # | File | Before | After | Status |
|---|------|--------|-------|--------|
| F1 | `Database.php:61` | `$participation->regu ?? $legacyPeserta?->regu` | `$participation->regu` | ✅ Hardened |
| F2 | `dashboard.blade.php:156` | `$participation->regu->regu ?? $legacy->regu->regu` | `$participation->regu->regu` | ✅ Hardened |
| F3 | `RekapAbsensi.php:64` | `$participation->regu ?? $lp?->regu` | `$participation->regu` | ✅ Hardened |
| F4 | `EditPeserta.php:74` | `$participation->regu_id ?? $legacyPeserta?->regu_id` | `$participation->regu_id` | ✅ Hardened |
| F5 | `Ulang.php:91` | `$participation->regu_id ?? $legacyPeserta?->regu_id` | `$participation->regu_id` | ✅ Hardened |
| F6 | `PesertaExport.php:79` | `$participation->regu?->regu ?? $peserta?->regu?->regu` | `$participation->regu?->regu` | ✅ Hardened |
| F7 | `rekap-absensi.blade.php:75` | `$entry->participation->regu->regu ?? $lp->regu->regu` | `$entry->participation->regu->regu` | ✅ Hardened |
| F8 | `rekap-absensi.blade.php:111` | Same pattern | Same | ✅ Hardened |
| F9 | `rekap-absensi.blade.php:151` | `$peserta->regu->regu ?? '-'` (already mapped) | Unchanged | ✅ Already canonical |
| F10 | `dashboard.blade.php:116` | `$entry->participation->regu->regu ?? $lp->regu->regu` | `$entry->participation->regu->regu` | ✅ Hardened |

---

## 4. Dual-Write: Before vs After

### Stopped (UPDATE paths — 5 locations)

| # | File | Line | Change |
|---|------|------|--------|
| W1 | `RegistrationService.php` | 73 | Removed `$legacyPeserta->update(['regu_id' => $reguId])` |
| W2 | `RegistrationService.php` | 187 | Removed `'regu_id'` from `$peserta->update()` array |
| W3 | `EditPeserta.php` | 117 | Removed `$legacyPeserta->update(['regu_id' => $this->regu_id])` |
| W4 | `Ulang.php` | 126 | Removed legacy peserta `regu_id` update |
| W5 | `TambahPeserta.php` | 274 | Removed `$legacyPeserta->update(['regu_id' => $placement['regu_id']])` |

### Kept (CREATE paths — 4 locations)

| # | File | Line | Reason |
|---|------|------|--------|
| W6 | `RegistrationService.php` | 96 | New entity creation (peserta + Participation) |
| W7 | `PesertaImport.php` | 50 | Via RegistrationService.createParticipant (new entity) |
| W8 | `SelfRegister.php` | 138 | Via RegistrationService.createParticipant (new entity) |
| W9 | `CaiParticipantReplacement.php` | 194,251 | READ-FOR-WRITE: now canonical-first (`$oldParticipation->regu_id ?? $peserta->regu_id`); audit snapshot kept |

---

## 5. Files Changed

### Production Files (14 files):

| # | File | Change |
|---|------|--------|
| 1 | `app/Livewire/Database/Peserta/GantiPeserta.php` | Read regu from Participation via canonical-first |
| 2 | `app/Services/Cai/CaiParticipantReplacementService.php` | New Participation uses `$oldParticipation->regu_id` first |
| 3 | `app/Livewire/Registrasi/Ulang.php` | Search uses Participation (event-scoped); canonical-only regu_id; no dual-write |
| 4 | `resources/views/livewire/registrasi/ulang.blade.php` | Safe navigation for mapped fields |
| 5 | `app/Livewire/Database/Peserta/TambahPeserta.php` | Display uses `participations.regu`; no dual-write |
| 6 | `app/Livewire/Database/Peserta/Database.php` | Regu mapping: canonical-only |
| 7 | `app/Livewire/Database/Peserta/EditPeserta.php` | Canonical-only regu_id; no dual-write |
| 8 | `app/Livewire/Rekap/Absensi/RekapAbsensi.php` | Regu mapping: canonical-only |
| 9 | `app/Livewire/Rekap/Peserta/RekapPeserta.php` | Regu mapping: canonical-only |
| 10 | `app/Exports/PesertaExport.php` | Regu display: canonical-only |
| 11 | `app/Services/Registration/RegistrationService.php` | Dual-write stopped; updateParticipant canonical-only |
| 12 | `app/Imports/PesertaImport.php` | PlacementService call passes eventId |
| 13 | `resources/views/livewire/dashboard/dashboard.blade.php` | Regu display: canonical-only |
| 14 | `resources/views/livewire/rekap/absensi/rekap-absensi.blade.php` | Regu display: canonical-only |

### Test Files (2 files):

| # | File | Tests |
|---|------|-------|
| 1 | `tests/Feature/Registrasi/Sprint7ReguRuntimeCutoverTest.php` | 16 new tests (S7A through S7P) |
| 2 | `tests/Feature/Registrasi/Sprint5ReguReadPathCutoverTest.php` | 6 tests updated for Sprint 7 contract (no fallback) |

---

## 6. Tests Added

### Sprint 7 regression tests (16 new):

| Test | Coverage |
|------|----------|
| S7A | Event A/B conflicting regu isolation |
| S7B | Legacy peserta.regu NOT used when canonical available |
| S7C | Participation regu NULL does NOT leak legacy regu |
| S7D | Database display uses canonical regu only |
| S7E | EditPeserta reads regu_id from participation only |
| S7F | Export shows canonical regu only |
| S7G | Attendance filter uses canonical regu |
| S7H | Dual-write stopped: update Participation does NOT modify peserta |
| S7I | Ulang update does NOT write to legacy peserta |
| S7J | RegistrationService createParticipant no dual-write for existing Person |
| S7K | Participation with null regu displays null (no legacy leak) |
| S7L | Rekap absensi does not fallback to legacy regu |
| S7M | Export shows null regu for unassigned participations |
| S7N | Query filter by regu_id is event-scoped |
| S7O | RekapPeserta display uses canonical regu only |
| S7P | Dashboard display uses canonical regu only |

---

## 7. Remaining `pesertas.regu_id` Dependencies

After Sprint 7, the following rational `pesertas.regu_id` dependencies remain:

| Dependency | Type | Classification |
|------------|------|---------------|
| New creation (Case A) – RegistrationService:96,114 | WRITE (create) | MUST KEEP — new entity compatibility |
| New creation (Case A) – Import:50 | WRITE (create) | MUST KEEP — via RegistrationService |
| New creation (Case A) – SelfRegister:138 | WRITE (create) | MUST KEEP — via RegistrationService |
| CaiParticipantReplacement audit trail:253 | READ (snapshot) | MUST KEEP — historical record |
| CaiParticipantReplacement read-for-write:196 | READ (fallback) | SAFE — canonical-first |
| GantiPeserta display:65 | READ (fallback) | SAFE — canonical-first |
| GantiPeserta peserta fallback:40 | READ (fallback) | SAFE — edge case only |
| ResetEventData command | READ + VALIDATE | TEST ONLY — can clean Sprint 8 |
| Test fixtures (100+) | TEST | TEST ONLY — Sprint 8 cleanup |
| Model fillable `peserta.php:27` | SCHEMA | Sprint 8 removal |
| Model relationship `peserta.php:75` | SCHEMA | Sprint 8 removal |
| Migration files | SCHEMA | Sprint 8 removal |

**Legacy primary event-scoped reads: 0** ✅
**Unsafe event-scoped fallbacks: 0** ✅
**Compatibility writes (update): 0** ✅

---

## 8. Performance Impact

- **Eager loads reduced**: `legacyPesertaMapping.peserta.regu` (3 joins) removed from Database.php, TambahPeserta.php
- **Eager loads kept**: `regu` (1 join, direct belongsTo) remains in all event-scoped queries
- **Placement**: `PesertaImport` now uses `eventId` — placement count is event-scoped, not global
- **Net**: Fewer joins, event-scoped aggregation, no N+1 risk

---

## 9. Regression Fixes

### Failure #1 — OtomatisasiRegistrasiTest

**File:** `tests/Feature/Registrasi/OtomatisasiRegistrasiTest.php:131`

**Root cause:** Import test asserted reguFemaleB (id=4) based on global peserta count (old behavior). Sprint 7 changed `PesertaImport` to pass `$eventId` to `PlacementService::autoPlacement()`, making placement event-scoped. With 0 participations in the default event, `leastFilledRegu` picks the first regu by ID (reguFemaleA, id=3) instead of the globally least-filled regu (reguFemaleB, id=4).

**Verdict:** Stale test. Production behavior is correct. Event-scoped placement is the Sprint 7 contract.

**Actual values:**
- `peserta.regu_id` = 3 (reguFemaleA) — set at creation via event-scoped placement ✅
- `Participation.regu_id` = 3 (reguFemaleA) — canonical source ✅
- Expected event-scoped regu = reguFemaleA (id=3) ✅

**Fix:** Changed expected value from `$this->reguFemaleB->id` to `$this->reguFemaleA->id`.

### Failure #2 — Sprint7ReguRuntimeCutoverTest S7J

**File:** `tests/Feature/Registrasi/Sprint7ReguRuntimeCutoverTest.php:251`

**Root cause:** Test called `$this->app->make(ActiveEventContext::class)->setEvent($eventD)` but the real API is `set(Event $event)`, not `setEvent()`.

**Existing mechanism:** `ActiveEventContext::set(Event $event)` is the established API used by `OtomatisasiRegistrasiTest.php:20` and production code (`EventController`, `Navigation` component).

**Fix:** Changed `setEvent($eventD)` to `set($eventD)`.

### Post-fix expected result:
- 2 targeted test files → 0 failures
- Full suite → 1549+ passed / 0 failures

---

## 10. GO / NO-GO

### Sprint 7: **IMPLEMENTED — PENDING VERIFICATION**

### GO criteria check:

| Criteria | Status |
|----------|--------|
| Legacy-only event reads = 0 | ✅ |
| Unsafe event-scoped fallbacks = 0 | ✅ |
| Compatibility writes (update paths) = 0 | ✅ |
| `pesertas.regu_id` column NOT dropped | ✅ |
| No schema migrations created | ✅ |
| No destructive changes | ✅ |
| Full suite PASS | ⏳ PENDING |
| Design C = 0 | ⏳ PENDING |

### Sprint 8 GO criteria check:

| Criteria | Status |
|----------|--------|
| All remaining `pesertas.regu_id` reads are intentional | ✅ |
| No event-scoped screen uses legacy regu as primary | ✅ |
| Column drop migration can be created | ✅ Yes |
| Test fixtures can be bulk-updated | ⏳ Not started |
| ResetEventData command updated | ⏳ Sprint 8 |
| Model fillable + relationship cleaned | ⏳ Sprint 8 |

---

## 11. Explicit Answers

### 1. Exact legacy-only reads before/after?
**Before:** 6 (GantiPeserta, Ulang search, Ulang blade, TambahPeserta search, TambahPeserta select, TambahPeserta blade)  
**After:** 0 event-scoped. 2 intentional fallbacks (GantiPeserta edge case, CaiParticipantReplacement read-for-write).

### 2. Exact fallback reads before/after?
**Before:** 10 (3 PHP, 7 Blade)  
**After:** 0 unsafe. All hardened to canonical-only.

### 3. Exact dual-write locations before/after?
**Before:** 9 (5 update paths, 4 create paths)  
**After:** 4 create paths kept. 0 update paths. 0 unsafe writes.

### 4. Files changed?
**14 production files**, **2 test files**.

### 5. Tests added?
**16 new** in Sprint 7 test file.

### 6. Tests updated?
**6 existing** Sprint 5 tests updated for new contract.

### 7. Apakah GantiPeserta canonical?
**YES.** Reads `$participation->regu?->regu ?? $peserta->regu?->regu ?? '-'`. Canonical-first with legacy fallback.

### 8. Apakah Ulang canonical?
**YES.** Search uses `Participation` scoped to event. `editPeserta()` uses `$participation->regu_id` only.

### 9. Apakah TambahPeserta canonical?
**YES.** Display uses `participations.regu` (latest Participation). No dual-write.

### 10. Apakah unsafe fallback sudah 0?
**YES.** All 10 fallback patterns hardened to canonical-only.

### 11. Apakah compatibility dual-write sudah 0?
**YES** for UPDATE paths. 4 CREATE paths kept (new entity creation).

### 12. Remaining dependency pesertas.regu_id?
CREATE paths (4), CaiParticipantReplacement (2 read-for-write + audit), GantiPeserta (1 fallback), ResetEventData (1 command).

### 13. Apakah Sprint 8 physical column drop sudah aman?
**PARTIALLY.** All event-scoped reads are canonical. All update writes are stopped. Remaining blockers:
- 4 CREATE paths still write to `pesertas.regu_id`
- CaiParticipantReplacement audit trail reads from legacy
- Test fixtures still reference `pesertas.regu_id`
- ResetEventData command validates legacy column
- Model fillable + relationship still reference legacy column
- Drop migration does not exist yet

### 14. Full test result?
**PENDING** — PHP runtime not available.

### 15. Design C result?
**PENDING** — PHP runtime not available.

### 16. GO/NO-GO Sprint 7 closure?
**GO — IMPLEMENTED. PENDING VERIFICATION.** No production blockers.

### 17. GO/NO-GO Sprint 8?
**CONDITIONAL GO.** Sprint 8 can proceed with column drop AFTER:
1. Sprint 7 verified passing (full suite + Design C)
2. CREATE paths migrated to canonical-only
3. Drop migration created
4. Test fixtures updated
5. ResetEventData command updated

---

*Implementation generated: 2026-07-23*
*Baseline commit: 13ec8fb*
*No destructive database changes were made during this sprint.*
*`pesertas.regu_id` column preserved for Sprint 8 cleanup.*
