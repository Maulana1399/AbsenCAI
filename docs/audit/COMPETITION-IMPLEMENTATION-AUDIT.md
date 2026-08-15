# COMPETITION IMPLEMENTATION AUDIT

> Audit & adjust modul **Competition** yang sudah ada (Competition V1 — Sprint 7–10).
> Tidak membuat Competition dari nol; reuse code valid; implementasi foundation secara additive.
> Design C (`Person → Participation → Attendance`) dan **Regu** tidak disentuh.

- **Tanggal:** 2026-08-13
- **Status:** COMPETITION IMPLEMENTATION COMPLETE
- **Baseline:** 2265 passed / 5881 assertions / 0 failed / 0 skipped (sebelum task ini)
- **Final:** 2283 passed / 5932 assertions / 0 failed / 0 skipped

---

## 1. Existing Competition Architecture

```
Event
 └── CompetitionCategory (pengelompokan lomba — event_id)
      └── CompetitionClass (lomba — event_id, competition_category_id, gender)
           ├── CompetitionRegistration (participation_id → Participation → Person)
           │     registration_type ('individual')
           ├── CompetitionSchedule (match/heat — class_id, venue_id, status)
           │     └── CompetitionScheduleEntry (→ competition_registration_id, lane/corner/position)
           ├── CompetitionOutcome (per registration — position/score/status/remarks)
           ├── CompetitionBracket + CompetitionBracketMatch (single elimination, registration-based)
           ├── CompetitionMatchOfficial (schedule → user, role)
           └── CompetitionAnnouncement (event_id)
```

**Semantic mapping (diputuskan untuk minimal, tanpa redesign):**
- `CompetitionClass` = **lomba** (contoh: "Tarik Tambang Putra", "Pingpong Mahasiswa"). Format, team, result, schedule melekat pada class.
- `CompetitionCategory` = pengelompokan lomba.
- **Tidak ada** tabel `competitions` tingkat atas — tidak diperlukan; class sudah event-scoped dan memuat segala sesuatunya.

**Registration (sudah mendukung "satu Person banyak lomba"):**
- `competition_registrations` unique `(participation_id, competition_class_id)` → satu Person boleh terdaftar di banyak lomba (banyak baris), Person tetap satu (via `Participation → Person`). ✓ Reused.

**Match engine (sudah ada):**
- `CompetitionWorkflowService` — state machine Schedule (Scheduled→Ready→Playing→Waiting Result→Finished), bracket advance, officials.
- Schedule entries menunjuk `competition_registration_id` (per individu).

---

## 2. Existing Database

| Table | Event scope | Notes |
|---|---|---|
| `competition_categories` | event_id FK restrict | unique (event,name), (event,code) |
| `competition_classes` | event_id FK restrict | + competition_category_id FK; gender L/P/M; unique (event,name),(event,code) |
| `competition_registrations` | **transitif** via participation→event | unique (participation,class); registration_type string |
| `competition_schedules` | transitif via class→event | status Scheduled/Ready/Playing/Waiting Result/Finished; required_participants; winner_registration_id, finish_* |
| `competition_schedule_entries` | transitif | unique (schedule,registration); cascade delete schedule |
| `competition_outcomes` | transitif | unique per registration; cascade delete |
| `competition_match_officials` | transitif | cascade delete user/schedule |
| `competition_brackets` / `_matches` | transitif via class | unique (bracket,round,position) |
| `competition_announcements` | event_id FK cascade | — |

**Tidak ada** tabel `competition_teams` / `competition_team_members`. Team sebelumnya hanya `registration_type` string + `participations.regu_id` (regu, di-retire dari competition domain).

---

## 3. Existing UI

