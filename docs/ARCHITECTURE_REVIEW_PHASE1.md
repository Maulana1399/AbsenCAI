# ARCHITECTURE REVIEW PHASE 1

## Scope Review

Review ini mengevaluasi Service Layer yang sudah dibuat pada fase awal refactor:

- `PlacementService`
- `RegistrationService`
- `AttendanceService`

Fokus review:

1. Apakah pembagian tanggung jawab sudah sesuai
2. Apakah masih ada business logic di Livewire atau Model yang seharusnya dipindahkan
3. Apakah ada ketergantungan antar-service yang perlu diperbaiki

Dokumen ini **tidak mengubah kode**.

---

## Executive Summary

Secara umum, arah arsitektur sudah benar.

Hal yang sudah baik:
- Business logic inti mulai dipindahkan ke Service
- Livewire mulai menjadi lebih tipis
- Model `peserta` sudah tidak lagi memuat logika NIP generation langsung
- Attendance flow sudah terpisah dari UI scanner
- Registration persistence sudah dipindahkan ke service

Hal yang masih perlu diperbaiki di fase berikutnya:
- `RegistrationService` masih memakai array input mentah
- `AttendanceService` masih mencampur beberapa keputusan proses dalam satu method
- Livewire masih memegang query dan sebagian rule bisnis ringan
- Model `peserta` masih menyimpan logic placement non-trivial (`autoPlacement`, `leastFilledRegu`)
- Beberapa flow masih bergantung pada Eloquent langsung di Livewire, jadi Service belum sepenuhnya menjadi pusat bisnis

---

## 1. Service Layer Review

### 1.1 `PlacementService`

**Location:** `app/Services/Placement/PlacementService.php`

**What it does now:**
- Generate participant number from gender
- Preserve deterministic numbering
- Keep backward compatibility with `peserta::nextAutoNip()` wrapper

**Assessment:**
- **Good separation**
- One responsibility
- Low dependency count
- Simple and safe

**Notes:**
- Service ini masih tergantung langsung pada `peserta::max()` dan query rentang NIP.
- Itu masih acceptable untuk fase awal karena behavior lama dipertahankan.

**Verdict:**
- Sudah sesuai dengan prinsip service layer
- Layak dipertahankan sebagai contoh pattern untuk service berikutnya

---

### 1.2 `RegistrationService`

**Location:** `app/Services/Registration/RegistrationService.php`

**What it does now:**
- Create participant
- Update participant status
- Update participant details
- Convert duplicate DB error menjadi validation error

**Assessment:**
- **Arah sudah benar**, tetapi tanggung jawabnya masih terlalu general
- Service ini masih bertindak sebagai "write gateway" untuk participant data
- Method menerima array mentah, sehingga struktur input tidak eksplisit

**What is good:**
- Livewire sudah tidak menyimpan persistence logic utama
- Error duplicate sudah ditangani di service, sehingga UI tetap sederhana
- Existing flow tetap identik

**What still needs improvement:**
- `createParticipant()` mencampur persistence dan duplicate exception mapping
- `updateParticipantStatus()` dan `updateParticipant()` masih berada dalam satu service yang cukup umum
- Service belum memakai DTO / typed input object, jadi raw array masih rentan typo

**Verdict:**
- Cukup baik untuk fase 1
- Tapi nanti akan lebih sehat jika dipisah menjadi service/aksi yang lebih spesifik atau memakai DTO

---

### 1.3 `AttendanceService`

**Location:** `app/Services/Attendance/AttendanceService.php`

**What it does now:**
- Cari peserta berdasarkan NIP
- Cari sesi aktif atau sesi pilihan
- Cek duplikasi absensi
- Create absensi
- Return status result

**Assessment:**
- **Sudah lebih dekat ke service yang ideal** dibanding logic sebelumnya di Livewire
- Tetapi method `processScan()` masih mengandung beberapa keputusan berbeda dalam satu alur

