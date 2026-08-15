# SPRINT R4 — AUDIT: Team as Competitor (Team vs Team + Team Mass)

> **AUDIT ONLY** — tidak ada perubahan code/database/migration/seeder/UI.
> Pertanyaan: apakah architecture Competition saat ini **siap** menjadikan `CompetitionTeam` sebagai competitor untuk Team vs Team & Team Mass?

- **Tanggal:** 2026-08-14
- **Baseline:** 2311 passed / 6031 assertions / 0 failed / 0 skipped
- **Verdict:** **BELUM SIAP (PARTIAL)** — Team ada lengkap sebagai **entitas + formasi + management anggota**, tetapi **engine match/heat/result/bracket/ranking seluruhnya berbasis `CompetitionRegistration` (person)**. Team belum bisa menjadi competitor.

---

## 1. Representasi Team saat ini (TERVERIFIKASI)

```
CompetitionTeam (event_id, competition_class_id, name, kelompok_id, is_active)
    ↓ competition_team_members (is_substitute, sort_order)
CompetitionTeamMember → competition_registration_id → CompetitionRegistration
                                                              ↓
                                                        Participation (Design C) → Person
```

| Entitas/Service | Isi | Status |
|---|---|---|
| `competition_teams` | event_id FK, class_id FK, name, kelompok_id nullable, is_active; unique (class,name) & (class,kelompok) | ✅ event-scoped; 1 kelompok = 1 team per class |
| `competition_team_members` | team_id, registration_id, is_substitute, sort_order; unique (team,registration) | ✅ anggota = registrasi Design C |
| `CompetitionTeamFormationService` | `formForClass(eventId, classId)` — auto formation by kelompok, team size = kelompok terkecil, players/substitutes; **transactional; tanpa Regu** | ✅ |
| `CompetitionTeamService` | add/remove/setSubstitute/shuffle/listForClass; validasi kelas sama, kelompok sama, satu-team-per-lomba | ✅ |
| UI `Competition/Team/Index` | pilih class, auto formation, daftar team, players/substitutes, add/remove/move/shuffle | ✅ (management anggota, BUKAN match) |

**Kontrak team (terpenuhi):** event-scoped ✅ · 1 kelompok = 1 team ✅ · tanpa Regu ✅ · Person tetap Design C ✅ · satu Person banyak competition ✅ · tidak lintas event ✅ · tidak tercampur class ✅ · Regu tidak diubah ✅ · Person→Participation→Attendance tidak diubah ✅.

---

## 2. Engine Competitor — BUKTI (grep + schema)