- `Competition/Dashboard`, `Registration` (search person → kategori → kelas), `ParticipantList`, `MatchCenter`, `OfficialPanel`, `OperatorDashboard`, `BracketManager`, `Schedule/*` (Index, EntryManager, OutcomeManager), `Category/*`, `Class/*`, `Venue/*`, `Report/*` (Summary, Registration, Schedule, Outcome, Statistics), `Viewer`.
- Routes di prefix `events/{event}/competition/*` dengan `resolve.active-event` + gate event.
- Sidebar Competition nav (Dashboard, Registrasi, Peserta, Operasional/Jadwal/Match Center/Official Panel/Bracket/Operator, Laporan, Konfigurasi).

**Gap UI:** tidak ada halaman **Teams** (player/substitute) — ditambahkan pada task ini.

---

## 4. Existing Services

- `CompetitionRegistrationService` — daftarkan Person ke class (reuse Participation; gender check; duplicate per class check). ✓ Reused + diperbaiki (return deterministik, param `registration_type`).
- `CompetitionWorkflowService` — state machine match/bracket. ✓ Reused.
- `CompetitionReportService` — laporan. ✓ Reused.
- **Tidak ada** service team/formation → ditambahkan (`CompetitionTeamFormationService`, `CompetitionTeamService`).

---

## 5. Existing Authorization

- Semua route competition di bawah `events/{event}/competition` + `resolve.active-event` + `can:{ability}` (view-dashboard / manage-registration / manage-events / manage-matches / submit-result / view-reports).
- Livewire mutation `Gate::authorize(...)`.
- Event Membership (task sebelumnya) — permission event-scoped via `EventPermissionService` (user_id OR person_id).
- Catatan gap (di luar scope task ini, dari audit sebelumnya): beberapa komponen load record by-ID tanpa cek event_id (`ParticipantList::render` memakai category/class dari input; competition CRUD by-ID di halaman `manage-events`/platform). Format/team baru **wajib** event-scoped (diverifikasi di service & test).

---

## 6. Existing Tests

- `tests/Feature/Competition/*` — CompetitionWorkflowTest, WorkflowDecisionTest, WorkflowEnforcementTest, CompetitionReportTest, DataIntegrityTest (115 test).
- Team foundation test baru: `tests/Feature/Competition/CompetitionTeamFoundationTest.php` (+18).

---

## 7. Reusable Components

- `CompetitionRegistrationService` — registration (reuse; one-person-many-lomba).
- `CompetitionSchedule` / `CompetitionScheduleEntry` / `CompetitionOutcome` / `CompetitionWorkflowService` — match/heat/result/bracket engine (struktur format-agnostik).
- `CompetitionClass` / `CompetitionCategory` — hierarchy + event_id.
- Livewire Competition layout, route group, sidebar nav, `EventOwnership`, `ActiveEventContext`, Permission Engine.

---

## 8. Domain Gaps (sebelum task ini)

| Gap | Status setelah task |
|---|---|
| Team entity (players/substitutes) | ✅ `competition_teams` + `competition_team_members` |
| Auto team formation (kelompok terkecil) | ✅ `CompetitionTeamFormationService` |
| Team member management (PJ) | ✅ `CompetitionTeamService` + UI |
| 5 format lomba | ✅ `competition_classes.format` + `CompetitionFormat` |
| Competition status | ✅ `competition_classes.status` + `CompetitionStatus` |
| Result type (win/loss · score · time · ranking) | 🟡 Diderivasi dari format (`CompetitionFormat::defaultResultType`) — kolom/UI eksplisit = TODO |
| Jumlah juara (konfigurasi lomba) | 🔲 TODO (belum dimodelkan) |
| Match/heat per-format (schedule entry untuk team) | 🔲 TODO — schedule entries masih menunjuk `competition_registration_id` |

---

## 9. Database Gaps

| Gap | Resolusi |
|---|---|
| Tidak ada `competition_teams` | ✅ Migration additive (`2026_08_21_000001`) |
| Tidak ada `competition_team_members` | ✅ Migration additive |
| Tidak ada `format`/`status` di class | ✅ Migration additive (default `individual_heat` / `registration_open`) |
| `competition_schedule_entries.competition_team_id` (scheduling team) | 🔲 TODO (additive) |
| `competition_schedules.winner_team_id` (bracket team) | 🔲 TODO (additive) |

