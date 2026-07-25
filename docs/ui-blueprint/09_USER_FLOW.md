# 09 — USER FLOW BLUEPRINT

> Complete user journey maps for all primary roles.
> Benchmark: Linear onboarding, Stripe checkout flow, Notion invite flow.

---

## 1. FLOW MAP LEGEND

```
[PAGE]         Halaman / Screen
{ACTION}       User action
(DECISION)     Conditional branch
=>             Navigation
-->            System action (background)
[!]            Error / edge case
```

---

## 2. AUTHENTICATION FLOW

```
[LANDING PAGE]
  |
  | {Klik "Login"}
  |
  v
[LOGIN PAGE]
  |
  | {Input Email & Password} + {Klik Submit}
  |
  +--> Validasi?
  |      |
  |      ├── Valid --> System: Create session
  |      |              |
  |      |              v
  |      |           [WORKSPACE]
  |      |              |
  |      |              | (DECISION: First time?)
  |      |              ├── Yes --> Onboarding tooltip/highlight
  |      |              └── No  --> Normal workspace
  |      |
  |      └── Invalid --> [!] Error message "Email/password salah"
  |                        |
  |                        v
  |                     [LOGIN PAGE] (retry)
  |
  | {Klik "Lupa Password"}
  |
  v
[FORGOT PASSWORD PAGE]
  |
  | {Input Email} + {Klik Kirim}
  |
  v
[SUCCESS PAGE] "Cek email untuk reset password"
  |
  | {Klik link di email}
  |
  v
[RESET PASSWORD PAGE]
  |
  | {Input new password} + {Konfirmasi}
  |
  v
[SUCCESS] -> [LOGIN PAGE]

--- Edge Cases ---

[!] Email not registered
    => "Email tidak terdaftar" message
    => Option to register

[!] Session expired
    => Redirect to [LOGIN PAGE]
    => "Sesi berakhir, silakan login ulang"

[!] Account inactive/suspended
    => "Akun non-aktif, hubungi administrator"
```

---

## 3. PRIMARY FLOW: ABSENSI (Operator Scan)

```
[WORKSPACE]
  |
  | {Klik "Absen Cepat" di Quick Actions}
  | atau
  | {Klik Event Card > Pilih module Absensi}
  |
  v
[EVENT HOME]
  |
  | {Klik Modul Absensi}
  |
  v
[ABSENSI PAGE]
  |
  | ┌── Kamera QR Scanner aktif ──────────┐
  | │  Arahkan QR peserta ke viewfinder    │
  | └──────────────────────────────────────┘
  |
  | {Peserta scan QR}
  |
  +--> System: Validasi QR
  |      |
  |      ├── QR Valid + Peserta terdaftar
  |      |      |
  |      |      v
  |      |   [SUCCESS NOTIFICATION]
  |      |   "Budi Santoso — Hadir ✅"
  |      |   Vibration feedback (mobile)
  |      |   Sound confirmation
  |      |      |
  |      |      v
  |      |   (Kembali ke scanner, siap scan berikutnya)
  |      |
  |      ├── QR Valid + Peserta sudah absen
  |      |      |
  |      |      v
  |      |   [!] Warning "Sudah absen pada 08:15"
  |      |   Option: Mark ulang (override)
  |      |
  |      ├── QR tidak valid
  |      |      |
  |      |      v
  |      |   [!] Error "QR tidak dikenal"
  |      |   Option: Cari manual
  |      |
  |      └── QR valid + peserta beda event
  |             |
  |             v
  |          [!] Warning "Peserta terdaftar di event lain"
  |
  | {Cari Manual} (alternatif)
  |  |
  |  v
  | [CARI PESERTA] modal / search
  |  |
  |  | {Ketik nama} -> muncul hasil
  |  | {Tap person} -> Mark Hadir
  |  |
  |  v
  | Konfirmasi: "Mark Budi Santoso sebagai Hadir?"
  | {Ya} -> [SUCCESS]
  | {Tidak} -> Kembali
  |
  v
[!] No internet / offline
  |
  v
[OFFLINE MODE]
  | QR scan cached locally
  | Data sync ketika koneksi kembali
  | Indikator "Offline — X antrean pending"
```

