# PGM.19 SPRINT 6 — LEGACY REGU RETIREMENT AUDIT

**Status:** AUDIT COMPLETE — READ ONLY

---

## 1. Executive Summary

Sprint 5 completed canonical-first read-path cutover across 24/26 locations. Sprint 6 evaluates whether `pesertas.regu_id` can be retired, dual-write stopped, and the column dropped.

**Verdict: NOT YET RETIRABLE — HIGH DEPENDENCY COUNT.**

| Dependency Category | Count | Migratable? |
|---------------------|-------|-------------|
| Dual-write WRITE locations | 9 | Sprint 7+ |
| Canonical-first FALLBACK reads | 10 | After dual-write stops |
| Legacy-only READ locations | 6 | Sprint 7 |
| Legacy attendance tables | 2 | Sprint 8+ |
| Test fixtures with `pesertas.regu_id` | 100+ | Sprint 7 |
| Schema definitions | 4 files | Sprint 8 |
| Sync service exclusion | 1 file | Sprint 8 |

---

## 2. Dual-Write Inventory (9 locations)

All locations that write `regu_id` to `pesertas` table:

| # | File | Line | Context | Writes To |
|---|------|------|---------|-----------|
| W1 | `RegistrationService.php` | 62-73 | Create Participant (existing Person) | `pesertas.regu_id` + `participations.regu_id` |
| W2 | `RegistrationService.php` | 96 | Create Participant (new Person) | `pesertas.regu_id` + `participations.regu_id` |
| W3 | `RegistrationService.php` | 187 | Update Participant (legacy) | `pesertas.regu_id` + `participations.regu_id` |
| W4 | `RegistrationService.php` | 193 | Update Participant (participation) | `pesertas.regu_id` + `participations.regu_id` |
| W5 | `EditPeserta.php` | 102,117 | Edit Peserta form submit | `pesertas.regu_id` + `participations.regu_id` |
| W6 | `Ulang.php` | 113,126 | Ulang form submit | `pesertas.regu_id` + `participations.regu_id` |
| W7 | `TambahPeserta.php` | 270,274 | Add existing Person to event | `pesertas.regu_id` + `participations.regu_id` |
| W8 | `PesertaImport.php` | 50 | Import peserta | `pesertas.regu_id` only |
| W9 | `SelfRegister.php` | 138 | Self-registration | `pesertas.regu_id` (via RegistrationService) |

**Critical finding:** Dual-write is intentional and required — whenever one side updates, the other side MUST be updated too for consistency. Stopping dual-write means `pesertas.regu_id` becomes stale immediately.

**Dual-write can only stop after ALL legacy reads are migrated and all fallbacks removed.**

---

## 3. Canonical-First Fallback Reads (10 locations)

These use `$participation->regu ?? $legacyPeserta?->regu` pattern:

| # | File | Line | Context |
|---|------|------|---------|
| F1 | `Database.php` | 61 | `$participation->regu ?? $legacyPeserta?->regu` |
| F2 | `dashboard.blade.php` | 156 | `$participation->regu->regu ?? $legacy->regu->regu` |
| F3 | `RekapAbsensi.php` | 64 | `$participation->regu ?? $lp?->regu` |
| F4 | `EditPeserta.php` | 74 | `$participation->regu_id ?? $legacyPeserta?->regu_id` |
| F5 | `Ulang.php` | 91 | `$participation->regu_id ?? $legacyPeserta?->regu_id` |
| F6 | `PesertaExport.php` | display | `$participation->regu?->regu ?? $peserta?->regu?->regu` |
| F7 | `rekap-absensi.blade.php` | 75 | `$entry->participation->regu->regu ?? $lp->regu->regu` |
| F8 | `rekap-absensi.blade.php` | 111 | `$entry->participation->regu->regu ?? $lp->regu->regu` |
| F9 | `rekap-absensi.blade.php` | 151 | `$peserta->regu->regu ?? '-'` (mapped canonical-first in RekapAbsensi) |
| F10 | `dashboard.blade.php` | 116 | `$entry->participation->regu->regu ?? $lp->regu->regu` |

**Note:** F9 is canonical-first because RekapAbsensi.php maps `'regu' => $participation->regu ?? $lp?->regu`. The blade accesses the mapped property which already resolved canonical-first.

**Fallbacks can only be simplified to canonical-only AFTER dual-write stops and `pesertas.regu_id` is confirmed stale-safe.**

