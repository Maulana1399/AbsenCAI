# Competition UAT Data — KJA Event Manager

> Dokumen ini menjelaskan dataset UAT Competition yang di-seed oleh
> `Database\Seeders\CompetitionUatSeeder` untuk pengujian manual seluruh
> workflow Competition melalui UI.

**Status:** Aktif — seeder + reset command tersedia (`competition:uat-reset`).

---

## 1. Cara Menjalankan Seeder

```bash
# Seed dataset UAT Competition 2026 (idempotent — aman dijalankan ulang)
php artisan db:seed --class=CompetitionUatSeeder

# Reset dataset UAT (hapus SEMUA data bermarker UAT, dry-run dulu)
php artisan competition:uat-reset

# Eksekusi reset yang sebenarnya
php artisan competition:uat-reset --apply
```

Catatan:

- Seeder **idempotent**: `updateOrCreate`/`firstOrCreate` + guard keberadaan
  schedule/team. Menjalankan ulang seeder TIDAK menggandakan data.
- Reset **hanya menghapus data bermarker UAT** (event "UAT Competition 2026",
  kategori/kelas/heat/registrasi/team/desa/kelompok "UAT ..."). Event lain,
  master data non-UAT, dan data legacy **tidak pernah disentuh**.
- Reset dibungkus satu transaksi; `--apply` untuk eksekusi, tanpa `--apply`
  = dry-run (tidak ada perubahan database).

---

## 2. Dataset

### 2.1 Event

| Field | Nilai |
|-------|-------|
| Nama | `UAT Competition 2026` |
| Slug | `uat-competition-2026` |
| Tipe | `competition` |
| Status | `active` |
| Waktu | mulai hari ini, selesai +14 hari |

Event ini muncul di **Event Switcher** (semua event aktif ter-ekspose) dan
bisa dijadikan Active Event.

### 2.2 Peserta

60 Person/participation dummy dengan nama jelas:

- `UAT Peserta 001` s.d. `UAT Peserta 060`
- Gender: bergantian L/P (masing-masing 30 orang)
- Setiap peserta punya: `person_id`, `participation` (participant_number +
  attendance_code + jenis_peserta), `desa_id`, `kelompok_id`, tanggal lahir
  sesuai grup usia.
- Semua peserta terhubung ke event UAT dan siap diregistrasi.

### 2.3 Desa & Kelompok (master data dummy)

| Marker | Volume | Keterangan |
|--------|--------|------------|
| `UAT Desa 01..03` | 3 desa | dipakai semua person |
| `UAT Futsal Team 01..08` | 8 kelompok | person 1..32 (4 per tim) |
| `UAT Kelompok 01..06` | 6 kelompok | person 33..60 |

> Desa/kelompok ini ikut di-reset oleh `competition:uat-reset`.

### 2.4 Kategori (CompetitionCategory)

| Kategori | Kode | Peserta (indeks person) | Kegunaan |
|----------|------|--------------------------|----------|
| UAT - PAUD | `uat-paud` | 1..10 | kelas Heat 5/2 - 4 Peserta |
| UAT - SD | `uat-sd` | 11..24 | kelas Heat 5/2 - 5 Peserta, UAT Time |
| UAT - SMP | `uat-smp` | 25..38 | Heat 9, Rebuild, Score, Protection |
| UAT - SMA | `uat-sma` | 39..52 | Heat 10, Bracket, Round Advancement |
| UAT - Dewasa | `uat-dewasa` | 53..60 | Ranking, Silat Putri |
| UAT - Beregu | `uat-beregu` | 1..32 | Team Competition, Team Heat |

### 2.5 Kelas (CompetitionClass) — daftar scenario

Semua kelas aktif (`is_active`), status `registration_open`. Format terpasang
adalah format engine existing (tidak ada format baru yang diarang).