**What is good:**
- UI scanning tetap di Livewire
- Logic penting absensi sudah pindah ke service
- Duplicate prevention tetap ada
- Active session handling tetap ada

**What still needs improvement:**
- `processScan()` saat ini punya beberapa outcome sekaligus (`not_found`, `session_required`, `duplicate`, `success`)
- Itu masih valid, tetapi nanti dapat dipisah menjadi helper methods internal:
  - `findParticipant()`
  - `resolveSession()`
  - `checkDuplicate()`
  - `storeAttendance()`
- Method masih menggunakan array response, yang cukup fleksibel tetapi kurang ketat dibanding value object / result object

**Verdict:**
- Sudah benar untuk fase 1
- Masih bisa disempurnakan di fase berikutnya tanpa mengubah behavior

---

## 2. Business Logic Still Remaining in Livewire

### 2.1 `app/Livewire/Registrasi/SelfRegister.php`

**Current state:**
- Validation masih di Livewire
- Auto placement masih dipanggil dari Model
- Flash message dan redirect tetap di Livewire
- Persistence sudah ke `RegistrationService`

**Remaining logic in Livewire:**
- Aturan validasi input
- Refresh auto placement saat gender berubah
- Session flash
- Redirect success page

**Review:**
- Ini masih sesuai arahan saat ini
- Livewire memang sebaiknya tetap memegang validation, flash, redirect, dan UI
- Namun ada satu catatan: Livewire masih memanggil `peserta::autoPlacement()` langsung

**Potential future move:**
- Auto placement bisa menjadi `PlacementService` call agar Livewire tidak lagi bergantung pada model untuk business rule

---

### 2.2 `app/Livewire/Registrasi/Ulang.php`

**Current state:**
- Search masih dilakukan di Livewire
- Edit modal state masih di Livewire
- Flash dan refresh tetap di Livewire
- Persistence update sudah ke `RegistrationService`

**Remaining logic in Livewire:**
- Pencarian peserta
- Mapping form edit
- State modal
- Rendering data list

**Review:**
- Untuk tahap ini masih dapat diterima
- Tetapi search logic adalah query/business read logic dan nanti dapat dipindahkan ke service agar konsisten

**Potential future move:**
- `searchParticipants()` atau `getRegistrationCandidates()` ke service

---

### 2.3 `app/Livewire/Dashboard/Scan.php`

**Current state:**
- Camera/QR/browser events tetap di Livewire
- Service menangani scan processing
- Livewire masih set `message`, `nama`, `nip`, `jam_scan`

**Remaining logic in Livewire:**
- UI state update
- Reset scanner
- Mount session list
- Browser event dispatch

**Review:**
- Sudah sesuai target
- Livewire tetap jadi presenter UI, service jadi pemroses data

**Potential future move:**
- `mount()` masih melakukan query `SesiAbsensi::orderBy...` dan `where(aktif, true)`
- Itu masih UI-facing, jadi tidak wajib dipindah sekarang
- Namun bila dashboard makin besar, session resolution bisa dibuat reusable helper/service

---

## 3. Business Logic Still Remaining in Models

### 3.1 `app/Models/peserta.php`

**Current state:**
- `nextAutoNip()` sudah jadi wrapper ke `PlacementService`
- `autoPlacement()` masih berada di model
- `leastFilledRegu()`, `leastFilledReguId()`, `leastFilledReguName()` masih berada di model

**Review:**
- `nextAutoNip()` sudah aman karena hanya wrapper kompatibilitas
- Tapi `autoPlacement()` dan `leastFilledRegu*()` masih merupakan business logic
- Ini berarti model masih memegang placement rule, walaupun sebagian sudah di-service-kan

**Recommended future move:**
- Pindahkan `autoPlacement()` dan `leastFilledRegu()` ke `PlacementService`
- Model cukup menyimpan relasi, accessor, fillable, dan compatibility wrapper sementara

**Risk if left as is:**
- Logic placement masih tersebar antara Model dan Service
- Ini bisa membingungkan ketika ada perubahan aturan regu atau gender