| Komponen | Kolom/Logika | Mengenal Team? |
|---|---|---|
| `competition_schedule_entries` (migrasi 08-09) | hanya `competition_registration_id` (FK restrict, unique schedule+registration) | ❌ TIDAK ada `competition_team_id` |
| `competition_outcomes` (migrasi 08-10) | hanya `competition_registration_id` (unique), position/status/score | ❌ TIDAK ada team FK |
| `competition_schedules` (migrasi 08-17#2) | `winner_registration_id` (satu pemenang per orang) | ❌ TIDAK ada `winner_team_id` |
| `competition_brackets` / `_matches` | entry & advance berbasis `competition_registration_id` | ❌ registration-based |
| `CompetitionWorkflowService` | `submitResult(winnerRegistrationId)`; `advanceWinner` menyalin entry registration ke match berikut | ❌ registration-based |
| `CompetitionBracketPodiumService` | Juara 1/2/3 = `competition_registration_id` | ❌ registration-based |
| `CompetitionResultService` | `rankSchedule` (outcome per registration), `aggregateHeatResults` (heat_result per registration), `podiumForClass/Schedule` (outcome per registration) | ❌ registration-based |
| `Schedule\EntryManager` | available/assigned = `CompetitionRegistration` | ❌ tidak ada pilihan team |
| `MatchCenter` / `OfficialPanel` | winner = registration (person) | ❌ tidak ada team |
| `CompetitionReportService` + report blades | outcome/schedule/registration — tanpa team | ❌ tidak ada team |

**Grep:** `competition_team_id` HANYA muncul di `competition_team_members` (+ model & formasi). **NOL** kemunculan di schedule_entries / outcomes / schedules / brackets / workflow / result / bracket-podium / report.

---

## 3. Jawaban per Format

### Team vs Team
- **Team sebagai competitor: ❌ TIDAK.** `competition_schedule_entries` hanya menampung `competition_registration_id`; EntryManager/MatchCenter/OfficialPanel bekerja pada registrasi (person). Team tidak bisa dimasukkan ke match, tidak bisa jadi winner.
- **Bracket team: ❌ TIDAK.** Bracket (single-elim) meng-advance registration; `CompetitionBracketPodiumService` menulis posisi ke registration.

### Team Mass
- **Ranking massal team: ❌ TIDAK.** `CompetitionResultService::rankSchedule`/`aggregateHeatResults` membaca `CompetitionOutcome`/`CompetitionHeatResult` yang berbasis registration (person). Tidak ada `CompetitionOutcome` per team.
- **Podium team: ❌ TIDAK.** `podiumForClass` membaca outcome registration.

---

## 4. Gap yang harus ditutup agar Team jadi Competitor

(Belum dikerjakan — AUDIT; urutan prioritas untuk sprint mendatang)

1. **Team sebagai entry di schedule** — tambah `competition_schedule_entries.competition_team_id` (nullable, additive) + validasi "team ATAU registration" (salah satu), event/class konsisten.
2. **Hasil per team** — sumber result team (mis. tabel team-outcome baru, atau entry position dipakai untuk ranking team) — `competition_outcomes` saat ini unique per registration.
3. **Winner team** — `competition_schedules.winner_team_id` (additive) ATAU representasi competitor generic (team/registration).
4. **Ranking/aggregasi team** — perluas `CompetitionResultService` (ranking team untuk Team Mass; best-time/podium team).
5. **Bracket team** — advance & Juara 1/2/3 berbasis team (atau proxy team-registration).
6. **Podium team** — sumber podium team (Team Mass + Team vs Team).
7. **UI** — EntryManager/MatchCenter/OfficialPanel pilih team; OutcomeManager baris team; Team manager aksi "jadwalkan team".
8. **Event scoping baru** — setiap entitas baru wajib membawa `event_id` (langsung atau via class) — konsisten dengan `competition_teams`.

---

## 5. Kesiapan (apa yang SUDAH siap)

- ✅ `CompetitionTeam` + `CompetitionTeamMember` (event-scoped, 1 kelompok = 1 team).
- ✅ Auto formation + member management + validasi.
- ✅ Kontrak Design C & Regu dipatuhi.
- ✅ Struktur schedule/heat/result engine existing **bisa dipakai ulang** (format-agnostik) — tinggal menambahkan jalur competitor team.

## 6. Kesimpulan

```
NOT READY — Team sebagai competitor BELUM ter-wire.
```

`CompetitionTeam` sudah sempurna sebagai **unit manajemen peserta (entitas + formasi + anggota)**,
tetapi **bukan unit kompetisi**: tidak ada jalur team di `competition_schedule_entries`, `competition_outcomes`,
`competition_schedules.winner_*`, bracket, workflow, result engine, maupun report. Seluruh engine kompetisi
adalah registration-based (person). Team vs Team & Team Mass **belum dapat dijalankan**.

Perubahan yang dibutuhkan bersifat **additive** (kolom/FK/tabel baru) dan TIDAK merusak contract yang sudah ada
(event-scoped, Design C, Regu, formasi team).

---

## Lampiran — lokasi bukti

- `database/migrations/2026_08_09_000001_create_competition_schedule_entries_table.php` (hanya registration_id)
- `database/migrations/2026_08_10_000001_create_competition_outcomes_table.php` (unique per registration_id)
- `database/migrations/2026_08_17_000002_add_match_result_fields_to_competition_schedules.php` (winner_registration_id)
- `database/migrations/2026_08_21_000001_add_competition_teams_and_format.php` (competition_teams + team_members)
- `app/Services/Competition/CompetitionWorkflowService.php`, `CompetitionResultService.php`, `CompetitionBracketPodiumService.php`
- `app/Livewire/Competition/Schedule/EntryManager.php`, `MatchCenter.php`, `OfficialPanel.php`, `Competition/Team/Index.php`
- grep `competition_team_id` → hanya di team_members (NOL di schedule/outcome/bracket/engine)
