# FIX — Competition OutcomeManager 500 (`competition_heat_results` missing)

> Debug + fix 500 pada `GET /events/4/competition/schedules/1/outcomes` (Individual Mass).
> Root cause dibuktikan dari `storage/logs/laravel.log`.

- **Tanggal:** 2026-08-14
- **Status:** **PASS**
- **Baseline suite:** 2328 passed / 6084 assertions · **Final suite:** 2329 passed / 6093 assertions / 0 failed / 0 skipped

---

## 1. Root Cause Persis

Dari `storage/logs/laravel.log` (production):

```
production.ERROR: SQLSTATE[42S02]: Base table or view not found: 1146
Table 'absencai.competition_heat_results' doesn't exist
SQL: select * from `competition_heat_results` where `competition_heat_results`.`competition_registration_id` in (6,7,8,9,10)
at .../app/Livewire/Competition/Schedule/OutcomeManager.php(53) → loadParticipants() → render() (OutcomeManager.php:308)
```

**Akar masalah (dua lapis):**
1. **Lingkungan:** database production **belum menjalankan migration additive R2** `2026_08_23_000001_create_competition_heat_results_table.php` → tabel `competition_heat_results` (dan `competition_team_outcomes` R4A) **tidak ada** di MariaDB.
2. **Kode (fragile):** `OutcomeManager::loadParticipants()` melakukan **eager-load `competitionRegistration.heatResults` dan `team.outcome` untuk SEMUA format** — termasuk Individual Mass — sehingga halaman outcomes mass ikut mengeksekusi `SELECT * FROM competition_heat_results ...` → 500 walau tabel itu tidak relevan untuk mass.

## 2. File + Baris Bermasalah

- `app/Livewire/Competition/Schedule/OutcomeManager.php`
  - `loadParticipants()` — eager load `competitionRegistration.heatResults` / `team.outcome` tanpa syarat format (sebelum fix: blok `$entries = CompetitionScheduleEntry::with([...])` di awal `loadParticipants()`; error dipicu di baris query (log: line 53) saat `render()` (line 308).

## 3. Fix yang Dilakukan

### a. Kode (minimal, tanpa mengubah behavior R1–R4)
Eager-load dibuat **format-spesifik**:
```php
$with = ['competitionRegistration.participation.person.desa', 'competitionRegistration.participation.person.kelompok'];

if ($this->isTeam) {
    $with[] = 'team.outcome';
} else {
    $with[] = 'competitionRegistration.outcome';
    if ($this->isHeat) {
        $with[] = 'competitionRegistration.heatResults';
    }
}
$entries = CompetitionScheduleEntry::with($with)->where(...)->get();
```
- Individual Mass / score / vs → hanya `competitionRegistration.outcome` → **tidak lagi menyentuh** `competition_heat_results` / `competition_team_outcomes`.
- Heat → `outcome` + `heatResults`; Team → `team.outcome` (relasi yang memang dipakai branch tsb).
- Tidak ada perubahan logic lain; tidak ada migration/schema/DB yang diubah oleh fix.

### b. Operasional (WAJIB untuk halaman Heat/Team — production)
Root cause utama adalah migration belum dijalankan. Di environment dengan akses MariaDB jalankan migration additive (sudah di-audit PASS di `COMPETITION-MIGRATION-PRODUCTION-AUDIT.md`):
```bash
php artisan migrate --force
```
(MariaDB production tidak dapat dijangkau dari sandbox — `127.0.0.1` connection refused; `172.20.0.1` grant `[1130]`. Karena itu fix kode diprioritaskan agar halaman Individual Mass langsung pulih; migration tetap perlu dijalankan di production untuk format Heat/Team.)

## 4. Test yang Ditambahkan (regression)

`tests/Feature/Competition/CompetitionOutcomeManagerMissingTableTest.php` (1 test):
- **Drop** `competition_heat_results` + `competition_team_outcomes` (mensimulasikan production yang belum migrate R2/R4).
- Render halaman OutcomeManager untuk **Individual Mass** → assert: peserta tampil (`UAT 01/02/03`), tombol **Rank Otomatis**, tombol **Simpan Outcome**, dan **podium** (`Juara 1`) bila ada hasil.
- **Terbukti mereproduksi bug:** saat fix kode di-revert (eager-load unconditional), test **FAIL** dengan `QueryException` (tabel tidak ada); dengan fix **PASS** (9 assertions).

## 5. Hasil Test Competition

```
tests/Feature/Competition  →  161 passed (405 assertions)  (termasuk regression baru + R1–R4)
```

## 6. Hasil Full Suite

```
Tests:    2329 passed (6093 assertions)
Duration: ~77s
0 failed / 0 skipped
```

## 7. Pint

```
vendor/bin/pint app/Livewire/Competition/Schedule/OutcomeManager.php tests/Feature/Competition/CompetitionOutcomeManagerMissingTableTest.php
  PASS  ... 2 files
```
Cache dibersihkan: `php artisan optimize:clear` (dengan `CACHE_STORE=file` karena store default database/MariaDB tak terjangkau sandbox).

## 8. Migration / DB yang Berubah

- **TIDAK ada** migration/schema baru dari fix ini.
- Dev `database.sqlite` tidak di-migrate ulang; tabel `competition_heat_results` & `competition_team_outcomes` tetap ada.
- Production MariaDB: **belum bisa diakses dari sandbox** → migration additive R2–R4 masih **pending** (perlu `php artisan migrate --force` di production). Fix kode membuat halaman Individual Mass pulih tanpa menunggu migration.

## 9. Data UAT

- **TIDAK dihapus / diubah.** Smoke-test di dev hanya membuat + menghapus schedule sementara; tidak ada Person/Participation/Registration/Team/Outcome UAT yang dimodifikasi.

---

## Status: **PASS**

Halaman `/events/{event}/competition/schedules/{id}/outcomes` untuk **Individual Mass** kembali dapat dibuka (peserta + input hasil + tombol Rank Otomatis + podium) — dibuktikan oleh regression test. Catatan: production tetap harus menjalankan `php artisan migrate --force` agar halaman **Heat (R2)** dan **Team (R4A/R4B)** juga berfungsi.
