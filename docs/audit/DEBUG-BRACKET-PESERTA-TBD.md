# AUDIT BUG — BRACKET PESERTA MASIH TBD

> **AUDIT ONLY** — tidak ada perubahan code/migration/database/seeder/data UAT.
> Halaman: `/events/4/competition/bracket-manager` — UAT Individual vs Individual bracket (4): semifinal & final tampil **TBD**.

- **Tanggal:** 2026-08-14

---

## Root Cause

### RC1 — (utama) Bracket TIDAK pernah di-auto-seed dengan peserta

`App\Livewire\Competition\BracketManager::generate(int $classId)` **hanya membuat struktur**, bukan kompetitor:

- `BracketManager.php:48-126`:
  - membuat `CompetitionBracket` (status active);
  - untuk tiap round/position membuat `CompetitionSchedule` **kosong** (`:100-105`; `required_participants = 2`, status Scheduled);
  - membuat `CompetitionBracketMatch` + `source_match_a/b` untuk round bawah (`:107-120`);
  - **TIDAK pernah membuat `competition_schedule_entries`** → semua match dimulai tanpa peserta → tampil "TBD".

Satu-satunya sumber pengisian `competition_schedule_entries`:
- `Schedule\EntryManager.php:138` — operator **manual** meng-assign registration/team ke tiap schedule match;
- `CompetitionWorkflowService::advanceWinner` (`:205`) / `advanceWinnerTeam` (`:316`) — menyalin **winner** ke round berikut **setelah match Finished** (bukan seeding awal);
- `CompetitionWorkflowService::assignParticipant` (`:453`) — didefinisikan tapi **tidak ada caller di app**.

Tidak ada service seeding (dari `CompetitionRegistration` class → slot bracket). **Ini berlaku sama untuk Individual vs Individual (R3) dan Team vs Team (R4B).**

### RC2 — (sekunder) Bracket display team masih "TBD" walau team ter-seed

`resources/views/livewire/competition/bracket-manager.blade.php:136-137`:
```php
$nameA = $participantA?->competitionRegistration?->participation?->person?->nama ?? 'TBD';
```
- Hanya membaca `competitionRegistration` (person). Entry team (`competition_team_id`) → selalu `?? 'TBD'`.
- Eager load `BracketManager.php:246` → `bracketMatches.schedule.scheduleEntries.competitionRegistration.participation.person` — **tidak** menyertakan `scheduleEntries.team`.
- Winner display blade `:138-139` → `$schedule?->winner` (registration), bukan `winnerTeam`.

Jadi **bracket team (R4B) juga akan menampilkan TBD** meskipun team sudah di-assign — hanya Individual vs Individual yang saat ini benar (TBD karena memang belum diisi).

## Exact File + Method + Line

| File | Method / Line | Peran |
|---|---|---|
| `app/Livewire/Competition/BracketManager.php` | `generate()` :48-126 | hanya struktur; tidak membuat entries |
| `app/Livewire/Competition/BracketManager.php` | eager load :246 | tidak include `scheduleEntries.team` |
| `resources/views/livewire/competition/bracket-manager.blade.php` | :136-139 | nameA/B hanya registration → team selalu TBD |
| `app/Livewire/Competition/Schedule/EntryManager.php` | `assign()` :138 | satu-satunya jalur seeding (manual) |
| `app/Services/Competition/CompetitionWorkflowService.php` | `advanceWinner` :205 / `advanceWinnerTeam` :316 | advance winner (bukan seeding awal) |
| `app/Services/Competition/CompetitionWorkflowService.php` | `assignParticipant` :453 | didefinisikan, tidak dipakai |

## Data Flow Sekarang

```
generate() → bracket + schedules (kosong) + bracket_matches (source link)
    ↓ (MANUAL, bukan otomatis)
EntryManager: assign registration/team ke schedule SEMIFINAL → entries
    ↓ (setelah match Finished)
advanceWinner/advanceWinnerTeam → salin winner ke schedule round berikut
    ↓
Final → (bracket podium)
```

## Expected Data Flow (harapan user)

```
generate() → otomatis isi SEMIFINAL dari registrations class
    (registrations A,B,C,D → M1: A+B, M2: C+D)
    ↓ winner advance
Final → (bracket podium)
```

## Klasifikasi

**Keduanya (code + data/flow UAT):**
- **Code:** (a) fitur **auto-seeding** belum ada — generate tidak mengisi competitor; (b) bug display team bracket (`TBD` walau team ter-seed).
- **Data/UAT:** semifinal schedules **belum diisi competitor secara manual** (via EntryManager) — dev DB membuktikan bracket yang berisi (entries=2) hanya karena di-seed manual pada smoke R3; bracket production user masih 0 entry.

Bukan bug query/relasi (`competition_bracket_matches`/`competition_schedule_entries` benar); bukan masalah class/schedule yang salah (bracket milik class yang benar, format individual_vs_individual).

## Minimal Fix yang Direkomendasikan (belum diimplementasikan)

1. **Auto-seed (fitur):** tambah service/method (mis. `CompetitionBracketSeederService::seedFromCompetitors(eventId, bracketId)`) yang mengisi `competition_schedule_entries` pada match **round terbawah** (semifinal) dari:
   - class individual → `CompetitionRegistration` (urutan `id`/`participant_number`, 2 per match);
   - class team → `CompetitionTeam` (via `competition_team_id`);
   - event/class-scoped + idempotent (skip bila match sudah ada entry).
   Dipanggil dari `BracketManager::generate()` (setelah struktur) ATAU sebagai aksi terpisah. **Agar tidak mengubah behavior bracket existing**, pertimbangkan dijalankan hanya bila registrations/teams tersedia; dokumentasikan.
2. **Fix display team bracket (sekunder, aman):** `bracket-manager.blade.php` nameA/B = `...->person?->nama ?? $e->team?->name ?? 'TBD'`; eager load + `winnerTeam`; tampilkan winner team.

## Regression Risk

- **R3 Individual bracket & R4B Team bracket:** test yang mengasumsikan `generate()` = struktur kosong akan berubah jika auto-seed otomatis dijalankan (mis. `CompetitionWorkflowTest` "bracket winner automatically advances" yang meng-seed semi1 manual lalu advance). → Auto-seed harus **opsional/conditional** agar tidak mengubah test/behavior existing; atau update test.
- **Fix display team** (hanya blade + eager load) → tidak mengubah data/logika; risiko rendah.
- Tidak ada migration/schema baru; Design C, Regu, model `CompetitionTeam`, `BracketManager` logic inti tidak dirombak.

## Test yang Harus Ditambahkan

1. Auto-seed individual: generate bracket 4 dengan 4 registrations → semifinal M1 = A+B, M2 = C+D (per urutan).
2. Auto-seed team: generate bracket team class dengan team ter-seed → semifinal berisi team.
3. Display team: bracket blade menampilkan nama team (bukan TBD) untuk bracket team yang di-seed.
4. Regression: R3 individual bracket (manual seed + advance) tetap PASS; R4B team bracket tetap PASS; `advanceWinner`/`advanceWinnerTeam` tetap berfungsi.
