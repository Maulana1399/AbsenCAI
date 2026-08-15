# SPRINT R4B — TEAM VS TEAM BRACKET + PODIUM

> Implementasi terbatas R4B: **Team vs Team → Bracket → Match → Winner Team → Final → Juara 1/2/3**.
> Memakai fondasi R4A (team sebagai competitor canonical) + engine bracket existing (R3). **Tidak ada migration baru**.
> Di luar scope: Team Mass baru (kecuali regression), redesign.

- **Tanggal:** 2026-08-14
- **Baseline suite (R4A):** 2322 passed / 6066 assertions / 0 failed
- **Final suite:** 2328 passed / 6084 assertions / 0 failed / 0 skipped (+6 test, +18 assertions)

---

## 1. Flow yang sekarang berjalan

```text
Team (Team vs Team class)
    ↓
Bracket (CompetitionBracket 4/8/16/32 — reuse)
    ↓
Round / Match (CompetitionScheduleEntry.competition_team_id)
    ↓
Winner Team (CompetitionWorkflowService::submitTeamResult → winner_team_id)
    ↓
advanceWinnerTeam (salin team pemenang ke match berikut)
    ↓
Final (round 1)
    ↓
finalizeTeamPodiumForSchedule → CompetitionTeamOutcome
    ↓
🥇 Juara 1 (team) · 🥈 Juara 2 (team) · 🥉 Juara 3 (semifinal losers, seri)
```

## 2. Perubahan

### a. `CompetitionWorkflowService` — jalur team (paralel jalur registration, tanpa duplikasi engine)
- `isTeamMatch(schedule)` = class `isTeamFormat()`.
- `submitTeamResult(schedule, winnerTeamId, ...)` — mirror `submitResult`; set `winner_team_id`, `advanceWinnerTeam`, `promoteReadyMatch`, `finalizePodium`.
- `advanceWinnerTeam(schedule)` — salin `winner_team_id` sebagai entry `competition_team_id` ke match berikut (mirror `advanceWinner`).
- `rollbackTeamAdvancement(schedule)` — mirror rollback registration.
- `resetMatch` di-generalisasi (branch team/registration; hapus `competition_team_outcomes` team ATAU `competition_outcomes` registration; clear kedua winner field).
- `finalizePodium` branch → team: `CompetitionBracketPodiumService::finalizeTeamPodiumForSchedule`; registration: `finalizePodiumForSchedule`.

### b. `CompetitionBracketPodiumService::finalizeTeamPodiumForSchedule` (baru)
- Team version Juara 1/2/3: final (round 1) Finished → `winner_team_id` = Juara 1, runner-up = Juara 2, semifinal losers (round 2) = Juara 3 (seri). Tulis ke `competition_team_outcomes` (unique per team). Event/class-scoped via bracket.

### c. `OfficialPanel` — match team
- `openSubmitDialog`: `availableParticipants` = **teams** (entries' teams) untuk team match; `scheduleEntries.team` eager-loaded.
- `submitResult`: deteksi team match → `submitTeamResult`; assignedIds = `competition_team_id`.
- `rules()`: `selectedWinnerId` di-relaks ke `integer` (validasi `in_array` terhadap entries tetap ada).
- Blade: peserta = team name fallback; pemenang tampilkan `winnerTeam`.

### d. Display
- `MatchCenter` + `match-card`: eager load `scheduleEntries.team` + `winnerTeam`; nama peserta = person ATAU team.
- `BracketManager::podiumForSelected`: untuk class team → `podiumForTeams`, else `podiumForClass` (kartu Juara 1/2/3).

## 3. Files Changed (R4B)

- `app/Services/Competition/CompetitionWorkflowService.php`
- `app/Services/Competition/CompetitionBracketPodiumService.php`
- `app/Livewire/Competition/OfficialPanel.php`
- `app/Livewire/Competition/MatchCenter.php`
- `app/Livewire/Competition/BracketManager.php`
- `resources/views/livewire/competition/official-panel.blade.php`
- `resources/views/livewire/competition/match-card.blade.php`
- `tests/Feature/Competition/CompetitionTeamBracketPodiumTest.php` (baru, 6 test)

**Tidak ada migration baru** (reuse R4A: `competition_schedule_entries.competition_team_id`, `competition_schedules.winner_team_id`, `competition_team_outcomes`).
**Tidak disentuh:** Regu, Design C, jalur registration (Individual vs Individual) R3.

## 4. Tests

- **Baru (6):** team bracket 4 → advance winner team ke final → Juara 1/2/3 team; `podiumForTeams` top 3; OfficialPanel submit team winner (available = teams); non-final no-op; unfinished-final no-op; `resetMatch` team (clear winner_team + team outcomes).
- **Competition dir:** 160 passed (termasuk R3 registration bracket). **Full suite: 2328 passed / 6084 assertions / 0 failed / 0 skipped.**

## 5. Kesiapan

| Kemampuan | Status |
|---|---|
| Team masuk schedule/heat | ✅ (R4A) |
| Winner team di match | ✅ `submitTeamResult` + `winner_team_id` |
| Advance team di bracket | ✅ `advanceWinnerTeam` |
| Juara 1/2/3 team | ✅ `finalizeTeamPodiumForSchedule` + `podiumForTeams` |
| Official submit team | ✅ OfficialPanel team branch |
| MatchCenter/bracket display team | ✅ |
| Team Mass ranking | ✅ (R4A `rankTeams`) |
| Registration (Individual vs Individual) | ✅ tidak berubah |

## 6. Catatan / Batas R4B

- Juara 3 team = **seri** antara dua semifinal losers (single-elim tanpa bronze) — konsisten R3.
- `rollbackAdvancement`/`resetMatch` team sudah didukung; `resetMatch` menghapus team_outcomes entri schedule tsb.
- Team Mass penuh (multi-heat team, dsb.) di luar scope R4B.
