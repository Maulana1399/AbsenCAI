# SPRINT R4D — TEAM VS TEAM NON-BRACKET OFFICIAL FLOW

> Fix: format `team_vs_team` (dan `individual_vs_individual`) **non-bracket** kini memakai official flow yang sama dengan bracket.
> Audit sumber: `docs/audit/DEBUG-TEAM-VS-TEAM-OFFICIAL-PANEL.md`.

- **Tanggal:** 2026-08-14
- **Baseline suite:** 2329 passed / 6093 assertions · **Final suite:** 2341 passed / 6139 assertions / 0 failed / 0 skipped

---

## Root Cause

- `CompetitionWorkflowService::requiresOfficial()` hanya true untuk **bracket** (`isBracketMatch`).
- `completeMatch()`: non-bracket → **Playing → Finished langsung**, tidak pernah **Waiting Result**.
- Official Panel (`status='Waiting Result'`) + `submitTeamResult` (butuh Waiting Result) tidak terjangkau untuk match vs non-bracket.
- Official Panel juga hanya menampilkan match dengan `CompetitionMatchOfficial` assignment (dipertahankan — tidak di-bypass).

## Perubahan

### a. `app/Services/Competition/CompetitionWorkflowService.php`
- `requiresOfficial()` (minimal, additive):
  - bracket (individual/team) → tetap official;
  - non-bracket `team_vs_team` / `individual_vs_individual` (`CompetitionFormat::isVsFormat`) → kini official;
  - mass/heat → TIDAK berubah (false).
- `submitTeamResult()`:
  - parameter opsional `?int $eventId` — bila diberikan, validasi `schedule.competitionClass.event_id === eventId` (tolak schedule event lain);
  - validasi winner team wajib merupakan entry `competition_team_id` schedule;
  - set `winner_team_id`, status `Finished`;
  - `advanceWinnerTeam`/`finalizePodium` **no-op** bila schedule bukan bracket (tetap aman).
- `completeMatch()`: untuk vs yang membutuhkan official → Playing → Waiting Result; tidak langsung Finished. Guard `canTransitionTo` mencegah duplicate transition.

### b. `app/Livewire/Competition/OfficialPanel.php`
- `submitTeamResult(..., eventId)` — kirim `ActiveEventContext::current()->id` (validasi event).
- **Tetap** memfilter `$assignedScheduleIds` (`CompetitionMatchOfficial`) — tidak ada bypass.

## Flow Sebelum / Sesudah

**Sebelum (non-bracket vs):**
```
Playing → (completeMatch) → Finished      ← official tidak pernah terlibat
```

**Sesudah (non-bracket vs):**
```
Playing → (completeMatch) → Waiting Result → Official Panel (hanya official yg di-assign)
→ pilih Winner Team → submitTeamResult() → Finished → winner_team_id tersimpan
→ (advance/podium HANYA jika memang bracket)
```

## Test Result

- **Baru (12)** `tests/Feature/Competition/CompetitionNonBracketTeamVsTeamTest.php`:
  - A non-bracket team requires official; individual_vs_individual non-bracket official; mass/heat bukan official.
  - B Playing → Waiting Result (non-bracket team); tidak ada duplicate transition; mass/heat masih langsung Finished.
  - C Official Panel menampilkan match hanya untuk official yang di-assign (assertSee team; unassigned → assertDontSee).
  - D Official submit non-bracket team winner via panel → `winner_team_id` + Finished.
  - E non-bracket team result sets `winner_team_id`.
  - + reject team bukan entry; reject schedule event lain.
  - F non-bracket tidak ada bracket advancement / bracket / podium.
  - G regression bracket (R3 individual, R4B team) → official + Waiting Result.
  - I regression mass/heat `finishMatch` → Finished.
- **Competition dir:** **173 passed (451 assertions)**.
- **Full suite:** **2341 passed / 6139 assertions / 0 failed / 0 skipped**.

## Regression Result

- R3 Individual vs Individual bracket ✅ (test G + `CompetitionBracketPodiumTest`).
- R4B Team vs Team bracket ✅ (test G + `CompetitionTeamBracketPodiumTest`).
- Team vs Team non-bracket baru ✅.
- Individual vs Individual non-bracket ✅.
- Individual Mass / Individual Heat / Team Mass ✅ (test I + suite).
- Official Panel tetap hanya menampilkan match yang di-assign ✅ (test C).

## Pint

`vendor/bin/pint` → **3 files, 2 style issues fixed** (unary operator spacing; ordered imports) — tidak ada perubahan logika. Test ulang Competition tetap hijau.

Cache dibersihkan (`php artisan optimize:clear`, `CACHE_STORE=file`).

## File Changed

- `app/Services/Competition/CompetitionWorkflowService.php`
- `app/Livewire/Competition/OfficialPanel.php`
- `tests/Feature/Competition/CompetitionNonBracketTeamVsTeamTest.php` (baru)

## Check

- **Migration = 0** · **Design C = 0** · **Regu = 0** · **Data UAT production = 0** · model `CompetitionTeam` tidak diubah · `BracketManager` tidak dirombak · tidak ada bypass official assignment · tidak ada reset/wipe/truncate.

**Status: PASS** — runtime flow yang dituju terverifikasi: Playing → Waiting Result → Official Panel (assigned official) → submit winner team → Finished → winner_team_id tersimpan.