| # | Kelas (nama di UI) | Format | Result Type | Gender | Peserta/Team | Format Heat |
|---|---------------------|--------|-------------|--------|--------------|-------------|
| 1 | UAT - Heat 5/2 - 5 Peserta | individual_heat | time | M | 5 | R1 5/2 |
| 2 | UAT - Heat 5/2 - 9 Peserta | individual_heat | time | M | 9 | R1 5/2 |
| 3 | UAT - Heat 5/2 - 10 Peserta | individual_heat | time | M | 10 | R1 5/2 |
| 4 | UAT - Heat 5/2 - 4 Peserta | individual_heat | time | M | 4 | R1 5/2 |
| 5 | UAT - Heat Rebuild | individual_heat | time | M | 5 | R1 5/2 + legacy round |
| 6 | UAT - Existing Result Protection | individual_heat | time | M | 5 | R1 5/2 + legacy round + hasil |
| 7 | UAT - Round Advancement | individual_heat | time | M | 9 | R1 5/2, R2 4/2, R3 2/1 |
| 8 | UAT - Silat Putri | individual_heat | time | P | 6 | R1 5/2 |
| 9 | UAT - Time | individual_heat | time | M | 6 | R1 5/2 |
| 10 | UAT - Score | individual_heat | score | M | 6 | R1 5/2 |
| 11 | UAT - Ranking | individual_mass | ranking | M | 10 | 1 schedule mass |
| 12 | UAT - Bracket | individual_vs_individual | score | M | 8 | Bracket 8 (QF→SF→F) |
| 13 | UAT - Team Competition | team_vs_team | win_loss | M | 8 tim | Bracket 8 (QF→SF→F) |
| 14 | UAT - Team Heat | team_heat | time | M | 8 tim | R1 5/2 |

---

## 3. Skenario UAT

> Persyaratan utama: dataset tidak mengandung hasil otomatis kecuali pada
> scenario yang memang bertujuan menguji guard data. Operator mengisi hasil
> melalui UI (Heat Manager → Input Hasil / Match Center / Outcome).

### 3.1 UAT - Heat 5/2 - 5 Peserta (Case A)

- **Tujuan:** format 5 peserta/heat, top-2 lolos.
- **Data:** 5 peserta → 1 heat.
- **Expected:** Heat 1 = 5 peserta, `required_participants` = 5,
  top 2 lolos.
- **Langkah:**
  1. Buka menu **Heat** → pilih kelas ini.
  2. Lihat format R1 (5 peserta/heat, top 2) + heat hasil generate (1 heat, 5/5).
  3. Input hasil (waktu) per peserta → submit.
  4. Lihat ranking per-heat (tercepat ranking terbaik).

### 3.2 UAT - Heat 5/2 - 9 Peserta (Case B)

- **Tujuan:** pengujian heat penuh + heat tidak penuh.
- **Data:** 9 peserta → 2 heat.
- **Expected:**
  - Heat 1 = 5 peserta
  - Heat 2 = 4 peserta
  - Top 2 per heat → total qualifier = 4
- **Langkah:**
  1. Menu Heat → pilih kelas.
  2. Pastikan 2 heat muncul (5 dan 4 peserta).
  3. Input hasil di kedua heat → klik **Generate Round Berikutnya** (butuh
     format R2 terlebih dahulu — lihat 3.5).
  4. Verifikasi 4 qualifier lolos ke babak berikutnya.

### 3.3 UAT - Heat 5/2 - 10 Peserta (Case C)

- **Tujuan:** dua heat penuh.
- **Data:** 10 peserta → 2 heat.
- **Expected:** Heat 1 = 5, Heat 2 = 5; top 2 per heat → 4 qualifier.

### 3.4 UAT - Heat 5/2 - 4 Peserta (Case D)

- **Tujuan:** regression fix "4 peserta jangan jadi 2+2".
- **Data:** 4 peserta → 1 heat.
- **Expected:** 1 heat berisi 4 peserta (bukan 2+2), `required_participants` = 5.

### 3.5 UAT - Round Advancement

- **Tujuan:** progression Round 1 → Round 2 → Final.
- **Data:** 9 peserta; format R1=5/2, R2=4/2, R3=2/1. Hanya R1 yang di-generate.
- **Expected:**
  - R1 = 2 heat (5,4) → top 2 per heat = 4 qualifier
  - Setelah R1 selesai: **Generate Round Berikutnya** → R2 1 heat (4 peserta)
  - R2 selesai → **Generate Round Berikutnya** → R3 (2 peserta, 1 lolos)
- **Catatan:** engin tidak pernah memfabrikasi babak tanpa format. Tanpa format
  R2/R3, Generate Round Berikutnya ditolak `no_next_format`.

### 3.6 UAT - Heat Rebuild

- **Tujuan:** menguji deteksi `needs_rebuild` dan tombol "Generate Ulang Babak Ini".
- **Data:** 5 peserta, format R1 5/2, namun round dibuat secara legacy dengan
  `required_participants = 2` (2/2/2 pesertanya: 2, 2, 1). Tidak ada hasil,
  round belum dimulai (status Scheduled).