---

### 3.2 `app/Models/Absensi.php` dan `app/Models/SesiAbsensi.php`

**Assessment:**
- Keduanya masih sehat
- Hanya relasi dan fillable
- Tidak ada business logic berat

**Verdict:**
- Sudah sesuai standar model

---

## 4. Dependency Analysis Between Services

### Current Dependency Graph

- `PlacementService` → depends on `peserta`
- `RegistrationService` → depends on `peserta`
- `AttendanceService` → depends on `peserta`, `Absensi`, `SesiAbsensi`

### Assessment

**Good:**
- Tidak ada circular dependency antar-service
- Tidak ada service yang memanggil service lain saat ini
- Struktur masih sederhana dan aman

**Potential issue:**
- `RegistrationService` dan `AttendanceService` masih bergantung pada model langsung untuk lookup dan persistence
- Itu tidak salah, tetapi membuat service layer belum sepenuhnya menjadi "single source of business rules"

**What to avoid next:**
- Jangan membuat `AttendanceService` memanggil `RegistrationService` tanpa kebutuhan nyata
- Jangan membuat service terlalu besar seperti `EventService` atau `SystemService`
- Pertahankan prinsip satu service satu tanggung jawab

---

## 5. Gaps Compared to Service Plan

Berdasarkan `SERVICE_PLAN.md`, service ideal harus:
- satu tanggung jawab
- return data only
- tidak tahu Blade/Livewire

### Gaps Found

1. **Model masih memegang logic placement**
   - `autoPlacement()` dan `leastFilledRegu()` belum dipindah

2. **Service return masih array-based**
   - Boleh untuk awal, tapi kurang eksplisit

3. **Read logic belum dipusatkan**
   - Search peserta dan session resolution masih tersebar di Livewire / model

4. **Livewire masih melakukan query langsung**
   - Masih wajar, tetapi masih ada logic yang bisa dipusatkan nanti

---

## 6. Recommended Next Refactors

### Priority 1
**Move placement helper logic out of `peserta` model**
- `autoPlacement()`
- `leastFilledRegu()`
- `leastFilledReguId()`
- `leastFilledReguName()`

Reason:
- Ini adalah sisa business logic paling jelas yang masih berada di model
- Akan membuat `PlacementService` benar-benar menjadi pusat placement logic

### Priority 2
**Split `AttendanceService::processScan()` into smaller private methods**
- find participant
- resolve session
- check duplicate
- create attendance

Reason:
- Memperjelas alur dan memudahkan testing

### Priority 3
**Introduce typed result object / DTO for service responses**
- Mengganti array response service secara bertahap

Reason:
- Mengurangi typo key dan membuat kontrak service lebih jelas

### Priority 4
**Move registration search/query helper logic to service**
- Search participant for re-registration
- Search candidates / filters if needed

Reason:
- Mengurangi query logic yang masih tersisa di Livewire

---

## 7. Overall Assessment

### Scorecard

| Area | Status | Notes |
|------|--------|------|
| PlacementService | Good | Small, clear, reusable |
| RegistrationService | Good enough for phase 1 | Still array-based, but acceptable |
| AttendanceService | Good enough for phase 1 | Needs internal decomposition later |
| Livewire Thinness | Improving | Validation/UI still in Livewire, persistence moved out |
| Model Cleanliness | Partial | Placement helpers still remain in model |
| Service Dependencies | Safe | No circular dependency found |

### Final Verdict

Phase 1 architecture is on the right track.

The biggest remaining architectural debt is **placement logic still living inside `peserta` model**. After that, the next improvement should be breaking up `AttendanceService` internals and gradually reducing query logic in Livewire.

---

## 8. Conclusion

The current Service Layer is structurally sound for an early refactor phase:
- no dangerous service coupling
- behavior remains compatible
- Livewire is thinner than before
- core workflows are already isolated

The next phase should focus on **finishing the migration of business logic out of the model**, starting with placement-related helper methods.
