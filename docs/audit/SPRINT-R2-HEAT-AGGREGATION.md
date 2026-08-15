# SPRINT R2 — Individual Heat + Multi-Heat Aggregation + Final Podium

> Implementasi terbatas Sprint R2: **Individual Heat** (individu bergantian) → hasil per heat → **aggregate lintas heat** → **final ranking** → **podium 1/2/3**.
> Di luar scope (tidak dikerjakan): Team competitor, Team Mass, perubahan Bracket besar.

- **Tanggal:** 2026-08-14
- **Baseline suite (R1):** 2295 passed / 5966 assertions / 0 failed
- **Final suite:** 2306 passed / 6011 assertions / 0 failed / 0 skipped (+11 test, +45 assertions)

---

## 1. Flow yang sekarang berjalan

```text
Competition Class (format = individual_heat)
        ↓
Individual Heat = CompetitionSchedule (order by sort_order)
        ↓
Heat 1, Heat 2, Heat 3, ...
        ↓
Result per (heat, participant) → competition_heat_results (score = detik, status)
        ↓
CompetitionResultService::aggregateHeatResults
        ↓
Aggregate (best time / best score across heats) → final ranking (ties: 1,1,3)
        ↓
Final → competition_outcomes (score + position) — unique per registration
        ↓
Podium 1st / 2nd / 3rd (podiumForClass)
```

## 2. Gap yang diselesaikan (dari audit R1)

| Audit gap | Solusi R2 |
|---|---|
| Outcome hanya 1 result per `competition_registration_id` | Tabel baru `competition_heat_results` — hasil per (schedule/heat, registration). `competition_outcomes` tetap dipakai untuk **final** (unique per registration). |
| Tidak ada result identity per heat | `competition_heat_results.competition_schedule_id` = identity heat. |
| Tidak ada aggregation lintas heat | `CompetitionResultService::aggregateHeatResults(eventId, classId)` menggabungkan seluruh heat class → final. |
| Tidak ada ranking final lintas heat | Aggregation → sort (best) → competition ranking (ties 1,1,3) → `position` final. |
| TIME belum ada representasi/parsing | `App\Support\CompetitionTime` — parse `M:SS.mmm` / `SS.mmm` / plain detik; format balik `M:SS.mmm`; disimpan sebagai detik di `score` (decimal). |

## 3. Perubahan

### a. Migration additive
`database/migrations/2026_08_23_000001_create_competition_heat_results_table.php`
- `competition_heat_results`: `competition_schedule_id` (cascade), `competition_registration_id` (restrict), `score` decimal(10,2), `position` nullable, `status` string, `notes`, timestamps.
- Unique `(competition_schedule_id, competition_registration_id)` — satu hasil per heat per peserta; index registration_id & schedule_id.
- Additive; kompatibel SQLite + MariaDB. Tidak ada DROP/delete.

### b. `App\Support\CompetitionTime` (baru)
- `parse('1:32.5')` → 92.5; `parse('92.5')` → 92.5; `parse('')` → null; invalid → null.
- `format(92.5)` → `1:32.500`.

### c. `App\Models\CompetitionHeatResult` (baru) + `CompetitionRegistration::heatResults()`
- Model hasil per heat; casts score decimal, position int; relasi schedule() & competitionRegistration().

### d. `CompetitionResultService` — tambahan
- `aggregateHeatResults(int $eventId, int $classId): array`
  - Event-scoped: `CompetitionClass::where('event_id', $eventId)->findOrFail` → event lain = ModelNotFound.
  - Transactional.
  - Kumpulkan `competition_heat_results` seluruh heat class; buang status eksklusi (Diskualifikasi/Tidak Hadir/Gugur/DNF/DNS/DSQ) dan skor null.
  - Aggregate per peserta: **best** (min untuk time/ranking; max untuk score).
  - Sort → competition ranking (ties 1,1,3) → `CompetitionOutcome::updateOrCreate(score=aggregate, position=final, remarks="N heat")`.
  - win_loss → no-op.
- `podiumForClass(int $eventId, int $classId): array` — top 3 final dari outcomes class (dipakai podium Individual Heat).

### e. `OutcomeManager` + blade — branch heat
- `isHeat = class.format === individual_heat`.
- Heat: input **waktu** (`timeText` M:SS.mmm) + status + catatan per peserta → simpan ke `competition_heat_results`; kolom "Posisi Final" (dari outcome); tombol **"Generate Final Ranking"** → `aggregateHeatResults`; podium = `podiumForClass`.
- Non-heat (mass/score): perilaku R1 tetap (outcomes, autoRank, podium per schedule).

## 4. Files Changed

- `database/migrations/2026_08_23_000001_create_competition_heat_results_table.php` (baru)
- `app/Support/CompetitionTime.php` (baru)
- `app/Models/CompetitionHeatResult.php` (baru)
- `app/Models/CompetitionRegistration.php` (+ heatResults relation)
- `app/Services/Competition/CompetitionResultService.php` (+ aggregateHeatResults, podiumForClass, isExcludedStatus)
- `app/Livewire/Competition/Schedule/OutcomeManager.php` (branch heat + saveHeatResults + aggregateFinal)
- `resources/views/livewire/competition/schedule/outcome-manager.blade.php` (heat UI + Generate Final Ranking)
- `tests/Feature/Competition/CompetitionHeatAggregationTest.php` (baru, 11 test)
- `tests/Feature/Competition/WorkflowEnforcementTest.php` (asertion disesuaikan: class tanpa format → default individual_heat → jalur heat)
- Backup dev DB: `database/database.sqlite.backup.pre-R2-*`

**Tidak disentuh:** Regu, Design C, CompetitionTeam/Formation, Bracket engine, Team competitor, Team Mass.

## 5. Tests

- **Baru:** 11 (`CompetitionHeatAggregationTest`) — schema heat_results; CompetitionTime parse/format; aggregate 2 heat → final best-time + posisi; eksklusi heat DSQ; aggregate score-desc; ties final (1,1,3); event-scope (event lain → ModelNotFound); win_loss no-op; podiumForClass top-3; komponen OutcomeManager (simpan waktu per heat → aggregateFinal → podium).
- **Adjust existing:** `WorkflowEnforcementTest::OutcomeManager only loads schedule entries` — class tanpa format kini default `individual_heat` → asertion dialihkan ke `heatResults` (perilaku baru yang disengaja).
- **Competition dir:** 138 passed. **Full suite: 2306 passed / 6011 assertions / 0 failed / 0 skipped.**

## 6. Smoke Test (dev `database.sqlite`, UAT Individual Heat)

- 2 heat (3 peserta masing-masing), waktu detik [92.5, 88.2, 95.0, 90.1, 93.4, 86.7].
- `aggregateHeatResults` → final ranking by **best time** lintas heat.
- Podium: **Juara 1** UAT Competition 02 (`1:28.200`), **Juara 2** UAT Competition 04 (`1:30.100`), **Juara 3** UAT Competition 01 (`1:32.500`). ✅

## 7. Catatan / Batas R2

- Aggregation default = **best** (min time / max score). Mode lain (total/rata-rata) belum termasuk scope.
- `competition_outcomes` tetap unique per registration → final hanya untuk **satu rangkaian heat per class** (sesuai scope; bukan aggregasi lintas class).
- Status eksklusi per-heat: heat yang didiskualifikasi diabaikan dari aggregate peserta tsb.
- Team competitor & Team Mass tetap out of scope.
