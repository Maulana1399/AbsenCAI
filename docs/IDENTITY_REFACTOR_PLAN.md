# IDENTITY REFACTOR PLAN

## Executive Summary

Dokumen ini menjelaskan desain perpindahan dari identitas lama berbasis `nip` menuju arsitektur identitas baru yang lebih aman dan scalable.

### Prinsip utama desain baru

1. **Database ID** menjadi identitas internal utama
2. **Participant Number** menjadi nomor yang dibaca manusia
3. **Attendance Code** menjadi isi QR Code
4. QR Code **tidak lagi berisi NIP**
5. Participant Number hanya untuk tampilan dan kebutuhan operasional
6. Attendance Code harus unik, sulit ditebak, dan bisa diganti jika diperlukan

### Arah perubahan

Arsitektur baru akan memisahkan tiga hal yang sebelumnya bercampur:
- identitas internal database
- identitas peserta yang dibaca manusia
- identitas untuk absensi QR

Ini mengurangi risiko keamanan, memudahkan regenerasi QR, dan membuat sistem lebih siap untuk multi event di masa depan.

---

## Current Architecture

### Identitas saat ini

Saat ini sistem masih berada pada pola berikut:

- `nip` dipakai sebagai nomor peserta
- `nip` juga dipakai sebagai isi QR
- absensi mencari peserta berdasarkan `nip`
- import dan registrasi masih sangat bergantung pada `nip`

### Masalah arsitektur saat ini

1. QR mudah ditebak karena hanya berisi nomor berurutan
2. Jika kartu QR bocor, identitas peserta mudah disalahgunakan
3. Pergantian QR menjadi sulit karena QR dan identitas peserta masih sama
4. Nomor peserta dan identitas internal belum dipisahkan
5. Database belum punya lapisan identitas yang jelas untuk future multi event

### Service yang sudah ada

Berdasarkan review fase 1:
- `PlacementService` sudah menangani generation participant number
- `RegistrationService` sudah menangani create/update participant
- `AttendanceService` sudah menangani proses scan

Namun, ketiganya masih memakai struktur identitas lama sehingga perlu disesuaikan ke model baru.

---

## Target Architecture

### Struktur identitas yang dituju

```text
Database ID
   ↓
Participant Number
   ↓
Attendance Code
```

### Peran tiap identitas

#### 1. Database ID
- Dipakai internal oleh database
- Tidak ditampilkan ke user
- Tidak dipakai di QR
- Menjadi primary key utama untuk relasi antar tabel

#### 2. Participant Number
- Nomor manusiawi yang dipakai panitia dan peserta
- Contoh:
  - `KL001`
  - `KL002`
  - `KP001`
  - `KP002`
- Dipakai pada kartu identitas, laporan, dan tampilan operasional
- Bukan isi QR

#### 3. Attendance Code
- String acak dan unik
- Contoh:
  - `KJA-8F4X9Q2M`
- Dipakai sebagai isi QR
- Harus bisa diregenerasi
- Harus bisa dinonaktifkan jika kartu hilang atau dicurigai bocor

---

## Migration Strategy

### Prinsip migrasi

Migrasi harus bertahap dan backward compatible.

### Fase migrasi data

#### Fase 1: Tambah struktur baru
- Tambah kolom untuk participant number
- Tambah kolom attendance code
- Tambah kolom status aktif/tidak aktif untuk attendance code jika diperlukan
- Tetap simpan `nip` sementara untuk kompatibilitas

#### Fase 2: Backfill data lama
- Isi participant number untuk semua peserta lama
- Isi attendance code untuk semua peserta lama
- Pastikan QR lama masih dapat dipetakan selama masa transisi

#### Fase 3: Switch scan engine
- Scan QR membaca attendance code, bukan NIP
- Lookup absensi dan peserta menggunakan attendance code

#### Fase 4: Deprecate old identity path
- Hentikan penggunaan `nip` sebagai QR payload
- `nip` lama tetap disimpan jika masih dibutuhkan untuk riwayat atau kompatibilitas sementara

### Cara menangani peserta lama

Peserta lama harus diberi nilai baru tanpa menghapus data historis.

Contoh:
- lama: `nip = 1001`
- baru: `participant_number = KL001`
- attendance code di-generate baru: `KJA-8F4X9Q2M`

