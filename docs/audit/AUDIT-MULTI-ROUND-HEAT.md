# AUDIT — MULTI-ROUND HEAT / BABAK PENYISIHAN (SPRINT R4H-FINAL)

> Audit arsitektur untuk fitur **multi-round heat (Individual Heat + Team Heat)**: banyak babak,
> banyak heat per babak, >2 peserta per heat, kualifikasi top-N, advancement antar babak,
> final/podium Juara 1/2/3 — dengan **menekan perubahan schema seminimal mungkin** (0 migration
> bila memungkinkan). Dilakukan sebelum implementasi.

- **Tanggal audit:** 2026-08-15
- **Hasil:** butuh **1 migration kecil** (kolom `competition_team_id` pada `competition_heat_results`)
  karena Team Heat tidak punya penyimpanan hasil per-heat. Round/babak + kualifikasi **tanpa kolom
  baru** (konvensi `sort_order` + parameter `top_n`). Tidak ada perubahan pada VS/bracket engine,
  Mass, R4D/R4E/R4F, Bronze Match, atau dua UI GAP.

---

## 1. Ruang Lingkup Sprint (Objective)

- **Individual Heat + Team Heat** multi-round / babak penyisihan.
- Banyak **babak (round)**, banyak **heat per babak**, **>2 peserta per heat** (tidak pernah VS).
- **Top-N kualifikasi** per heat → peserta/team lolos ke babak berikutnya.
- Advancement hanya setelah **SEMUA heat dalam satu babak lengkap/siap advance** — schedule lifecycle
  `Finished` ATAU semua `competition_heat_results.status` terisi (FIX UAT 2026-08-15; lihat §4c) →
  heat babak berikutnya menjadi `Ready` (tidak pernah auto-`Playing`; R4H auto-start ban tetap
  berlaku; `Playing` hanya via operator).
- **Final → podium Juara 1/2/3** dari ranking final. Tidak ada Bronze Match untuk Heat.
- **Team Heat:** kompetitor = `CompetitionTeam`; ranking per team, tidak pernah per anggota.
- Hard constraint: tanpa perubahan VS/bracket engine, tanpa perubahan Mass, tanpa R4D/R4E/R4F,
  tanpa Bronze Match, tanpa menyentuh dua UI GAP, tanpa reset/truncate/UAT data.

## 2. Fakta Schema & Arsitektur yang Ditemukan (Audit Read)

### 2.1 Hasil per-heat (`competition_heat_results`) — INDIVIDUAL ONLY
`database/migrations/2026_08_23_000001_create_competition_heat_results_table.php`:
- Kolom: `competition_schedule_id`, `competition_registration_id` (FK NOT NULL `restrictOnDelete`),
  `score decimal(10,2)`, `position unsigned`, `status string(50)`, `notes text`, timestamps.
- `unique(['competition_schedule_id','competition_registration_id'])` (`uniq_heat_schedule_registration`).
- **TIDAK ada kolom team.** Satu hasil per (schedule/heat, registration). Model
  `app/Models/CompetitionHeatResult.php` fillable sama, tanpa relasi team.

### 2.2 Babak/round (`competition_schedules`)
- `competition_schedules` **tidak punya kolom round/babak**. Yang ada: `competition_class_id`,
  `venue_id`, `start_at/end_at`, `status`, `required_participants`, `winner_registration_id`,
  `winner_team_id`, `finish_*`, `notes`, `sort_order`.
- `sort_order` dipakai sebagai penanda urutan (BracketManager memakai `($totalRounds-$round)*100 + $pos`).
  → **konvensi `sort_order` = `round*100 + heatIndex`** (round ≥ 1). Contoh: babak 1 heat 1 = 101,
  babak 1 heat 2 = 102, babak 2 heat 1 = 201, final = 301. Legacy heat `sort_order` kecil (1, 2, …)
  diperlakukan sebagai round 1.
- `required_participants` = kapasitas heat (jumlah peserta/team yang boleh duduk di heat itu).

### 2.3 Kualifikasi (`competition_classes`)
- `competition_classes` **tidak punya kolom qualification_type/qualification_value**.
- Konvensi: `top_n` dilewatkan sebagai **parameter** method advancement (tidak disimpan), pola
  `qualification_type=top_n, qualification_value=2`. Tidak perlu kolom → 0 migration untuk kualifikasi.
- `result_type` (nullable) + fallback `CompetitionFormat::defaultResultType()` sudah ada; reuse
  `CompetitionResultType::sortDirection()` (score → desc; time/ranking → asc). Tidak menduplikasi sorting.

### 2.4 Format & branch existing
- `CompetitionFormat`: 5 konstanta; `MASS_FORMATS=[individual_mass, team_mass]`; heat tidak di MASS;
  VS = `[team_vs_team, individual_vs_individual]`. `isTeamFormat()` menutup `team_vs_team` & `team_mass`.
  → **Tidak ada konstanta `team_heat`.** Sprint menambahkannya (code-only, bukan migration).
