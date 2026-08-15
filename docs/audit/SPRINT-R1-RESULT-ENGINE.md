# SPRINT R1 — Competition Result Engine + Individual Mass + Podium

> Implementasi terbatas Sprint R1: Individual Mass dapat mencatat hasil, **ranking otomatis**, dan menentukan **Juara 1/2/3 (podium)**.
> Di luar scope (tidak dikerjakan): Heat aggregation, Team competitor, Team Mass, perubahan Bracket besar.

- **Tanggal:** 2026-08-14
- **Baseline suite:** 2283 passed / 5932 assertions / 0 failed
- **Final suite:** 2295 passed / 5966 assertions / 0 failed / 0 skipped (+12 test, +34 assertions)

---

## 1. Flow yang sekarang berjalan

```text
Registration
    ↓
Mass Schedule (CompetitionSchedule + ScheduleEntry)
    ↓
Participants
    ↓
Input Result (score — OutcomeManager)
    ↓
Result Engine (CompetitionResultService::rankSchedule)
    ↓
Sort / Rank (by result_type direction, competition ranking with ties)
    ↓
Position (persisted ke CompetitionOutcome.position)
    ↓
Podium (1st / 2nd / 3rd — podiumForSchedule + UI Juara 1/2/3)
```

## 2. Perubahan

### a. Migration additive
`database/migrations/2026_08_22_000001_add_result_type_to_competition_classes.php`
- `competition_classes.result_type` string nullable (additive; fallback runtime ke `CompetitionFormat::defaultResultType(format)` bila null).
- Backfill data (raw SQL CASE, portabel MariaDB/SQLite): individual_heat→`time`, individual_mass→`ranking`, team_vs_team→`win_loss`, team_mass→`ranking`, individual_vs_individual→`score`, else→`ranking`.
- Tidak ada DROP/delete; kompatibel SQLite + MariaDB.

### b. `app/Support/CompetitionResultType.php` (baru)
- Konstanta `win_loss` / `score` / `time` / `ranking`; `isRanked()` (score/time/ranking = auto-rankable), `sortDirection()` (`score`→desc, time/ranking→asc), `label()`.

### c. `app/Models/CompetitionClass.php`
- `result_type` masuk `$fillable`.
- `resultType(): string` → `result_type` valid ATAU `CompetitionFormat::defaultResultType(format)`.

### d. `app/Services/Competition/CompetitionResultService.php` (baru) — RESULT ENGINE
- `rankSchedule(int $eventId, int $scheduleId): array`
  - Event-scoped: `CompetitionSchedule::whereHas('competitionClass', event_id)::findOrFail` → schedule event lain = ModelNotFound (DENY).
  - Transactional (`DB::transaction`).
  - win_loss → no-op (`ranked=false`, `reason=win_loss`).
  - Score-based: sort by `outcome.score` sesuai `sortDirection`; **competition ranking dengan tie** (1,1,3); persist `position`.
  - Status eksklusi (tidak di-ranking, position di-null-kan): `Diskualifikasi`, `Tidak Hadir`, `Gugur`, `DNF`, `DNS`, `DSQ`.
  - Peserta tanpa `score` → tidak di-ranking.
- `podiumForSchedule(int $eventId, int $scheduleId): array` → top 3 (`position` 1..3) dari entries schedule, beserta nama/no-peserta/skor.

### e. `app/Livewire/Competition/Schedule/OutcomeManager.php`
- `autoRank()` (gate `manage-events`) → panggil Result Engine, flash ringkasan.
- Properti: `resultType`, `resultDirection`, `canAutoRank` (hanya score/time/ranking), `podium`.

### f. Blade `outcome-manager.blade.php`
- Label result type + arah auto-rank.
- **Kartu Podium Juara 1/2/3** (nama, no peserta, skor; warna emas/perak/perunggu) bila sudah ada hasil ter-rank.
- Tombol **"Rank Otomatis"** (muncul hanya untuk result type score/time/ranking) + "Simpan Outcome".

### g. Test `tests/Feature/Competition/CompetitionResultEngineTest.php` (baru, 12 test)

## 3. Detail Engine (tie & arah)

| Result type | Arah | Contoh |
|---|---|---|
| `ranking` (mass) | asc (terkecil dahulu) | skor 22/25/28/30/35 → posisi 1..5 |
| `time` (heat) | asc (tercepat dahulu) | waktu terendah → posisi 1 |
| `score` | desc (terbesar dahulu) | skor 30/25/10 → posisi 1/2/3 |
| `win_loss` | — (tidak di-rank otomatis) | via OfficialPanel/winner |

Tie → standard competition ranking: skor [25,25,30] → posisi [1,1,3].

## 4. Files Changed

- `database/migrations/2026_08_22_000001_add_result_type_to_competition_classes.php` (baru)
- `app/Support/CompetitionResultType.php` (baru)
- `app/Services/Competition/CompetitionResultService.php` (baru)
- `app/Models/CompetitionClass.php` (result_type + resultType())
- `app/Livewire/Competition/Schedule/OutcomeManager.php` (autoRank + podium + result type)
- `resources/views/livewire/competition/schedule/outcome-manager.blade.php` (podium + Rank Otomatis)
- `tests/Feature/Competition/CompetitionResultEngineTest.php` (baru, 12 test)
- Backup dev DB: `database/database.sqlite.backup.pre-R1-*`

**Tidak disentuh:** Regu, Design C (people/participations/event_attendances), CompetitionTeam/Formation, Bracket engine, Team competitor, Team Mass.

## 5. Tests

- **Baru:** 12 test (`CompetitionResultEngineTest`) — schema result_type; resolusi result type (default + override); ranking mass asc; ranking score desc; tie (1,1,3); eksklusi DSQ/absent; skip tanpa skor; win_loss no-op; event-scope (event lain → ModelNotFound); podium top-3; komponen OutcomeManager autoRank.
- **Competition dir:** 127 passed (115 + 12).
- **Full suite:** **2295 passed / 5966 assertions / 0 failed / 0 skipped** (Duration ±75s).

## 6. Smoke Test (dev `database.sqlite`, UAT Individual Mass)

- Backfill result_type UAT: class #2 `individual_mass` → `ranking` (dll: time/score/win_loss/ranking) ✅.
- Skenario: 5 peserta UAT mass, schedule + entries, skor [52.3,48.1,50.9,47.5,49.2] → `rankSchedule` → posisi 47.5→1, 48.1→2, 49.2→3, 50.9→4, 52.3→5 ✅.
- Podium: **Juara 1** UAT Competition 09 (47.5), **Juara 2** UAT Competition 07 (48.1), **Juara 3** UAT Competition 10 (49.2) ✅.

## 7. Migration Readiness

- Additive; tidak ada DROP/delete; backfill data non-destruktif (mengisi kolom baru).
- Terverifikasi di SQLite (suite + dev); SQL CASE portabel untuk MariaDB.
- Siap dijalankan di environment dengan akses MariaDB: `php artisan migrate --force`.

## 8. Catatan / Batas R1

- Time disimpan sebagai `score` desimal (detik); parsing mm:ss belum termasuk R1.
- Podium dihitung dari `CompetitionOutcome.position` (unique per registration) — masih **satu race per class**; aggregasi lintas-heat di luar scope R1.
- Team competitor & Team Mass belum ter-wire (out of scope).