Jika lama punya QR berbasis NIP, sistem transisi perlu:
- tetap menerima NIP sementara di mode kompatibilitas
- mencatat bahwa QR lama bersifat legacy
- menolak penggunaan QR lama setelah cutover final jika dibutuhkan

---

## Database Plan

> Bagian ini adalah rencana desain, bukan implementasi.

### Entitas inti

#### Participant / Person
Kolom identitas yang disarankan:
- `id` sebagai primary key internal
- `participant_number` sebagai nomor manusiawi
- `attendance_code` sebagai kode QR
- `attendance_code_active` atau field sejenis untuk status aktif
- kolom lama `nip` dipertahankan sementara bila diperlukan backward compatibility

### Index dan constraint yang disarankan

1. `participant_number` unique
2. `attendance_code` unique
3. index pada `attendance_code` untuk scan cepat
4. index pada `participant_number` untuk lookup manual

### Database changes required

#### Required changes
- tambah kolom participant number
- tambah kolom attendance code
- tambah status aktif attendance code
- sediakan mekanisme rotasi attendance code
- pertahankan compatibility path untuk data lama

#### Optional later changes
- normalisasi tabel participation
- pemisahan person-event-participation penuh untuk phase berikutnya

---

## Answers to Required Questions

### 1. What database changes are required?

- Tambah `participant_number`
- Tambah `attendance_code`
- Tambah flag/status untuk attendance code aktif
- Tambah index unique pada keduanya
- Pertahankan `nip` sementara untuk backward compatibility

### 2. How should participant_number be generated?

Disarankan format:
- Male: `KL001`, `KL002`, `KL003`
- Female: `KP001`, `KP002`, `KP003`

Rules:
- 3 digit running number
- sequence terpisah per gender
- deterministik
- continue dari nomor terbesar yang sudah ada
- tidak boleh duplikat

### 3. How should attendance_code be generated?

Rules:
- random unique string
- prefix konsisten seperti `KJA-`
- panjang cukup untuk aman dan tidak mudah ditebak
- unik di database
- dihasilkan saat registrasi atau saat pembuatan peserta

Rekomendasi format:
- `KJA-` + 8 sampai 12 karakter alfanumerik uppercase

### 4. Should attendance_code be immutable?

**Recommended answer: yes, default-nya immutable.**

Alasan:
- QR yang stabil memudahkan operasional
- mengurangi kebingungan panitia
- mencegah QR berubah tanpa alasan

Namun sistem tetap perlu fitur regenerasi manual jika:
- kartu hilang
- code bocor
- ada alasan keamanan

Jadi modelnya:
- immutable by default
- mutable only via explicit regenerate action

### 5. How should old participants be migrated?

- Semua peserta lama diberi participant number baru sesuai gender
- Semua peserta lama diberi attendance code baru
- NIP lama dipertahankan sementara bila masih dipakai di laporan/riwayat
- Buat mapping internal agar transisi tidak memutus histori

### 6. How should QR regeneration work?

Rekomendasi:
- regenerasi hanya mengganti `attendance_code`
- participant number tidak berubah
- old QR code otomatis tidak valid setelah code baru aktif
- regenerasi harus tercatat di log/audit bila nanti audit tersedia

### 7. How should RegistrationService change?

RegistrationService sebaiknya berevolusi menjadi service yang menangani:
- create participant/person
- update participant data
- set participant status
- assign participant number
- assign attendance code saat create

Jangan jadikan service ini tempat UI, validation, atau QR rendering.

### 8. How should PlacementService evolve?

PlacementService perlu fokus pada:
- generate participant number
- menentukan sequence berdasarkan gender
- memastikan no duplicate
- membantu migrasi/backfill participant number lama

Jangan campur dengan attendance code generator.

### 9. How should AttendanceService evolve?

AttendanceService harus berpindah dari lookup berbasis NIP ke lookup berbasis attendance code.

Tanggung jawab baru:
- validasi attendance code
- cari participant dari code
- cari sesi aktif
- cek duplicate attendance per session
- simpan attendance

### 10. How should Import behave?

Import sebaiknya:
- masih menerima data peserta seperti sekarang
- tidak mewajibkan attendance code dari Excel
- generate participant number dan attendance code di sistem
- tetap backward compatible dengan format Excel lama