- `OutcomeManager`: `isHeat = format === individual_heat`; `isTeam = isTeamFormat()`; heat result
  disimpan via `saveHeatResults()` → `CompetitionHeatResult::updateOrCreate` per (schedule, registration).
  `aggregateFinal()` → `CompetitionResultService::aggregateHeatResults()` (agregat SEMUA heat kelas).
- `CompetitionResultService`:
  - `rankSchedule(eventId, scheduleId)` — ranking per schedule dari `competition_outcomes` (Mass).
  - `aggregateHeatResults(eventId, classId)` — agregat semua heat kelas → `competition_outcomes`
    (ranking competition ties 1,1,3; `remarks = "{n} heat"`).
  - `podiumForClass()` / `podiumForTeams()` — podium dari outcomes.
  - `rankTeams(eventId, classId)` — Team Mass dari `competition_team_outcomes`.
  - `EXCLUDED_STATUSES` public const — dipakai service baru (tanpa duplikasi logika).
- `CompetitionWorkflowService`: heat finish langsung (non-official) via `completeMatch`/`finishMatch`.
  `moveToWaitingResult()` (service) memindahkan match Playing → `Waiting Result` untuk semua format —
  dipakai `MatchCenter::moveToWaitingResult` (UAT fix: tombol "Finish Match" pada heat/mass tidak
  finish langsung, melainkan → `Waiting Result`; non-Playing tetap ditolak).
  R4H: tidak ada auto-start; `advanceWinner`/`advanceWinnerTeam` berhenti di `Ready`. R4H ban dijaga.

### 2.5 GAP utama yang harus diatasi
1. **Team Heat tidak punya tempat menyimpan hasil per-heat.** `competition_heat_results` hanya
   individual (`competition_registration_id` NOT NULL). → butuh kolom `competition_team_id` nullable
   + index unique (schedule, team). **= 1 migration, STOP & report (sudah dikonfirmasi user).**
2. **Tidak ada konsep round.** → konvensi `sort_order` (0 migration), dibungkus service baru.
3. **Tidak ada kualifikasi top-N.** → parameter `top_n` pada service advancement.
4. **`aggregateHeatResults()` agregat SEMUA heat kelas** — salah untuk multi-round (akan mencampur
   babak penyisihan + final). → service baru agregat per-round (khususnya round final) untuk podium.
5. **Ranking per-heat belum ada.** → `rankHeat()` baru: sorting per-heat via
   `CompetitionResultType::sortDirection()`, menulis `position` ke `competition_heat_results.position`.

## 3. Keputusan Desain (dikonfirmasi user)

1. **Team Heat storage:** tambah kolom nullable `competition_team_id` pada `competition_heat_results`
   + dua index unique terpisah (schedule, registration) dan (schedule, team). 1 migration, diizinkan.
2. **Round encoding:** konvensi `sort_order = round*100 + heatIndex`. 0 migration.
3. **Kualifikasi:** `top_n` sebagai parameter method; tidak disimpan. 0 migration.
4. **Format baru `team_heat`:** konstanta code-only di `CompetitionFormat` (masuk `ALL`,
   `TEAM_FORMATS`, label, `defaultResultType=time`). 0 migration, tidak mengubah `team_mass`.
5. **Service baru `CompetitionMultiRoundHeatService`** memakai infra existing
   (`CompetitionResultType`, `CompetitionResultService::EXCLUDED_STATUSES`, model `CompetitionSchedule`,
   `CompetitionHeatResult`, `CompetitionScheduleEntry`, `CompetitionOutcome`,
   `CompetitionTeamOutcome`) tanpa menulis ulang sorting.

## 4. Service yang Dirancang

`app/Services/Competition/CompetitionMultiRoundHeatService.php` (baru):

- `roundOf(int $sortOrder): int` — round dari sort_order (`max(1, intdiv($sortOrder, 100))`).
- `roundSchedules(int $classId, int $round)` — schedule kelas di round tertentu, urut sort_order.
- `nextRound(int $classId, int $round): ?int` — round berikutnya yang punya schedule; null = final.
- `rankHeat(int $eventId, int $scheduleId): array` — ranking satu heat (individual/team) dari
  `competition_heat_results`, tulis `position` (ties 1,1,3), pakai `sortDirection()`. Bukan win_loss.
- `advanceRound(int $eventId, int $classId, int $round, int $topN): array` — wajib semua heat round
  lengkap (`isHeatCompleteForAdvancement`: lifecycle `Finished` ATAU semua `competition_heat_results.status`
  terisi — FIX UAT, lihat §4c); untuk tiap heat ambil kompetitor `position <= topN` & non-excluded; buat
  `CompetitionScheduleEntry` di heat round berikutnya (isi sampai `required_participants`, urut
  sort_order); set heat next round jadi `Ready` (tidak pernah `Playing`). Idempoten: tidak duplikasi.
