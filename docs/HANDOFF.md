# HANDOFF DOCUMENT — AbsenCAI
## Sistem Absensi CAI berbasis Laravel Livewire

**Dokumen ini dibuat untuk developer baru atau panitia teknis yang melanjutkan project.**
Tidak perlu menguasai coding untuk membaca dokumen ini. Setiap bagian teknis akan dijelaskan dengan bahasa sehari-hari.

---

## Daftar Isi

1. [FILE MAP — Peta File Project](#1-file-map--peta-file-project)
2. [DATA FLOW — Alur Data](#2-data-flow--alur-data)
3. [AUTH FLOW — Alur Login & Hak Akses](#3-auth-flow--alur-login--hak-akses)
4. [RISK MAP — Peta Risiko](#4-risk-map--peta-risiko)
5. [CHANGE GUIDE — Panduan Mengubah Aplikasi](#5-change-guide--panduan-mengubah-aplikasi)
6. [TROUBLESHOOTING — Masalah & Solusi](#6-troubleshooting--masalah--solusi)
7. [DEPLOYMENT NOTES — Cara Menjalankan Aplikasi](#7-deployment-notes--cara-menjalankan-aplikasi)
8. [GIT SAFETY — Panduan Aman Menggunakan Git](#8-git-safety--panduan-aman-menggunakan-git)

---

## 1. FILE MAP — Peta File Project

> **Apa itu Laravel?**
> Laravel adalah kerangka kerja (framework) PHP. Bayangkan seperti "template besar" yang sudah menyediakan fondasi aplikasi — kita tinggal mengisi bagian yang spesifik untuk kebutuhan kita.

> **Apa itu Livewire?**
> Livewire adalah plugin Laravel yang membuat halaman web bisa berubah secara langsung tanpa reload. Mirip seperti Google Sheets yang langsung update tanpa perlu refresh halaman.

---

### Struktur Folder Utama

```
AbsenCAI/
├── app/                  ← LOGIKA aplikasi (otak sistem)
├── resources/views/      ← TAMPILAN halaman (yang dilihat user)
├── database/             ← STRUKTUR tabel database
├── routes/               ← PETA URL / alamat halaman
├── .env                  ← KONFIGURASI rahasia (password, URL, dll)
├── public/               ← File yang bisa diakses publik (gambar, CSS)
└── storage/              ← File upload, log error, cache
```

---

### `app/` — Otak Aplikasi

Folder ini berisi semua logika bisnis. Dibagi menjadi beberapa sub-folder:

---

#### `app/Models/` — Bentuk Data Aplikasi

> **Apa itu Model?**
> Model adalah "cetak biru" data. Seperti formulir kosong yang menentukan kolom apa saja yang ada. Ketika data disimpan ke database, model yang mengaturnya.

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `peserta.php` | Data peserta CAI. Berisi: auto-generate NIP, pembagian regu otomatis, relasi ke desa/kelompok/regu, status registrasi (Belum Registrasi / Self Register / Registrasi Ulang), jenis peserta (Wajib / Kiriman / Person) | Jika ada kolom baru peserta, aturan NIP berubah, atau cara pembagian regu berubah |
| `regu.php` | Data regu. Menyimpan nama regu dan jenis kelamin regu (Laki-Laki / Perempuan) | Jika ada kolom baru pada tabel regu |
| `desa.php` | Data desa asal peserta | Jika ada kolom baru pada tabel desa |
| `kelompok.php` | Data kelompok asal peserta. Setiap kelompok terhubung ke satu desa | Jika ada kolom baru pada tabel kelompok |
| `Absensi.php` | Data absensi. Menyimpan: NIP, nama, jam scan, dan sesi absensi | Jika ada kolom baru pada tabel absensi |
| `SesiAbsensi.php` | Data sesi absensi (mis: "Pembukaan Hari 1"). Setiap sesi punya tanggal dan status aktif/tidak | Jika ada kolom baru pada sesi, atau aturan sesi aktif berubah |
| `User.php` | Data akun admin yang bisa login ke sistem | Jika ada penambahan data profil admin |

**Detail penting di `peserta.php`:**

- **NIP Laki-laki** dimulai dari `1001` ke atas (range 1000–1999)
- **NIP Perempuan** dimulai dari `2001` ke atas (range 2000–2999)
- **Auto Regu**: sistem mencari regu yang paling sedikit anggotanya (sesuai jenis kelamin), lalu memasukkan peserta ke sana secara otomatis

---

#### `app/Livewire/` — Komponen Interaktif Halaman

> **Apa itu Livewire Component?**
> Setiap fitur interaktif (form, tabel, tombol yang langsung bereaksi) punya file PHP di sini. File PHP ini menangani logika, dan pasangannya di `resources/views/livewire/` menangani tampilan.

**Grup: Dashboard**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `Dashboard/Dashboard.php` | Menampilkan statistik: total peserta, desa, kelompok, regu. Juga menampilkan siapa yang sudah/belum absen pada sesi aktif | Jika ingin menambah statistik atau mengubah tampilan dashboard |
| `Dashboard/Scan.php` | Menangani scan QR Code. Membaca NIP dari QR, cek ke database, catat absensi | Jika aturan scan berubah (mis: boleh absen 2x, atau ada validasi tambahan) |

**Grup: Registrasi**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `Registrasi/SelfRegister.php` | Form pendaftaran mandiri oleh peserta. Mengisi nama, desa, kelompok, jenis kelamin — NIP dan regu terisi otomatis | Jika form self-register berubah |
| `Registrasi/Ulang.php` | Panitia mencari peserta by nama/NIP, lalu klik "Registrasi Ulang" untuk menandai peserta sudah datang | Jika alur registrasi ulang berubah |

**Grup: Database (manajemen data master)**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `Database/Peserta/Database.php` | Tabel daftar semua peserta dengan filter dan search | Jika ada filter baru |
| `Database/Peserta/TambahPeserta.php` | Form tambah peserta manual satu per satu | Jika ada kolom baru |
| `Database/Peserta/EditPeserta.php` | Form edit data peserta | Jika ada kolom baru |
| `Database/Peserta/ImportPeserta.php` | Upload file Excel peserta | Jika format Excel berubah |
| `Database/Peserta/HapusPeserta.php` | Konfirmasi & hapus peserta | Jarang perlu diedit |
| `Database/Desa/` | CRUD data desa (tambah, edit, hapus, import) | Jika ada perubahan data desa |
| `Database/Kelompok/` | CRUD data kelompok | Jika ada perubahan data kelompok |
| `Database/Regu/` | CRUD data regu | Jika ada perubahan data regu |
| `Database/Sesi/` | CRUD sesi absensi (nama sesi, tanggal, aktif/tidak) | Setiap event baru perlu tambah sesi |

**Grup: Rekap**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `Rekap/Peserta/RekapPeserta.php` | Laporan peserta dengan filter (regu, kelompok, desa, jenis kelamin, jenis peserta). Bisa export ke Excel | Jika ada kolom baru di laporan |
| `Rekap/Absensi/RekapAbsensi.php` | Laporan absensi per sesi — siapa yang sudah/belum hadir, persentase kehadiran | Jika ada kolom baru di laporan absensi |

**Grup: Auth (Login)**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `Auth/Login.php` | Form login dengan email & password. Ada rate limiter (max 5x salah) | Jarang perlu diedit |
| `Auth/Register.php` | Form daftar akun admin baru | Jarang perlu diedit |
| `Auth/ForgotPassword.php` | Lupa password | Jarang perlu diedit |

---

#### `app/Imports/` — Proses Import Excel

> File-file ini "membaca" file Excel yang diupload dan menyimpannya ke database.

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `PesertaImport.php` | Membaca Excel peserta. Kolom yang dibaca: `nama`, `jenis_kelamin`, `desa`, `kelompok`, `jenis_peserta`. Auto-generate NIP dan regu. Jika peserta sudah ada (nama+desa+kelompok sama), skip/tidak duplikat | Jika format kolom Excel berubah |
| `ReguImport.php` | Membaca Excel regu. Kolom: `regu`, `jenis_kelamin`. Ada normalisasi otomatis "laki laki" → "Laki - Laki" | Jika format Excel regu berubah |
| `KelompokImport.php` | Membaca Excel kelompok. Kolom: `kelompok`, `desa` | Jika format Excel kelompok berubah |
| `DesaImport.php` | Membaca Excel desa. Kolom: `desa` | Jika format Excel desa berubah |

---

#### `app/Exports/` — Export ke Excel

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `PesertaExport.php` | Menghasilkan file Excel rekap peserta. Kolom: No, Nama, NIP, Jenis Kelamin, Jenis Peserta, Desa, Kelompok, Regu, Status Registrasi | Jika kolom laporan berubah |

---

#### `app/Http/Controllers/` — Penghubung URL ke Aksi

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `ImportDataController.php` | Menerima file upload dari form HTML, memvalidasi tipe file (xlsx/xls/csv), lalu memanggil kelas Import yang sesuai | Jika ada import baru yang ditambahkan |

---

### `resources/views/` — Tampilan Halaman

> **Apa itu Blade?**
> Blade adalah bahasa template Laravel. File `.blade.php` adalah HTML biasa yang ditambahkan kemampuan menampilkan data dari PHP, seperti `{{ $nama }}`.

**Halaman utama:**

| File | URL | Fungsi |
|------|-----|--------|
| `welcome.blade.php` | `/` | Halaman depan / landing page |
| `dashboard.blade.php` | `/dashboard` | Layout halaman dashboard utama |
| `dashboard/absensi.blade.php` | `/absensi` | Halaman scan QR absensi (fullscreen, tanpa sidebar) |

**Folder `registrasi/`:**

| File | URL | Fungsi |
|------|-----|--------|
| `peserta.blade.php` | `/registrasi` | Halaman registrasi peserta (dioperasikan panitia) |
| `ulang.blade.php` | `/registrasi/ulang` | Halaman registrasi ulang (panitia cari peserta, klik hadir) |
| `self-register-success.blade.php` | `/register/success` | Halaman sukses setelah self-register |

**Folder `database/`:**

| File | URL | Fungsi |
|------|-----|--------|
| `database.blade.php` | `/database` | Halaman tabel peserta |
| `desa.blade.php` | `/desa` | Halaman kelola desa |
| `kelompok.blade.php` | `/kelompok` | Halaman kelola kelompok |
| `regu.blade.php` | `/regu` | Halaman kelola regu |
| `sesi.blade.php` | `/sesi-absensi` | Halaman kelola sesi absensi |

**Folder `rekap/`:**

| File | URL | Fungsi |
|------|-----|--------|
| `peserta.blade.php` | `/rekap-peserta` | Halaman laporan peserta |
| `absensi.blade.php` | `/rekap-absensi` | Halaman laporan absensi |

**Folder `components/layouts/`:**

| File | Fungsi | Kapan Diedit |
|------|--------|--------------|
| `app/sidebar.blade.php` | Layout utama dengan sidebar navigasi kiri. **Di sinilah menu aplikasi diatur** | Jika ingin tambah/ubah menu navigasi |
| `app.blade.php` | Layout wrapper aplikasi | Jarang perlu diedit |
| `auth.blade.php` | Layout halaman login/register | Jika ingin ubah tampilan halaman login |

---

### `database/` — Struktur Database

> **Apa itu Migration?**
> Migration adalah "resep" untuk membuat tabel database. Seperti blueprint bangunan. Dijalankan sekali untuk membuat tabel. Jika ingin ubah struktur tabel, buat migration baru — jangan edit yang lama.

**Tabel yang ada:**

| Tabel | File Migration | Isi |
|-------|---------------|-----|
| `pesertas` | `2025_06_16_071812_create_peserta_table.php` | Data peserta: nama, nip, jenis_kelamin, kelompok_id, desa_id, regu_id |
| `desas` | `2025_06_16_071207_create_desa_table.php` | Data desa: desa_asal |
| `kelompoks` | `2025_06_16_071801_create_kelompok_table.php` | Data kelompok: kelompok_asal, desa_id |
| `regus` | `2025_06_16_071211_create_regu_table.php` | Data regu: regu, jenis_kelamin |
| `absensis` | `2025_06_23_031629_create_absensis_table.php` | Data absensi: nip, nama, jam_scan, sesi_id |
| `sesi_absensis` | `2026_07_06_000001_create_sesi_absensis_table.php` | Data sesi: nama_sesi, tanggal, aktif |
| `users` | `0001_01_01_000000_create_users_table.php` | Akun admin: name, email, password |

**Migration tambahan (alterasi tabel):**

| File | Fungsi |
|------|--------|
| `2026_07_01_000001_add_status_registrasi_to_pesertas_table.php` | Menambah kolom `status_registrasi` ke tabel peserta |
| `2026_07_01_000002_make_jenis_kelamin_nullable_on_pesertas_table.php` | Membuat `jenis_kelamin` bisa kosong |
| `2026_07_01_000003_add_jenis_kelamin_to_regus_table.php` | Menambah kolom `jenis_kelamin` ke tabel regu |
| `2026_07_02_000001_add_unique_constraints_to_tables.php` | Menambah aturan unik (tidak boleh duplikat) |
| `2026_07_03_000001_add_unique_participant_identity_to_pesertas_table.php` | Menambah constraint unik gabungan nama+desa+kelompok |
| `2026_07_07_125113_add_jenis_peserta_to_pesertas_table.php` | Menambah kolom `jenis_peserta` (Wajib/Kiriman/Person) |

**`database/seeders/DatabaseSeeder.php`:**
Berisi data contoh untuk testing: 1 user admin, 2 desa, 2 kelompok, 4 regu (2 laki-laki, 2 perempuan), 10 peserta contoh.

---

### `routes/` — Peta Alamat URL

> **Apa itu Route?**
> Route menentukan: "kalau user buka URL ini, tampilkan halaman ini." Seperti daftar isi yang menghubungkan alamat ke tindakan.

| File | Fungsi |
|------|--------|
| `routes/web.php` | Semua URL halaman aplikasi. Hampir semua dilindungi `auth` (harus login dulu) |
| `routes/auth.php` | URL khusus login, register, lupa password, verifikasi email |

**Daftar URL Aplikasi:**

| URL | Nama Route | Keterangan |
|-----|-----------|------------|
| `/` | `home` | Halaman depan |
| `/login` | `login` | Halaman login |
| `/register` | `register` | Self-register peserta |
| `/dashboard` | `dashboard` | Dashboard utama |
| `/absensi` | `absensi` | Scan QR absensi |
| `/registrasi` | `registrasi.peserta` | Registrasi peserta oleh panitia |
| `/registrasi/self` | `registrasi.self` | Self-register |
| `/registrasi/ulang` | `registrasi.ulang` | Registrasi ulang |
| `/database` | `database` | Database peserta |
| `/desa` | `desa` | Kelola desa |
| `/kelompok` | `kelompok` | Kelola kelompok |
| `/regu` | `regu` | Kelola regu |
| `/sesi-absensi` | `sesi.absensi` | Kelola sesi absensi |
| `/rekap-peserta` | `rekap.peserta` | Laporan peserta |
| `/rekap-absensi` | `rekap.absensi` | Laporan absensi |
| `/import/peserta` | `import.peserta` | Upload Excel peserta (POST) |

---

### `.env` — File Konfigurasi Rahasia

> File ini berisi pengaturan sensitif. **JANGAN commit file ini ke Git publik.**

Pengaturan penting:

| Key | Nilai Saat Ini | Arti |
|-----|---------------|------|
| `APP_URL` | `https://cai.kja-techno.my.id` | URL website |
| `APP_ENV` | `production` | Mode produksi (bukan testing) |
| `APP_DEBUG` | `false` | Error tidak tampil ke publik |
| `DB_CONNECTION` | `sqlite` | Database menggunakan SQLite (file tunggal) |
| `SESSION_DRIVER` | `database` | Session disimpan di database |

---

## 2. DATA FLOW — Alur Data

### A. Import Peserta dari Excel

```
Panitia menyiapkan file Excel
Kolom wajib: nama | jenis_kelamin | desa | kelompok
Kolom opsional: jenis_peserta
        ↓
Buka halaman /database → tab Import Peserta
Upload file .xlsx / .xls / .csv
        ↓
ImportDataController.php
Validasi: file harus Excel/CSV
        ↓
PesertaImport.php membaca baris per baris:
  - Cari desa di database (cocok nama desa_asal)
  - Cari kelompok di database (cocok nama kelompok_asal)
  - Cek: apakah peserta sudah ada? (nama + desa + kelompok sama)
    → Jika sudah ada: SKIP (tidak duplikat)
    → Jika belum ada: lanjut
        ↓
Model peserta.php dipanggil:
  autoPlacement(jenis_kelamin)
    → nextAutoNip()     : cari NIP tertinggi sesuai gender, tambah 1
    → leastFilledRegu() : cari regu yang paling sedikit anggotanya
        ↓
Data disimpan ke tabel 'pesertas':
  nama, nip (auto), jenis_kelamin, jenis_peserta,
  desa_id, kelompok_id, regu_id (auto),
  status_registrasi = 'Belum Registrasi'
        ↓
Halaman reload, muncul pesan "Data peserta berhasil diimpor"
```

> **Catatan penting:** Urutan kolom Excel tidak masalah, tapi **nama kolom header harus tepat**: `nama`, `jenis_kelamin`, `desa`, `kelompok`, `jenis_peserta`

---

### B. Self-Register Peserta

```
Peserta buka /register atau /registrasi/self
        ↓
SelfRegister.php (Livewire) dimuat
Sistem langsung preview: NIP otomatis & regu otomatis
        ↓
Peserta mengisi:
  - Nama lengkap
  - Pilih Jenis Kelamin → NIP & regu otomatis terupdate
  - Pilih Desa
  - Pilih Kelompok
  - Pilih Jenis Peserta (Wajib / Kiriman / Person)
        ↓
Klik tombol "Daftar"
        ↓
Validasi:
  - Nama tidak boleh kosong
  - NIP harus unik di database
  - Jenis kelamin harus Laki-Laki atau Perempuan
  - Desa dan Kelompok harus ada di database
  - Gabungan nama + desa + kelompok harus unik
        ↓
Jika lolos validasi:
  peserta::create(...) → simpan ke database
  status_registrasi = 'Self Register'
        ↓
Redirect ke /register/success
Tampil halaman sukses berisi: nama, NIP, desa, kelompok
```

---

### C. Registrasi Ulang (Panitia)

```
Panitia buka /registrasi/ulang
        ↓
Ketik nama atau NIP peserta di kolom pencarian
        ↓
Ulang.php (Livewire) mencari peserta secara real-time
Tampil maksimal 10 hasil
        ↓
Panitia menemukan peserta yang datang
Klik tombol "Registrasi Ulang"
        ↓
peserta::update(['status_registrasi' => 'Registrasi Ulang'])
        ↓
Data peserta tersebut ter-update di database
Muncul notifikasi "Registrasi ulang berhasil"
```

> **Opsional:** Panitia juga bisa klik "Edit" untuk mengubah data peserta (nama, desa, kelompok, regu) sebelum atau sesudah registrasi ulang.

---

### D. Scan Absensi QR Code

```
Panitia buka /absensi (halaman full-screen, tanpa sidebar)
        ↓
Pilih Sesi Absensi yang sedang berlangsung
        ↓
Kamera aktif, siap scan QR Code
        ↓
QR Code dipindai → sistem membaca NIP dari QR
        ↓
Scan.php (Livewire) memproses:
  1. Cari peserta berdasarkan NIP
     → Tidak ditemukan? Tampil pesan error
  2. Cek apakah sesi sudah dipilih
     → Belum? Tampil pesan "Pilih sesi dulu"
  3. Cek apakah peserta sudah absen di sesi ini
     → Sudah? Tampil pesan "Sudah absen"
  4. Semua lolos → catat ke tabel 'absensis':
     nip, nama, jam_scan (waktu sekarang), sesi_id
        ↓
Tampil nama peserta + jam scan
Klik "Scan Lagi" → kamera kembali aktif
```

> **Format QR Code:** QR Code harus berisi NIP peserta (angka saja, mis: `1001`)

---

### E. Rekap Peserta

```
Admin buka /rekap-peserta
        ↓
RekapPeserta.php (Livewire) memuat semua peserta
        ↓
Admin bisa filter berdasarkan:
  - Regu
  - Kelompok
  - Desa
  - Jenis Kelamin
  - Jenis Peserta
        ↓
Tampil tabel + ringkasan statistik:
  Total, Total Laki-Laki, Total Perempuan,
  Sudah Registrasi Ulang, Belum Registrasi
        ↓
Klik "Export Excel"
        ↓
PesertaExport.php membuat file .xlsx
  dengan filter yang sama seperti yang ditampilkan
        ↓
File Excel terdownload otomatis
```

---

### F. Rekap Absensi

```
Admin buka /rekap-absensi
        ↓
RekapAbsensi.php (Livewire)
        ↓
Pilih Sesi Absensi (wajib dipilih dulu)
Opsional: filter berdasarkan Regu
        ↓
Tampil:
  - Daftar yang SUDAH absen (urut jam scan)
  - Daftar yang BELUM absen
  - Jumlah sudah hadir, belum hadir
  - Persentase kehadiran
```

---

## 3. AUTH FLOW — Alur Login & Hak Akses

### Alur Login

```
User buka website → redirect ke /login
        ↓
Isi form: Email + Password
        ↓
Login.php (Livewire) memproses:
  - Validasi format email
  - Rate limit: maksimal 5x salah → dikunci sementara
  - Laravel Auth::attempt() cek ke tabel 'users'
        ↓
Jika email/password salah:
  → Muncul pesan error, dicatat sebagai percobaan gagal
Jika benar:
  → Session dibuat
  → Redirect ke /dashboard
```

### Role & Hak Akses

> **Saat ini aplikasi tidak memiliki sistem role (peran) yang terpisah.** Semua user yang login memiliki akses yang sama ke seluruh fitur.

| Siapa | Bisa Apa |
|-------|----------|
| **Admin (user yang login)** | Akses semua halaman: dashboard, scan, registrasi, database, rekap, laporan, kelola sesi, import, export |
| **Peserta** | Hanya bisa self-register di `/register` (tanpa login). Setelah daftar, tidak bisa login ke sistem |
| **Publik (tidak login)** | Hanya bisa akses `/` (halaman depan) dan `/register` |

> **Catatan:** Jika di masa depan perlu ada role Admin vs Operator vs Viewer, perlu tambahkan sistem role secara terpisah.

### Middleware yang Melindungi Halaman

> **Apa itu Middleware?**
> Middleware adalah "penjaga pintu." Sebelum user masuk ke halaman, middleware mengecek apakah user boleh masuk atau tidak.

| Middleware | Fungsi |
|-----------|--------|
| `auth` | Halaman hanya bisa diakses jika sudah login |
| `verified` | Halaman hanya bisa diakses jika email sudah diverifikasi (saat ini verifikasi dinonaktifkan di `User.php`) |
| `guest` | Halaman hanya bisa diakses jika BELUM login (mis: halaman login — jika sudah login, redirect ke dashboard) |

### File Terkait Auth

| File | Fungsi |
|------|--------|
| `routes/auth.php` | Mendaftarkan URL login, register, lupa password |
| `routes/web.php` | Semua route dilindungi dengan `->middleware(['auth', 'verified'])` |
| `app/Livewire/Auth/Login.php` | Logika proses login |
| `app/Livewire/Auth/Register.php` | Logika daftar akun admin |
| `app/Models/User.php` | Struktur data akun admin |
| `app/Livewire/Actions/Logout.php` | Proses logout |

---

## 4. RISK MAP — Peta Risiko

> Bagian-bagian berikut adalah yang paling sensitif. Salah edit bisa merusak data atau alur sistem. **Selalu backup database sebelum mengubah bagian ini.**

---

### Import Peserta

**File:** `app/Imports/PesertaImport.php`
**Risiko:**
- Jika header kolom Excel salah → peserta tidak terbaca sama sekali, tidak ada error jelas
- Jika logika cek duplikat diubah → bisa ada peserta ganda dengan NIP berbeda
- Jika mapping `desa` atau `kelompok` tidak cocok → peserta masuk tanpa desa/kelompok

**Cara aman edit:**
1. Backup database dulu
2. Test dengan file Excel kecil (5–10 baris) di lingkungan lokal
3. Cek jumlah peserta sebelum dan sesudah import

---

### Auto Generate NIP

**File:** `app/Models/peserta.php` — fungsi `nextAutoNip()`
**Risiko:**
- Jika range NIP diubah (1000–1999 untuk laki-laki, 2000–2999 untuk perempuan) → NIP bisa bentrok dengan peserta lama
- Jika ada bug di sini → semua peserta baru mendapat NIP yang sama → constraint database akan error

**Cara aman edit:**
1. Backup database
2. Cek NIP tertinggi yang sudah ada sebelum ubah range
3. Pastikan tidak ada gap atau overlap range antara laki-laki dan perempuan

---

### Auto Penempatan Regu

**File:** `app/Models/peserta.php` — fungsi `leastFilledRegu()`
**Risiko:**
- Bergantung pada kolom `jenis_kelamin` di tabel `regus` — jika regu tidak punya jenis kelamin, peserta bisa masuk regu yang salah
- Jika nama jenis kelamin tidak persis "Laki - Laki" atau "Perempuan" (perhatikan spasi di sekitar strip `-`) → tidak ada regu yang cocok, `regu_id` jadi `null`

**Cara aman edit:**
1. Selalu pastikan data regu sudah punya `jenis_kelamin` sebelum import peserta
2. Format wajib: `Laki - Laki` (dengan spasi sebelum dan sesudah strip)

---

### Registrasi Ulang

**File:** `app/Livewire/Registrasi/Ulang.php`
**Risiko:**
- Fungsi `registrasiUlang()` tidak ada konfirmasi — klik langsung update status
- Fungsi `updatePeserta()` bisa mengubah desa, kelompok, regu peserta — jika salah bisa mengubah data penting

**Cara aman edit:**
1. Tambahkan konfirmasi dialog jika ingin mencegah klik tidak sengaja
2. Catat perubahan (audit log) jika perlu akuntabilitas

---

### Scan QR Absensi

**File:** `app/Livewire/Dashboard/Scan.php`
**Risiko:**
- Saat ini satu sesi hanya bisa scan 1x per peserta (cek duplikat per sesi)
- Jika cek duplikat dihilangkan → peserta bisa tercatat hadir berkali-kali
- Jika `sesi_id` tidak dipilih → absensi tidak tersimpan

**Cara aman edit:**
1. Jangan hapus logika `if ($last)` yang mengecek apakah sudah absen
2. Pastikan sesi aktif sudah diatur sebelum operasi scan dimulai

---

### Database Peserta

**File:** `app/Livewire/Database/Peserta/HapusPeserta.php`
**Risiko:**
- Menghapus peserta tidak bisa di-undo
- Data absensi peserta tersebut tidak ikut terhapus (berpotensi data orphan)

**Cara aman edit:**
1. Backup database sebelum hapus massal
2. Pertimbangkan "soft delete" jika perlu riwayat

---

### Tabel Risiko Ringkas

| Fitur | File Utama | Risiko Tertinggi | Cara Aman |
|-------|-----------|-----------------|-----------|
| Import Excel Peserta | `PesertaImport.php` | NIP ganda, peserta tanpa regu | Test dengan data kecil, backup dulu |
| Auto NIP | `peserta.php::nextAutoNip()` | NIP bentrok antar gender | Jangan ubah range tanpa cek data |
| Auto Regu | `peserta.php::leastFilledRegu()` | Regu null jika jenis kelamin tidak cocok | Pastikan data regu sudah benar sebelum import |
| Registrasi Ulang | `Registrasi/Ulang.php` | Update data tanpa konfirmasi | Tambah dialog konfirmasi jika diperlukan |
| Scan QR | `Dashboard/Scan.php` | Absen ganda jika cek duplikat dihapus | Jangan hapus logika `$last` check |
| Hapus Peserta | `Database/Peserta/HapusPeserta.php` | Tidak bisa di-undo | Backup sebelum hapus |
| Import Regu | `ReguImport.php` | Jenis kelamin tidak terbaca jika format salah | Gunakan format template Excel yang benar |

---

## 5. CHANGE GUIDE — Panduan Mengubah Aplikasi

> **Sebelum mengubah apapun:** baca bagian Git Safety di bawah, lakukan backup database, dan test di lingkungan lokal terlebih dahulu jika memungkinkan.

---

### A. Mengubah Tampilan (Warna, Teks, Layout)

Tampilan menggunakan **Flux UI** (library komponen berbasis Tailwind CSS).

**Langkah:**
1. Temukan file `.blade.php` yang sesuai di `resources/views/`
2. Edit teks atau class CSS yang diinginkan
3. Untuk warna: cari class seperti `bg-blue-500`, `text-red-600`, dll
4. Refresh browser untuk melihat perubahan

**Contoh:** Ingin ubah judul di halaman dashboard
- Buka `resources/views/livewire/dashboard/dashboard.blade.php`
- Cari teks yang ingin diubah
- Edit teks tersebut

> **Perhatian:** Jangan ubah nama variabel PHP di dalam `{{ }}` kecuali yakin variabel itu tersedia.

---

### B. Mengubah Menu Navigasi

Menu sidebar ada di satu file terpusat:

**File:** `resources/views/components/layouts/app/sidebar.blade.php`

**Cara tambah menu baru:**
1. Buka file tersebut
2. Temukan grup menu yang sesuai (mis: `heading="Database"`)
3. Tambahkan baris baru mengikuti pola yang ada:
   ```html
   <flux:navlist.item :href="route('nama.route')" wire:navigate>
       Nama Menu Baru
   </flux:navlist.item>
   ```
4. Pastikan `nama.route` sudah terdaftar di `routes/web.php`

---

### C. Mengubah Format Import Excel

**Contoh: Tambah kolom baru di import peserta**

1. **Tambah kolom di Excel template** (beri tahu panitia format baru)
2. **Edit `app/Imports/PesertaImport.php`:**
   - Tambahkan pembacaan kolom baru: `$row['nama_kolom_baru']`
   - Tambahkan ke array data yang disimpan
3. **Tambah kolom di database jika diperlukan:**
   - Buat migration baru: `php artisan make:migration add_kolom_baru_to_pesertas_table`
   - Isi migration, jalankan: `php artisan migrate`
4. **Update Model `app/Models/peserta.php`:**
   - Tambahkan nama kolom ke array `$fillable`
5. **Test** dengan file Excel kecil

---

### D. Mengubah Laporan / Rekap

**Contoh: Tambah kolom baru di export Excel peserta**

1. **Buka `app/Exports/PesertaExport.php`**
2. Di fungsi `collection()`, tambahkan kolom baru:
   ```php
   'Nama Kolom Baru' => $peserta->kolom_baru,
   ```
3. Di fungsi `headings()`, tambahkan header:
   ```php
   'Nama Kolom Baru',
   ```
4. Test dengan klik "Export Excel" di halaman rekap peserta

---

### E. Tambah Kolom Peserta Baru (Contoh Lengkap)

**Skenario:** Ingin tambah kolom "No HP" untuk peserta.

**Langkah:**
1. **Buat migration:**
   ```bash
   php artisan make:migration add_no_hp_to_pesertas_table
   ```
   Edit file migration yang dibuat di `database/migrations/`:
   ```php
   $table->string('no_hp')->nullable();
   ```
   Jalankan:
   ```bash
   php artisan migrate
   ```

2. **Update Model** `app/Models/peserta.php`:
   Tambahkan `'no_hp'` ke array `$fillable`.

3. **Update Form Livewire:**
   - Buka `app/Livewire/Database/Peserta/TambahPeserta.php`
   - Tambahkan properti: `public string $no_hp = '';`
   - Tambahkan ke array yang disimpan

4. **Update Tampilan Blade:**
   - Buka `resources/views/livewire/database/peserta/tambah-peserta.blade.php`
   - Tambahkan input field untuk No HP

5. **Update Import jika perlu:**
   - Buka `app/Imports/PesertaImport.php`
   - Tambahkan `'no_hp' => $row['no_hp'] ?? null`

6. **Test** seluruh alur: tambah manual, import Excel, dan lihat di tabel.

---

### F. Menambah Sesi Absensi Baru

Ini tidak perlu edit kode — cukup lewat UI:

1. Login ke aplikasi
2. Buka menu **Sesi Absensi** (`/sesi-absensi`)
3. Klik "Tambah Sesi"
4. Isi nama sesi dan tanggal
5. Aktifkan sesi yang sedang berlangsung
6. Mulai scan absensi

---

### G. Menambah Fitur Baru

**Langkah umum:**
1. Buat Livewire component baru:
   ```bash
   php artisan make:livewire NamaFitur
   ```
   Ini akan membuat dua file:
   - `app/Livewire/NamaFitur.php` (logika)
   - `resources/views/livewire/nama-fitur.blade.php` (tampilan)

2. Tambahkan route di `routes/web.php`

3. Tambahkan menu di `resources/views/components/layouts/app/sidebar.blade.php`

4. Implementasikan logika di file PHP dan tampilan di file blade

---

## 6. TROUBLESHOOTING — Masalah & Solusi

---

**Masalah: Error 500 (Internal Server Error)**
**Penyebab:** Bisa banyak hal — syntax error PHP, database tidak terhubung, permission folder, cache lama
**Solusi:**
1. Cek file log error:
   ```bash
   cat storage/logs/laravel.log
   ```
   Lihat baris terakhir untuk pesan error spesifik
2. Bersihkan cache:
   ```bash
   php artisan optimize:clear
   ```
3. Pastikan `APP_DEBUG=true` sementara di `.env` untuk melihat detail error (kembalikan ke `false` setelah selesai)
4. Cek permission folder:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

---

**Masalah: Import Excel Gagal / Tidak Ada Data Masuk**
**Penyebab:** Header kolom Excel tidak sesuai, atau nama desa/kelompok tidak cocok dengan yang ada di database
**Solusi:**
1. Pastikan baris pertama Excel berisi header: `nama`, `jenis_kelamin`, `desa`, `kelompok`, `jenis_peserta` (huruf kecil, tanpa spasi ekstra)
2. Pastikan nama desa di Excel persis sama dengan yang ada di tabel desa (termasuk huruf besar/kecil)
3. Pastikan nama kelompok di Excel persis sama dengan yang ada di tabel kelompok
4. Coba import 1–2 baris dulu untuk test
5. Jika masih gagal, cek log: `storage/logs/laravel.log`

---

**Masalah: NIP Duplikat (Duplicate NIP)**
**Penyebab:** Import dilakukan bersamaan atau ada bug di `nextAutoNip()`
**Solusi:**
1. Jangan import peserta secara bersamaan dari dua browser/komputer
2. Jika sudah terjadi duplikat, cek di database peserta mana yang NIP-nya sama
3. Edit NIP salah satu peserta secara manual via halaman Database Peserta
4. Untuk mencegah: ada unique constraint di database yang akan memblokir NIP ganda — error ini akan muncul di log

---

**Masalah: Jenis Kelamin Tidak Terbaca / Filter Tidak Berfungsi**
**Penyebab:** Format jenis kelamin di database tidak persis `Laki - Laki` atau `Perempuan`
**Solusi:**
1. Cek data di tabel `pesertas` langsung melalui database viewer
2. Nilai yang valid:
   - `Laki - Laki` (huruf L besar, ada spasi sebelum dan sesudah tanda `-`)
   - `Perempuan` (huruf P besar)
3. Jika ada data yang tidak sesuai format, edit manual atau buat script update:
   ```bash
   php artisan tinker
   # Lalu jalankan query update
   ```
4. Untuk import regu, `ReguImport.php` sudah ada normalisasi otomatis

---

**Masalah: Permission Error di Laravel (storage not writable)**
**Penyebab:** Folder `storage/` atau `bootstrap/cache/` tidak bisa ditulis oleh web server
**Solusi:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```
(Ganti `www-data` dengan user nginx/apache yang digunakan server)

---

**Masalah: Cache Lama (perubahan tidak terlihat)**
**Penyebab:** Laravel menyimpan cache konfigurasi, route, dan view
**Solusi:**
```bash
php artisan optimize:clear
```
Atau satu per satu:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

**Masalah: Halaman Scan QR Tidak Muncul Kamera**
**Penyebab:** Browser tidak mengizinkan akses kamera, atau website tidak menggunakan HTTPS
**Solusi:**
1. Pastikan website diakses via `https://` (bukan `http://`)
2. Di browser, izinkan akses kamera untuk website ini
3. Coba browser lain (Chrome disarankan)
4. Jika di mobile, coba buka di browser bawaan (bukan in-app browser)

---

**Masalah: Halaman Login Stuck / Loop Redirect**
**Penyebab:** Session corrupt atau cache middleware
**Solusi:**
```bash
php artisan optimize:clear
php artisan session:table  # jika perlu buat ulang tabel session
php artisan migrate
```
Atau di browser: clear cookies untuk domain ini

---

## 7. DEPLOYMENT NOTES — Cara Menjalankan Aplikasi

### Kebutuhan Sistem

- PHP 8.2 atau lebih baru
- Composer (pengelola paket PHP)
- Node.js & NPM (untuk build aset CSS/JS)
- SQLite (sudah termasuk di PHP — tidak perlu install terpisah)

---

### Langkah Instalasi Fresh (Server Baru)

```bash
# 1. Clone atau upload project ke server
# Pastikan berada di folder project

# 2. Install dependency PHP
composer install --no-dev --optimize-autoloader

# 3. Install dependency JavaScript & build aset
npm install
npm run build

# 4. Salin file konfigurasi
cp .env.example .env

# 5. Generate app key (kunci enkripsi)
php artisan key:generate

# 6. Buat file database SQLite
touch database/database.sqlite

# 7. Jalankan migrasi database (buat semua tabel)
php artisan migrate

# 8. (Opsional) Isi data contoh untuk testing
php artisan db:seed

# 9. Buat symlink storage (untuk file upload)
php artisan storage:link

# 10. Set permission folder
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 11. Bersihkan cache dan optimasi
php artisan optimize:clear
php artisan optimize
```

---

### File `.env` — Konfigurasi yang Perlu Diubah

Buka `.env` dan sesuaikan:

```env
APP_NAME=AbsenCAI             # Nama aplikasi
APP_ENV=production            # Jangan ubah di production
APP_DEBUG=false               # Jangan ubah ke true di production
APP_URL=https://domain-kamu.com   # Sesuaikan dengan domain

DB_CONNECTION=sqlite          # Biarkan sqlite jika tidak ganti database
# File database ada di: database/database.sqlite
```

> **PENTING:** Setelah ubah `.env`, selalu jalankan `php artisan optimize:clear`

---

### Database SQLite

- File database ada di: `database/database.sqlite`
- **Backup database** cukup dengan copy file ini:
  ```bash
  cp database/database.sqlite database/database.sqlite.backup-$(date +%Y%m%d)
  ```
- File ini **TIDAK boleh di-commit ke Git** (sudah ada di `.gitignore`)

---

### Permission Folder

| Folder | Permission | Fungsi |
|--------|-----------|--------|
| `storage/` | 775 | Log, cache, file upload |
| `bootstrap/cache/` | 775 | Cache framework |
| `public/` | 755 | Aset publik (bisa baca publik) |

---

### Nginx (Jika Menggunakan Nginx)

Konfigurasi dasar Nginx untuk Laravel:

```nginx
server {
    listen 80;
    server_name domain-kamu.com;
    root /var/www/AbsenCAI/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Setelah update konfigurasi Nginx:
```bash
sudo nginx -t          # test konfigurasi
sudo systemctl reload nginx
```

> **Catatan:** Ada file `udo systemctl restart php-fpm` di root folder project — ini kemungkinan perintah yang pernah dijalankan dan tidak sengaja tersimpan sebagai file. File ini tidak berbahaya tapi bisa dihapus.

---

### Command Penting Sehari-hari

| Command | Fungsi |
|---------|--------|
| `php artisan migrate` | Jalankan migration baru yang belum dijalankan |
| `php artisan optimize:clear` | Bersihkan semua cache |
| `php artisan optimize` | Build cache untuk production (lebih cepat) |
| `php artisan db:seed` | Isi data contoh (hanya untuk testing) |
| `php artisan tinker` | Masuk ke console interaktif Laravel |
| `php artisan route:list` | Lihat semua URL yang terdaftar |

---

### Update Aplikasi (Deploy Versi Baru)

```bash
# 1. Pull perubahan terbaru
git pull origin main

# 2. Install dependency baru jika ada
composer install --no-dev --optimize-autoloader

# 3. Build aset baru
npm run build

# 4. Jalankan migration baru jika ada
php artisan migrate

# 5. Bersihkan dan rebuild cache
php artisan optimize:clear
php artisan optimize

# 6. Restart PHP-FPM (jika diperlukan)
sudo systemctl restart php8.2-fpm
```

---

## 8. GIT SAFETY — Panduan Aman Menggunakan Git

> **Apa itu Git?**
> Git adalah sistem pencatatan perubahan kode. Bayangkan seperti "riwayat dokumen" di Google Docs — kita bisa melihat siapa yang mengubah apa dan kapan, serta kembali ke versi sebelumnya jika ada kesalahan.

---

### Sebelum Mulai Edit Kode

Selalu cek status terlebih dahulu:

```bash
# Lihat file apa yang sudah berubah
git status

# Lihat detail perubahan
git diff

# Lihat riwayat commit terakhir
git log --oneline -10
```

---

### Menyimpan Perubahan ke Git

```bash
# 1. Cek apa yang berubah
git status

# 2. Tambahkan file yang ingin disimpan
git add .                          # Tambahkan semua perubahan
# ATAU spesifik file:
git add app/Models/peserta.php     # Hanya file tertentu

# 3. Simpan dengan pesan yang jelas
git commit -m "Tambah kolom no_hp di peserta"

# 4. Kirim ke server (remote repository)
git push origin main
```

> **Tips pesan commit yang baik:**
> - "Tambah fitur export absensi ke Excel"
> - "Fix: NIP duplikat saat import bersamaan"
> - "Update tampilan dashboard — tambah filter regu"

---

### File yang TIDAK Boleh Di-commit

File-file berikut sudah diatur di `.gitignore` dan tidak akan ikut di-commit:

| File/Folder | Alasan |
|------------|--------|
| `.env` | Berisi password dan key rahasia |
| `database/database.sqlite` | Data aktif — backup terpisah |
| `vendor/` | Terlalu besar, di-install ulang dengan `composer install` |
| `node_modules/` | Terlalu besar, di-install ulang dengan `npm install` |
| `storage/logs/` | Log tidak perlu di-commit |

**Cara cek apakah file sensitif tidak ikut ter-commit:**
```bash
git status
# Pastikan .env dan database.sqlite tidak muncul di sana
```

---

### Rollback — Kembali ke Versi Sebelumnya

**Jika baru saja commit tapi belum di-push:**
```bash
# Batalkan commit terakhir, tapi perubahan tetap ada di file
git reset HEAD~1

# ATAU batalkan commit DAN kembalikan file ke versi sebelumnya (HATI-HATI)
git reset --hard HEAD~1
```

**Jika sudah di-push ke remote:**
```bash
# Lihat ID commit yang ingin dikembalikan
git log --oneline

# Buat commit baru yang "membalik" perubahan commit tertentu
git revert <id-commit>
git push origin main
```

**Jika ingin kembali ke commit tertentu di file tertentu:**
```bash
# Kembalikan satu file ke versi dari commit tertentu
git checkout <id-commit> -- app/Models/peserta.php
git add app/Models/peserta.php
git commit -m "Revert peserta.php ke versi sebelumnya"
```

---

### Workflow yang Disarankan

```
Sebelum kerja:
  git pull origin main          ← Ambil update terbaru dari server

Saat kerja:
  Edit file yang diperlukan
  Test di local

Setelah kerja:
  git status                    ← Cek apa yang berubah
  git add .                     ← Tandai perubahan
  git commit -m "Deskripsi"    ← Simpan dengan pesan jelas
  git push origin main          ← Kirim ke server
```

---

### Backup Manual Database (Sangat Disarankan Sebelum Perubahan Besar)

```bash
# Backup database SQLite
cp database/database.sqlite database/database.sqlite.backup-$(date +%Y%m%d-%H%M%S)

# Simpan backup ke tempat aman (bisa juga di-download ke komputer lokal)
```

---

## Ringkasan Kontak & Referensi

| Sumber | Link |
|--------|------|
| Dokumentasi Laravel | https://laravel.com/docs |
| Dokumentasi Livewire | https://livewire.laravel.com |
| Dokumentasi Flux UI | https://fluxui.dev |
| Maatwebsite Excel | https://laravel-excel.com |

---

*Dokumen ini dibuat berdasarkan analisa kode project AbsenCAI per Juli 2026.*
*Jika ada perubahan besar pada project, update dokumen ini agar tetap relevan.*