### 11. How should Export behave?

Export sebaiknya:
- menampilkan participant number, bukan NIP sebagai identitas utama
- jika perlu, tampilkan NIP lama sebagai kolom legacy sementara
- jangan mengekspos attendance code di export umum kecuali memang dibutuhkan

### 12. How should ID Card change?

ID card sebaiknya memuat:
- nama
- participant number
- foto bila ada
- QR code berisi attendance code

Tidak disarankan menampilkan attendance code dalam teks biasa pada kartu jika tidak perlu.

### 13. How many implementation sprints are required?

Rekomendasi: **4 sprints**

#### Sprint A
- Database extension
- generate participant number + attendance code
- preserve legacy fields

#### Sprint B
- migration/backfill data lama
- registration/update flow adjustment
- import adjustment

#### Sprint C
- attendance scan switch to attendance code
- QR regeneration flow
- verification and compatibility path

#### Sprint D
- export/id card/report cleanup
- legacy nip deprecation plan
- documentation and regression testing

### 14. What are the risks?

#### High risks
- QR lama tidak terbaca setelah cutover
- duplicate participant number saat backfill
- attendance code collision jika generator lemah
- operasional terganggu kalau scan engine berubah terlalu cepat

#### Medium risks
- laporan lama masih bergantung pada NIP
- import lama belum menyesuaikan participant number
- ID card lama perlu dicetak ulang

#### Low risks
- tampilan UI jika hanya menampilkan field baru

### 15. Rollback strategy

Rollback harus dirancang sebelum implementasi.

#### Level 1: soft rollback
- disable scan by attendance code
- kembali ke compatibility path berbasis NIP sementara

#### Level 2: data rollback
- restore database backup sebelum cutover
- gunakan mapping backup untuk participant number dan attendance code

#### Level 3: deployment rollback
- revert commit terakhir
- deploy versi sebelumnya
- keep database changes backward compatible selama mungkin

### Rollback recommendation

Sebelum cutover final:
- backup database penuh
- backup mapping peserta lama ke participant_number dan attendance_code
- simpan old QR compatibility route bila masih diperlukan

---

## Sprint Plan

### Sprint 2.1 — Design only
- finalisasi identitas target
- finalisasi database plan
- finalisasi migration strategy
- finalisasi service evolution plan

### Sprint 2.2 — Database foundation
- implement kolom baru
- unique constraint
- backfill helper plan

### Sprint 2.3 — Registration and import transition
- update registration flow untuk attendance code
- update import behavior
- preserve legacy support

### Sprint 2.4 — Attendance and QR transition
- switch QR payload to attendance code
- regenerate QR flow
- compatibility handling

### Sprint 2.5 — Export and ID card transition
- update export fields
- update ID card layout
- cleanup legacy fields

---

## Risk Analysis

### Architecture risks
- identity confusion between old `nip` and new participant number
- service responsibilities overlapping jika tidak dipisah tegas
- attendance code generator terlalu sederhana

### Operational risks
- operator masih memakai istilah NIP saat sudah pindah ke participant number
- QR lama masih beredar di lapangan
- data lama belum seluruhnya backfilled

### Technical risks
- scan performance jika lookup attendance code tidak di-index
- collision jika generator code acak tidak cukup kuat
- migration order salah bisa memutus registrasi/absensi

---

## Rollback Plan

### Before implementation
- freeze schema assumptions
- backup database and file exports
- document all legacy behavior that must stay alive

### During rollout
- keep legacy NIP lookup as fallback for a transition period
- keep old reports and exports working
- introduce feature flags for QR switch if needed

### If failure occurs
1. disable new scan path
2. revert to NIP compatibility mode
3. restore backup if data corruption occurs
4. re-issue QR codes only after data validated

---

## Recommendation

### Recommended decision

Proceed with the identity refactor, but implement it in **phases with backward compatibility**.

### Key recommendation

- Treat `participant_number` as the human-facing identity
- Treat `attendance_code` as the QR identity
- Treat database `id` as the internal truth
- Keep `nip` only as a temporary compatibility bridge until all modules are migrated

### Final note

Do not cut over attendance QR and import/export simultaneously in one step. Start from database foundation, then registration, then attendance, then reporting.
