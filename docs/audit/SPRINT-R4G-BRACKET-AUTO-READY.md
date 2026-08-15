# SPRINT R4G — BRACKET AUTO-READY → MATCH CENTER

> Fix minimal-additive: setelah auto-seed (R4E), match round pertama yang entry-nya sudah lengkap **dipromosikan `Scheduled → Ready`** melalui workflow existing, sehingga muncul di Match Center.
> Audit sumber: `docs/audit/DEBUG-BRACKET-MATCH-CENTER.md`.

- **Tanggal:** 2026-08-14
- **Baseline suite:** 2353 passed / 6189 assertions (R4F) · **Final suite:** 2359 passed / 6210 assertions / 0 failed / 0 skipped (+6 test, +21 assertions)

---

## Root Cause

`BracketManager::generate()` membuat schedule bracket dengan status `Scheduled`. R4E `seedInitialRound()` mengisi `competition_schedule_entries` round pertama namun **tidak pernah memanggil `checkAutoReady()`**, jadi status match round pertama tetap `Scheduled`. `MatchCenter::render()` hanya meng-query status `['Ready','Playing','Waiting Result']` (dan menghitung Playing/Waiting/Ready/Finished) → match auto-seed tak terlihat → **semua counter 0** (PLAYING 0 / WAITING 0 / READY 0 / FINISHED 0) padahal data entry benar.

Bandingkan: flow non-bracket (registration/team mass) melewati `EntryManager::assign()` yang memanggil `workflow->checkAutoReady()` (EntryManager.php:138-144; CompetitionWorkflowService.php:472-486) — di situlah status naik ke `Ready`. Bracket auto-seed tidak melewati jalur tersebut.

## Decision

- **TIDAK** mengubah query Match Center (`MatchCenter.php:150-168`) untuk menampilkan `Scheduled` — itu menutupi masalah dan membuat status `Scheduled` (bukan Ready) tampil sebagai playable.
- **TIDAK** mengubah `BracketManager::generate()` untuk men-set status langsung — mem-bypass workflow.
- Solusi: panggil **workflow existing** `checkAutoReady($schedule)` setelah seeding tiap match. `checkAutoReady()` idempotent dan internal hanya menaikkan `Scheduled → Ready` jika `canAutoReady()` (entry lengkap memenuhi `required_participants`). Match yang entry-nya belum lengkap tetap `Scheduled` (TBD).

## Implementation

`app/Services/Competition/CompetitionBracketSeederService.php`:
- Constructor injection `CompetitionWorkflowService $workflow` (same namespace).
- Class docblock diperbarui: "Auto-seed initial round of a single-elimination bracket (Sprint R4E + R4G)" + kontrak R4G (promote ke Ready via checkAutoReady).
- Di dalam loop `seedInitialRound()`, setelah membuat entry pasangan:
  ```php
  // Sprint R4G: setelah seeding, evaluasi status lewat workflow
  // existing. Hanya match yang entry-nya sudah memenuhi kebutuhan
  // (required_participants) yang naik ke `Ready`; yang masih kurang
  // tetap `Scheduled` (TBD). checkAutoReady() idempotent.
  $this->workflow->checkAutoReady($match->schedule);
  ```
- Tidak ada perubahan schema, model, controller, atau Livewire component.
- Idempotency R4E tidak berubah: match yang sudah punya entry tetap di-skip sebelum evaluasi; match yang di-skip manual tetap tidak dipromosikan (bukan tanggung jawab auto-seed).

## Files Changed

- `app/Services/Competition/CompetitionBracketSeederService.php` (constructor + 1 baris panggil `checkAutoReady`)
- `tests/Feature/Competition/CompetitionBracketAutoSeedTest.php` (+6 test: K–P)
- `docs/audit/SPRINT-R4G-BRACKET-AUTO-READY.md` (ini)

## Tests

`CompetitionBracketAutoSeedTest` (14 = 8 R4E + 6 R4G):
- **K** individual auto-seed 4 → M1 `Ready`, M2 `Ready`, Final (round 1) tetap `Scheduled`.
- **L** Match Center (`Livewire::test(MatchCenter::class)`) menampilkan athlete A/B/C/D pada class bracket auto-seed; skenario: 2 schedule `Ready`.
- **M** team bracket auto-seed 4 → M1/M2 `Ready` (entry = team id).
- **N** bracket tidak lengkap (2 dari 4 athlete) → M1 `Ready`, M2 tetap `Scheduled` dengan 0 entry (tidak ada force Ready).
- **O** re-seed idempotent → `seeded = 0`, M1/M2 tetap `Ready`, entry tidak ganda.
- **P** isolasi event → event A punya 2 `Ready`, event B 0 schedule.

## Regression Result

- Competition dir: **191 passed (522 assertions)** (sebelumnya 185; +6 test R4G).
- Full suite: **2359 passed / 6210 assertions / 0 failed / 0 skipped**.
  - Terdapat 1 flake transient pada `UserManagementRbacConsistencyTest` (MasterData/User, tidak terkait Competition) di satu run — test tsb hijau saat dijalankan isolasi dan hijau pada run penuh sebelum/sesudahnya; dir Competition konsisten 191.
- R3 Individual bracket ✅ · R4B Team bracket ✅ · R4D non-bracket official ✅ · R4E auto-seed ✅ · mass/heat ✅ · WorkflowDecision/Enforcement ✅.

## Pint

`vendor/bin/pint` pada `CompetitionBracketSeederService.php` + `CompetitionBracketAutoSeedTest.php` → 2 files, 1 style issue fixed (constructor formatting `) {}`) — non-logik. `pint --test` PASS. Test ulang setelah Pint: Competition dir 191 hijau, full suite hijau.

## Migration / Schema Status

- **Migration = 0 · Schema change = 0 · Seeder change = 0 · Production/UAT data = 0**
- Query Match Center = 0 perubahan · `BracketManager::generate()` = 0 perubahan · workflow = 0 perubahan · design C / regu / `CompetitionTeam` contract = 0.

## Final Verdict

**PASS** — setelah auto-seed, match round pertama yang lengkap kini `Ready` dan tampil di Match Center (individu & team); match tidak lengkap tetap `Scheduled`; re-seed idempotent; isolasi event terjaga. Tidak ada masking query, tidak ada bypass workflow, tidak ada perubahan data/migration.