---

## 4. Legacy-Only READ Locations (6 locations)

These read `pesertas.regu_id` without canonical fallback:

| # | File | Line | Context | Path |
|---|------|------|---------|------|
| L1 | `GantiPeserta.php` | 55 | Display regu in ganti-peserta modal | `$peserta->regu?->regu` |
| L2 | `Ulang.php` | 145 | Search legacy peserta table | `peserta::with('regu')` |
| L3 | `ulang.blade.php` | 35 | Search results regu display | `$peserta->regu->regu` |
| L4 | `TambahPeserta.php` | 172,184 | Search existing Person for event-add | `legacyPesertaMapping.peserta.regu` |
| L5 | `TambahPeserta.php` | 191,200 | Person select display | `legacyPesertaMapping.peserta.regu` |
| L6 | `tambah-peserta.blade.php` | 102,121 | Search results + selected person regu | `$result['regu']`, `$selectedPerson['regu']` |

### L1: GantiPeserta — Deep Dive

```
GantiPeserta.php:36 — peserta::with(['desa', 'kelompok', 'regu'])->findOrFail($id);
GantiPeserta.php:55 — $this->regu = $peserta->regu?->regu ?? '-';
```

- **Why legacy?** `GantiPeserta` operates on CAI participant replacement, which is a legacy-only feature. It uses `peserta.id` directly (no Participation, no event context).
- **Migration path:** Replace with Person+Peserta model. But this requires changes to `CaiParticipantReplacementService` which is deeply tied to legacy.
- **Risk:** LOW — cosmetic display only. If `pesertas.regu_id` goes stale, it shows wrong regu but doesn't break replacement logic.

### L2-L3: Ulang Search — Deep Dive

```
Ulang.php:145 — $peserta = peserta::with(['desa', 'kelompok', 'regu'])...
ulang.blade.php:35 — {{ $peserta->regu->regu ?? '-' }}
```

- **Why legacy?** Searches `pesertas` table directly (not Participation). Lists participants who can re-register.
- **Migration path:** Change query to `Participation::with('regu')` with event scope.
- **Risk:** LOW — only affects search results display for admin re-registration.

### L4-L6: TambahPeserta — Deep Dive

```
TambahPeserta.php:172 — ->with(['desa', 'kelompok', 'legacyPesertaMapping.peserta.regu'])
TambahPeserta.php:184 — 'regu' => $p->legacyPesertaMapping?->peserta?->regu?->regu,
TambahPeserta.php:191 — Person::with(['desa', 'kelompok', 'legacyPesertaMapping.peserta.regu'])
TambahPeserta.php:200 — 'regu' => $person->legacyPesertaMapping?->peserta?->regu?->regu,
```

- **Why legacy?** Shows the regu that a Person currently has (across all events) when adding them to a new event. This is a cross-event display.
- **Migration path:** Could show regu from most recent Participation, or leave as legacy since this is informational.
- **Risk:** LOW — only cosmetic. If stale, wrong regu is shown but doesn't affect placement (auto-placement recalculates).

---

## 5. Write Path: CaiParticipantReplacementService (2 locations)

| # | File | Line | Context |
|---|------|------|---------|
| C1 | `CaiParticipantReplacementService.php` | 194 | New Participation created with `'regu_id' => $peserta->regu_id` |
| C2 | `CaiParticipantReplacementService.php` | 251 | Audit snapshot records `'regu_id' => $peserta->regu_id` |

C1 reads `$peserta->regu_id` to set on the new Participation. This is a READ-FOR-WRITE — it copies legacy regu to canonical on replacement.

C2 snapshots the value for audit trail. This is read-only and will become historical after retirement.

---

## 6. Schema Definitions

| Table | Column | FK | Nullable | On Delete |
|-------|--------|----|----------|-----------|
| `pesertas` | `regu_id` | `regus.id` | YES (after 2026-07-01 migration) | `CASCADE` |
| `participations` | `regu_id` | `regus.id` | YES | `SET NULL` |
| `cai_participant_replacements` | `regu_id` | `regus.id` | YES | not specified (no explicit cascade) |

### Migration Files

