# SPRINT R4C — AUDIT: TEAM MASS END-TO-END

> **AUDIT ONLY** — tidak ada perubahan code/database/migration/seeder/UI.
> Pertanyaan: apakah Team Mass (Schedule → Team Results → Ranking → Podium 1/2/3) berjalan end-to-end dengan arsitektur saat ini?

- **Tanggal:** 2026-08-14
- **Baseline:** 2328 passed / 6084 assertions / 0 failed / 0 skipped
- **Verdict:** **READY (untuk Team Mass single-race)** — flow lengkap berjalan. Batasan terdokumentasi di §5 (multi-heat team & laporan hasil team → sprint berikut).

---

## 1. Flow Team Mass — TERVERIFIKASI end-to-end

```text
CompetitionClass (format = team_mass, result_type = ranking)
    ↓ Team formation (CompetitionTeamFormationService — 1 kelompok = 1 team)
Team (CompetitionTeam, event-scoped, class-scoped)
    ↓ EntryManager (mode team) — assign team ke schedule (competition_team_id)
Schedule / Mass Race (CompetitionSchedule, banyak entry team; required_participants = jumlah team)
    ↓ MatchCenter (start → Playing) → completeMatch/finishMatch → Finished (tanpa bracket → tanpa official)
OutcomeManager (mode team) — input skor/status per team → competition_team_outcomes
    ↓ CompetitionResultService::rankTeams() — sort (asc) → competition ranking (ties 1,1,3) → position
    ↓ CompetitionResultService::podiumForTeams() → top 3 team
    ↓ 🥇 / 🥈 / 🥉 (Juara 1/2/3 team)
```

---

## 2. Audit per komponen

| Komponen | Peran Team Mass | Status |
|---|---|---|
| `CompetitionFormat` | `team_mass`; `defaultResultType` = `ranking`; `isTeamFormat` ✅; `isMass` ✅; `requiresBracket` = false | ✅ |
| `CompetitionResultType` | `ranking` → `isRanked` ✅, `sortDirection` = `asc` (terkecil menang) | ✅ |
| `CompetitionClass` | `resultType()` (fallback `ranking`); `isTeamFormat()` | ✅ |
| `CompetitionSchedule` | entry team via `competition_team_id`; `winner_team_id` (vs-only) | ✅ |
| `CompetitionScheduleEntry` | competitor = team ATAU registration | ✅ |
| `CompetitionTeam` / `CompetitionTeamOutcome` | team + hasil final (unique per team: position/status/score/remarks) | ✅ |
| `CompetitionResultService::rankTeams(eventId, classId)` | sort skor team (asc/desc by result_type), competition ranking (ties), eksklusi DSQ/DNF/DNS/Tidak Hadir, event-scoped, transactional, persist position | ✅ |
| `CompetitionResultService::podiumForTeams(eventId, classId)` | top 3 team outcomes | ✅ |
| `CompetitionWorkflowService` | mass schedule tanpa bracket → `requiresOfficial` false → `completeMatch`/`finishMatch` → Finished langsung (bukan `submitTeamResult` yang vs-only) | ✅ |
| `OutcomeManager` | `isTeam` → `teamOutcomes`; `saveTeamOutcomes` → `CompetitionTeamOutcome::updateOrCreate`; `autoRank` → `rankTeams`; podium → `podiumForTeams` | ✅ |
| `EntryManager` | mode team: assign/unassign team, validasi team milik class | ✅ |
| `Schedule\Index` | buat schedule generik + `required_participants` (operator set = jumlah team) | ✅ |
| `MatchCenter` / `match-card` | tampil entry team + `winnerTeam` (vs) | ✅ |
| `CompetitionReportService` + `Report/*` | **TIDAK ada outcome team** (report hanya `competition_outcomes` registration) | ⚠️ gap |

---

## 3. Jalur yang diverifikasi dengan bukti

- `rankTeams` — `app/Services/Competition/CompetitionResultService.php:257` (class-wide, event-scoped, ties, eksklusi).
- `podiumForTeams` — `CompetitionResultService.php:308` (top 3).
- `OutcomeManager` team path — `app/Livewire/Competition/Schedule/OutcomeManager.php` (`isTeam` mount; `teamOutcomes` load; `saveTeamOutcomes`; `autoRank`→`rankTeams` :225; `podium`→`podiumForTeams` :295; `canAutoRank` true untuk team_mass).
- `EntryManager` team mode — `app/Livewire/Competition/Schedule/EntryManager.php` (`isTeam`, `loadTeamLists`, `assign` validasi class).
- `competition_team_outcomes` — migrasi `2026_08_24_000001` (unique per team).
- UAT/regression: `CompetitionTeamCompetitorTest` (rankTeams, podiumForTeams, entry team) & `CompetitionTeamBracketPodiumTest` (vs team) — hijau.

