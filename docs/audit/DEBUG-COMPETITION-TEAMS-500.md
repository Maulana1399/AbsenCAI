# DEBUG — Competition Teams 500 (Root Cause)

> Reproduksi & analisis 500 pada menu `Competition → Teams` (`competition.teams`) setelah UAT Competition Dummy di-seed.
> **Tidak ada perubahan kode.** Tidak migrate/seed/reset/wipe/truncate/ubah DB.

- **Tanggal:** 2026-08-14
- **Status:** ROOT CAUSE DITEMUKAN (runtime)

---

## 1. Reproduce (identifikasi route)

```bash
php artisan route:list | grep -i competition
```

Route yang dipanggil:

```
GET|HEAD  events/{event}/competition/teams   competition.teams › App\Livewire\Competition\Team\Index
```

Controller/component: `app/Livewire/Competition/Team/Index.php` (+ `resources/views/livewire/competition/team/index.blade.php`).
Middleware: `auth`, `verified`, `resolve.active-event`, `can:manage-registration`.

> Catatan sandbox: MariaDB production tetap tidak dapat dijangkau dari container ini (host `172.20.0.4` ditolak grant [1130]; `127.0.0.1:3306` connection refused). Reproduksi dilakukan pada **`database/database.sqlite`** yang berisi data dummy UAT identik (event #1, 5 class, 56 registrasi, 5 team). Karena bug bersifat murni code-level, hasil identik di environment mana pun.

## 2. Hasil Reproduksi (runtime)

Render komponen `Competition\Team\Index` dengan memilih class (skenario UAT normal: pilih kategori → pilih kelas):

```
Error: Class "App\Livewire\Competition\Team\CompetitionTeamMember" not found
       at app/Livewire/Competition/Team/Index.php:86
```

- **Initial load** (tanpa pilih class) → tidak kena bug (getter `availableRegistrations` return `collect()` lebih awal).
- **Setelah memilih class** → `render()` menghitung properti `availableRegistrations` → eksekusi baris 86 → `Class ...CompetitionTeamMember not found` → **500**.

## 3. Root Cause

**`app/Livewire/Competition/Team/Index.php` baris 86** memakai `CompetitionTeamMember` **tanpa import**:

```php
// namespace App\Livewire\Competition\Team;
use App\Models\CompetitionCategory;   // ✓
use App\Models\CompetitionClass;      // ✓
use App\Models\CompetitionTeam;       // ✓
// ❌ use App\Models\CompetitionTeamMember;  ← TIDAK ADA

public function getAvailableRegistrationsProperty()
{
    ...
    $assignedRegistrationIds = CompetitionTeamMember::whereHas('team', function ($query) use ($event) {   // line 86
```

Karena tidak ada `use App\Models\CompetitionTeamMember;`, PHP me-resolve `CompetitionTeamMember` ke namespace komponen itu sendiri → `App\Livewire\Competition\Team\CompetitionTeamMember` (tidak ada) → `Error: Class not found` → 500.

Ini **bug kode murni**, bukan masalah schema/MariaDB/data. Terbukti: reproduksi di SQLite (yang tabel `competition_teams`/`competition_team_members`-nya lengkap) menghasilkan error yang sama persis.

## 4. Catatan Sekunder (verifikasi tambahan)

- Blade `team/index.blade.php` aman (semua referensi model fully-qualified atau null-safe: `\App\Support\CompetitionFormat::label(...)`, `$member->competitionRegistration?->participation?->person?->nama ?? '-'`).
- Referensi unqualified lain di komponen: tidak ada (hanya baris 86). `CompetitionRegistration` sudah ditulis fully-qualified (`\App\Models\CompetitionRegistration`) di baris 91; `CompetitionTeam` sudah di-import.
- Karena seeder UAT berhasil membuat team (lewat `CompetitionTeamFormationService` yang butuh tabel `competition_teams`), kemungkinan besar schema MariaDB sudah lengkap. Tetap disarankan memverifikasi migration `2026_08_21_000001` sudah ter-apply di MariaDB (`php artisan migrate:status`) — ini **bukan** penyebab 500 yang direproduksi, hanya pemeriksaan pelengkap.

## 5. Fix yang Disarankan (belum diterapkan)

Tambah satu import di `app/Livewire/Competition/Team/Index.php`:

```php
use App\Models\CompetitionTeamMember;
```

Setelah itu page Teams kembali normal (render teams, available registrations, auto formation, shuffle, dst). Perbaikan ini tidak menyentuh DB/event/Person/Participation/Regu.

## 6. Ringkasan

| Item | Nilai |
|---|---|
| Route | `competition.teams` (`events/{event}/competition/teams`) |
| Component | `App\Livewire\Competition\Team\Index` |
| Error | `Class "App\Livewire\Competition\Team\CompetitionTeamMember" not found` |
| File:Line | `app/Livewire/Competition/Team/Index.php:86` |
| Penyebab | Import `App\Models\CompetitionTeamMember` hilang (referensi unqualified) |
| Muncul saat | Pilih category + class di halaman Teams (render `availableRegistrations`) |
| Environment | Bug code-level — terjadi di semua DB (bukan masalah MariaDB/schema/data) |
| Fix | `use App\Models\CompetitionTeamMember;` (1 baris, belum diterapkan) |
