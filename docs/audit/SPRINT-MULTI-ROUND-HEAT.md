# SPRINT — MULTI-ROUND HEAT / BABAK PENYISIHAN (R4H-FINAL)

> Kontrak fitur **multi-round heat (Individual Heat + Team Heat)** dan laporan sprint R4H.
> Dokumen ini membedakan dengan tegas antara **kontrak/desain**, **yang sudah diimplementasikan**,
> dan **yang masih jadi scope sprint berikutnya**. Tidak ada klaim implementasi tanpa bukti test.

- **Tanggal laporan:** 2026-08-15
- **Baseline full suite:** 2400 passed / 6369 assertions / 0 failed / 0 skipped
  (wajib dijalankan dengan `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`; default CLI 128M
  adalah environment constraint, bukan bug aplikasi).
- **Audit pendamping:** `docs/audit/AUDIT-MULTI-ROUND-HEAT.md` (detail arsitektur & keputusan desain).

---

## 1. Kontrak Multi-Round Heat

### A. Individual Heat
- Banyak peserta boleh mengikuti lomba yang sama.
- Jika peserta terlalu banyak untuk satu heat, peserta **dibagi ke beberapa heat** (Heat 1, Heat 2, Heat 3, …).
- Setiap heat **menyimpan hasil/ranking** pesertanya sendiri.
- Hasil antar-heat dipakai untuk menentukan **peserta yang lanjut**.
- Sistem **mengulang babak/heat berikutnya** sampai didapat **Juara 1, Juara 2, Juara 3**.
- **Satu heat ≠ satu pertandingan 1vs1.** Heat adalah kompetisi mass/parallel: banyak kompetitor
  bertanding dalam satu heat dan diranking.

### B. Team Heat
- Konsep sama dengan Individual Heat, tetapi **competitor adalah `CompetitionTeam`**, bukan
  Person/`CompetitionRegistration` individual.
- Setiap heat harus dapat menghasilkan **ranking/hasil per team**.
- Beberapa team dapat bertanding dalam **satu heat**.
- Jika jumlah team besar, **dibagi ke beberapa heat**.
- Hasil heat berikutnya menentukan **siapa yang lanjut**.
- Sampai akhirnya didapat **Juara 1, 2, 3 team**.

### C. Beda dengan Bracket
- **Bracket** = eliminasi head-to-head / match tree (VS).
- **Heat** = beberapa kompetitor bertanding dalam satu heat dan **diranking**.
- Heat **tidak boleh dipaksa memakai model bracket**.
- **R4E bracket yang sudah selesai tidak boleh diubah.**

### D. Round/Babak
- Untuk sementara gunakan kontrak **`sort_order`** yang sudah dipilih dalam audit
  (`round*100 + heatIndex`; legacy `<100` = round 1).
- Ikuti pola existing `BracketManager`.
- **Tidak menambah migration hanya untuk round** selama tidak diperlukan acceptance criteria sprint ini.

### E. Database
- **Tidak membuat migration baru** untuk Team Heat hanya berdasarkan asumsi.
- **Tidak mengubah production/UAT data** (tidak reset, tidak truncate, tidak seeder).
- **Tidak menyentuh Regu.**
- **Tidak mengubah Design C.**
- **Tidak merusak R4D/R4E.**

---

## 2. Kontrak → Penerapan (implementasi aktual)

### 2.1 Lifecycle Heat
| Tahap | Status `competition_schedules` | Siapa yang menggerakkan |
|---|---|---|
| Heat dibuka | `Scheduled` | Operator/seed |
| Peserta/team diisi | entries `CompetitionScheduleEntry` | Operator / `advanceRound` |
| Siap bertanding | `Ready` | Operator (`canAutoReady`) / `advanceRound` — **tidak pernah auto-`Playing`** |
| Sedang lomba | `Playing` | **Hanya operator** (R4H auto-start ban tetap) |
| Selesai | `Finished` | `finishMatch`/`completeMatch` (heat) |
| Menunggu hasil | `Waiting Result` | `MatchCenter::moveToWaitingResult` (UAT fix: tombol "Finish Match" memindahkan heat Playing → `Waiting Result`, bukan langsung `Finished`; official submit → `Finished`) |
| Hasil diisi | `competition_heat_results` (score + status) | `OutcomeManager::saveHeatResults()` |
| Ranking heat | `competition_heat_results.position` | `rankHeat()` |
| Lanjut babak | entries babak berikutnya + `Ready` | `advanceRound()` |
| Juara | `competition_outcomes` / `competition_team_outcomes` | `aggregateRoundResults()` → `finalizePodium()` |

