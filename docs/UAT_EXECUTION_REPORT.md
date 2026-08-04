# UAT EXECUTION REPORT

> Based on `docs/UAT_CHECKLIST.md`
>
> This report is for manual execution only.
>
> Do not modify source code during execution.

---

----------------------------------------------------

## 1.7

Masuk Event dari Platform Dashboard

### Tujuan

Memastikan user dapat masuk ke event aktif dari Platform Dashboard dan ActiveEventContext terset dengan benar.

### Preconditions

- User sudah login
- Minimal ada 1 event aktif
- User memiliki akses ke event tersebut

### Langkah Uji

1. Buka Platform Dashboard.
2. Pilih salah satu kartu event aktif.
3. Klik tombol Masuk Event.
4. Periksa redirect dan event context aktif.

### Expected Result

User diarahkan ke Event Dashboard event yang dipilih dan event aktif terset.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 1.9

Quick Access — Scan QR (banyak event)

### Tujuan

Memastikan flow quick access scan QR bekerja ketika ada banyak event dan pengguna memilih event tujuan.

### Preconditions

- User login
- Lebih dari 1 event aktif/terlihat sesuai permission
- Role memiliki akses ke quick access scan QR

### Langkah Uji

1. Buka Platform Dashboard.
2. Pada quick access Scan QR, pilih flow multi-event.
3. Pilih event yang dituju.
4. Verifikasi halaman tujuan yang terbuka.

### Expected Result

Dialog pemilihan event muncul dan user masuk ke halaman absensi event yang dipilih.

### Actual Result

cai dan compe jalan tapi pengajian belum jalan

### Status

⬜ PASS

✅ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 1.14

Event Switcher (event aktif vs semua event)

### Tujuan

Memastikan event aktif dapat diganti tanpa kehilangan akses dan context tetap konsisten.

### Preconditions

- User login
- User memiliki akses ke minimal 2 event
- Sidebar menampilkan event switcher

### Langkah Uji

1. Buka halaman mana pun yang memakai layout utama.
2. Buka Event Switcher pada sidebar.
3. Ganti event aktif ke event lain.
4. Verifikasi context dan redirect halaman.

### Expected Result

Event aktif berubah dan user tetap memiliki akses sesuai permission event baru.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 2.12

Rekap Peserta

### Tujuan

Memastikan daftar peserta dan filter desa/kelompok/regu sesuai data canonical terbaru.

### Preconditions

- User login
- User punya akses ke halaman rekap peserta
- Ada data peserta dengan variasi desa/kelompok/regu

### Langkah Uji

1. Buka halaman Rekap Peserta.
2. Periksa daftar peserta yang tampil.
3. Coba filter desa, kelompok, dan regu.
4. Bandingkan hasil filter dengan data master/participation.

### Expected Result

Daftar peserta dan filter menampilkan data yang konsisten dengan Person/Participation serta sinkron legacy compatibility.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 2.20

Self-register

### Tujuan

Memastikan peserta dapat mendaftar sendiri melalui form publik pada event yang sesuai.

### Preconditions

- Event target aktif
- Form self-register tersedia
- Data seed/event context sesuai

### Langkah Uji

1. Buka halaman self-register.
2. Isi data peserta yang valid.
3. Simpan form.
4. Verifikasi hasil registrasi dan record yang terbentuk.

### Expected Result

Peserta terdaftar melalui form publik dan data tersimpan sesuai flow current app.

### Actual Result

gagal tombol daftar tidak berfungsi

### Status

⬜ PASS

✅ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 2.21

Ganti peserta (replacement)

### Tujuan

Memastikan proses replacement menjaga mapping dan data lama tetap terjaga.

### Preconditions

- User login
- Ada peserta existing yang dapat diganti
- User punya akses ke fitur replacement

### Langkah Uji

1. Buka flow replacement peserta.
2. Pilih peserta lama.
3. Pilih/isi data pengganti.
4. Simpan dan verifikasi mapping.

### Expected Result

Replacement tersimpan dan mapping lama tetap konsisten.

### Actual Result

gagal sama seperti self register

### Status

⬜ PASS

✅ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 5.11

Route-level Gate konsisten dengan component Gate

### Tujuan

Memastikan route protection dan component protection selalu konsisten.

### Preconditions

- Beberapa role tersedia untuk testing
- User login dengan role berbeda

### Langkah Uji

1. Coba akses route yang dibatasi via URL langsung.
2. Coba buka halaman yang sama melalui UI.
3. Lakukan aksi Livewire/component yang sama.
4. Bandingkan hasil route-level dan component-level.

### Expected Result

Tidak ada path bypass; route dan component sama-sama menolak akses yang tidak sah.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 5.12

User tanpa role tidak melihat menu operasional

### Tujuan

Memastikan sidebar dan navigasi mengikuti permission engine terbaru.

### Preconditions

- User login dengan role yang relevan
- Event aktif sudah ada

### Langkah Uji

1. Login dengan user role terbatas.
2. Buka sidebar pada layout utama.
3. Bandingkan menu yang tampil dengan permission user.
4. Switch event bila diizinkan dan verifikasi menu berubah sesuai context.

### Expected Result

Sidebar hanya menampilkan menu yang sesuai permission dan event scope user.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 6.4

Import Regu — nilai jenis kelamin invalid

### Tujuan

Memastikan file import regu dengan nilai gender yang tidak valid ditolak dengan benar.

### Preconditions

- User login dengan permission import regu
- File uji berisi nilai gender invalid

### Langkah Uji

1. Buka halaman import regu.
2. Upload file dengan nilai gender invalid.
3. Jalankan import.
4. Periksa pesan error validasi.

### Expected Result

Import ditolak dan error field ditampilkan.

### Actual Result

(Kosong)

### Status

⬜ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 9.3

Person — edit + sync legacy

### Tujuan

Memastikan perubahan Person tersinkron ke legacy compatibility path sesuai aturan current architecture.

### Preconditions

- User login
- Ada Person existing yang terhubung ke legacy peserta

### Langkah Uji

1. Buka halaman edit Person.
2. Ubah field yang diizinkan.
3. Simpan perubahan.
4. Verifikasi sinkronisasi ke data legacy yang relevan.

### Expected Result

Nama/gender/desa/kelompok tersinkron sesuai flow current app dan tidak merusak mapping.

### Actual Result

(Kosong)

### Status

✅ PASS

⬜ FAIL

### Catatan

(Kosong)

----------------------------------------------------

## 9.9

Correction request review

### Tujuan

Memastikan review permintaan koreksi berjalan sesuai filter current data model.

### Preconditions

- User login dengan permission review
- Ada data correction request untuk diuji

### Langkah Uji

1. Buka halaman correction request review.
2. Gunakan filter yang tersedia.
3. Uji pemilihan data yang bergantung pada desa/relasi.
4. Verifikasi hasil list dan detail review.

### Expected Result

Filter dan daftar review konsisten dengan current architecture dan relasi data.

### Actual Result

(Kosong)

### Status

⬜ PASS

⬜ FAIL

### Catatan

(Kosong)

---

# UAT EXECUTION SUMMARY

| Metric | Count |
|--------|-------|
| Total Manual Verification | 8 |
| PASS | 0 |
| FAIL | 0 |
| Belum Diuji | 8 |
| Coverage % | 0% |

## Release Sign-off

- [ ] Semua UAT PASS
- [ ] Tidak ada bug blocker
- [ ] Tidak ada regression
- [ ] Test suite terakhir PASS
- [ ] Dokumentasi sinkron
- [ ] Siap Merge
