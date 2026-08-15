# FIX — Competition Teams 500 (Production)

> Perbaikan minimal 500 pada `Competition → Teams` (`competition.teams`).
> Root cause terbukti runtime: `Class "App\Livewire\Competition\Team\CompetitionTeamMember" not found` di `app/Livewire/Competition/Team/Index.php:86`.

- **Tanggal:** 2026-08-14
- **Status:** **PASS**
- **Files Changed:** 1 file, 1 baris (import).

---

## Root Cause

`app/Livewire/Competition/Team/Index.php` memakai `CompetitionTeamMember::whereHas('team', ...)` di properti `availableRegistrations` (baris 86) **tanpa meng-import model** `App\Models\CompetitionTeamMember`. Karena komponen berada di namespace `App\Livewire\Competition\Team`, PHP me-resolve `CompetitionTeamMember` ke `App\Livewire\Competition\Team\CompetitionTeamMember` (tidak ada) → `Error: Class not found` → 500 saat user memilih kategori + kelas di halaman Teams.

Bug code-level murni — bukan masalah schema/MariaDB/data (terbukti: error identik di SQLite dengan tabel lengkap).

## Fix

Di `app/Livewire/Competition/Team/Index.php`, tambahkan import (1 baris):

```php
use App\Models\CompetitionTeamMember;
```

Verifikasi namespace model: `app/Models/CompetitionTeamMember.php` → `namespace App\Models; class CompetitionTeamMember extends Model` ✅ (tidak dibuat class baru, tidak dipindah, tidak diubah).

Tidak ada perubahan lain: model/migration/database/seeder/.env/business logic Competition/Design C/Regu tidak disentuh.

## Files Changed

- `app/Livewire/Competition/Team/Index.php` — tambah `use App\Models\CompetitionTeamMember;` (baris 8). Hanya ini.

## Tests

- `php -l app/Livewire/Competition/Team/Index.php` → **No syntax errors detected**.
- `php artisan optimize:clear` → config/cache/compiled/events/routes/views cleared ✅ (dijalankan dengan `CACHE_STORE=file` karena store cache default = database (MariaDB) tidak terjangkau dari sandbox — kendala environment, bukan terkait fix).
- Pest Competition relevan:
  - `tests/Feature/Competition/CompetitionTeamFoundationTest.php` → **18 passed (51 assertions)**.
  - `tests/Feature/Competition/*` → **115 passed (244 assertions)**.
  - Termasuk test `teams page accessible for authorized event member` ✅.

## Smoke Test

- HTTP GET `events/1/competition/teams` (event `UAT Competition Dummy`, super_admin `admin@kja.local`) terhadap `database/database.sqlite` (data dummy UAT):
  - **status: 200** (sebelumnya 500).
  - Initial load menampilkan selector kategori/kelas; **tidak ada error "Class ... not found"**.
- Render komponen dengan kategori `2` (UAT Lomba Beregu) + kelas `4` (UAT Team vs Team):
  - **"KM 7" terlihat**, **"KM 10" terlihat**, label **Players** & **Substitutes** tampil, `class_not_found = false` → available registrations + team UAT tampil. ✅
- Kategori & kelas bisa dipilih (render berjalan normal setelah seleksi).

## Regression Check

Full suite Pest:
```
Tests:    2283 passed (5932 assertions)
0 failed, 0 skipped — identik dengan baseline sebelum fix.
```

## Ringkasan

| Item | Hasil |
|---|---|
| Root Cause | Import `App\Models\CompetitionTeamMember` hilang (referensi unqualified di `Index.php:86`) |
| Fix | `use App\Models\CompetitionTeamMember;` (1 baris) |
| Files Changed | `app/Livewire/Competition/Team/Index.php` (1 baris import) |
| Tests | 133 test Competition lulus; full suite 2283 passed / 0 failed |
| Smoke Test | HTTP 200; Teams tampil (KM 7, KM 10, Players, Substitutes); tanpa Class not found |
| Regression | 2283 passed (5932 assertions), 0 failed |

**PASS**