### 2.2 Pembagian heat (multi-heat per babak)
- Satu kelas punya **beberapa schedule per round**, dibedakan dengan `sort_order`:
  `round*100 + heatIndex` (round 1 heat 1 = 101, heat 2 = 102; round 2 heat 1 = 201; final = 301).
- Legacy heat (`sort_order < 100` atau null) diperlakukan sebagai **round 1**
  (`CompetitionMultiRoundHeatService::roundSchedules()` + `roundOf()`).
- Kapasitas tiap heat = `required_participants`.

### 2.3 Hasil per heat
- Individual: `competition_heat_results.competition_registration_id`.
- Team: `competition_heat_results.competition_team_id` (kolom nullable baru, 1 migration).
- Unique: `(schedule, registration)` dan `(schedule, team)` — satu hasil per kompetitor per heat.
- Score = nilai `result_type` (default `time` untuk `team_heat` → detik via `CompetitionTime`).
- Status excluded (dari `CompetitionResultService::EXCLUDED_STATUSES`) & `score = null`
  **dilewati** saat ranking (tidak dapat posisi).

### 2.4 Advancement (top-N)
- Gate: **SEMUA heat babak `$round` harus lengkap & siap advance**. FIX UAT (2026-08-15):
  predicate `isHeatCompleteForAdvancement()` sekarang menerima DUA signal selesai:
  1. lifecycle schedule = `Finished` (jalur `finishMatch`/official submission), ATAU
  2. **SEMUA kompetitor dijadwalkan punya `competition_heat_results.status` terisi**
     (mis. `Lolos`/`Gugur`/`Tidak Hadir`/`Diskualifikasi`/DNF/DNS/DSQ).
  Sebelumnya predicate HANYA memakai lifecycle `Finished` → operator yang mengisi hasil
  per-kompetitor (`Lolos`) via OutcomeManager lalu `Advance Top N` selalu ditolak
  `not_all_finished` karena heat masih `Ready`/`Playing`/`Waiting Result`. Lifecycle guard
  tidak diubah; schedule tetap menuju `Finished` via official submission.
- Jika ada heat yang tidak lengkap, `advanceRound` menolak (`reason: not_all_finished`).
- Ambil per heat: `position > 0 && position <= topN` dan non-excluded.
- Kompetitor yang lolos dijadwalkan ke heat babak berikutnya, **isi sampai `required_participants`**,
  urut schedule lalu `order_number` berurutan.
- Idempoten: kompetitor yang sudah duduk di heat next round tidak diduplikasi.
- Heat next round di-set ke `Ready` (dari `Scheduled`) bila `canAutoReady()` — **tidak pernah `Playing`**.

### 2.5 Finalisasi Juara 1/2/3
- `aggregateRoundResults($round)` mengagregasi **hasil SEMUA heat round tertentu** (best-by-resultType:
  `min` untuk time/ranking asc, `max` untuk score desc) → ditulis persisten:
  - Individual → `competition_outcomes` (`remarks = "Round N (M heat)"`).
  - Team → `competition_team_outcomes`.
- Ties mengikuti pola competition ranking **1,1,3** (sama baik per-heat maupun agregat).
- `finalizePodium($round)` = agregat round final + podium Juara 1/2/3.
- **Tidak ada Bronze Match untuk Heat.**

### 2.6 Beda Individual Heat vs Team Heat
| Aspek | Individual Heat | Team Heat |
|---|---|---|
| Kompetitor | `CompetitionRegistration` | `CompetitionTeam` |
| Hasil per heat | `competition_heat_results.registration_id` | `competition_heat_results.team_id` |
| Ranking | per registration | per team |
| Outcome final | `competition_outcomes` | `competition_team_outcomes` |
| Podium nama | person (`person.nama`) | team (`team.name`) |
| `defaultResultType` | existing per class/format | `time` (konstanta `CompetitionFormat::TEAM_HEAT`) |

### 2.7 Beda Heat vs Bracket
| Aspek | Heat | Bracket |
|---|---|---|
| Model | mass/parallel, diranking | eliminasi head-to-head (match tree) |
| Satu schedule | >2 kompetitor, semua dinilai & diranking | VS (dua kompetitor / match) |
| Advancement | top-N per heat → isi heat berikutnya | pemenang tiap match → node bracket |
| Engine | `CompetitionMultiRoundHeatService` (baru) | `CompetitionBracket*` / `BracketManager` (R4E, **tidak disentuh**) |