---

## 10. Event Scoping Gaps

- Semua entitas competition baru (`competition_teams`, `competition_team_members`) membawa `event_id` langsung (teams) atau via `competition_registration_id → class → event` (members).
- `CompetitionTeamFormationService::formForClass(eventId, classId)` — `CompetitionClass::where('event_id', $eventId)->findOrFail()` → class event lain = ModelNotFound (DENY). ✓
- `CompetitionTeamService` — validasi kelompok sama, kelas sama, satu-team-per-lomba; panggilan dari komponen mewajibkan `CompetitionTeam::where('event_id', $eventId)->findOrFail()`. ✓
- Route `competition.teams` — `resolve.active-event` + `can:manage-registration` → event lain = 403. ✓ (diuji)
- Gap existing (dokumentasi, bukan diperbaiki di task ini): `ParticipantList`/`Registration` load class by input category/class id tanpa cek event (tetapi UI kategori sudah event-scoped; risiko rendah, lintas-event hanya bisa dilakukan via crafted request yang tetap butuh permission event). Catatan di §Remaining TODO.

---

## 11. Team Design

```
Event
 └── CompetitionClass (lomba, format team)
      └── CompetitionTeam (event_id, class_id, name, kelompok_id?)
           └── CompetitionTeamMember (team_id, competition_registration_id, is_substitute, sort_order)
                 → CompetitionRegistration → Participation → Person
```

- Satu Kelompok = satu Team per lomba (unique `(class, kelompok)`).
- Team **tidak memakai Regu** — berbasis `kelompok_id` + `competition_registration_id`.
- Players (`is_substitute=false`) + Substitutes (`is_substitute=true`).
- PJ dapat: lihat nama, pindah player↔cadangan, hapus, tambah (dengan validasi), shuffle, auto formation. (Cari/mengganti nama peserta = TODO UI lanjutan; service sudah mendukung.)

---

## 12. Registration Design

- Reuse `Participation` (Design C) → `CompetitionRegistration` → `CompetitionClass`.
- Satu Person banyak lomba: satu `CompetitionRegistration` per (participation, class); Person tidak diduplikasi. ✓ (diuji)
- `CompetitionRegistrationService::registerForPerson(..., registrationType)` — dukungan tipe registrasi (individual default).

---

## 13. Match/Heat Design

- Reuse `CompetitionSchedule`/`CompetitionScheduleEntry`/`CompetitionOutcome`/`CompetitionWorkflowService` — struktur format-agnostik (banyak entry = mass; dua entry = vs; outcome posisi/score = ranking).
- Format melekat pada class; `CompetitionFormat` menyediakan `requiresBracket()` (vs), `isMass()`, `defaultResultType()`.
- Bracket hanya untuk format vs (`team_vs_team`, `individual_vs_individual`) — sesuai rule.
- TODO: schedule entry menunjuk team (kolom `competition_team_id`) agar format team bisa di-schedule langsung; advance winner team di bracket.

---

## 14. Result Design

- `CompetitionOutcome` (per registration): `position`, `status`, `score` (decimal), `remarks` — mendukung WIN/LOSS (winner di schedule + status), SCORE (score), TIME (score/remarks), RANKING (position).
- `CompetitionSchedule.winner_registration_id` + `finish_reason/finish_notes/finished_at` — hasil per match.
- Result type default per format via `CompetitionFormat::defaultResultType()` (win_loss / score / time / ranking).
- TODO: kolom `result_type` eksplisit + UI input sesuai tipe (mis. waktu vs skor).

---

## 15. Implementation Performed

1. **Migration additive** `2026_08_21_000001_add_competition_teams_and_format.php`:
   - `competition_classes.format` (default `individual_heat`), `competition_classes.status` (default `registration_open`).
   - `competition_teams` (event-scoped, unique class+name, unique class+kelompok).
   - `competition_team_members` (unique team+registration; is_substitute; sort_order).
