# SPRINT R4A — TEAM AS COMPETITOR FOUNDATION

> Implementasi terbatas R4A: menjadikan `CompetitionTeam` sebagai **competitor canonical** di Competition Engine
> tanpa merusak competitor Registration/Person yang sudah berjalan.
> FOUNDATION — bukan Team vs Team / Team Mass penuh.

- **Tanggal:** 2026-08-14
- **Baseline suite (R3):** 2311 passed / 6031 assertions / 0 failed
- **Final suite:** 2322 passed / 6066 assertions / 0 failed / 0 skipped (+11 test, +35 assertions)

---

## 1. Core Contract

```
Registration competitor  → Person / Participation (tetap)
Team competitor          → CompetitionTeam  (baru, additive)
```

`competition_schedule_entries` kini menerima **dua jenis competitor** (salah satu per entry):
- `competition_registration_id` (person) — tetap.
- `competition_team_id` (team) — baru.

## 2. Perubahan

### a. Migration additive `2026_08_24_000001_add_team_competitor_foundation.php`
1. `competition_schedule_entries.competition_team_id` (nullable FK → competition_teams, restrict) + unique `(schedule, team)` `uniq_schedule_team` + index; `competition_registration_id` dibuat **nullable** (agar entry team bisa tanpa registration).
2. `competition_schedules.winner_team_id` (nullable FK → teams, nullOnDelete) — fondasi vs team (workflow penuh di sprint berikut).
3. Tabel baru `competition_team_outcomes` — hasil final per team (mirror `competition_outcomes`): `competition_team_id` unique (cascade), `position`, `status`, `score`, `remarks`.

Tidak ada DROP/delete data; kompatibel SQLite + MariaDB (PRAGMA foreign_keys OFF hanya untuk rebuild SQLite).

### b. Models
- `CompetitionScheduleEntry`: + `competition_team_id` fillable, `team()` relation.
- `CompetitionSchedule`: + `winner_team_id` fillable, `winnerTeam()` relation.
- `CompetitionTeamOutcome` (baru): `team()` relation.
- `CompetitionTeam`: + `outcome()` (hasOne), `scheduleEntries()` (via team_id).

### c. Services
- `CompetitionResultService`:
  - `rankTeams(eventId, classId)` — ranking TEAM (Team Mass) by `competition_team_outcomes.score`, event-scoped, competition ranking dengan ties (1,1,3), persist position.
  - `podiumForTeams(eventId, classId)` — top 3 team.
- `CompetitionTeamFormationService`: **guard** — tolak regenerate bila team sudah dipakai di jadwal/hasil (mencegah delete team yang ter-schedule).

### d. UI
- `Schedule\EntryManager`: mode **team** untuk class team-format — available/assigned = `CompetitionTeam`; assign/unassign/moveUp/moveDown memakai `competition_team_id`; validasi competitor milik class (menolak team class lain).
- `Schedule\OutcomeManager`: mode **team** — baris team (posisi/status/skor/remarks) → `competition_team_outcomes`; tombol **Rank Otomatis** → `rankTeams`; podium = `podiumForTeams`. Jalur registration/heat R1–R2 tidak berubah.

## 3. Files Changed

- `database/migrations/2026_08_24_000001_add_team_competitor_foundation.php` (baru)
- `app/Models/CompetitionScheduleEntry.php`, `app/Models/CompetitionSchedule.php`, `app/Models/CompetitionTeam.php`, `app/Models/CompetitionTeamOutcome.php` (baru)
- `app/Services/Competition/CompetitionResultService.php` (+ rankTeams, podiumForTeams)
- `app/Services/Competition/CompetitionTeamFormationService.php` (+ guard)
- `app/Livewire/Competition/Schedule/EntryManager.php`, `app/Livewire/Competition/Schedule/OutcomeManager.php`
- `resources/views/livewire/competition/schedule/outcome-manager.blade.php` (section team + podium generic)
- `tests/Feature/Competition/CompetitionTeamCompetitorTest.php` (baru, 11 test)
- Backup dev DB: `database/database.sqlite.backup.pre-R4A-*`

**Tidak disentuh:** Regu, Design C, `competition_outcomes` (registration), `competition_heat_results`, CompetitionRegistration/Formation existing, bracket engine registration.

## 4. Tests

- **Baru (11):** schema fondasi team; EntryManager team (list + assign, unique duplikat, tolak team class lain); registrasi & team coexist; `rankTeams` (rank + tie + eksklusi + event-scope); `podiumForTeams`; formasi guard (tolak regenerate bila team ter-schedule); OutcomeManager team path (simpan → rank → podium).
- **Competition dir:** 154 passed. **Full suite: 2322 passed / 6066 assertions / 0 failed / 0 skipped.**

## 5. Kesiapan

| Kemampuan | Status |
|---|---|
| Team sebagai competitor di schedule (entry) | ✅ `competition_schedule_entries.competition_team_id` |
| Team memiliki hasil final | ✅ `competition_team_outcomes` |
| Ranking team (Team Mass) | ✅ `rankTeams` |
| Podium team | ✅ `podiumForTeams` |
| Winner team (fondasi vs) | ✅ `competition_schedules.winner_team_id` (schema) |
| Bracket team / advance team | 🔲 Sprint berikutnya (perlu `winner_team_id` wiring + advance) |
| UI vs-team penuh | 🔲 Sprint berikutnya |
| Team per-heat / aggregasi team | 🔲 Sprint berikutnya |

**Contract team tetap:** event-scoped ✅ · 1 kelompok = 1 team ✅ · tanpa Regu ✅ · Design C ✅ · satu Person banyak lomba ✅ · tidak lintas event/class ✅ · `competition_outcomes` registration tidak berubah ✅.

## 6. Catatan / Batas R4A

- Entry team & entry registration **coexist** di `competition_schedule_entries`, tapi untuk class team-format UI EntryManager menampilkan **team saja** (registrasi di class team bukan kombinasi yang didukung UI).
- Team Mass: satu `competition_team_outcome` per team (final per class, mirror registration outcome) — sesuai satu race per class.
- Team vs Team penuh (winner team + advance bracket + Juara 1/2/3 team) memakai `winner_team_id` yang sudah disiapkan — sprint berikutnya.
