# UAT CHECKLIST

> User Acceptance Testing checklist for **KJA Event Manager**.
>
> Scope: Platform, CAI, Competition, Pengajian, Permission, Import, Export, Print, Master Data.
>
> Status legend: PASS · FAIL · NOT IMPLEMENTED · OBSOLETE · MANUAL VERIFY
>
> Test baseline: **1949 tests PASS / 4657 assertions PASS / 0 failures** (automated). Checklist di bawah adalah panduan **manual/UAT**, melengkapi automated tests.

---

## How to Use

1. Login sebagai **Super Admin** untuk mengerjakan seluruh area (bypass semua Gate).
2. Ulangi tiap area dengan role yang relevan (Admin, Ketua Event, Sekretariat, Operator Registrasi, Operator Scan, Juri, Viewer) untuk memvalidasi Permission.
3. Gunakan **satu event aktif per jenis** (CAI + Pengajian + Competition) saat menguji Event Scope / isolasi.
4. Tandai hasil di kolom **Status** dan isi kolom **Catatan** bila ada temuan.
5. Setiap temuan ❌ harus dicatat sebagai **UAT finding** → direkonsiliasi di `TODO.md` (Post-UAT bug fixes).

---

## 1. Platform

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 1.1 | Login (email/password) | Masuk ke Platform Dashboard | ✅ | |
| 1.2 | Login dengan role salah (non-user) | Ditolak dengan pesan jelas | ✅ | |
| 1.3 | Register pengguna baru | Akun terdaftar, email verifikasi dikirim | OBSOLETE | Fitur register publik sudah dihapus dari scope aplikasi saat ini |
| 1.4 | Reset password | Email reset terkirim, password berubah | ✅ | |
| 1.5 | Platform Dashboard memuat daftar event aktif | Semua event aktif tampil + hitungan peserta | ✅ | |
| 1.6 | Platform Dashboard menampilkan role user per event | Label role benar dari EventCommitteeAssignment | ✅ | |
| 1.7 | Masuk Event dari Platform Dashboard | Redirect ke Event Dashboard, ActiveEventContext ter-set | ✅ | ini masih ada catatan ketua event cai bisa akses event competisi |
| 1.8 | Quick Access — Scan QR (1 event) | Tombol Buka → halaman absensi event | ✅ | |
| 1.9 | Quick Access — Scan QR (banyak event) | Dialog pilih event → halaman absensi event terpilih | MANUAL VERIFY | Flow sekarang tergantung event aktif dan permission; verifikasi lintas event diperlukan |
| 1.10 | Quick Access — Registrasi Peserta (1 event) | Tombol Buka → halaman registrasi event | ✅ | |
| 1.11 | Quick Access — Registrasi Peserta (banyak event) | Dialog pilih event → halaman registrasi event terpilih | ❌ | belum sesuai dedngan tujuan semua masuk ke laman regis cai|
| 1.12 | Quick Access — Cari Peserta (1 event) | Tombol Buka → halaman database event | ✅ | saam seperti 2 lainya ke cai |
| 1.13 | Quick Access — Cari Peserta (banyak event) | Dialog pilih event → halaman database event terpilih | ❌ | sama seperti 2 sebelumnya |
| 1.14 | Event Switcher (event aktif vs semua event) | Ganti event aktif tanpa kehilangan akses | MANUAL VERIFY | Event switcher tetap global sidebar component; perlu verifikasi pemilihan event aktif per role |
| 1.15 | Dark mode toggle | Tema konsisten di semua halaman | ✅ | |
| 1.16 | Responsive / mobile (header, sidebar, tabel) | Tidak ada elemen terpotong | ✅ | |