---

## 4. PRIMARY FLOW: REGISTRASI (Operator Registrasi)

```
[EVENT HOME]
  |
  | {Klik Modul Peserta / Registrasi}
  |
  v
[MANAJEMEN PESERTA]
  |
  | {Klik "Tambah Peserta"}
  |
  v
[TAMBAH PESERTA FORM]
  |
  | {Input data person}
  |   - Nama (required)
  |   - Desa (select from master data)
  |   - Kelompok (select from master data)
  |   - Kontak / detail lain
  |
  | {DECISION: Person sudah ada di database?}
  |
  +--> Ya (suggest existing)
  |      |
  |      | {Confirm: daftarkan person ke event ini}
  |      |
  |      v
  |   [SUCCESS] "Person terdaftar ke event"
  |
  +--> Tidak (baru)
         |
         | {Klik Simpan}
         | System: Buat person baru + daftarkan ke event
         |
         v
      [SUCCESS] "Person baru berhasil dibuat & terdaftar"
```

---

## 5. PRIMARY FLOW: CETAK QR & LABEL (PJ Divisi)

```
[EVENT HOME]
  |
  | {Klik Modul QR & Label}
  |
  v
[CETAK QR PAGE]
  |
  | {Filter: Peserta per Desa / All}
  | {DECISION: Cetak semua atau per desa}
  |
  | {Klik "Cetak QR"}
  |
  v
[PRINT DIALOG]
  |
  | Format:
  | - QR per peserta (A6 card)
  | - Label nama (A4 sticker)
  | - Daftar per desa (A4, grouped)
  |
  | {Pilih format + jumlah copy}
  |
  v
[{ACTION: Print via browser / PDF}]
```

---

## 6. PRIMARY FLOW: ANALYTICS (Sekretariat)

```
[WORKSPACE] -> {Klik Analytics di sidebar}
  |
  v
[ANALYTICS PAGE]
  |
  | Default: Semua event, 7 hari terakhir
  |
  | {Pilih event filter: "Pengajian Ramadhan"}
  | {Pilih date range: "1 Bulan"}
  |
  v
(Query update) -> KPI cards + chart refresh
  |
  | {Scroll / lihat breakdown}
  |
  | {Klik "Export"}
  | |
  | v
  | Export options:
  | - PDF (full report)
  | - Excel (raw data)
  | - CSV (raw data)
  |
  | {Pilih export format} -> Download file
  |
  | {Klik KPI Card "Hadir"}
  | |
  | v
  | [DETAIL MODAL] daftar peserta hadir periode ini
```

---

## 7. PRIMARY FLOW: EVENT MANAGEMENT (Admin)

```
[WORKSPACE] -> {Klik Administration}
  |
  v
[ADMINISTRATION]
  |
  | {Klik "Event Management"}
  |
  v
[EVENT TABLE]
  |
  | {Klik "Buat Event Baru"}
  |
  v
[CREATE EVENT FORM]
  |
  | Step 1: Identitas
  |   - Nama Event
  |   - Tipe Event (Pengajian/CAI/PAUD/Seminar/Lomba)
  |   - Status (Draft/Aktif/Selesai)
  |
  | Step 2: Detail
  |   - Lokasi (Masjid, Aula, etc)
  |   - Tanggal Mulai & Selesai
  |   - Desa (if specific)
  |
  | Step 3: Konfigurasi
  |   - Module yang tersedia (Absensi, Peserta, etc)
  |   - Roles & permissions
  |
  | {Klik Simpan}
  |
  v
[SUCCESS] -> Event baru dibuat
  | (Navigate ke Event Home event baru)
```

---