---

## 3. Status Implementasi (fakta, bukan klaim)

### 3.1 Sudah diimplementasikan & diverifikasi test
| # | Item | Lokasi |
|---|---|---|
| 1 | Konstanta format baru `team_heat` (`ALL`, `TEAM_FORMATS`, label, `defaultResultType=time`) | `app/Support/CompetitionFormat.php` |
| 2 | Kolom nullable `competition_team_id` + dual unique pada `competition_heat_results` (1 migration) | `database/migrations/2026_08_25_000001_add_team_to_competition_heat_results.php` |
| 3 | Relasi: `CompetitionHeatResult.competitionTeam()`, `CompetitionTeam.heatResults()`, `CompetitionSchedule.heatResults()` | Models |
| 4 | Round derivation dari `sort_order` (`roundOf`, `roundSchedules`, `rounds`, `nextRound`, `isFinalRound`) | `CompetitionMultiRoundHeatService` |
| 5 | Ranking per heat (individual & team, ties 1,1,3, excluded/skip no-score) → menulis `position` | `rankHeat()` |
| 6 | Advancement top-N per heat → isi heat babak berikutnya (capacity), idempoten, next round `Ready`, **tidak pernah `Playing`** | `advanceRound()` |
| 7 | Agregasi hasil round (best-by-resultType) → `competition_outcomes` / `competition_team_outcomes` | `aggregateRoundResults()` |
| 8 | Podium Juara 1/2/3 (individual & team), tanpa Bronze Match | `finalizePodium()` / `podiumForClass()` |
| 9 | UI `OutcomeManager`: branch `team_heat` (isi hasil per team), badge round, aksi `rankHeat` / `advanceHeatRound($topN)` / `finalizeHeatFinal` | `OutcomeManager.php` + blade |
| 10 | Sorting tidak diduplikasi — memakai `CompetitionResultType::sortDirection()`; excluded memakai `CompetitionResultService::EXCLUDED_STATUSES` | service |
| 11 | 30 test kontrak A–AD: individual (multi-heat, multi-round, advancement, podium), team heat, regresi (R4H ready-not-auto-start, Team Mass `rankTeams`, finish direkt heat/mass/team_heat, OutcomeManager individual & team flow, delete schedule cascade) | `tests/Feature/Competition/CompetitionMultiRoundHeatTest.php` |

### 3.2 Regresi yang dijaga (tidak rusak)
- `CompetitionHeatAggregationTest` — agregasi all-heat (individual) tetap seperti existing.
- `CompetitionTeamFoundationTest` — diperbarui: **enam** format (bertambah `team_heat`), `team_heat`
  = team format, non-bracket, `defaultResultType = time`.
- R4H ban: heat tidak pernah auto-`Playing` (`CompetitionBracketReadyNotAutoStartTest`).
- Team Mass `rankTeams`, `CompetitionNonBracketTeamVsTeamTest`, `OutcomeManager only loads schedule entries`.
- UAT fix: `MatchCenter::moveToWaitingResult` memakai `moveToWaitingResult()` (service) sehingga
  match Playing — termasuk heat/mass — berpindah ke `Waiting Result` (bukan finish langsung) dan
  non-Playing tetap ditolak dengan pesan "Only Playing matches can be sent to Waiting Result."
  (`CompetitionWorkflowTest`, `WorkflowDecisionTest`, `CompetitionMultiRoundHeatTest::AE`).
- UAT gap UI (input hasil heat): backend/sou hasil sudah ada (`CompetitionHeatResult` +
  `OutcomeManager::saveHeatResults()` + `CompetitionResultService::aggregateHeatResults()`), yang
  kurang hanyalah jalur UI. Ditambahkan:
  - tombol **"Input Hasil"** → `competition.schedule.outcomes` pada kartu `Waiting Result` di
    Match Center (`match-card.blade.php`) dan `Playing` (heat) pula;
  - branch aksi **`Waiting Result`** di `schedule/index.blade.php` (badge 🟡 + tombol Input Hasil);
  - input heat **result-type aware** di `OutcomeManager`: `time` → field waktu, `score`/`ranking` →
    field skor numerik, `win_loss` → official flow (bukan heat).
  Regression: `CompetitionHeatAggregationTest` (4 test baru).