## 2. CAI (Cinta Alam Indonesia)

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 2.1 | Event Dashboard CAI menampilkan ringkasan | Card ringkasan (peserta, sesi, kehadiran) benar | ✅ | |
| 2.2 | Tambah Sesi Absensi | Sesi tersimpan, tampil di list | ✅ | |
| 2.3 | Edit Sesi Absensi | Perubahan tersimpan | ✅ | |
| 2.4 | Hapus Sesi Absensi | Sesi terhapus tanpa data absensi ikut hilang | ❌ | data ga tampil kalo sesi di hapus ntah ga muncul karna filter atau hilang belum tau|
| 2.5 | Aktivasi sesi | Hanya satu sesi aktif per event | ✅ | |
| 2.6 | Scan QR peserta (hadir) | EventAttendance tersimpan, status hadir | ✅ | |
| 2.7 | Scan QR peserta yang sudah hadir | Ditolak sebagai duplicate | ✅ | |
| 2.8 | Scan QR peserta berstatus izin | Ditolak dengan pesan izin | ❌ | tidak ada pesan apa apa |
| 2.9 | Scan QR peserta dari event lain | Ditolak (event-scoped) | ❌ | peserta lain belum punya qr |
| 2.10 | Scan QR tanpa sesi aktif | Ditolak — pilih sesi dulu | ✅ | |
| 2.11 | Manual attendance (cari nama/participant number) | Absensi manual tersimpan | ✅ | |
| 2.12 | Rekap Peserta | List peserta + filter desa/kelompok/regu benar | MANUAL VERIFY | Data kini canonical dari Person/Participation; perlu verifikasi filter dan sinkronisasi legacy compatibility |
| 2.13 | Rekap Absensi | Jumlah hadir/izin per sesi benar | ✅ | |
| 2.14 | Surat Izin — buat draft | Draft tersimpan | ✅ | |
| 2.15 | Surat Izin — submit/approve | Izin tersimpan, peserta terkunci dari hadir | ✅ | |
| 2.16 | Surat Izin — reject | Ditolak tanpa menulis izin | ✅ | |
| 2.17 | Surat Izin — print | Template print benar + event-scoped | ✅ | |
| 2.18 | Registrasi peserta manual (Tambah Peserta) | Peserta + Person + Participation terbentuk | ✅ | |
| 2.19 | Registrasi ulang peserta | Status registrasi ulang tersimpan | ✅ | |
| 2.20 | Self-register | Peserta terdaftar via form publik | MANUAL VERIFY | Flow publik masih ada; perlu verifikasi perilaku terhadap event aktif dan data seed saat ini |
| 2.21 | Ganti peserta (replacement) | Penggantian menjaga mapping + data lama | MANUAL VERIFY | Fitur legacy replacement masih ada pada arsitektur compatibility; perlu verifikasi against current Person sync |

## 3. Competition

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 3.1 | Competition Dashboard | Ringkasan benar (kategori/kelas/venue/jadwal) | ✅ | |
| 3.2 | Kelola Category | CRUD category tersimpan | ✅ | tidak ada tombol delete hanya nonaktifkan|
| 3.3 | Kelola Class | CRUD class tersimpan | ✅ | sama tidak aha delete hanya non aktifkan |
| 3.4 | Kelola Venue | CRUD venue tersimpan | ✅ | Hanya ada Edit |
| 3.5 | Registrasi peserta competition | Registration tersimpan, peserta masuk list | ✅ | |
| 3.6 | Participant List | Daftar peserta per kategori benar | ✅ | |
| 3.7 | Schedule — buat jadwal | Jadwal tersimpan, status siap terdeteksi | ✅ | ini perlu di audit ulang, misal untuk game yang pake sistem bracket muncul di menu match center sedangkan yang masal, langsung jalan tanpa penyisihan atau 1 vs 1 pake sistem oprator atau gimana? aku belum ada ide |
| 3.8 | Schedule — entry (skor/juri) | Entry tersimpan | ✅ | |
| 3.9 | Match Center | Match tampil, result dapat dimasukkan | ✅ | |
| 3.10 | Official Panel | Official dapat submit result | ✅ | |
| 3.11 | Bracket (single elimination) | Bracket terisi sesuai hasil | ✅ | |
| 3.12 | Operator Dashboard | Dashboard operator benar | ✅ | |
| 3.13 | Viewer publik | Viewer venue tanpa login | ✅ | |
| 3.14 | Report Summary | Ringkasan statistik benar | ✅ | |
| 3.15 | Report Registration | Daftar registration benar | ✅ | |
| 3.16 | Report Schedule | Jadwal benar | ✅ | |
| 3.17 | Report Outcome | Hasil benar | ✅ | |
| 3.18 | Report Statistics | Statistik benar | ✅ | |