| File | Created | Purpose |
|------|---------|---------|
| `2025_06_16_071812_create_peserta_table.php` | Sprint 1 | Created `pesertas.regu_id` (original) |
| `2026_07_01_000002_make_jenis_kelamin_nullable_on_pesertas_table.php` | Sprint 3/4 | Recreated table with nullable `regu_id` |
| `2026_08_10_000001_add_regu_id_to_participations_table.php` | Sprint 4 | Added `regu_id` to `participations` + backfill |
| `2026_07_21_095318_create_cai_participant_replacements_table.php` | Sprint 2 | Created `cai_participant_replacements.regu_id` |

### Migration to drop `pesertas.regu_id`
**Does not exist yet.** Would need to be created as part of Sprint 8.

---

## 7. Legacy Attendance Fallback (LegacyReguRead)

### AttendanceReadService still reads legacy attendance:
- `Absensi` table (legacy hadir records)
- `IzinAbsensi` table (legacy izin records)
- `config('features.attendance_legacy_write')` still active

**Regu-specific:** AttendanceReadService DOES use canonical `participations.regu_id` for filtering (Sprint 5 completed). But the attendance status resolution still falls back to legacy tables.

**Impact on regu retirement:** LOW — regu filter is already canonical. Legacy attendance tables don't store regu data.

---

## 8. Test Fixture Impact

Tests create `pesertas` records with `regu_id` as part of fixtures. If column is dropped, ALL these tests break:

| Test File | Occurrences |
|-----------|-------------|
| `Sprint4ReguEventScopingTest.php` | ~30 |
| `Sprint5ReguReadPathCutoverTest.php` | ~15 |
| `Sprint2MappingContractTest.php` | 2 |
| `RegistrationServiceTest.php` | 3 |
| `DesignCDiagnosticsTest.php` | 1 |
| `AttendanceStatusSummaryTest.php` | 2 |
| `EventCaiProtectionTest.php` | 1 |
| `ExportLogTest.php` | 1 |
| `MasterDataProtectionTest.php` | 1 |
| `PersonMasterDataTest.php` | 5 |
| `HapusPesertaSafetyTest.php` | 2 |
| `CaiParticipantReplacementDesignCTest.php` | 1 |
| `CaiParticipantReplacementTest.php` | 2 |
| `ImportDataTest.php` | 1 |
| `PlacementServiceTest.php` | 4 |
| `DatabaseSeeder.php` | 2 |

**Total: ~75+ direct fixture usages + 25 assertion/query usages = ~100+ total.**

---

## 9. ResetEventData Command

`ResetEventData.php` explicitly validates and references `pesertas.regu_id`:
- Line 164: FK dependency documented
- Line 198, 281: Selects `regu_id` in snapshot/validation queries
- Line 325-331: Validates no orphan `regu_id`
- Line 293, 300: Includes `regu_id` in field-level verification

**Must be updated if column is dropped.**

---

## 10. PersonLegacySyncService

`PersonLegacySyncService.php:18` — Explicitly documents:
```
* Fields NOT synced: nip, regu_id, participant_number, attendance_code, status_registrasi
```

This means Person edits never change `pesertas.regu_id`. The only way `pesertas.regu_id` gets updated is through the dual-write paths in RegistrationService, EditPeserta, Ulang, and TambahPeserta.

**Implication:** If dual-write stops, `pesertas.regu_id` becomes stale immediately. After Sprint 5, it's only a fallback — but if dual-write stops, the fallback becomes unreliable.

---

## 11. Retirement Readiness Scorecard

| Requirement | Status | Sprint Target |
|-------------|--------|---------------|
| All event-scoped reads use canonical-first | ✅ Sprint 5 | Sprint 5 |
| All filters use `participations.regu_id` | ✅ Sprint 5 | Sprint 5 |
| Multi-event isolation confirmed | ✅ Sprint 5 | Sprint 5 |
| Legacy-only reads migrated (GantiPeserta, Ulang) | ❌ 6 remaining | Sprint 7 |
| Dual-write stopped | ❌ 9 locations | Sprint 7 |
| Fallback patterns simplified to canonical-only | ❌ 10 locations | Sprint 7 |
| Legacy attendance tables removed | ❌ Still active | Sprint 8+ |
| `pesertas.regu_id` column dropped | ❌ Not started | Sprint 8 |
| Test fixtures updated | ❌ 100+ changes | Sprint 7 |
| ResetEventData command updated | ❌ Not started | Sprint 8 |
| PersonLegacySyncService documentation updated | ❌ Not started | Sprint 8 |

---

## 12. Dependency Graph