- UAT bug advancement (2026-08-15): `Advance Top N` menolak `not_all_finished` walau semua
  kompetitor sudah `Lolos` — akar: predicate advancement membaca **status lifecycle schedule**
  (`Finished`), bukan status hasil per-kompetitor. FIX: `isHeatCompleteForAdvancement()` menerima
  `Finished` ATAU semua `competition_heat_results.status` terisi. Regression: `CompetitionMultiRoundHeatTest`
  **AF** (4 kompetitor, `result_type=time`, semua `Lolos`, Advance Top 2 sukses, 2 tercepat lolos,
  2 lainnya tidak) & **AG** (1 kompetitor belum selesai → tetap `not_all_finished`).
- UAT determination `no_next_round` (2026-08-15): setelah fix `not_all_finished`, UAT berikutnya
  menerima `no_next_round`. Verifikasi kode + kontrak menentukan: ini **bukan bug aplikasi** —
  sesuai kontrak §4 item 1–2, service NEVER membuat schedule baru; round 2 (sort_order 200–299)
  HARUS dibuat operator via UI Jadwal (`Sort Order` 201/301) sebelum advancement. `advanceRound`
  hanya mengisi heat next round yang SUDAH ADA. Regression: **AH** (hanya round 1, hasil lengkap,
  Advance Top 2 → `advanced=false`, `reason=no_next_round`, TIDAK ada fake Round 2 / schedule baru,
  entry tidak berubah). UI `OutcomeManager` kini menampilkan pesan jelas untuk `no_next_round`/
  `no_next_heats`/`not_all_finished` (tanpa mengubah logika service atau ranking).

### 3.3 Verification
- Competition dir: **239 passed / 718 assertions / 0 failed** (`tests/Feature/Competition`).
- Test baru: **30 kontrak A–AD + AE UAT regression + WorkflowDecision heat regression**
  + **4 test** UAT gap UI (CompetitionHeatAggregationTest): operator buka heat Waiting Result via
  `competition.schedule.outcomes`, input waktu per peserta (result-type `time`), input skor numerik
  (result-type `score`/`ranking`), agregasi `aggregateHeatResults` tetap jalan, submission official
  tetap finalisasi (Finished + winner_registration_id), Match Center menampilkan link "Input Hasil".
  + **2 test** UAT advancement (AF/AG) + **1 test** UAT determination no_next_round (AH).
- Full suite: **2407 passed / 6406 assertions / 0 failed / 0 skipped** (command baseline:
  `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`).
- Pint: 10 file berubah — PASS setelah perbaikan 2 style issue (unary operator, unused import).
  Dua file lain (`CompetitionTeamCompetitorTest`, `UserEventMembershipTest`) punya style issue
  **pre-existing**, di luar scope sprint, tidak diubah. Commit UAT gap UI: 5 file, 1 style issue
  fixed (unused import pada test) — PASS.

---

## 4. Masih Scope Sprint Berikutnya (TIDAK diklaim implemented)

Item berikut adalah **kontrak/desain lanjutan** yang TIDAK diimplementasikan sprint ini:

1. **Penjadwalan heat otomatis** (auto-split peserta → multi-heat per round). Saat ini pembagian heat
   dilakukan operator (entry/seed manual); service hanya mengisi heat **babak berikutnya** saat
   advancement, tidak membuat schedule baru.
2. **UI pembuatan schedule multi-round** (create round/heat dari UI). Round diekspresikan lewat
   `sort_order`; UI existing menambahkan schedule seperti biasa.
3. **Penyimpanan parameter kualifikasi** (`top_n`) — sengaja tidak disimpan (parameter method),
   sesuai keputusan audit. Jika sprint berikutnya butuh persisten, perlu kolom/konfigurasi baru
   (0 migration saat ini).
4. **Ranking lintas-babak yang lebih kaya** — misalnya gabungan waktu terbaik antar beberapa heat
   di babak yang sama (saat ini `aggregateRoundResults` memakai best-by-resultType saja).
5. **Bronze Match** — sengaja tidak dibuat (kontrak heat final = podium 1/2/3 langsung).
6. **UI live-scoreboard per heat** (tampilan realtime hasil antar-heat selama lomba berlangsung).
7. **Perbaikan dua UI GAP** yang sudah ada — sengaja di luar scope (hard constraint).

---

## 5. Batasan & Konsistensi Kontrak
- **Bracket R4E**: tidak diubah sama sekali.
- **Mass / Team Mass**: tidak diubah (hanya dipakai `rankTeams` untuk regresi).
- **VS (individual_vs_individual / team_vs_team)**: tidak diubah; heat service menolak format
  win/loss (`ranked: false`).
