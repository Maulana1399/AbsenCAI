# Report — Pembersihan Data Dummy Person (Udin & Bira)

**Tanggal:** 2026-08-09

> **PENTING:** Database produksi `absencai` (MariaDB @127.0.0.1:3306) **tidak dapat
> diakses dari environment kerja ini** (connection refused). Identifikasi + uji
> end-to-end dilakukan pada **salinan backup produksi**
> (`database-before-cleanup-20260722-210934.sqlite`, 155 Person) yang memuat data
> Udin. **Tidak ada DELETE terhadap database produksi pada task ini.** Command
> aman sudah siap dijalankan di produksi oleh user.
>
> **Temuan penting:** `Bira` **TIDAK ditemukan** di seluruh data yang dapat diakses
> (semua backup sqlite + scan seluruh tabel). Hanya `Udin` yang ada.

---

## 1. Identifikasi Person

| Nama | ID Person | jenis_kelamin | desa_id | kelompok_id | Hasil |
|---|---|---|---|---|---|
| Udin | 151 | L | 2 | 2 | Ditemukan |
| Bira | — | — | — | — | Tidak ditemukan |

> Hanya ada satu Person bernama "Udin" (case-insensitive, trimmed). Tidak ada kandidat
> ganda, sehingga tidak perlu konfirmasi pilihan record. Jika di produksi ternyata ada
> >1 "Udin", command akan **menolak eksekusi dan menampilkan kandidat** (wajib pakai
> `--id`).

## 2. Dependensi Udin (person 151)

| Tabel | Kolom | Row terkait | Aksi |
|---|---|---|---|
| `participations` | person_id | #156 (event 1, KL096, KJA-QJLT9OD4) | HAPUS |
| `event_attendances` | participation_id | #1241 (sesi 18, status izin) | HAPUS |
| `activity_registrations` | participation_id | 0 | — |
| `competition_registrations` | participation_id | 0 (tabel belum ada di snapshot) | HAPUS bila ada |
| `competition_schedule_entries` | competition_registration_id | 0 | HAPUS bila ada |
| `competition_outcomes` | competition_registration_id | 0 | HAPUS bila ada |
| `legacy_peserta_mappings` | person_id / participation_id | #147 | HAPUS |
| `legacy_participation_mappings` | person_id / participation_id | #147 | HAPUS |
| `cai_participant_replacements` | old/new person & participation | 0 | HAPUS bila ada |
| `identity_correction_requests` | person_id | 0 | HAPUS bila ada |
| `event_committee_assignments` | person_id | 0 | HAPUS bila ada |
| `surat_izins` | participation_id | 0 | SET NULL |
| `users` | person_id | 0 | SET NULL (user tidak dihapus) |
| `pesertas` (legacy) | via mapping | #184 (nama Udin, nip 1116) | **DILAPORKAN, TIDAK DIHAPUS** |

Legacy `pesertas` #184 & `absensis`/`surat_izins`/`izin_absensis` untuk Udin tidak
memiliki dependen tambahan (0 row). Row legacy peserta **sengaja tidak dihapus**
(out of scope Person; menghindari risiko orphan di tabel legacy).

## 3. Urutan DELETE (anti-orphan, dalam satu transaction)

1. `event_attendances`
2. `competition_schedule_entries` → `competition_outcomes` → `competition_registrations`
3. `activity_registrations`
4. `legacy_peserta_mappings`, `legacy_participation_mappings`,
   `cai_participant_replacements`
5. `identity_correction_requests`, `event_committee_assignments`
6. `surat_izins.participation_id = NULL`, `users.person_id = NULL`
7. `participations` → `people`

## 4. Command yang dibuat

`app/Console/Commands/PersonPurgeDummy.php` → `person:purge-dummy`

```bash
php artisan person:purge-dummy Udin Bira                 # dry run (audit, tanpa tulis)
php artisan person:purge-dummy Udin --apply              # eksekusi + backup + verifikasi
php artisan person:purge-dummy --id=151 --apply          # target eksplisit per ID
php artisan person:purge-dummy Udin --apply --backup-path=/path/backup.json
```

Pengaman:
- Dry-run default; DELETE hanya dengan `--apply`.
- Nama ganda → abort + daftar kandidat (wajib `--id`).
- Backup JSON semua row terdampak **sebelum** delete (default
  `storage/app/backups/person-purge-<timestamp>.json`).
- Transaction; hapus hanya dependensi eksklusif target. Event/User/Desa/Kelompok/
  Regu/master data tidak dihapus; FK nullable di-NULL-kan.
- Verifikasi pasca-purge (Person/Participation/Attendance = 0, orphan scan).

## 5. Hasil uji pada salinan backup (155 → 154 Person)

- Person dihapus: **#151 Udin**
- Participation dihapus: **#156**
- Attendance dihapus: **#1241** (1 row)
- Relasi lain dibersihkan: `legacy_peserta_mappings` #147, `legacy_participation_mappings` #147
- Master data dipertahankan: Event, desa, kelompok, regu, `pesertas` #184
- Orphan scan: **0** | VERIFICATION OK
- Backup dibuat: JSON berisi person_ids [151], participation_ids [156], rows per tabel

## 6. Test

- `tests/Feature/Commands/PersonPurgeDummyTest.php` — **11 passed, 58 assertions**
  (dry-run tanpa tulis, ambiguitas nama, `--id`, hard-delete dependency, tidak
  menyentuh person lain, users di-NULL-kan bukan dihapus, master data tetap,
  backup dibuat, idempotent).
- Full suite: **2229 passed, 5782 assertions**.
- Pint: **PASS — 596 files**.

## 7. Status produksi

Belum dieksekusi di produksi (DB unreachable dari sini). Langkah di server produksi:

```bash
# 1. backup penuh (opsional tapi disarankan) + audit
php artisan person:purge-dummy Udin Bira

# 2. setelah audit dipastikan benar (Bira mungkin 'No Person found'),
#    jalankan eksekusi:
php artisan person:purge-dummy Udin --apply
```

> Jika produksi ternyata memiliki record `Bira`, jalankan
> `php artisan person:purge-dummy Udin Bira --apply`.
> Jika ada >1 kandidat per nama, command menolak & meminta `--id`.
