# SPRINT R4E — BRACKET AUTO-SEED + TEAM DISPLAY

> Implementasi additive: setelah `BracketManager::generate()`, competitor (registration/team) **otomatis masuk ke round pertama** bracket; display team diperbaiki (tidak lagi TBD walau team ter-seed).
> Audit sumber: `docs/audit/DEBUG-BRACKET-PESERTA-TBD.md`.

- **Tanggal:** 2026-08-14
- **Baseline suite:** 2329 passed / 6093 assertions · **Final suite:** 2349 passed / 6155 assertions / 0 failed / 0 skipped

---

## Root Cause

`BracketManager::generate()` hanya membuat struktur bracket (schedules + bracket_matches) — **tidak membuat `competition_schedule_entries`** untuk round pertama → semua slot tampil **TBD**. Selain itu display bracket hanya membaca `competitionRegistration` (person) → team selalu TBD.

## Implementation

### a. `app/Services/Competition/CompetitionBracketSeederService.php` (baru) — AUTO-SEED
`seedInitialRound(int $eventId, int $bracketId)`:
- Event-scoped: `CompetitionClass::where('event_id', $eventId)->findOrFail(class)` → event lain = ModelNotFound.
- Class-scoped: individual → `CompetitionRegistration` class; team → `CompetitionTeam` (event+class).
- Pasangan **deterministik by posisi match**: match posisi P mengambil competitor `[2*(P-1), 2*(P-1)+1]` (urutan `id`).
- **Hanya round pertama** (`round == totalRounds`).
- **Idempotent**: match yang sudah punya entry di-skip (tidak duplicate; tidak overwrite); slot kosong diisi; competitor kurang → slot sisa TBD.
- Tidak membuat competitor palsu; tidak menyentuh final/semifinal (TBD sampai winner advancement).

### b. `app/Livewire/Competition/BracketManager.php`
- `generate()` → setelah membuat bracket, panggil `seedInitialRound(eventId, bracketId)`; flash menampilkan jumlah seeded.
- Eager load bracket display: tambah `scheduleEntries.team` + `schedule.winnerTeam`.

### c. `resources/views/livewire/competition/bracket-manager.blade.php`
- `nameA/B = registration?->person?->nama ?? entry->team?->name ?? 'TBD'`.
- winner = `winner?->person?->nama ?? winnerTeam?->name`.
- Winner logic tidak diubah.

## Auto-Seed Contract

```
Individual vs Individual:  [A,B,C,D] → M1 = A+B, M2 = C+D
Team vs Team:              [A,B,C,D] → M1 = A+B, M2 = C+D
4 → 2 match awal · 8 → 4 · 16 → 8 · 32 → 16
kompetitor < slot → isi tersedia, sisanya TBD
round berikut (final/semi) → TBD sampai winner advancement
```

## Idempotency

- Re-seed (generate/retry) tidak membuat duplicate (`uniq_schedule_registration` / `uniq_schedule_team` + skip match berisi).
- Match yang sudah punya entry (manual) **tidak di-overwrite** — slot pasangannya tidak dipakai untuk match lain (position-based slicing).

## Team Display

- Entry team → `team.name` tampil (bukan TBD).
- Eager load mencakup `scheduleEntries.team` + `winnerTeam`.
- Winner team tampil di bracket.

## Files Changed

- `app/Services/Competition/CompetitionBracketSeederService.php` (baru)
- `app/Livewire/Competition/BracketManager.php`
- `resources/views/livewire/competition/bracket-manager.blade.php`
- `tests/Feature/Competition/CompetitionBracketAutoSeedTest.php` (baru, 8 test)

## Tests

`CompetitionBracketAutoSeedTest` (8):
- A individual auto-seed 4 → M1 A+B, M2 C+D.
- B team auto-seed 4 → M1 A+B, M2 C+D.
- C idempotency (re-seed tidak duplicate; 4 entry tetap 4).
- D event isolation (competitor event lain tidak ikut).
- E class isolation (competitor class lain tidak ikut).
- F team display (seeded team tampil sebagai nama, bukan TBD).
- I winner advancement setelah auto-seed (M1/M2 → final A+C).
- J bracket manual tidak di-overwrite (M1 manual dipertahankan; M2 kosong diisi).

## Regression Result

- Competition dir: **181 passed (467 assertions)**.
- Full suite: **2349 passed / 6155 assertions / 0 failed / 0 skipped** (3× run hijau; 1 flake transient di run pertama tidak ter-reproduksi dan tidak terkait Competition — dir Competition konsisten 181).
- R3 Individual bracket ✅ · R4B Team bracket ✅ · R4D non-bracket official ✅ · mass/heat ✅.

## Pint

`vendor/bin/pint` → 3 files, 1 style issue fixed (ordered imports) — non-logik. Test ulang R4E hijau.

Cache dibersihkan (`php artisan optimize:clear`, `CACHE_STORE=file`).

## Migration / Schema Status

- **Migration = 0 · Schema change = 0 · Seeder change = 0 · Production/UAT data = 0**
- Design C = 0 · Regu = 0 · `CompetitionTeam` contract = tetap · R4D official flow = tetap.

## Final Verdict

**PASS** — bracket yang di-generate kini otomatis mengisi round pertama dari competitor class (deterministik, event/class-scoped, idempotent), dan display team memperlihatkan nama team (bukan TBD).