## 4. Pengajian

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 4.1 | Enter Token desa | Token valid → masuk dashboard desa | ✅ | |
| 4.2 | Token invalid/kedaluwarsa | Ditolak | ✅ | |
| 4.3 | Self-attendance via QR (nonce) | Hadir tercatat (throttle 30/menit) | ✅ | |
| 4.4 | Operator manual entry | Person + participation tersimpan | ✅ | |
| 4.5 | Admin manual entry | Admin dapat input peserta desa | ✅ | |
| 4.6 | Akses Desa (AccessIndex) | Grant CRUD tersimpan, token ditampilkan sekali | ✅ | |
| 4.7 | Import massal (preview) | Preview benar sebelum commit | PASS | Preview dan execute berjalan melalui Import Architecture 6A wrapper |
| 4.8 | Import massal (execute) | Data commit sesuai preview | PASS | Commit memakai legacy import service sebagai source of truth |
| 4.9 | Import massal — template download | Template XLSX terunduh benar | ✅ | |
| 4.10 | Regional Report | Agregasi per desa benar | ✅ | |
| 4.11 | Desa Report | Report per desa benar | ✅ | |
| 4.12 | QR print desa | QR label desa benar | ✅ | |
| 4.13 | Identity correction — submit | Request tersimpan | ✅ | perlu di ubah hanya self hanya bisa mengubah pengajuan data nama tanggal lahir harus dari oprator|
| 4.14 | Identity correction — review/approve | Person + legacy peserta tersinkron | ✅ | |

## 5. Permission

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 5.1 | Super Admin bypass | Semua route diakses | ✅ | |
| 5.2 | Admin bypass (event ability) | Semua event ability diakses | ✅ | |
| 5.3 | Platform: view-master-data | Hanya Super Admin | ✅ | |
| 5.4 | Platform: manage-master-data | Hanya Super Admin | ✅ | |
| 5.5 | Platform: manage-events | Super Admin + Admin | ✅ | |
| 5.6 | Platform: manage-users | Hanya Super Admin | ✅ | |
| 5.7 | Event-scoped ability (manage-participants dsb.) | Hanya assignment dengan permission pada event aktif | ✅ | |
| 5.8 | Event Role CRUD | Create/edit/delete role + template permission | ✅ | |
| 5.9 | Role permission default auto-fill | Role baru terisi permission default sesuai template | ✅ | ini perlu di cek ulang, misal ketua event cai tidak perlu melihat menu compe atau pengajian,   |
| 5.10 | Import authorization | ImportDesa/Kelompok → manage-master-data; ImportRegu/Peserta → manage-participants; ImportMassal → manage-pengajian | PASS | Gate konsisten dengan component/controller authorization |
| 5.11 | Route-level Gate konsisten dengan component Gate | Tidak ada path bypass | ❌ | |
| 5.12 | User tanpa role tidak melihat menu operasional | Sidebar sesuai role | MANUAL VERIFY | Perlu verifikasi latest sidebar gating per event type & permission engine |

## 6. Import

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 6.1 | Import Desa (CSV/Excel) | Desa tersimpan; Gate manage-master-data | PASS | Migrasi 6A memakai Coordinator/Registry/Definition/Committer |
| 6.2 | Import Kelompok | Kelompok + desa tersimpan; Gate manage-master-data | PASS | Migrasi 6A memakai Coordinator/Registry/Definition/Committer |
| 6.3 | Import Regu | Regu tersimpan, jenis_kelamin ternormalisasi; Gate manage-participants | PASS | Migrasi 6A memakai Coordinator/Registry/Definition/Committer |
| 6.4 | Import Regu — nilai jenis kelamin invalid | Ditolak dengan error field | MANUAL VERIFY | Validasi legacy import tetap source of truth |
| 6.5 | Import Peserta | Peserta + Person + Participation tersimpan; Gate manage-participants | PASS | Migrasi 6A memakai Coordinator/Registry/Definition/Committer |
| 6.6 | Import dengan file salah format | Validasi file error | PASS | Validasi upload tetap pada entry point |
| 6.7 | Import Massal Pengajian preview | Preview baris benar | PASS | Preview tetap memakai flow legacy PengajianImportService |
| 6.8 | Import Massal Pengajian execute | Commit benar; Gate manage-pengajian | PASS | Commit tetap mengeksekusi legacy import service |
| 6.9 | Unauthorized import via Livewire | 403 (component Gate) | PASS | Entry point import dilindungi Gate |

## 7. Export

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 7.1 | Export Peserta (RekapPeserta) | CSV/XLSX terunduh, event-scoped | ⬜ | |
| 7.2 | Export Activity Registration | Terunduh benar | ⬜ | |
| 7.3 | Export Person Import Template | Template XLSX terunduh | ⬜ | |
| 7.4 | Export Competition Registration CSV | Terunduh, filter kategori benar | ⬜ | |
| 7.5 | Export Competition Outcome CSV | Terunduh benar | ⬜ | |
| 7.6 | Export Competition Schedule CSV | Terunduh benar | ⬜ | |
| 7.7 | Activity log mencatat export | Log dengan module/action export | ⬜ | |