2. **`App\Support\CompetitionFormat`** — 5 format + helpers (isTeam/isVs/isMass/requiresBracket/defaultResultType/label).
3. **`App\Support\CompetitionStatus`** — 7 status + isValid/label.
4. **Models** — `CompetitionTeam`, `CompetitionTeamMember`; `CompetitionClass` (+format/status/teams/isTeamFormat); `CompetitionRegistration` (+teamMember/team).
5. **`CompetitionTeamFormationService`** — auto formation transactional (kelompok terkecil = team size; players/substitutes; regenerate; tanpa Regu).
6. **`CompetitionTeamService`** — add/remove/setSubstitute/shuffle/listForClass + validasi (kelas sama, kelompok sama, satu team per lomba).
7. **`CompetitionRegistrationService`** — return deterministik + `registration_type`.
8. **UI** — `Livewire/Competition/Team/Index` + blade + route `competition.teams` (`can:manage-registration`) + sidebar "Teams".
9. **Docs** + laporan ini.

---

## 16. Files Changed

**Migration (additive):**
- `database/migrations/2026_08_21_000001_add_competition_teams_and_format.php` (baru)

**App:**
- `app/Support/CompetitionFormat.php`, `app/Support/CompetitionStatus.php` (baru)
- `app/Models/CompetitionTeam.php`, `app/Models/CompetitionTeamMember.php` (baru)
- `app/Models/CompetitionClass.php`, `app/Models/CompetitionRegistration.php`
- `app/Services/Competition/CompetitionTeamFormationService.php`, `app/Services/Competition/CompetitionTeamService.php` (baru)
- `app/Services/Competition/CompetitionRegistrationService.php`
- `app/Livewire/Competition/Team/Index.php` (baru)

**Views / routes:**
- `resources/views/livewire/competition/team/index.blade.php` (baru)
- `resources/views/components/layouts/app/sidebar.blade.php` (menu Teams)
- `routes/web.php` (route `competition.teams`)

**Tests:**
- `tests/Feature/Competition/CompetitionTeamFoundationTest.php` (baru, 18 test)

**Docs:**
- `docs/MODULES.md`, `docs/DATABASE.md`, `docs/TERMINOLOGY.md`, `docs/ARCHITECTURE.md`, `docs/FEATURE.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/CHANGELOG.md`, `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md`

**TIDAK disentuh:** `regus`/`Database/Regu/*`/`PlacementService`/`regu_id` (Regu FREEZE), `people`/`participations`/`event_attendances` (Design C).

---

## 17. Migration Details

- Additive, backward compatible (SQLite dev/test + MariaDB prod).
- Kolom baru `competition_classes.format`/`status` dengan default → data existing tetap valid.
- Tabel baru `competition_teams` / `competition_team_members` — tidak menyentuh tabel lama.
- Tidak ada DROP COLUMN / DROP TABLE.
- Backfill: tidak diperlukan (kolom baru punya default; tabel baru kosong).
- Verifikasi: `migrate` sukses di SQLite (test suite + uji terpisah); migration siap dijalankan di env dengan akses MariaDB.

---

## 18. Tests

**Perintah:** `/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`

**Baseline (sebelum task):** 2265 passed / 5881 assertions / 0 failed / 0 skipped
**Final:** 2283 passed / 5932 assertions / 0 failed / 0 skipped / Duration 75.65s

**Baru (+18) — `CompetitionTeamFoundationTest.php`:**
- 5 format terdefinisi; class menyimpan format + status; helper format.
- Registrasi: satu Person ikut banyak lomba tanpa duplicate Person.
- Auto formation: KM7=10/KM10=8/KM12=15/KM15=12 → team size 8; KM7=8+2, KM10=8+0, KM12=8+7, KM15=8+4.
- Auto formation tidak menyentuh regus; transactional (re-run regenerate); tolak format non-team; tolak tanpa peserta berkelompok.
- Event scope: formation class event lain → ModelNotFound; team.event_id = event class.
- Team member: validasi kelompok sama; harus terdaftar di kelas sama; tidak boleh di team lain pada lomba sama; move player↔substitute; remove; shuffle preserve split.
- Teams page: authorized event member → OK; unauthorized → 403; user event lain → 403.

