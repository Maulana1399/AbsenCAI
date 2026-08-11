# Report — Normalisasi Kapitalisasi Nama Person (Existing Data)

**Tanggal:** 2026-08-08

> **CATATAN PENTING:** Database produksi `absencai` (MariaDB @ 127.0.0.1:3306)
> **tidak dapat diakses dari environment kerja ini** (connection refused — tidak ada
> server MariaDB yang berjalan). Verifikasi kode + mekanisme dijalankan pada
> **salinan backup produksi** (`database-before-cleanup-20260722-210934.sqlite` =
> 155 Person) dan test suite penuh. Dry-run produksi (511 Person / 164 change /
> 0 collision) dilakukan oleh user di produksi. **Belum ada update database — dry-run
> saja pada task ini. `--apply` BELUM dijalankan.**

---

## 1. Method normalizer yang digunakan

`App\Services\Import\Support\PersonIdentityNormalizer::normalizeNama()`
(`app/Services/Import/Support/PersonIdentityNormalizer.php:14`) — single source of
truth yang sama dipakai import **Person**, **Participation**, dan (untuk logic tanggal)
**Pengajian**. Command `person:normalize-names` memakai normalizer yang sama.
Tidak ada normalizer nama kedua.

## 2. Logic normalizeNama — sebelum

- `trim` + collapse `/\s+/u`
- lalu per kata: huruf pertama uppercase, sisanya lowercase (title case polos).

Hasil bermasalah: `Ahmad Agung FF` → `Ahmad Agung Ff`, `Al-Banie` → `Al-banie`,
`Naf'An` → `Naf'an`, `Nuraini.R` → `Nuraini.r`.

## 3. Logic normalizeNama — sesudah

- `trim` + collapse `/\s+/u` (dipertahankan).
- `isCapsDominantName()`: deteksi nama yang diketik dominan kapital (token uppercase
  len>1 lebih banyak daripada token lainnya). Jika dominan → seluruh token di-title-case
  (tanpa preservasi acronym).
- `normalizeWord()`: pecah setiap kata per separator `-`, `'` (ASCII), `.` —
  `preg_split` + `PREG_SPLIT_DELIM_CAPTURE` — lalu normalisasi tiap segmen dan gabung
  kembali (struktur separator dipertahankan).
- `normalizeSegment()`: base rule (huruf pertama upper, sisanya lower) + preservasi
  acronym (lihat rule di bawah).

## 4. Rule acronym

- Token seluruhnya uppercase dengan panjang **> 1** (FF, AF, QA, AB, …) **dipertahankan
  apa adanya** — selama nama tersebut **tidak dominan kapital**.
- Panjang 1 tidak pernah dianggap acronym (`F` → `F`, `a` → `A`).
- Contoh: `Ahmad Agung FF` → `Ahmad Agung FF`; `Muhammad Aldafi Zanuar AF` → `Muhammad
  Aldafi Zanuar AF`; `Budi AF` → `Budi AF`.
- `BINTI CHUSNA` → `Binti Chusna` (nama dominan kapital → tidak ada tebakan acronym).
- Batas tebakan: kata kapital minoritas dipertahankan, kata kapital mayoritas di-title-case
  (mis. `WAHYUNI PUTRI NUR hidayah` → `Wahyuni Putri Nur Hidayah`).

## 5. Rule hyphen

- Pecah berdasarkan `-`, normalisasi tiap segmen, gabung kembali dengan `-`.
- `Al-Banie` → `Al-Banie`; `al-banie` → `Al-Banie`; `as-sidiq` → `As-Sidiq`.
- Tidak pernah menghasilkan `Al-banie`, `Al-tofi`, `Al-fikriy`.

## 6. Rule apostrophe

- Pecah berdasarkan apostrophe ASCII `'`, normalisasi tiap segmen, gabung kembali.
- `Naf'An` → `Naf'An`; `Rif'At` → `Rif'At`; `Mut'Mainnah` → `Mut'Mainnah`;
  `rif'at` → `Rif'At`.
- Apostrophe typographic (mis. U+2019) tidak diubah karakter-nya; token diperlakukan
  sebagai satu kata (hanya ASCII `'` yang menjadi pemisah).

## 7. Rule dot / initial

- Pecah berdasarkan `.`, normalisasi tiap segmen, pertahankan struktur dot (termasuk dot
  di akhir, mis. `A.`).
- `Nuraini.R` → `Nuraini.R`; `Nuraini.r` → `Nuraini.R`; `Aditya Javan A.` → `Aditya
  Javan A.`.

## 8. Whitespace & nama sudah benar

- `trim` + collapse: `"  ROYAN   CHIYARUL   ICHSAN  "` → `Royan Chiyarul Ichsan`.
- Nama yang sudah canonical tetap sama: `Royan Chiyarul Ichsan` → `Royan Chiyarul
  Ichsan`. Tidak ada perubahan yang tidak perlu.