- `aggregateRoundResults(int $eventId, int $classId, int $round): array` — agregat hasil heat round
  tertentu (best-by-resultType) → `competition_outcomes` (individual) / `competition_team_outcomes`
  (team). Mirip `aggregateHeatResults` tapi scoped per round.
- `finalizePodium(int $eventId, int $classId, int $round): array` — `aggregateRoundResults` round
  final + podium Juara 1/2/3 (via `podiumForClass`/`podiumForTeams` pattern).

`OutcomeManager` (UI heat) ditambah aksi: `rankHeat()` (ranking heat ini), `advanceHeatRound(int $topN)`,
`finalizeHeatFinal()`. Branch `team_heat` memakai `heatResults` berbasis team. Blade heat ditambah
tombol minimal. `isHeat` diperluas ke `team_heat`.

### 4b. UAT gap UI — jalur input hasil heat (2026-08-15)

Backend sudah lengkap (`CompetitionHeatResult` + `OutcomeManager::saveHeatResults()` +
`CompetitionResultService::aggregateHeatResults()`); yang kurang hanyalah akses UI:

- **`match-card.blade.php`** — kartu heat `Waiting Result` kini menampilkan tombol utama **"Input Hasil"**
  → route `competition.schedule.outcomes`; kartu `Playing` (heat) diberi tombol ghost "Input Hasil".
- **`schedule/index.blade.php`** — branch aksi `Waiting Result` (badge 🟡 + tombol **Input Hasil** +
  tombol Edit), filter "Waiting Result", hint "Menunggu hasil / input hasil heat".
- **`OutcomeManager.php` + blade** — input heat **result-type aware**: `result_type=time` → field waktu
  (`CompetitionTime::parse` via `parseHeatScore()`); `score`/`ranking` → field skor numerik
  (`scoreValue`, round 2). `saveHeatResults()` membaca `timeText` vs `scoreValue` sesuai result_type.
  Official submission (`OfficialPanel`) tetap finalisasi: `Waiting Result` → `Finished`
  (winner + reason Normal/WO/DQ/Cancel + notes).

Regression: 4 test baru di `CompetitionHeatAggregationTest` (operator buka heat Waiting Result via
outcomes route; input waktu per peserta; input skor result-type score; agregasi ranking tetap jalan;
official submission tetap Finished + winner_registration_id; Match Center menampilkan "Input Hasil" +
link outcomes).

### 4c. FIX UAT — predicate advancement (root cause `not_all_finished`) (2026-08-15)

**Gejala UAT:** Individual Heat, `result_type=time`, 4 kompetitor, semua hasil disimpan dengan
`status='Lolos'`, `Rank Heat Ini` sukses (4 di-ranking), `Advance Top 2` → `not_all_finished`.

**Akar masalah:** `CompetitionMultiRoundHeatService::advanceRound()` menghitung "finished" dengan
predicate `$schedule->status !== 'Finished'` — membaca **status lifecycle schedule**
(`competition_schedules.status`), BUKAN status hasil per-kompetitor di `competition_heat_results`.
Alur operator (isi hasil via OutcomeManager → rank → advance) tidak menempuh lifecycle official
(`Playing → Waiting Result → official submit → Finished`), sehingga heat masih `Ready`/`Playing`/
`Waiting Result` (≠ `Finished`) → guard `not_all_finished` terpicu walau seluruh kompetitor `Lolos`.
Ini **bukan** masalah input UI dan bukan bug migrasi `competition_team_id`.

**FIX:** predicate canonical baru `isHeatCompleteForAdvancement(CompetitionSchedule, bool $isTeam)`:
- heat dianggap selesai bila `status === 'Finished'` (jalur lama/official, tetap valid), ATAU
- **semua** `CompetitionScheduleEntry` heat memiliki `CompetitionHeatResult` dengan `status` terisi
  (string non-empty; nilai `Lolos`/`Gugur`/`Tidak Hadir`/`Diskualifikasi`/DNF/DNS/DSQ sama-sama
  "completed"). Heat tanpa entry → dianggap belum selesai.
- Lifecycle guard (`LEGAL_TRANSITIONS`/`canTransitionTo`), official submission, dan
  `Playing → Waiting Result → Finished` **tidak diubah**; `not_all_finished` tetap ditolak bila ada
  kompetitor yang status-nya kosong.

Ranking tetap `result_type`-aware (`CompetitionResultType::sortDirection`: time/ranking asc =
terkecil menang; score desc = terbesar menang) — tidak diubah.

