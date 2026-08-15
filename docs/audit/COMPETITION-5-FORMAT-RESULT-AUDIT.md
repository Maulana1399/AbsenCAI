# AUDIT — Competition 5 Format + Ranking Juara 1/2/3 (Result Engine)

> MURNI AUDIT — tanpa perubahan kode/database/migration/seeder/UI/test.
> Tujuan: apakah sistem Competition saat ini benar-benar mampu menjalankan 5 format sampai menentukan juara 1/2/3.

- **Tanggal:** 2026-08-14
- **Kesimpulan:** Sistem adalah **state-machine schedule/match + entri hasil manual (winner untuk vs-format; position/score/status diketik operator)**. **TIDAK ada engine ranking/aggregasi/juara otomatis.** Format vs (bracket) jalan; format massal hanya bisa dicatat manual; format team **belum ter-wire** ke engine match. Lihat per-format di bawah.

---

## 0. Data Model Hasil (bukti)

| Model | Kolom hasil | Kunci |
|---|---|---|
| `CompetitionOutcome` (`app/Models/CompetitionOutcome.php`; migrasi `2026_08_10_000001_create_competition_outcomes_table.php:13`) | `position` int, `status` string, `score` decimal(10,2), `remarks` text | **UNIQUE per `competition_registration_id`** → SATU outcome per peserta per lomba (bukan per heat/match) |
| `CompetitionSchedule` | `winner_registration_id` (satu pemenang), `finish_reason` (Normal/WO/DQ/Cancel), `finish_notes`, `finished_at` | per schedule/match |
| `CompetitionScheduleEntry` | `order_number`, `lane`, `corner`, `position` | schedule → `competition_registration_id` (slot/urutan, bukan hasil) |
| `CompetitionBracket` / `CompetitionBracketMatch` | round, position, source_match_a/b | single elimination |

Entri hasil:
- `Competition\Schedule\OutcomeManager` (`.../OutcomeManager.php:57-81`) — operator mengetik `position`/`status`/`score`/`remarks` per peserta (updateOrCreate). Blade: `position`=number, `status`=select `Lolos/Gugur/Diskualifikasi/Tidak Hadir`, `score`=number step 0.01.
- `Competition\OfficialPanel` (`.../OfficialPanel.php:86-121`) — untuk match vs (Waiting Result): pilih **satu pemenang** (`winner_registration_id`) + finishReason (Normal/WO/DQ/Cancel).
- `CompetitionWorkflowService::submitResult` (`.../CompetitionWorkflowService.php:118-142`) — simpan winner + advance bracket.

---

## A. Result Type — yang didukung sekarang

| Tipe | Dukungan | Bukti |
|---|---|---|
| WIN/LOSS (winner/loser) | ✅ vs-format | `winner_registration_id` + `submitResult`; bracket `advanceWinner` (`WorkflowService:144-199`). Loser tidak disimpan eksplisit sebagai "loss/position 2". |
| SCORE | ⚠️ sebagian | `outcome.score` decimal + input numeric (`OutcomeManager:55,64,74`). **Tidak** ada perbandingan skor → penentuan winner otomatis; skor vs-skor tidak diturunkan jadi hasil. |
| TIME | ❌ | Tidak ada format mm:ss / parsing / kolom tipe waktu. `score` bisa diisi detik desimal, tapi tidak ada sorting-by-time. |
| RANKING / POSITION | ⚠️ manual | `outcome.position` int; **diketik operator** (`OutcomeManager:49,62,72`). Tidak ada engine sort→assign posisi. |
| FINISH ORDER | ⚠️ = position manual | sama seperti position. |
| DNF / DNS / DSQ | ⚠️ hanya label UI | `status` string bebas; select blade hanya `Lolos/Gugur/Diskualifikasi/Tidak Hadir` (`outcome-manager.blade.php:48-51`). Tidak ada konstanta/penalaran DNF/DNS/DSQ. `finishReason` DQ/WO adalah alasan **seluruh match**, bukan status peserta. |
| TIE | ❌ | Tidak ada handling tie / skor sama. |

`CompetitionFormat::defaultResultType()` (`app/Support/CompetitionFormat.php:79`) mendefinisikan win_loss/score/time/ranking per format, tetapi **tidak pernah dipakai** oleh kode runtime mana pun (grep: hanya definisi; yang dipakai hanya `label()` dan `isTeamFormat()`).

---

## B. Ranking Massal

**TIDAK ADA engine.** Tidak ada kode yang:
- mengurutkan hasil (score/time) lalu menetapkan posisi 1/2/3 otomatis,
- atau mengaggreagasi lintas heat.

Satu-satunya "ranking": `CompetitionReportService::outcomeReport` (`.../CompetitionReportService.php:114`) → `orderBy('position')->orderBy('id')` — **hanya mengurutkan tampilan** berdasarkan nilai `position` yang diketik operator. Blade laporan hasil menampilkan kolom Posisi/Skor/Status (tanpa medali/juara).

---

## C. Heat (Individual Heat)

- Bisa membuat banyak `CompetitionSchedule` (heat) per class dan mengisi entries (`Schedule\Index`, `Schedule\EntryManager`).
- **TAPI**: `competition_outcomes` UNIQUE per `competition_registration_id` → **satu outcome per peserta** (bukan per heat). Jika peserta berlaga di beberapa heat, penyimpanan berikutnya **menimpa** outcome yang sama. Tidak ada `competition_schedule_id` di outcome.
- **Tidak ada aggregasi hasil lintas-heat → ranking keseluruhan.** Heat bergantian hanya bisa direpresentasikan sebagai deretan schedule terpisah dengan outcome per peserta yang saling menimpa.