**Existing:** 2265 test tetap hijau (termasuk 115 test Competition + seluruh suite auth/event-membership/regu/design C).

---

## 19. Remaining TODO

1. **Match/heat per-format team** — `competition_schedule_entries.competition_team_id` (additive) agar schedule/heat dapat berisi team; `competition_schedules.winner_team_id` untuk advance winner team di bracket.
2. **Result type eksplisit** — kolom `result_type` (win_loss/score/time/ranking) + UI input sesuai tipe; `CompetitionFormat::defaultResultType` menjadi fallback.
3. **Jumlah juara** — konfigurasi `juara` pada class (contoh: 3 juara), bukan bagian format.
4. **Integrasi format/status ke UI existing** — dropdown format & status di `Competition/Class/Index`; validasi registration ditutup saat `registration_closed`.
5. **UI lanjutan team** — cari/mengganti peserta, auto assignment per kelas, konfirmasi delete team.
6. **Event-scoping hardening existing** — `ParticipantList`/`Registration` load class by input id (tambahkan cek event); competition CRUD by-ID (record-level ownership) — gap dari audit sebelumnya, bukan scope task ini.
7. **Import Competition (IF-11)** — belum.

---

## COMPETITION IMPLEMENTATION COMPLETE

**Existing Features Reused:**
- `CompetitionRegistrationService` (registration via Participation/Design C; satu person banyak lomba).
- `CompetitionSchedule`/`CompetitionScheduleEntry`/`CompetitionOutcome`/`CompetitionWorkflowService`/Bracket (match/heat/result engine).
- `CompetitionClass`/`CompetitionCategory` hierarchy + event_id; route group + gate + sidebar nav; Permission Engine (Event Membership).

**New Features:**
- 5 format lomba (`CompetitionFormat`) + status lomba (`CompetitionStatus`).
- `competition_teams` + `competition_team_members` (event-scoped; players + substitutes).
- `CompetitionTeamFormationService` (auto formation by kelompok terkecil, transactional, tanpa Regu).
- `CompetitionTeamService` (member management + validasi).
- UI `competition.teams` + menu sidebar.

**Event Scope:** PASS — team event-scoped; formation menolak class event lain; route teams 403 untuk user event lain.

**Registration:** PASS — satu Person ikut banyak lomba; tanpa duplicate Person (tested).

**Team:** PASS — satu kelompok = satu team per lomba; team event-scoped; tidak memakai Regu (tested).

**Auto Team Formation:** PASS — KM7=8+2 / KM10=8+0 / KM12=8+7 / KM15=8+4 (team size 8; tested).

**Five Formats:** PARTIAL — format dimodelkan & divalidasi (5 format); match/heat engine existing reuse (format-agnostik); scheduling team & result-type eksplisit masih TODO (documented).

**Regu Modified:** MUST BE NO → **NO** (verified: tidak ada file regu/PlacementService berubah).

**Design C Modified:** MUST BE NO → **NO** (verified: people/participations/event_attendances tidak berubah).

**Tests:** 2283 passed / 5932 assertions / 0 failed / 0 skipped (baseline 2265 / 5881; +18 test, +51 assertions). Duration 75.65s.

**Files Changed:**
- Migration 1 (additive) · Support 2 (baru) · Models 4 (2 baru) · Services 3 (2 baru) · Livewire 1 (baru) · View 1 (baru) · Sidebar 1 · Routes 1 · Test 1 (baru, 18 test) · Docs 10.
- Regu = 0 · Competition existing = 0 (reuse) · Design C = 0.