- **Expected:**
  - Banner amber: "Heat yang ada tidak sesuai format ... kapasitasnya bukan 5 peserta/heat."
  - Terdapat tombol **Generate Ulang Babak Ini**.
  - Klik → round dibangun ulang menjadi 1 heat berisi 5 peserta
    (`required_participants` = 5).

### 3.7 UAT - Existing Result Protection

- **Tujuan:** menguji guard `has_results` pada rebuild.
- **Data:** 5 peserta, format R1 5/2, legacy round (required 2) **sudah punya
  hasil heat** (`competition_heat_results`).
- **Expected:**
  - Banner amber muncul (mismatch kapasitas) + tombol **Generate Ulang Babak Ini**.
  - Klik tombol → ditolak dengan pesan "Round sudah punya hasil yang diinput;
    hapus hasil heat dulu sebelum membangun ulang." — data aman.

### 3.8 UAT - Silat Putri (Cabang B — edge case)

- **Tujuan:** jumlah peserta berbeda (bukan 5/9/10) + kelas gender P.
- **Data:** 6 peserta (semua perempuan — kelas gender P) → 2 heat.
- **Expected:** Heat 1 = 5, Heat 2 = 1; top 2 per heat.

### 3.9 UAT - Time / Score / Ranking

- **Tujuan:** menguji ketiga hasil masih tersedia (time, score, ranking).
- **Data:**
  - Time: individual_heat, result_type time — heat 5/2 (2 heat 5,1).
  - Score: individual_heat, result_type score — heat 5/2 (2 heat 5,1), skor tertinggi menang.
  - Ranking: individual_mass, 1 schedule mass berisi 10 peserta — masukkan hasil
    lewat Input Hasil, lihat ranking (urutan finish).

### 3.10 UAT - Bracket & UAT - Team Competition

- **Tujuan:** menguji bracket QF → SF → Final untuk individual vs dan team vs.
- **Data:**
  - Bracket: 8 peserta (individual_vs_individual) — bracket size 8.
  - Team Competition: 8 tim futsal (`UAT Futsal Team 01..08`) — bracket size 8.
- **Expected:**
  - Bracket terlihat di **Bracket Manager** dengan round Quarter Final, Semi
    Final, dan Final (label engine existing).
  - Round awal (QF) sudah ter-seed (8 peserta/team ditempatkan ke 4 match).
  - Input hasil lewat Match Center/Official → pemenang maju ke semi → final.

### 3.11 UAT - Team Heat

- **Tujuan:** menguji format team_heat di Heat Manager (team sebagai kompetitor).
- **Data:** 8 tim → format R1 5/2 → 2 heat (5,3).
- **Expected:** heat terisi tim (bukan peserta perorangan), ranking per-heat
  memakai hasil team.

---

## 4. Verifikasi Otomatis

Tersedia test khusus: `tests/Feature/Database/CompetitionUatSeederTest.php`

```bash
/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest tests/Feature/Database/CompetitionUatSeederTest.php
```

Menjalankan seeder pada in-memory DB dan memverifikasi: event, 60 peserta,
6 kategori, 14 kelas scenario, seluruh Case A–D, rebuild, protection, round
advancement, silat putri, team, bracket, no-auto-result (kecuali protection),
idempotensi seeder, dan reset command.

Baseline saat ini: **2453 passed / 6839 assertions / 0 failed** (2026-08-26).

---

## 5. Reset

```bash
# Dry run (lihat apa yang akan dihapus, tidak ada perubahan)
php artisan competition:uat-reset

# Eksekusi (hapus semuanya ber-marker UAT saja)
php artisan competition:uat-reset --apply
```

Yang dihapus (hanya scoped ke event UAT Competition 2026 + master data UAT):

- Semua tabel competition milik event UAT (heat results, schedule entries,
  match officials, bracket matches, outcomes, schedules, brackets, team members,
  teams, heat formats, registrations, classes, categories).
- Participations + event UAT.
- Person bernama `UAT Peserta ...` (hanya yang tidak punya participation di
  event lain).
- Desa `UAT Desa ...` dan kelompok `UAT Futsal Team ...` / `UAT Kelompok ...`.

Tidak pernah dihapus: event lain, user, desa/kelompok non-UAT, data legacy.

---

## 6. Hal yang Tidak Diubah / Tidak Disinggung

- Tidak ada perubahan schema database (seeder hanya memakai tabel/migrasi existing).
- Tidak ada perubahan business logic / service / controller / view.
- Tidak ada hasil otomatis yang dibuat selain scenario protection.
- Format, engine bracket, ranking, outcome, status, dan lifecycle schedule
  tetap memakai logika existing.