---

## 4. Event / Class isolation — terverifikasi

- `rankTeams` → `CompetitionClass::where('event_id', $eventId)->findOrFail` → event lain = ModelNotFound (tested R4A).
- `podiumForTeams` → sama (event-scoped).
- `EntryManager::assign` → team wajib `competition_class_id` = schedule class.
- `competition_team_outcomes` → via `team` → class → event.
- Test: `rankTeams cannot run for class of another event` ✅.

---

## 5. Gap / Batasan (untuk sprint berikut, BUKAN blokir single-race)

1. **`rankTeams` CLASS-wide vs `rankSchedule` per-schedule (registration).** Konsisten hanya bila class punya **satu** schedule mass. Jika class punya beberapa heat/schedule, `rankTeams` mencampur team dari semua schedule. (Registration mass R1 = per schedule.)
2. **`competition_team_outcomes` unique per team** → hasil final **satu per team per class**; multi-heat team aggregation TIDAK didukung (mirror keterbatasan registration outcome).
3. **Laporan hasil team TIDAK ada** — `CompetitionReportService`/`Report/Outcome` hanya menampilkan `competition_outcomes` (registration). Team Mass hasil hanya terlihat di OutcomeManager + podium.
4. **Input waktu team = angka desimal** (detik); tanpa parsing `M:SS.mmm` seperti jalur heat (`CompetitionTime`). Bila Team Mass di-scoring waktu, operator memasukkan detik.
5. **Tidak ada auto-rank saat schedule Finished** — ranking dipicu manual via "Rank Otomatis" (konsisten R1 Individual Mass).
6. **Team Mass tidak memakai `winner_team_id`** (tidak ada winner tunggal — ranking) — benar; `winner_team_id` adalah vs-only.
7. **Status eksklusi** team didukung (string + UI select + `rankTeams` exclude) — konsisten.

---

## 6. Kesiapan per kemampuan

| Kemampuan | Status |
|---|---|
| Schedule berisi team (mass race) | ✅ |
| Input hasil per team | ✅ `competition_team_outcomes` + OutcomeManager |
| Ranking team otomatis | ✅ `rankTeams` |
| Podium team 1/2/3 | ✅ `podiumForTeams` |
| Event/class isolation | ✅ (tested) |
| DNF/DNS/DSQ / tie | ✅ |
| Team Mass multi-heat / aggregation | 🔲 sprint berikut (perlu `team_heat_results` atau outcome per schedule) |
| Laporan hasil team | 🔲 sprint berikut |

---

## 7. Kesimpulan

```
READY — Team Mass single-race end-to-end berjalan.
```

`Schedule → Team Results → Ranking → Podium 1/2/3` **sudah lengkap** untuk skenario satu mass race per class (format umum Team Mass). Semua entitas, service, UI, dan isolasi event/class sudah terpasang dan diuji. Gap yang tersisa (multi-heat team, laporan hasil team, parser waktu team) bersifat penambahan di sprint berikut dan **tidak menghalangi** operasi Team Mass dasar.

---

## Lampiran — lokasi bukti

- `app/Services/Competition/CompetitionResultService.php:257` (rankTeams), `:308` (podiumForTeams)
- `app/Livewire/Competition/Schedule/OutcomeManager.php` (team path: load/save/autoRank/podium)
- `app/Livewire/Competition/Schedule/EntryManager.php` (team mode + validasi class)
- `app/Support/CompetitionFormat.php` (team_mass: ranking, isTeam, isMass)
- `app/Support/CompetitionResultType.php` (ranking asc)
- `database/migrations/2026_08_24_000001_add_team_competitor_foundation.php` (competition_team_outcomes)
- `tests/Feature/Competition/CompetitionTeamCompetitorTest.php` (rankTeams/podium/entry/event-scope)
- grep `CompetitionTeamOutcome` di `CompetitionReportService`/`Report/*` → **kosong** (gap laporan team)