## 9. Test baru (task ini)

`tests/Unit/Services/Import/PersonIdentityNormalizerTest.php`:
- acronym dipertahankan (`Ahmad Agung FF`, `Muhammad Aldafi Zanuar AF`, spasi ganda);
- huruf tunggal bukan acronym (`Ahmad Agung F`);
- nama dominan kapital tetap collapse (`BINTI CHUSNA`, `MUHAMMAD ANDRA RAFA REQUELMI`);
- hyphen per segment (`Al-Banie`, `Al-Fikriy`, `Al-Tofi`, `as-sidiq` → `As-Sidiq`);
- apostrophe per segment (`Naf'An`, `Mut'Mainnah`, `Rif'At`, `rif'at` → `Rif'At`);
- dot (`Nuraini.R`, `Nuraini.r` → `Nuraini.R`, `Aditya Javan A.`);
- ditambah semua test lama (ALL CAPS, lowercase, mixed, whitespace, empty, duplicate
  case-insensitive, dll).

`tests/Unit/Services/Import/PersonImportTest.php`: title-case via shared normalizer di
jalur import.

`tests/Feature/Commands/PersonNormalizeNamesTest.php`: command memakai normalizer yang
sama — nama special-case (`FF`, `Al-Banie`, `Naf'An`, `Nuraini.R`, `Rif'At`) dilaporkan
`names needing change: 0` dan tidak diubah.

## 10. Hasil dry-run production

Dilakukan oleh user di produksi (sebelum perbaikan): **total Person 511, names needing
change 164, collision groups 0**.

**Task ini tidak menjalankan ulang dry-run produksi** (DB produksi tidak reachable dari
sini). Verifikasi logic baru dilakukan di:
- unit test (29 lulus),
- skrip verifikasi 19 kasus (semua PASS, termasuk 9 nama bermasalah dari laporan:
  `Ahmad Agung FF`, `Muhammad Aldafi Zanuar AF`, `Azifah Mayyasah Al-Banie`, `Daffa
  Al-Fikriy Rachmad`, `Fahira Al-Tofi Zakia`, `Zivana Zayyana Naf'An`, `Nurul Azizah
  Mut'Mainnah`, `Ummu Fathinmah Rif'At Khanifah`, `Febria Putri Nuraini.R`),
- dry-run pada salinan backup 155 Person (semua hasil wajar; 66 berubah, 1 collision).

Langkah untuk re-run di produksi setelah review:
```bash
php artisan person:normalize-names            # audit (dry-run) — JANGAN --apply dulu
```

## 11. Jumlah record yang berubah

- Backup (155 Person): **66** akan berubah (1 collision di-skip saat apply).
- Produksi: hasil dry-run user sebelumnya 164; **perlu re-run** setelah perbaikan —
  jumlah diharapkan turun karena nama special-case (acronym/hyphen/apostrophe/dot)
  sudah tidak lagi dihitung sebagai perubahan. Angka pasti produksi menunggu re-run.

## 12. Collision count

- Backup: **1 group** (#149 `lana` & #150 `Lana`, desa_id=2, tanggal_lahir NULL) —
  dilaporkan, tidak di-update, tidak merge/delete.
- Produksi (user): **0 collision**.
- Identity collision tetap: `nama + desa_id + tanggal_lahir`.

## 13. Database

**Tidak ada perubahan database pada task ini.** `--apply` BELUM dijalankan. Perintah
`person:normalize-names` tidak dijalankan di produksi; hanya dry-run/audit yang boleh
diulang setelah logic diverifikasi.

## 14. Test regression

Jalur yang diuji (semua PASS):
- `PersonIdentityNormalizerTest` (29)
- `PersonImportTest`, `PersonImportFrameworkTest`, `ParticipationImportTest`,
  `PengajianImportDefinitionTest`, `ImportAdapterTest`, `ImportPipelineTest`
- `PersonNormalizeNamesTest` (14)
- Full suite: **2218 passed, 5724 assertions** (durasi ±75s; perlu
  `-d memory_limit=1G` untuk build PHP statis).
- Pint: **PASS — 594 files**.

Tidak ada perubahan/regression pada tanggal lahir, FileParser, import framework,
duplicate detection, Person identity, participant_number, attendance_code, NIP.

---

## File yang diubah

- `app/Services/Import/Support/PersonIdentityNormalizer.php` — logic nama diperbaiki.
- `tests/Unit/Services/Import/PersonIdentityNormalizerTest.php` — test rule baru.
- `tests/Feature/Commands/PersonNormalizeNamesTest.php` — test command special-case.
- (Sebelumnya: `app/Console/Commands/PersonNormalizeNames.php`, `PersonImportTest.php`)

## Status

Logic selesai dan diverifikasi. **Belum ada update produksi.** Setelah laporan ini
direview dan dry-run produksi ulang terlihat benar, barulah `--apply` dijalankan.
