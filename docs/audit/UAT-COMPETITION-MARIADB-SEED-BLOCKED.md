# UAT COMPETITION — MARIADB PRODUCTION SEED (BLOCKED)

> Tujuan: menjalankan seeder UAT Competition ke **MariaDB production** yang aktif.
> **Status: BLOCKED — koneksi MariaDB tidak dapat dibangun dari sandbox/container ini.**
> Tidak ada data yang di-insert ke MariaDB. Tidak ada file aplikasi yang diubah.

- **Tanggal:** 2026-08-14
- **Blocker:** host-based grant MariaDB menolak IP container (`172.20.0.4`), untuk semua user termasuk `root`. Koneksi ke `127.0.0.1:3306` (host `.env`) juga ditolak (tidak ada server di dalam container).

---

## 1. Hasil Verifikasi Prasyarat (LANGKAH 1)

| # | Prasyarat | Hasil |
|---|---|---|
| 1 | `DB_CONNECTION=mysql` | ⚠️ `.env` memakai `DB_CONNECTION=mariadb` (driver `mariadb`, protokol sama dengan mysql/pdo_mysql). Tidak diubah (tanpa koneksi, mengubah config tidak menyelesaikan apa pun). |
| 2 | Koneksi MariaDB berhasil | ❌ **GAGAL** — lihat bukti di bawah. |
| 3 | Migration Competition sudah dijalankan | ❓ Tidak dapat diverifikasi (tanpa koneksi). Dari codebase, ada **5 migration pending** (08-18 s/d 08-21) yang harus dijalankan sebelum seed. Sudah dianalisis **PASS additive** (`docs/audit/COMPETITION-MIGRATION-PRODUCTION-AUDIT.md`). |
| 4 | Seeder tersedia | ✅ `database/seeders/UatCompetitionSeeder.php` ada, syntax OK, idempotent. |
| 5 | Event `uat-competition-dummy` belum ada | ❓ Tidak dapat diperiksa (tanpa koneksi). Seeder menggunakan `updateOrCreate(name)` — aman (tidak akan membuat duplicate) dan hanya menyentuh data milik dummy. |

### Bukti koneksi

```text
php artisan tinker --execute="dump(config('database.default')); dump(DB::connection()->getDatabaseName());"
  => "mariadb"
  => "absencai"

DB::select('select 1 as ok')
  => SQLSTATE[HY000] [2002] Connection refused   (Connection: mariadb, host 127.0.0.1:3306)

# Port scan:
127.0.0.1:3306  -> closed
172.20.0.1:3306 -> OPEN   (gateway Docker host — MariaDB berjalan di host)

# PDO ke gateway:
172.20.0.1:3306 -> SQLSTATE[HY000] [1130] Host '172.20.0.4' is not allowed to connect to this MariaDB server

# Uji user absencai / sail / root terhadap gateway:
semua -> [1130] Host '172.20.0.4' is not allowed to connect to this MariaDB server
```

**Kesimpulan teknis:** MariaDB production berjalan di host Docker (`172.20.0.1:3306`), tetapi **grant MariaDB tidak mengizinkan koneksi dari IP container `172.20.0.4`** (error 1130 = host-based user grant). Baik `absencai`, `sail`, maupun `root` ditolak. Di sisi lain, `127.0.0.1:3306` (nilai `.env`) tidak memiliki server di dalam container. Ini **boundary jaringan/grant**, bukan masalah kode.

---

## 2. Yang SUDAH dipersiapkan (tanpa menyentuh MariaDB)

- Seeder `database/seeders/UatCompetitionSeeder.php` — idempotent, hanya event "UAT Competition Dummy", reuse desa/kelompok existing, buat Person dummy `UAT Competition 01–24`, registrasi 5 class (satu per format), auto team formation, **tidak menyentuh Regu / event lain / Person-Participation non-dummy**.
- Migration additive Competition (`2026_08_21_000001` + 4 migration pendahulu 08-18..08-20) — **PASS** per `docs/audit/COMPETITION-MIGRATION-PRODUCTION-AUDIT.md`.
- Data dummy telah diverifikasi bekerja di `database/database.sqlite` (dev): event #1, 2 kategori, 5 class, 24 person, 56 registrasi, 5 team.

---

## 3. Runbook untuk environment yang memiliki akses MariaDB (dijalankan USER)

Jalankan di tempat aplikasi dapat terhubung ke MariaDB (host dengan `.env` yang benar, atau container yang grant-nya diizinkan):

```bash
cd /var/www/AbsenCAI

# 1) Verifikasi config + koneksi
php artisan tinker --execute="dump(config('database.default')); dump(DB::connection()->getDatabaseName());"
php artisan tinker --execute="try { dump(DB::select('select 1 as ok')); } catch (\Throwable \$e) { dump(\$e->getMessage()); }"

# 2) Jalankan migration additive (WAJIB sebelum seed) — aman, tanpa fresh/wipe/truncate
php artisan migrate --force

# 3) Pastikan event dummy belum ada (opsional, verifikasi manual)
mysql -uabsencai -p'PasswordBaru123!' absencai \
  -e "select id,name,event_type,status from events where name='UAT Competition Dummy' or slug='uat-competition-dummy';"

# 4) Seed (idempotent; aman dijalankan ulang)
php artisan db:seed --class=Database\\Seeders\\UatCompetitionSeeder --force
```

Jika menjalankan dari dalam container dan MariaDB di host hanya bisa diakses via gateway (setelah grant diizinkan):

```bash
DB_HOST=172.20.0.1 php artisan migrate --force
DB_HOST=172.20.0.1 php artisan db:seed --class=Database\\Seeders\\UatCompetitionSeeder --force
```

> **Prasyarat grant:** user DB (mis. `absencai`) harus diizinkan dari IP/asal koneksi aplikasi:
> `GRANT ALL ON absencai.* TO 'absencai'@'<app-host>' IDENTIFIED BY '...'; FLUSH PRIVILEGES;`

---

## 4. Ringkasan

- **Seeding ke MariaDB production: TIDAK dijalankan** — blocker lingkungan (grant host 1130; `127.0.0.1` di container tanpa server).
- **Tidak ada** perubahan pada: MariaDB, `.env`, event/Person/Participation/Regu existing, kode aplikasi.
- **Tidak ada** `migrate:fresh` / `migrate:refresh` / `db:wipe` / truncate / delete.
- Seeder siap dijalankan kapan pun koneksi tersedia (runbook §3). Idempoten: tidak akan membuat duplikat.