**Regression:** `CompetitionMultiRoundHeatTest` **AF** (4 kompetitor, time, semua `Lolos`, heat
`Waiting Result` bukan `Finished` → Advance Top 2 sukses; 2 tercepat lolos, 2 sisanya tidak) &
**AG** (salah satu kompetitor status kosong → `advanced=false`, `reason=not_all_finished`).

### 4d. UAT determination — `no_next_round` BUKAN bug aplikasi (2026-08-15)

**Gejala UAT:** setelah fix §4c, `Advance Top 2` lolos guard `not_all_finished` tetapi ditolak
`no_next_round` (tidak ada Round 2).

**Verifikasi:**
1. `advanceRound()` mengembalikan `no_next_round` bila `nextRound($classId, $round)` null —
   yaitu TIDAK ada schedule kelas dengan `sort_order` di rentang round berikutnya (200–299),
   per konvensi `sort_order = round*100 + heatIndex`.
2. Service multi-round heat **tidak pernah membuat `CompetitionSchedule`**. Satu-satunya pembuat
   schedule: `BracketManager` (bracket) dan `Schedule/Index.php` (form Jadwal manual, field
   `sort_order`). Ini konsisten dengan SPRINT doc §4 item 1–2: pembagian heat/pembuatan round
   dilakukan operator via UI; service hanya mengisi heat babak berikutnya saat advancement.
3. UAT `CompetitionSchedule` untuk kelas Individual Heat hanya berisi heat round 1 (sort_order
   `< 200`); belum ada schedule round 2 (201) sehingga `no_next_round` adalah hasil yang benar.

**Keputusan:** bukan bug logika; fixture UAT hanya memiliki satu babak. Operator harus membuat
heat babak berikutnya lebih dulu via menu Jadwal (`sort_order` = 201 untuk round 2, 301 = final)
sebelum `Advance Top 2`. Kontrak ini tidak diubah; **tidak ada perubahan production logic** untuk
`no_next_round`. UI `OutcomeManager::advanceHeatRound()` kini menampilkan pesan yang menjelaskan
aksi operator untuk `no_next_round` / `no_next_heats` / `not_all_finished` — tanpa menyembunyikan
eror, tanpa mengubah ranking/result_type, tanpa hardcode Round 2.

**Regression:** **AH** — kelas single-round (hanya heat 101, semua hasil `Lolos` tercatat,
rankHeat sukses): `advanceRound(round 1, top 2)` → `advanced=false`, `reason=no_next_round`,
`next_round=null`, `isFinalRound(1)=true`, jumlah `CompetitionSchedule` dan `CompetitionScheduleEntry`
tidak berubah (tidak ada fake Round 2).

## 5. Migration (1, diizinkan)

`database/migrations/2026_08_25_000001_add_team_to_competition_heat_results.php`:
- `competition_registration_id` → nullable (individu), tambah `competition_team_id` nullable FK
  `competition_teams` `restrictOnDelete`.
- Drop unique lama `uniq_heat_schedule_registration`; buat dua unique:
  `uniq_heat_schedule_registration` (schedule, registration) dan `uniq_heat_schedule_team`
  (schedule, competition_team_id).
- Index `competition_team_id`.
- `down()`: drop index/kolom, kembalikan registration NOT NULL + unique lama.

Tidak ada perubahan schema lain; tidak ada seeder/reset/UAT data.

## 6. Dampak pada File Existing

- `app/Support/CompetitionFormat.php` — tambah `TEAM_HEAT`, ke `ALL`, `TEAM_FORMATS`, label,
  `defaultResultType`. (code-only, format lain tidak berubah)
- `app/Models/CompetitionHeatResult.php` — fillable `competition_team_id` + relasi `competitionTeam()`.
- `app/Services/Competition/CompetitionResultService.php` — tidak diubah logika; hanya dipakai.
- `app/Livewire/Competition/Schedule/OutcomeManager.php` — branch `team_heat`, aksi rank/advance/final.
- `resources/views/livewire/competition/schedule/outcome-manager.blade.php` — tombol minimal.
- **Tidak diubah:** `CompetitionWorkflowService`, `CompetitionBracket*`, `MatchCenter`,
  `EntryManager`, `BracketManager`, `CompetitionTeam`, `CompetitionTeamOutcome`, migrations lain.

## 7. Verifikasi & Risiko

- Test baru `tests/Feature/Competition/CompetitionMultiRoundHeatTest.php`: A–P individual (16),
  Q–W team (7), X–AD regresi (7) = 30 test.
- Regresi yang dijaga: `CompetitionHeatAggregationTest` (agregat all-heat tetap), R4H
  ready-not-auto-start, WorkflowEnforcement `OutcomeManager only loads schedule entries`,
  `CompetitionNonBracketTeamVsTeamTest` (finish direkt heat/mass), Team Mass `rankTeams`.
- Disk pernah 100% (env) → verifikasi ulang bila tmpfile error.