```
pesertas.regu_id  ─┬──> Dual-Write (9 locations)
                    │         ↓
                    ├──> Legacy Reads (6 locations)
                    │         ↓
                    ├──> Fallback Reads (10 locations)
                    │         ↓
                    ├──> Test Fixtures (100+)
                    │         ↓
                    ├──> ResetEventData Command
                    │         ↓
                    ├──> CaiParticipantReplacementService (2 read-for-write)
                    │         ↓
                    └──> PersonLegacySyncService (explicit exclusion)
```

**Critical path:**
1. Stop dual-write → requires migrating legacy reads
2. Migrate legacy reads → requires replacing GantiPeserta, Ulang, TambahPeserta patterns
3. Remove fallbacks → requires dual-write stopped first
4. Drop column → requires all above complete

---

## 13. Recommended Sprint 7 Scope

1. **Migrate GantiPeserta** — Replace `peserta::with('regu')` with `Participation`-based lookup. Pass `peserta_id` through `Participation` mappings instead of direct legacy query.

2. **Migrate Ulang search** — Change `peserta::with('regu')` to `Participation::with('regu')->where('event_id', $event->id)`. This also makes search event-scoped (current bug: searches ALL events).

3. **Migrate TambahPeserta display** — Replace `legacyPesertaMapping.peserta.regu` with `person->participations()->latest()->first()?->regu` for cross-event regu display.

4. **Stop dual-write** — Remove `pesertas.regu_id` updates from:
   - `RegistrationService.php` (lines 73, 187)
   - `EditPeserta.php` (line 117)
   - `Ulang.php` (line 126)
   - `TambahPeserta.php` (line 274)
   - Keep `PesertaImport.php` and `SelfRegister.php` writes until import flow is updated
   - Keep `CaiParticipantReplacementService.php` reads-for-write until replacement is migrated

5. **Simplify fallback patterns** — Change `$participation->regu ?? $legacyPeserta?->regu` to `$participation->regu` in all 10 locations.

6. **Update test fixtures** — Remove `regu_id` from `peserta::create()` calls in tests.

---

## 14. Recommended Sprint 8 Scope

1. **Drop `pesertas.regu_id` column** — Create migration:
   ```php
   Schema::table('pesertas', function (Blueprint $table) {
       $table->dropForeign(['regu_id']);
       $table->dropColumn('regu_id');
   });
   ```

2. **Remove `peserta.regu()` relationship** — Remove from `app/Models/peserta.php`

3. **Remove `regu_id` from `peserta` fillable** — Remove from `$fillable` array

4. **Update ResetEventData command** — Remove all `regu_id` references

5. **Update PersonLegacySyncService** — Remove `regu_id` from "NOT synced" comment

6. **Update documentation** — Remove all `pesertas.regu_id` references from docs

---

## 15. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Stopping dual-write causes stale legacy data | HIGH | MEDIUM | Sprint 5 already made canonical primary; legacy is fallback only |
| GantiPeserta breaks if regu_id is stale | LOW | LOW | Only cosmetic display; replacement logic unaffected |
| Ulang search shows wrong regu | LOW | LOW | Only admin search results display |
| Tests break | HIGH | HIGH | Must update fixtures before dropping column |
| Rollback needed | MEDIUM | HIGH | Keep migration reversible; keep dual-write switch config |
| Legacy attendance still needed | MEDIUM | MEDIUM | regu retirement independent; separate work item |

---

## 16. GO / NO-GO Decision

### Retire `pesertas.regu_id` in Sprint 6?
**NO-GO.** Too many dependencies:

- 9 dual-write locations still active
- 6 legacy-only reads not migrated
- 10 fallback patterns still reference legacy
- 100+ test fixture changes needed
- No migration script exists to drop the column

### Stop dual-write in Sprint 6?
**NO-GO.** Legacy reads (GantiPeserta, Ulang, TambahPeserta) depend on `pesertas.regu_id` being up-to-date. Dual-write can only stop AFTER migrating those reads.

### Recommended: Sprint 7 for dual-write stop, Sprint 8 for column drop.

---

## 17. Sprint 7 Prerequisites

Before Sprint 7 can begin:

1. **Sprint 5 must be verified passing** — Run full test suite:
   ```bash
   php -d memory_limit=-1 vendor/bin/pest
   php artisan diagnose:design-c
   ```

2. **Sprint 5 must be stable in production** — At least 1 sprint of canonical-first read path in production to confirm no regressions.