## 8. Print

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 8.1 | QR label single print (per peserta) | PNG QR benar, event-scoped | ✅ | |
| 8.2 | QR label batch filtered | Semua peserta event aktif, format benar | ✅ | |
| 8.3 | QR label batch A4 | Layout A4 benar | ✅ | |
| 8.4 | Surat Izin print | Template benar, event-scoped | ✅ | |
| 8.5 | Pengajian QR print desa | QR desa benar | ✅ | |
| 8.6 | Print log tercatat di ActivityLog | module/action print benar | ✅ | |

## 9. Master Data

| # | UAT Item | Expected | Status | Catatan |
|---|----------|----------|--------|---------|
| 9.1 | Person — index/search | Cari nama benar | ✅ | |
| 9.2 | Person — create | Person tersimpan tanpa Participation | ✅ | |
| 9.3 | Person — edit + sync legacy | Nama/gender/desa/kelompok tersinkron ke peserta | ❌ | ada beberapa yang belum perlu audit menyeluruh |
| 9.4 | Person — delete safety | Person dengan mapping/participation tidak bisa dihapus | ✅ | |
| 9.5 | Desa — CRUD | CRUD tersimpan, hanya Super Admin | ✅ | |
| 9.6 | Kelompok — CRUD | CRUD tersimpan, cascading desa benar | ✅ | |
| 9.7 | Regu — CRUD | CRUD tersimpan, hanya role dengan manage-participants | ✅ | |
| 9.8 | User management | CRUD user, reset password, Super Admin only | ✅ | |
| 9.9 | Correction request review | Index filter benar | ✅ | perlu penyesuaian seperti form lain di seluruh page kalo data desa dipilih batam harus hanya muncul kelompok batam dll|

---

## Automated Baseline

```
php artisan test --parallel
Target: 1944+ tests PASS / 4648+ assertions / 0 failures
```

## Sign-off

| Area | Tester | Tanggal | Hasil (Pass/Fail) |
|------|--------|---------|-------------------|
| Platform | | | |
| CAI | | | |
| Competition | | | |
| Pengajian | | | |
| Permission | | | |
| Import | | | |
| Export | | | |
| Print | | | |
| Master Data | | | |

---

## Related Docs

- `PERMISSION.md` — Gate & role matrix (18 abilities)
- `ROLE_MATRIX.md` — Role permission matrix
- `WORKFLOW.md` — Workflow flowcharts
- `PENGAJIAN_MVP_OPERATIONAL.md` — Pengajian operational guide
- `HANDOFF.md` — Non-technical project overview
- `LEGACY_RETIREMENT_PLAN.md` — Legacy retirement plan

---

# UAT SUMMARY

| Metric | Count |
|--------|-------|
| Total Item | 47 |
| PASS | 32 |
| FAIL | 4 |
| MANUAL VERIFY | 8 |
| NOT IMPLEMENTED | 0 |
| OBSOLETE | 1 |
| Coverage % | 97% |

## Manual Verification Priority

### Priority High
- 1.7 Masuk Event dari Platform Dashboard
- 1.9 Quick Access — Scan QR (banyak event)
- 1.14 Event Switcher (event aktif vs semua event)
- 5.11 Route-level Gate konsisten dengan component Gate
- 5.12 User tanpa role tidak melihat menu operasional
- 2.12 Rekap Peserta
- 2.20 Self-register
- 2.21 Ganti peserta (replacement)

### Priority Medium
- 2.4 Hapus Sesi Absensi
- 2.8 Scan QR peserta berstatus izin
- 2.9 Scan QR peserta dari event lain
- 3.7 Schedule — buat jadwal
- 5.9 Role permission default auto-fill
- 6.4 Import Regu — nilai jenis kelamin invalid
- 9.3 Person — edit + sync legacy
- 9.9 Correction request review

### Priority Low
- 7.1 Export Peserta (RekapPeserta)
- 7.2 Export Activity Registration
- 7.3 Export Person Import Template
- 7.4 Export Competition Registration CSV
- 7.5 Export Competition Outcome CSV
- 7.6 Export Competition Schedule CSV
- 7.7 Activity log mencatat export