- **R4D/R4F, Regu, Design C, UAT/production data**: tidak disentuh.
- **Memory**: full suite membutuhkan `-d memory_limit=1G` (constraint environment PHP CLI 128M,
  bukan bug aplikasi — terdokumentasi di audit-audit sebelumnya).

---

## UAT 2026-08-15 — Follow-up: "Advance Top 2 memajukan kompetitor yang salah (C02, bukan C04)"

### Gejala
- Kelas Individual Heat `time`: R1 = C01 1:30, C02 1:40, C03 1:20, C04 1:10 (semua `Lolos`);
  R2 tampil berisi **C02 (KP001) ❌ + C03 (KL002) ✅**; C01/C04/C05 tampak "available".
- Ekspektasi top-2 (result_type time = terkecil menang): C04 + C03.

### Temuan verifikasi (probe + repro test)
1. **Logika service BENAR.** Reproduksi persis skenario (times benar, per-kompetitor
   `competition_heat_results` benar) → `rankHeat` beri C04 pos1 / C03 pos2 dan `advanceRound`(top 2)
   isi R2 persis `[C04, C03]`; C01/C02 tidak pernah ikut. (Regression `ReproUatAdvancementTest`.)
2. **Bug UI nyata: hasil heat tidak bisa disimpan dari OutcomeManager.** `render()` memanggil
   `loadParticipants()` setiap request Livewire → rebuild `$this->heatResults` dari DB, sehingga
   nilai `timeText` yang sedang diketik operator TERHAPUS sebelum `saveOutcomes()`. Test Livewire
   `set('heatResults.N.timeText', ...)` terbukti tersusun kosong setelah render dan tersimpan
   `score = 0.0`. => operator tidak bisa meng-input/mengoreksi waktu per baris secara andal,
   sehingga data produksi bisa berisi binding waktu↔registration yang tidak konsisten (skenario
   "C02 dapat 1:10, C04 dapat 1:40" terbukti menghasilkan persis gejala UAT ini). Test AA lama
   lolos karena memanipulasi `$component->instance()->heatResults` langsung (melewati boundary
   Livewire) — tidak pernah menangkap bug ini.
3. **Pesan success advancement menyesatkan.** `advanceHeatRound` melaporkan
   "`{qualifiers} peserta dijadwalkan`" padahal angka itu jumlah *qualified*, bukan yang benar-benar
   ditambahkan; service tidak pernah mengekspos jumlah assigned.

### Perbaikan (kode saja, data UAT tidak disentuh)
- `OutcomeManager`: array peserta/team dibangun di `mount()`; `render()` TIDAK lagi me-rebuild,
  sehingga nilai input Livewire yang sedang diketik persisten sampai `saveOutcomes()`.
  Rebuild tetap dipanggil setelah aksi commit (saveOutcomes, saveTeamOutcomes, autoRank, rankHeat,
  aggregateFinal, advanceHeatRound, finalizeHeatFinal) agar UI menampilkan state DB terkini.
- `CompetitionMultiRoundHeatService::advanceRound()`: kembalikan `assigned` (jumlah yang benar-benar
  ditambahkan). `advanceHeatRound` memakai `assigned` untuk pesan; bila `qualifiers > 0` tetapi
  `assigned == 0` (slot round berikutnya sudah penuh/muatan manual), tampilkan pesan error yang
  jelas (bukan "unknown") supaya operator tidak dikelirukan.
- Regression baru (`ReproUatAdvancementTest`): (a) identitas murni — R2 persis `[C04, C03]`,
  tanpa C01/C02; (b) input Livewire lintas-boundary — `set()` per baris tersimpan ke
  `competition_registration_id` yang benar (tanpa swap).

### Status / sisa
- **Operator harus meng-isi ulang atau mengoreksi `competition_heat_results` kelas UAT tersebut lewat
  UI (yang kini sudah bisa menyimpan)**; advancement akan memajukan kompetitor yang benar.
- Produk DB (MariaDB `absencai`) tidak dapat dibaca dari sandbox (connection refused) dan mirror
  lokal (`database/database.sqlite`) tidak punya data heat — sehingga binding busuk persis di prod
  tidak bisa dibuktikan byte-per-byte; mekanismenya dibuktikan lewat probe (score-swap C02↔C04
  mereproduksi gejala 1:1).
- Baseline setelah perbaikan: **2409 passed / 6422 assertions / 0 failed / 0 skipped**
  (`/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`).