3. **GantiPeserta migration design** — Document the design for replacing `peserta::with('regu')` with Participation-based lookup.

4. **Ulang search migration design** — Document the design for changing from `peserta` table to `Participation` table.

5. **TambahPeserta display migration design** — Document the design for cross-event regu display.

---

## 18. Appendix: Full `regu_id` Reference Map

### app/ — Production Code

#### Models
- `peserta.php:27` — `$fillable` includes `'regu_id'`
- `peserta.php:75-78` — `regu()` belongsTo relationship
- `Participation.php:15` — `$fillable` includes `'regu_id'`
- `Participation.php:28-31` — `regu()` belongsTo relationship
- `CaiParticipantReplacement.php:21` — `$fillable` includes `'regu_id'`

#### Services — Write Path
- `RegistrationService.php:62` — Fallback read: `$data['regu_id'] ?? $legacyPeserta?->regu_id`
- `RegistrationService.php:70` — Write to Participation: `'regu_id' => $reguId`
- `RegistrationService.php:73` — Dual-write to peserta: `$legacyPeserta->update(['regu_id' => $reguId])`
- `RegistrationService.php:96` — Write to peserta: `'regu_id' => $data['regu_id']`
- `RegistrationService.php:114` — Write to Participation: `'regu_id' => $data['regu_id']`
- `RegistrationService.php:187` — Dual-write to peserta: `'regu_id' => $data['regu_id']`
- `RegistrationService.php:193` — Dual-write to Participation: `'regu_id' => $data['regu_id']`
- `PlacementService.php:89` — Auto-placement returns `'regu_id' => $regu?->id`
- `CaiParticipantReplacementService.php:194` — Read-for-write: `'regu_id' => $peserta->regu_id`
- `CaiParticipantReplacementService.php:251` — Snapshot: `'regu_id' => $peserta->regu_id`

#### Services — Read Path
- `AttendanceReadService.php:25` — Filter: `$participationQuery->where('regu_id', $reguId)` ✅ canonical

#### Services — Sync
- `PersonLegacySyncService.php:18` — Explicitly does NOT sync `regu_id`

#### Livewire Components
- `Dashboard/Dashboard.php:21,49,74,83` — Filter: `$this->regu_id` → `$readService->getSessionAttendance(..., $reguId)` ✅ canonical
- `Rekap/Absensi/RekapAbsensi.php:15,20,43` — Filter: `$this->regu_id` → `getSessionAttendance(..., $reguId)` ✅ canonical
- `Rekap/Absensi/RekapAbsensi.php:64` — Display: `$participation->regu ?? $lp?->regu` ✅ canonical-first
- `Rekap/Peserta/RekapPeserta.php:18,46,47,119,127` — Filter: `where('regu_id', $this->regu_id)` ✅ canonical; Display: canonical-first
- `Database/Peserta/Database.php:23,36,61` — Display: `$participation->regu ?? $legacyPeserta?->regu` ✅ canonical-first
- `Database/Peserta/EditPeserta.php:28,74,87,102,117` — Read: `$participation->regu_id ?? $legacyPeserta?->regu_id` ✅ canonical-first; Write: dual-write
- `Database/Peserta/GantiPeserta.php:22,36,55,114` — Display: `$peserta->regu?->regu` ❌ legacy-only
- `Database/Peserta/TambahPeserta.php:31,57,84,134,172,184,191,200,270,274` — Display: `legacyPesertaMapping.peserta.regu` ❌ legacy-only; Write: dual-write
- `Registrasi/Ulang.php:91,113,126,145` — Read: `$participation->regu_id ?? $legacyPeserta?->regu_id` ✅ canonical-first; Write: dual-write
- `Registrasi/Ulang.php:145` — Search: `peserta::with('regu')` ❌ legacy-only
- `Registrasi/SelfRegister.php:36,57,84,138` — Write: dual-write via RegistrationService
- `QRLabel/Index.php:250` — Filter: `$query->where('regu_id', $this->filterRegu)` ✅ canonical

#### Exports
- `PesertaExport.php:14,22,30,47,48` — Filter: `$query->where('regu_id', $this->regu_id)` ✅ canonical; Display: canonical-first

#### Imports
- `PesertaImport.php:50` — Write: `'regu_id' => $autoPlacement['regu_id']`