---

## D. Individual vs Individual (bracket)

- ✅ Bracket single-elimination 4/8/16/32 (`BracketManager::generate` → `CompetitionBracketMatch` round/position; `required_participants=2`).
- Match = schedule berisi 2 entries; official/operator pilih pemenang → `advanceWinner` menyalin pemenang ke match berikutnya (`WorkflowService:144-199`).
- **Final → Juara 1** (pemenang final). **Juara 2** = runner-up final (tidak disimpan eksplisit sebagai position; hanya bisa diturunkan dari bracket). **Juara 3 TIDAK dihasilkan otomatis** — tidak ada bronze/third-place match di bracket single-elim.

---

## E. Team sebagai Competitor (Team vs Team & Team Mass)

- ❌ **Tidak ter-wire.** `competition_schedule_entries` hanya menunjuk `competition_registration_id` (per orang). Tidak ada `competition_team_id` di entries; `EntryManager`/`Workflow`/bracket bekerja pada registrasi (orang), bukan team.
- `competition_teams` + `competition_team_members` ada (auto formation), tapi **belum masuk engine match/result**. Outcome per registration (orang), bukan per team.
- **Team vs Team**: tidak bisa membuat match team vs team saat ini.
- **Team Mass**: tidak ada ranking massal team; tidak ada outcome per team.

---

## F. Juara 1/2/3

- **Tidak ada konfigurasi jumlah juara** (mis. `champion_count`/`juara`) pada `CompetitionClass`. Aturan "jumlah juara = configuration lomba" belum ada.
- **Tidak ada logika podium** (pemenang 1/2/3 otomatis). Posisi 1/2/3 = nilai manual operator di OutcomeManager. Dashboard/report menampilkan counts, bukan medali.

---

## Kesimpulan per Format

| Format | Mampu sampai Juara 1/2/3? | Rincian |
|---|---|---|
| 1. Individual Heat | ⚠️ **PARTIAL** | Schedule/heat ✅, entry ✅, catat hasil ✅ (manual), simpan waktu/skor ⚠️ (`score` desimal, tanpa format time). Gabung antar-heat ❌ (outcome unique per peserta, saling menimpa). Ranking otomatis ❌. Juara 1/2/3 = manual. |
| 2. Individual Mass | ⚠️ **PARTIAL** | Satu race (schedule banyak entry) ✅; catat finish/result ✅ manual; ranking ❌ otomatis (hanya order tampilan); juara 1/2/3 = manual; DNF/DNS/DSQ = label string. |
| 3. Individual vs Individual | ✅ **YES (bracket) / sebagian** | Match ✅, winner/loser ✅ (winner saja), progression ✅ auto-advance, final ✅, juara 1 ✅, juara 2 ⚠️ (runner-up, tak disimpan eksplisit), juara 3 ❌ (tanpa bronze). |
| 4. Team vs Team | ❌ **NO** | Team belum ter-wire ke schedule/entries/bracket; competitor saat ini = registrasi orang. |
| 5. Team Mass | ❌ **NO** | Tanpa outcome per team; tanpa ranking massal team. |

---

## Gap Utama (untuk mencapai full 5-format + juara 1/2/3)

1. **Result engine otomatis** — sorting (score/time) → assign `position`; dukungan DNF/DNS/DSQ/tie; konfigurasi jumlah juara.
2. **Per-heat result + aggregasi** — outcome harus bisa per (schedule/heat) lalu digabung jadi ranking keseluruhan (bukan unique per registration).
3. **Team-wire** — `competition_team_id` di schedule_entries + outcome per team + bracket team.
4. **Bronze/third-place** untuk juara 3 pada format vs/bracket.
5. **Time handling** — format mm:ss / parsing / perbandingan.

> Catatan: beberapa gap ini sudah didokumentasikan sebagai TODO di `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md` (§19: schedule_entries menunjuk team, result type eksplisit, jumlah juara). Audit ini mengonfirmasi dan memperluas temuan tersebut dari sisi **Result Engine**.

---

## Lampiran — lokasi bukti utama

- `app/Models/CompetitionOutcome.php` (position/status/score/remarks; unique per registration)
- `database/migrations/2026_08_10_000001_create_competition_outcomes_table.php:13` (unique)
- `app/Services/Competition/CompetitionWorkflowService.php` (state machine, winner, advanceWinner)
- `app/Livewire/Competition/Schedule/OutcomeManager.php` (entri manual position/score/status)
- `app/Livewire/Competition/OfficialPanel.php` (winner + finishReason)
- `app/Livewire/Competition/Schedule/EntryManager.php` (entries = registrations)
- `app/Livewire/Competition/BracketManager.php` (bracket single-elim, tanpa bronze)
- `app/Services/Competition/CompetitionReportService.php:114` (orderBy position saja)
- `app/Support/CompetitionFormat.php:79` (defaultResultType tak terpakai)
- `resources/views/livewire/competition/schedule/outcome-manager.blade.php:48-51` (status: Lolos/Gugur/Diskualifikasi/Tidak Hadir)
