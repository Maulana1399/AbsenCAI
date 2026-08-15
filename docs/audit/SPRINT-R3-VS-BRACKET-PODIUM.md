# SPRINT R3 — Individual vs Individual → Bracket → Final → Juara 1/2/3

> Implementasi terbatas Sprint R3: workflow Individual vs Individual lengkap hingga **Juara 1/2/3** dari bracket.
> Di luar scope (tidak dikerjakan): Team competitor, Team vs Team, Team Mass, redesign Competition.

- **Tanggal:** 2026-08-14
- **Baseline suite (R2):** 2306 passed / 6011 assertions / 0 failed
- **Final suite:** 2311 passed / 6031 assertions / 0 failed / 0 skipped (+5 test, +20 assertions)

---

## 1. Flow yang sekarang berjalan

```text
Registration
    ↓
Bracket (CompetitionBracket, single-elim 4/8/16/32)
    ↓
Round (CompetitionBracketMatch round/position)
    ↓
Match (CompetitionSchedule + ScheduleEntry)
    ↓
Winner / Loser (official submitResult → winner_registration_id; advanceWinner)
    ↓
Final (round 1)
    ↓
CompetitionBracketPodiumService (auto-hook saat final Finished)
    ↓
🥇 Juara 1 (pemenang final) · 🥈 Juara 2 (runner-up final) · 🥉 Juara 3 (semifinal losers, seri)
```

## 2. Gap yang diselesaikan (dari audit COMPETITION-5-FORMAT-RESULT-AUDIT)

| Audit gap | Solusi R3 |
|---|---|
| Juara 1 = pemenang final ✅, Juara 2 = runner-up (tidak disimpan eksplisit) | `CompetitionBracketPodiumService` menulis posisi 1 & 2 ke `competition_outcomes`. |
| Juara 3 TIDAK dihasilkan otomatis (tanpa bronze match) | Semifinal losers (round 2) ditulis posisi **3 (seri)** — konvensi single-elimination tanpa bronze. |
| Tidak ada peringkat otomatis dari bracket | Auto-hook di `CompetitionWorkflowService::submitResult`/`finishMatch` saat final Finished. |

## 3. Perubahan

### a. `CompetitionBracketPodiumService` (baru)
- `finalizePodiumForSchedule(CompetitionSchedule $schedule): array`
  - No-op kecuali schedule adalah **final bracket** (bracketMatch `round == 1`) dan `status === Finished`.
  - Event-scoped via class schedule (murni registrasi class tsb).
  - Juara 1 = `winner_registration_id` final; Juara 2 = entry lain di final; Juara 3 = losers semua match round 2 yang Finished (seri).
  - Tulis `CompetitionOutcome::updateOrCreate(position, status='Juara N', remarks='Bracket <name>')` — **score tidak diubah** (vs tidak punya skor).

### b. `CompetitionWorkflowService` — auto-hook
- `submitResult()` (jalur official bracket) & `finishMatch()` → setelah Finished, panggil `finalizePodium($schedule)`.
- Semifinal yang Finished tidak memicu (round ≠ 1); final yang belum Finished tidak memicu.

### c. Display Podium
- **`BracketManager`** (halaman bracket): kartu **Juara 1/2/3** ditampilkan dari `podiumForClass` setelah final selesai.
- **`OutcomeManager`**: untuk format vs (`individual_vs_individual` / `team_vs_team`), podium = `podiumForClass` (podium final kelas), bukan per-schedule; tombol "Rank Otomatis" disembunyikan untuk vs (hasil via bracket/winner).

### d. Test `CompetitionBracketPodiumTest` (baru, 5 test)

## 4. Files Changed

- `app/Services/Competition/CompetitionBracketPodiumService.php` (baru)
- `app/Services/Competition/CompetitionWorkflowService.php` (+ hook finalizePodium)
- `app/Livewire/Competition/Schedule/OutcomeManager.php` (podium vs-format; canAutoRank vs=false)
- `app/Livewire/Competition/BracketManager.php` (+ podiumForSelected)
- `resources/views/livewire/competition/bracket-manager.blade.php` (kartu Juara 1/2/3)
- `tests/Feature/Competition/CompetitionBracketPodiumTest.php` (baru)

**Tidak disentuh:** Regu, Design C, CompetitionTeam/Formation, Team vs Team, Team Mass, Competition redesign, migration (tidak ada migration baru — reuse tabel existing).

## 5. Tests

- **Baru (5):**
  - Final selesai → Juara 1 (pemenang final), Juara 2 (runner-up final), Juara 3 (kedua semifinal losers, seri).
  - `podiumForClass` → top 3 setelah final.
  - Bracket 8 → hanya semifinal losers dapat 3; quarterfinal losers tidak ditempatkan.
  - Match non-final yang Finished → tidak menulis Juara.
  - Final belum Finished → no-op.
- **Competition dir:** 143 passed. **Full suite: 2311 passed / 6031 assertions / 0 failed / 0 skipped.**

## 6. Smoke Test (dev `database.sqlite`, UAT Individual vs Individual)

- 4-bracket pada class UAT vs: semifinal 1 (UAT 01 vs 02 → 01), semifinal 2 (UAT 03 vs 04 → 03), final (01 vs 03 → 01).
- Podium otomatis setelah final: **Juara 1 = UAT Competition 01, Juara 2 = UAT Competition 03, Juara 3 = UAT Competition 02 (seri dengan 04)** ✅.

## 7. Catatan / Batas R3

- Juara 3 = **seri** antara dua semifinal losers (single-elim tanpa bronze match). Jika ingin Juara 3 tunggal, perlu bronze match — di luar scope R3.
- Bracket hanya 4/8/16/32 (existing `BracketManager::generate`); 2-peserta tidak didukung (min 4) → selalu ada semifinal.
- vs-format tetap memakai jalur winner (`winner_registration_id`); `score` tidak dipakai untuk bracket.