#### Commands
- `ResetEventData.php:164,198,207,281,293,300,325,326,329,331` — Validates and snapshots `pesertas.regu_id`

### resources/ — Blade Templates

- `dashboard/dashboard.blade.php:44,48,49,116,156` — Filter select + display ✅ canonical-first
- `rekap/absensi/rekap-absensi.blade.php:16,18,19,75,111,151` — Filter select + display ✅ canonical-first (mapped)
- `rekap/peserta/rekap-peserta.blade.php:9` — Filter select
- `database/peserta/database.blade.php:37` — Display: `$peserta->regu->regu` ✅ canonical-first (mapped in Database.php)
- `database/peserta/edit-peserta.blade.php:47,49,50` — Form select
- `database/peserta/ganti-peserta.blade.php:38` — Display: `$regu` ❌ legacy-only
- `database/peserta/tambah-peserta.blade.php:41,102,121` — Display: legacy ❌ legacy-only
- `database/peserta/import-peserta.blade.php:5` — Info text
- `registrasi/ulang.blade.php:35,165` — Display: `$peserta->regu->regu` ❌ legacy-only
- `registrasi/self-register.blade.php:48` — Display: auto-placement

### routes/web.php
- Line 153: `$query->where('regu_id', request('regu'))` ✅ canonical (QR filtered print)
- Line 330-331: Doc comment + `$query->where('regu_id', request('regu'))` ✅ canonical (A4 print)
- Line 341: Filter ✅ canonical

### database/ — Migrations
- `2025_06_16_071812_create_peserta_table.php:21` — Created original `regu_id`
- `2026_07_01_000002_make_jenis_kelamin_nullable_on_pesertas_table.php:22,27,28,49,54,55` — Made nullable, recreated column
- `2026_08_10_000001_add_regu_id_to_participations_table.php` — Added to participations + backfill
- `2026_07_21_095318_create_cai_participant_replacements_table.php:57` — Created for replacement audit

### database/seeders
- `DatabaseSeeder.php:103,121` — Creates peserta with `regu_id`

### tests/ — 100+ references in test fixtures and assertions

---

## 19. Key Findings Summary

1. **Sprint 5 was successful** — All event-scoped reads use canonical-first. Filters use `participations.regu_id`.

2. **`pesertas.regu_id` still has 9 write paths.** Dual-write is the #1 blocker for retirement.

3. **6 legacy-only reads remain** in GantiPeserta, Ulang, and TambahPeserta. None are event-scoped, all are cosmetic.

4. **10 fallback patterns still reference `pesertas.regu_id`.** These can only be simplified after dual-write stops.

5. **100+ test changes needed.** Not a blocker but heavy.

6. **No migration exists to drop the column.** Must be created.

7. **Legacy attendance tables (Absensi, IzinAbsensi) are independent** of regu retirement. They can be addressed separately in Sprint 8+.

8. **Design C `problem_total` must be 0** before any structural changes.

---

## 20. Conclusion

**Sprint 6 verdict: READ ONLY — NO PRODUCTION CHANGES.**

`pesertas.regu_id` cannot be retired in Sprint 6. Dependencies are too numerous and too deep. Three sprints are required:

| Sprint | Scope |
|--------|-------|
| **Sprint 6** | ✅ Audit (this document) — 0 production changes |
| **Sprint 7** | Migrate legacy reads → Stop dual-write → Simplify fallbacks → Update tests |
| **Sprint 8** | Drop column → Update commands/docs/sync service |

### Blocker Summary

| Blocker | Blocks | Resolution |
|---------|--------|------------|
| Sprint 5 not verified in production | All Sprint 6+ work | Run tests; observe production 1 sprint |
| 6 legacy-only reads not migrated | Dual-write stop | Migrate GantiPeserta, Ulang, TambahPeserta |
| 9 dual-write locations still active | Column drop | Stop dual-write after legacy reads migrated |
| 10 fallback patterns reference legacy | Column drop | Simplify to canonical-only after dual-write stop |
| Column drop migration not created | Column drop | Create migration in Sprint 8 |
| 100+ test fixtures need update | Column drop | Update in Sprint 7 |
| ResetEventData references `pesertas.regu_id` | Column drop | Update in Sprint 8 |
| PersonLegacySyncService references `regu_id` | Column drop | Update docs in Sprint 8 |

---

*Audit generated: 2026-07-23*
*Baseline commit: 13ec8fb*
*No production files were modified during this audit.*