## 8. SECONDARY FLOW: PEOPLE MANAGEMENT

```
[SIDEBAR] -> {Klik People}
  |
  v
[PEOPLE PAGE]
  |
  | Default: List semua person, search kosong
  | Search: {Ketik nama} -> filter by nama
  | Filter: {Pilih Desa} -> filter by desa
  |
  | {Klik person}
  |
  v
[PERSON DETAIL MODAL]
  | Informasi person:
  |   - Nama, Desa, Kelompok
  |   - Riwayat event (daftar event + status)
  |   - Total kehadiran
  |
  | {Klik "Edit"} -> Form edit person
  | {Klik "Hapus"} -> Confirm modal -> Hapus
  | {Klik "Tambah ke Event"} -> Pilih event -> Daftarkan
```

---

## 9. ERROR FLOWS

### 9.1 Network Error

```
[ANY PAGE]
  |
  | [!] Network disconnected
  |
  v
[OFFLINE BANNER]
  "Koneksi terputus. Perubahan akan disimpan saat online."
  |
  | Cache local untuk data penting
  | Mode read-only untuk data tersimpan
  | Queue untuk pending mutations
  |
  | {Koneksi kembali}
  |
  v
[SYNC IN PROGRESS]
  | Sinkronisasi data pending...
  | {Selesai} -> Banner hilang
  | {Gagal}  -> "Beberapa perubahan gagal disinkron"
```

### 9.2 Permission Denied

```
[NAVIGATE TO RESTRICTED PAGE]
  |
  v
[!] "Akses ditolak"
  | Redirect ke halaman sebelumnya atau Workspace
  | Tidak perlu tombol "Minta akses" (internal)
```

### 9.3 404 / Not Found

```
[INVALID URL]
  |
  v
[404 PAGE]
  "Halaman tidak ditemukan"
  [Kembali ke Workspace]
```

### 9.4 Server Error (500)

```
[ANY API CALL FAILS]
  |
  v
[ERROR TOAST / BANNER]
  "Terjadi kesalahan. Silakan coba lagi."
  [Coba Lagi] [Hubungi Admin]
```

---

## 10. CROSS-CUTTING FLOW: EVENT CONTEXT SWITCHING

```
[SIDEBAR]
  |
  | {Klik event lain di Events list}
  |
  v
[EVENT HOME] (event baru)
  | Hero banner mengubah accent color
  | Module grid menyesuaikan modul event
  | Stats bar menampilkan data event baru
  |
  | Context: Seluruh sesi sekarang di event ini
  | Analytics akan filter by event ini secara default
  | Absensi akan menggunakan event ini
```

---

## 11. COMPLETE USER JOURNEY: TYPICAL DAY (Sekretariat)

```
08:00 — Login → [WORKSPACE]
        Lihat agenda hari ini
        Lihat event aktif

08:15 — Cek kehadiran → [EVENT HOME → ABSENSI]
        Pantau progress absensi peserta

09:00 — Ada pendaftar baru → [REGISTRASI]
        Input data person baru

10:00 — Request data dari atasan → [ANALYTICS]
        Export PDF laporan kehadiran

11:00 — Cari peserta lama → [PEOPLE]
        Verifikasi data person

13:00 — Istirahat

14:00 — Administrasi → [ADMINISTRATION]
        Update status event
```

---

## 12. RULES

| Rule | Detail |
|------|--------|
| Setiap flow harus memiliki error state | Network, validation, permission |
| Offline mode harus didukung untuk absensi QR | Prioritas #1 |
| Flow registrasi harus reusable untuk semua event type | Beda event, form sama |
| Export harus multi-format | PDF, Excel, CSV |
| Event context switch harus seamless | Tanpa page reload penuh |
| Setiap form submission harus ada loading state | Spinner di button |
| Konfirmasi untuk destructive actions | Hapus, override absen |
| Toast notification untuk feedback non-blocking | Sukses, error, warning |
