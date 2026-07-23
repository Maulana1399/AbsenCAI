# LAPORAN AUDIT LEGACY CODE & CANONICAL ARCHITECTURE

**Proyek:** KJA Event Manager / AbsenCAI
**Tanggal:** 23 Juli 2026
**Mode:** READ ONLY — Tidak ada perubahan kode
**Baseline (pre-Sprint 1):** 1570 passed, 3803 assertions, 0 failures
**Baseline (post-Sprint 1):** 1494 passed, 3581 assertions, 0 failures
**Baseline (post-Sprint 2):** 1499 passed, 3592 assertions, 0 failures
**Diagnostic Design C (post-Sprint 2):** problem_total = 0

---

## PGM.18 Sprint 1 Status

**Status: ✅ COMPLETE**

Delivered:
1. Removed 6 historical backfill/migration commands (zero production callers)
2. Removed 4 service classes (zero production callers)
3. Removed 4 pure-historical-tooling test files
4. Refactored 2 mixed test files (removed 5 backfill-specific tests)
5. Removed informational string referencing `attendance:backfill` from `AttendanceDiagnose.php`
6. Fixed Design C diagnostic contract — `legacy_peserta_pointing_to_missing_participation` now excludes NULL participation_id (valid forward-reference state)
7. Added regression test for diagnostic contract

Full verification: 1494 passed / 3581 assertions / 0 failures
Design C diagnostic: problem_total = 0 (all 8 metrics 0)

> 76 tests removed vs baseline 1570 adalah EXPECTED (historical tooling tests). Satu regression test baru (diagnostic contract) membuat delta final -75. Bukan regression.

---

## PGM.18 Sprint 2 Status

**Status: ✅ COMPLETE**

**Tanggal:** 23 Juli 2026
**Tujuan:** Narrowing `LegacyPesertaMapping` contract — peserta↔Person only. `LegacyParticipationMapping` = sole event-specific bridge.

### Deliverables

1. **Foundation test refactored**: `LegacyPesertaMappingFoundationTest` — 22 → 14 tests (10 kept, 4 refactored, 8 removed). Factory default now creates peserta_id + person_id only.
2. **6 regression failures at Sprint 2 inception**: All fixed:
   - 4 Activity Foundation tests (`CategoryFoundationTest`, `DomainFoundationTest`, `ReportingIntegrationTest`, `VenueRundownFoundationTest`) — replaced `$mapping->participation`/`->event` assertions with `LegacyParticipationMapping`
   - `CaiParticipantReplacementTest` — replaced `$mapping->participation_id` assertion with `LegacyParticipationMapping`
   - `PrintLogTest:246` — genuine production regression: QR label single print route used `$participant->legacyPesertaMapping()` which returned NULL (depended on `participation_id` no longer set). Fixed by resolving via `LegacyParticipationMapping`.
3. **1 parse error fixed**: `CaiParticipantReplacementTest:272` — missing semicolon
4. **3 stale test contract references fixed**: `PersonReuseTest:96`, `MultiEventValidationRoutingTest:152`, `DesignCDiagnosticsTest:66` — all updated to use `LegacyParticipationMapping`
5. **Model audit**: `participation()` and `event()` relationships on `LegacyPesertaMapping` confirmed DEPRECATED — zero production runtime access to both. `participation_id`, `event_id`, `backfill_batch_id` in `$fillable` also deprecated.
6. **Production zero-reference audit**:
   - `LegacyPesertaMapping->participation`: 0 ✅
   - `LegacyPesertaMapping->event`: 0 ✅
   - `LegacyPesertaMapping.participation_id` (direct column read): 0 ✅
   - `LegacyPesertaMapping.event_id`: 0 ✅
   - `LegacyPesertaMapping.backfill_batch_id`: 0 ✅
   - `Participation::legacyPesertaMapping()` (hasOne via participation_id): 6 implicit references documented as Sprint 3 blockers
7. **Deprecated elements retained** (no migration):
   - `legacy_peserta_mappings.participation_id` — column NOT dropped
   - `legacy_peserta_mappings.event_id` — column NOT dropped
   - `legacy_peserta_mappings.backfill_batch_id` — column NOT dropped
   - `LegacyPesertaMapping::participation()` relationship — NOT removed
   - `LegacyPesertaMapping::event()` relationship — NOT removed
   - All Sprint 3 target

### Verification
- Full suite: **1499 passed / 3592 assertions / 0 failures**
- Design C diagnostic: **problem_total = 0**
- Increase from Sprint 1: +5 tests, +11 assertions (refactored coverage added back)

### Sprint 2 Contract Changes — Canonical Architecture Impact

**Before Sprint 2:**
```
LegacyPesertaMapping:
  peserta_id → peserta
  person_id → Person
  participation_id → Participation (deprecated but active in model)
  event_id → Event (deprecated but active in model)
```

**After Sprint 2:**
```
LegacyPesertaMapping:                ← Global bridge ONLY
  peserta_id → peserta
  person_id → Person
  (participation_id, event_id — inert, scheduled for Sprint 3 drop)

LegacyParticipationMapping:          ← Event-aware bridge (sole)
  peserta_id → peserta
  participation_id → Participation
  event_id → Event
```

### Sprint 2 Audit — Metrik Arsitektur

| Komponen | Sprint 1 Status | Sprint 2 Status | Sprint 3 Target |
|---|---|---|---|
| `LegacyPesertaMapping` → `Partisipasi` | B. ACTIVE (deprecated) | B. ACTIVE (inert — 0 prod refs) | DROP relationship + column |
| `LegacyPesertaMapping` → `Event` | B. ACTIVE (deprecated) | B. ACTIVE (inert — 0 prod refs) | DROP relationship + column |
| `LegacyPesertaMapping.participation_id` | B. ACTIVE (column) | B. ACTIVE (inert — 0 direct reads) | DROP column |
| `Participation::legacyPesertaMapping()` | B. ACTIVE | B. ACTIVE (6 implicit refs documented) | MIGRATE → `LegacyParticipationMapping` |
| `LegacyParticipationMapping` | B. ACTIVE | B. ACTIVE (confirmed sole bridge) | RETAIN |
| `LegacyPesertaMapping` (peserta↔Person) | B. ACTIVE | B. ACTIVE (contract narrowed) | RETAIN |

---

## 1. Ringkasan Eksekutif

Audit menemukan bahwa **arsitektur ganda (dual architecture) masih aktif penuh** meskipun database dikosongkan. Setiap registrasi peserta baru tetap menghasilkan 5 record: `peserta` + `Person` + `Participation` + `LegacyPesertaMapping` + `LegacyParticipationMapping`. Legacy `peserta` bukan dead code — ia adalah **wajib runtime dependency** untuk seluruh flow attendance, surat izin, rekap, dashboard, dan QR.

**Temuan Utama:**

| Komponen | Status | Klasifikasi |
|---|---|---|
| `peserta` model/table | Active Runtime | B. ACTIVE LEGACY COMPATIBILITY |
| `LegacyPesertaMapping` | Active Runtime | B. ACTIVE LEGACY COMPATIBILITY |
| `LegacyParticipationMapping` | Active Runtime | B. ACTIVE LEGACY COMPATIBILITY |
| `Absensi` model/table | Active Runtime (dual-write) | B. ACTIVE LEGACY COMPATIBILITY |
| `IzinAbsensi` model/table | Active Runtime (dual-write) | B. ACTIVE LEGACY COMPATIBILITY |
| NIP | Active Runtime | A. ACTIVE CANONICAL (Person + peserta) |
| `regu_id` | Hanya di peserta | C. FALLBACK ONLY → perlu migrasi ke Participation |
| `status_registrasi` | Hanya di peserta | C. FALLBACK ONLY → perlu migrasi ke Participation |

**Database kosong TIDAK secara signifikan mempermudah PGM.18** karena logic runtime tetap membuat record legacy. Manfaat database kosong hanya: (a) tidak perlu backfill/migrasi data lama, (b) aman menghapus/mengubah schema karena tidak ada data produksi.

---

## 2. Kondisi Arsitektur Saat Ini

### Arsitektur Aktual (Runtime)

```
Registrasi Baru (Case A):
  peserta (wajib) ← masih menjadi root identity untuk attendance legacy
    ↓
  Person
    ↓
  Participation
    ↓
  LegacyPesertaMapping (peserta_id ↔ person_id ↔ participation_id)
  LegacyParticipationMapping (peserta_id ↔ person_id ↔ participation_id ↔ event_id)

Registrasi Person Existing (Case B):
  Person (existing)
    ↓
  Participation (baru)
    ↓
  LegacyParticipationMapping (baru)
  (LegacyPesertaMapping sudah ada dari Case A sebelumnya)

Attendance Scan:
  Identifier → cari Participation via attendance_code
    ↓
  Jika ditemukan → tulis EventAttendance + (jika legacy_write=true) Absensi
    ↓
  Jika tidak ditemukan → cari peserta via attendance_code/nip
    ↓
  Jika ditemukan → resolve via LegacyParticipationMapping/LegacyPesertaMapping
    ↓
  Jika terresolve → tulis EventAttendance + Absensi

Read Attendance:
  Participation → EventAttendance (canonical)
    + fallback ke Absensi via NIP (legacy)
    + fallback ke IzinAbsensi via peserta_id (legacy)
```

### Dual-Write Flow

Feature flag `ATTENDANCE_LEGACY_WRITE=true` (default) menyebabkan setiap attendance menulis ke:
1. `event_attendances` (canonical)
2. `absensis` (legacy — jika identity punya peserta_id/nip)

Izin menulis ke:
1. `event_attendances` dengan status='izin' (canonical)
2. `izin_absensis` (legacy — jika peserta_id tersedia)

---

## 3. Dampak Database Kosong

### Positif
- Tidak perlu backfill/migrasi data lama
- Tidak ada data inkonsisten yang perlu diperbaiki
- Migration schema aman diubah (DROP TABLE, ALTER COLUMN)
- Data integrity diagnostic (Design C) selalu 0

### Negatif
- Runtime masih membuat record legacy — database baru tetap menghasilkan arsitektur ganda
- Semua legacy caller tetap aktif — tidak ada bedanya dengan database penuh
- Logic backfill/migration commands menjadi tidak berguna untuk data baru → kandidat ARCHIVE
- Beberapa migration legacy (add columns, unique constraints) masih dijalankan setiap fresh install

### Contoh Fresh Database

**Budi daftar Event A (Case A):**
```
INSERT INTO pesertas (nama, nip, participant_number, attendance_code, ...)
INSERT INTO people (nama, nip, jenis_kelamin, desa_id, kelompok_id)
INSERT INTO participations (person_id, event_id, participant_number, attendance_code, jenis_peserta)
INSERT INTO legacy_peserta_mappings (peserta_id, person_id, participation_id, event_id, ...)
INSERT INTO legacy_participation_mappings (peserta_id, person_id, participation_id, event_id, ...)
```
**Total: 5 INSERT untuk 1 peserta baru.**

**Budi ikut Event B (Case B):**
```
INSERT INTO participations (person_id, event_id, ...)
INSERT INTO legacy_participation_mappings (peserta_id, person_id, participation_id, event_id, ...)
```
**Total: 2 INSERT. Perhatikan: peserta lama tetap dipakai, LegacyParticipationMapping baru dibuat.**

**Budi absen Event A sesi 1:**
```
INSERT INTO event_attendances (participation_id, sesi_absensi_id, ...)
INSERT INTO absensis (nip, nama, jam_scan, sesi_id)  ← jika legacy_write=true
```
**Total: 1-2 INSERT tergantung feature flag.**

**Budi dihapus dari Event A:**
```
Hapus participation (jika bukan participation terakhir)
  → Hapus event_attendances terkait (CASCADE)
  → Hapus legacy_participation_mapping terkait
  → UPDATE legacy_peserta_mapping jika pointing ke participation ini
```
**Peserta TIDAK dihapus** — hanya participation + mapping yang dihapus.

---

## 4. Legacy Peserta

### Klasifikasi: B. ACTIVE LEGACY COMPATIBILITY — TIDAK BISA DIHAPUS SEKARANG

### Tabel Dependency

| Caller | Operasi | Legacy Dependency | Canonical Alternative | Aman Dihapus? | Prasyarat |
|---|---|---|---|---|---|
| `RegistrationService::createParticipant()` | CREATE | `peserta::create()` + NIP generation | Person + Participation | TIDAK | RegistrationService masih return `peserta` |
| `PlacementService::legacyNextNip()` | READ NIP | `peserta::max('nip')` | Person.nip | TIDAK | NIP range masih via peserta |
| `PlacementService::leastFilledRegu()` | READ | `regu::withCount('peserta')` | Participation.regu_id | TIDAK | regu_id belum di Participation |
| `AttendanceService::processScan()` | READ | `peserta::where('attendance_code')` + `peserta::where('nip')` | Participation.attendance_code | TIDAK | Fallback NIP masih diperlukan |
| `AttendanceService::resolveIdentity()` | READ | 3x `peserta::where()` | Participation queries | TIDAK | Fallback chain masih aktif |
| `AttendanceReadService::getSessionAttendance()` | READ | `peserta` via mapping chain | EventAttendance langsung | TIDAK | legacy fallback read |
| `AttendanceExceptionService::recordIzin()` | CREATE | `IzinAbsensi::create()` via peserta_id | EventAttendance | TIDAK | dual-write aktif |
| `SuratIzinService::create()` | CREATE | `peserta::find($peserta_id)` | Person/Participation | TIDAK | form masih pakai peserta |
| `SuratIzinService::approve()` | READ/WRITE | `peserta->nip` untuk cek duplikat | EventAttendance | TIDAK | multi-table cek |
| `Scan` Livewire | READ | `peserta::where('nama','nip','participant_number')` | Participation queries | TIDAK | fallback search |
| `SelfRegister` Livewire | CREATE | `RegistrationService::createParticipant()` | - | TIDAK | masuk melalui service |
| `TambahPeserta` Livewire | CREATE | `RegistrationService::createParticipant()` | - | TIDAK | masuk melalui service |
| `Ulang` Livewire | READ/WRITE | `peserta::where('nama','nip')` search | Participation search | TIDAK | UI masih cari peserta |
| `Database` Peserta Livewire | READ | `legacyPesertaMapping->peserta` for regu/kelompok | Participation fields | TIDAK | regu/kelompok belum di Participation |
| `EditPeserta` Livewire | UPDATE | `peserta->update(['regu_id'])` + sync | Participation.regu_id | TIDAK | regu_id belum migrasi |
| `HapusPeserta` Livewire | READ/DELETE | `LegacyPesertaMapping` pointer check | Participation langsung | TIDAK | logika surviving participation |
| `GantiPeserta` (Replacement) | CREATE/UPD | `peserta` sebagai slot holder | Person + Participation | TIDAK | replacement flow built on peserta |
| `RekapPeserta` Livewire | READ | `legacyPesertaMapping.peserta.regu/kelompok/status` | Participation fields | TIDAK | data belum di Participation |
| `RekapAbsensi` Livewire | READ | `legacyPeserta->regu/kelompok` | Participation fields | TIDAK | data belum di Participation |
| `SuratIzin/Create` Livewire | READ | `peserta::where('nama','nip')` | Person search | TIDAK | fallback search |
| `PesertaImport` | CREATE | `RegistrationService::createParticipant()` | - | TIDAK | masuk melalui service |
| `PesertaExport` | READ | `legacyPesertaMapping.peserta.*` | Participation fields | TIDAK | data belum di Participation |
| `CaiParticipantReplacementService` | CRUD | `peserta` sebagai slot utama | Person + Participation | TIDAK | entire flow built on peserta |
| `PengajianIdentityService` | READ | Tidak langsung (via Person) | - | N/A | sudah canonical |
| `Dashboard` Livewire | READ | `legacyPesertaMapping.peserta` chain | Participation fields | TIDAK | data belum di Participation |
| `DesignCDiagnostics` | READ | Mapping integrity check | - | N/A | diagnostic tool |
| `AttendanceDiagnose` | READ | `peserta::where('nip', $nip)` | Person.nip | TIDAK | diagnostic via NIP |
| `AuditLegacyData` | READ | Full peserta queries | - | N/A | audit tool |
| `ResetEventData` | DELETE | Preserves `pesertas` table | - | N/A | master data |
| `RebuildLegacyMappings` | CREATE | NIP-based matching | - | N/A | historical tool |
| `BackfillPersonKelompok` | UPDATE | `pesertas.kelompok_id → people.kelompok_id` | - | TIDAK | backfill tool |

### Kesimpulan Peserta

**`peserta` TIDAK bisa dihapus sekarang** karena:
1. RegistrationService mewajibkan return type `peserta`
2. `regu_id` dan `status_registrasi` hanya ada di tabel `pesertas`
3. NIP generation masih via `peserta::max('nip')`
4. Attendance read masih fallback ke peserta via NIP
5. Attendance write (Absensi, IzinAbsensi) masih via peserta_id/nip
6. Surat Izin masih wajib `peserta_id` di form dan service
7. Replacement flow (GantiPeserta) built on peserta sebagai slot holder
8. Export dan rekap masih baca regu/kelompok/status dari peserta via mapping
9. Seluruh Livewire Database Peserta (CRUD) masih operate pada peserta

---

## 5. LegacyPesertaMapping

### Klasifikasi: B. ACTIVE LEGACY COMPATIBILITY

### Tujuan Awal
Bridge antara legacy `peserta` (single-event) dengan canonical `Person` + `Participation` (multi-event).

### Tujuan Saat Ini
Runtime pointer yang menghubungkan satu `peserta` → satu `Person` → satu `Participation` primary.

### Siapa yang Membuat Record
- `RegistrationService::createParticipant()` — Case A (Person baru)
- `RebuildLegacyMappings` command — backfill via NIP match
- `LegacyPesertaBackfillService` — backfill

### Siapa yang Membaca Record
- `AttendanceService::resolveIdentity()` — resolve identity
- `AttendanceReadService::getSessionAttendance()` — baca regu/kelompok
- `LegacyParticipationResolver::*` — semua method resolusi
- `Scan` Livewire — search manual
- `Database` Peserta Livewire — tampilkan data
- `EditPeserta` Livewire — baca regu_id
- `HapusPeserta` Livewire — cek surviving participation
- `RekapPeserta` Livewire — baca regu/kelompok/status_registrasi
- `RekapAbsensi` Livewire — baca regu/kelompok
- `PesertaExport` — baca regu/kelompok/status_registrasi
- `PersonLegacySyncService` — sync Person → peserta
- `IdentityCorrectionService` — sync Person → peserta
- `CaiParticipantReplacementService` — update mapping ke person/participation baru
- `Dashboard` Livewire — data peserta
- `DesignCDiagnostics` — integrity check

### Apakah Masih Diperlukan untuk Database Baru?
**YA.** Setiap registrasi baru tetap membuat LegacyPesertaMapping karena seluruh attendance/izin/surat-izin chain masih membutuhkan pointer ke `peserta`.

### Syarat Penghapusan
1. `regu_id` dan `status_registrasi` dipindahkan ke `participations`
2. RegistrationService tidak lagi membuat `peserta`
3. AttendanceService tidak lagi fallback ke `peserta` table
4. AttendanceExceptionService tidak lagi menulis `izin_absensis`
5. SuratIzinService tidak lagi menggunakan peserta_id
6. Semua Livewire membaca regu/kelompok/status dari Participation langsung
7. Export membaca dari Participation langsung

---

## 6. LegacyParticipationMapping

### Klasifikasi: B. ACTIVE LEGACY COMPATIBILITY

### Tujuan Awal
Bridge multi-event: satu `peserta` dapat memiliki banyak `Participation` di banyak `Event`.

### Tujuan Saat Ini
Runtime bridge aktif untuk multi-event support. Setiap Case B (Person existing + Event baru) membuat record baru.

### Siapa yang Membuat Record
- `RegistrationService::createParticipant()` — Case A dan Case B
- `LegacyParticipationBackfillService` — backfill
- `TambahPeserta` Livewire — `tambahkanKeEvent()` method

### Siapa yang Membaca Record
- `LegacyParticipationResolver` — semua method resolusi (by peserta, person, participation, attendance_code, NIP)
- `AttendanceService::resolveIdentity()` — resolve melalui resolver
- `ParticipationResolver` — wrapper untuk resolver
- `QRIdentityResolver` — resolve QR code
- `HapusPeserta` Livewire — cek surviving participation
- `Scan` Livewire — search manual fallback
- `DesignCDiagnostics` — integrity check

### Apakah Masih Diperlukan untuk Database Baru?
**YA.** Setiap registrasi peserta ke event baru membuat LegacyParticipationMapping. Seluruh attendance resolution chain bergantung padanya.

### Syarat Penghapusan
1. AttendanceService harus bisa resolve identity tanpa mapping (cukup Participation.attendance_code)
2. Semua resolver harus menggunakan Participation langsung
3. Multi-event tracking harus via Participation.event_id langsung (sudah)
4. HapusPeserta harus menggunakan logika Participation saja tanpa mapping pointer

### Catatan Penting
LegacyParticipationMapping sebenarnya sudah bisa dieliminasi jika:
- AttendanceCode sudah unique secara global (bisa cari Participation langsung — sudah)
- NIP tidak digunakan sebagai fallback attendance identifier
- Semua peserta sudah punya Participation record
- **Semua kondisi di atas sudah terpenuhi untuk data baru** → LegacyParticipationMapping adalah kandidat REMOVE pertama jika seluruh caller direfactor.

---

## 7. Registration Architecture

### Case A — Person Baru + Event Baru

Flow `RegistrationService::createParticipant()`:

```
Input: nama, nip, jenis_kelamin, jenis_peserta, desa_id, kelompok_id, regu_id, status_registrasi

1. Cari Person by (nama + desa + kelompok)
2. Tidak ditemukan → Case A
3. Generate participant_number (via PlacementService)
4. Generate attendance_code (KJA-XXXXXXXX)
5. Validasi unique constraints

Records Created:
  a. peserta (semua field)
  b. Person (nama, nip, jenis_kelamin, desa_id, kelompok_id)
  c. Participation (person_id, event_id, participant_number, attendance_code, jenis_peserta)
  d. LegacyPesertaMapping (peserta_id, person_id, participation_id, event_id, legacy_nip, dll)
  e. LegacyParticipationMapping (peserta_id, person_id, participation_id, event_id)

Return: peserta model (bukan Person/Participation)
```

**Masalah:**
- Return type `peserta` memaksa semua caller membawa dependency peserta
- `peserta` dibuat duluan sebelum Person (karena return type)
- NIP divalidasi unique di dua tabel
- Dual mapping dibuat untuk setiap record

### Case B — Person Existing + Event Berbeda

```
1. Cari Person by (nama + desa + kelompok)
2. Ditemukan → Case B
3. Cari peserta legacy by (nama + desa + kelompok) — WAJIB ditemukan
4. Jika tidak ditemukan → THROW ValidationException

Records Created:
  a. Participation (baru, event berbeda)
  b. LegacyParticipationMapping (baru, pointing ke peserta yang sama)

Return: peserta (existing, refresh)
```

**Masalah:**
- Masih MEWAJIBKAN `peserta` sudah ada — jika Person ada tapi peserta tidak ada, registrasi GAGAL
- Exception: "Legacy peserta compatibility record tidak ditemukan"
- Ini BUG untuk skenario Person dibuat di Event A via jalur non-RegistrationService (misal via API/manual)

### Case C — Person Existing + Event Sama

```
Detected → throw ValidationException:
"Person ini sudah terdaftar pada event ini."
```

**Benar — tidak ada perubahan.**

### Blocker untuk Simplifikasi

1. **RegistrationService return type** `peserta` — harus diubah ke `Participation`
2. **NIP generation** masih via `peserta::max('nip')` — harus pindah ke `Person`
3. **Unique constraint** masih di `pesertas.nama+desa+kelompok` — harus pindah ke `Participations` atau `People`
4. **LegacyPesertaMapping** masih wajib untuk regu/status_registrasi
5. **ManualParticipantRegistrationService** sudah hampir canonical (hanya Person + Participation tanpa peserta) — ini bisa jadi model target

---

## 8. Attendance Architecture

### Dependency Graph Textual

```
Attendance Scan
  │
  ├── Identifier (attendance_code / NIP)
  │     │
  │     ├── Cari Participation by attendance_code (canonical)
  │     │     └── Ditemukan → EventAttendance (canonical) + Absensi (legacy if flag)
  │     │
  │     ├── Cari peserta by attendance_code (legacy fallback 1)
  │     │     └── Ditemukan → resolve via LegacyParticipationMapping
  │     │           └── Dapat Participation → EventAttendance + Absensi
  │     │
  │     ├── Cari via LegacyParticipationMapping by legacy_attendance_code (legacy fallback 2)
  │     │     └── Ditemukan → Participation → EventAttendance + Absensi
  │     │
  │     └── Cari peserta by NIP (legacy fallback 3)
  │           └── Ditemukan → resolve via LegacyParticipationMapping
  │                 └── Dapat Participation → EventAttendance + Absensi
  │
  └── Semua gagal → "Data peserta tidak ditemukan"

Attendance Read (Rekap/Dashboard)
  │
  ├── Ambil semua Participation by event
  │     └── Untuk setiap participation:
  │           ├── Cek EventAttendance (canonical)
  │           ├── Cek Absensi by NIP (legacy fallback)
  │           └── Cek IzinAbsensi by peserta_id (legacy fallback)
  │
  └── Resolve status: hadir/izin/belum

Attendance Izin (Manual/Surat Izin)
  │
  ├── Jika participation_id tersedia:
  │     └── EventAttendance (canonical, status=izin) + IzinAbsensi (legacy if flag)
  │
  └── Jika hanya peserta_id:
        └── IzinAbsensi (legacy-only) + EventAttendance jika bisa resolve participation
```

### Jawaban Bagian 5

1. **Canonical path:** Participation → EventAttendance via attendance_code
2. **Legacy path:** peserta → Absensi/IzinAbsensi via NIP/peserta_id
3. **Absensi masih ditulis runtime:** YA — jika `ATTENDANCE_LEGACY_WRITE=true` (default)
4. **EventAttendance sudah cukup:** YA — untuk data baru. Legacy fallback masih diperlukan untuk historical
5. **IzinAbsensi masih butuh peserta_id:** YA — untuk dual-write dan legacy fallback
6. **NIP fallback masih dibutuhkan:** YA — untuk legacy attendance records dan QR yang masih encode NIP
7. **QR sudah 100% attendance_code:** TIDAK — QRIdentityResolver masih fallback ke legacy attendance_code dan NIP
8. **Penghalang:**
   - Feature flag `attendance_legacy_write=true`
   - Absensi table masih dibaca untuk rekap/dashboard
   - IzinAbsensi masih ditulis via AttendanceExceptionService
   - NIP fallback di QR resolver

---

## 9. Regu & Status Registrasi

### Klasifikasi: Keduanya hanya ada di tabel `pesertas`

### Temuan

| Field | Tabel | Global/Event-Scoped | Seharusnya |
|---|---|---|---|
| `regu_id` | `pesertas` | GLOBAL (satu regu untuk semua event) | EVENT-SCOPED di `participations` |
| `status_registrasi` | `pesertas` | GLOBAL (satu status untuk semua event) | EVENT-SCOPED di `participations` |

### Dampak Global Field

**Jika Person ikut Event A dan Event B:**
- `regu_id` di `pesertas` adalah nilai yang SAMA untuk kedua event
- `status_registrasi` di `pesertas` adalah nilai yang SAMA untuk kedua event
- Regu untuk Event A bisa "bocor" ke Event B
- Status registrasi Event A bisa memengaruhi laporan Event B

### Caller

| Caller | Read/Write | Field |
|---|---|---|
| `PlacementService::leastFilledRegu()` | READ | `regu::withCount('peserta')` |
| `RegistrationService::createParticipant()` | WRITE | regu_id, status_registrasi |
| `SelfRegister` Livewire | WRITE | regu_id, status_registrasi |
| `Ulang` Livewire | WRITE | status_registrasi |
| `EditPeserta` Livewire | WRITE | regu_id |
| `Database` Peserta Livewire | READ | regu_id via mapping |
| `RekapPeserta` Livewire | READ | regu, status_registrasi via mapping |
| `RekapAbsensi` Livewire | READ | regu via mapping |
| `PesertaExport` | READ | regu, status_registrasi via mapping |
| `Dashboard` Livewire | READ | regu via mapping |
| `HapusPeserta` Livewire | READ | regu_id via mapping |

### Rekomendasi
Migrasikan `regu_id` dan `status_registrasi` ke tabel `participations`:
- `participations.regu_id` → event-scoped regu
- `participations.status_registrasi` → event-scoped status

---

## 10. NIP & Identifier

### Kondisi Saat Ini

| Identifier | Canonical | Legacy | Masih Aktif |
|---|---|---|---|
| NIP (1001+/2001+) | `Person.nip` + `peserta.nip` | Dual storage | YA |
| participant_number (KL001+/KP001+) | `Participations.participant_number` | `peserta.participant_number` | YA (dual) |
| attendance_code (KJA-XXXXXXXX) | `Participations.attendance_code` | `peserta.attendance_code` | YA (dual) |

### Siapa yang Generate NIP

- `PlacementService::legacyNextNip()` — membaca `peserta::max('nip')` untuk menentukan NIP berikutnya
- Dipanggil oleh `RegistrationService::createParticipant()` via `autoPlacement()`
- Juga dipanggil oleh `SelfRegister` dan `TambahPeserta` Livewire

### Siapa yang Membaca NIP

- `AttendanceService::resolveIdentity()` — fallback mencari peserta by NIP
- `AttendanceReadService` — fallback read Absensi by NIP
- `LegacyParticipationResolver::resolveByLegacyNip()` — resolve via NIP
- `ParticipationResolver::resolveByNip()` — wrapper
- `SuratIzinService::approve()` — cek duplikat via NIP
- `Scan` Livewire — menampilkan NIP di UI
- `RekapPeserta` — menampilkan NIP di UI
- `PesertaExport` — mengekspor NIP
- `Ulang` Livewire — search by NIP
- `Database` Peserta Livewire — search by NIP
- `SuratIzin/Create` Livewire — search by NIP
- `QRIdentityResolver` — fallback resolve by NIP
- `AuditLegacyData` command — audit NIP distribution

### Apakah NIP Bisa Dihapus?

**Untuk data baru:** NIP tidak diperlukan — `attendance_code` sudah cukup sebagai identifier attendance, `participant_number` sudah cukup sebagai identifier peserta per event, Person.id sudah cukup sebagai identitas global.

**Untuk runtime:** NIP masih diperlukan karena:
1. Absensi table menggunakan `nip` sebagai foreign key (bukan ID)
2. AttendanceService masih fallback ke NIP
3. QRIdentityResolver masih fallback ke NIP
4. Semua attendance read masih fallback ke Absensi via NIP
5. SuratIzinService masih cek duplikat via NIP

**Rekomendasi:** NIP sebaiknya dipertahankan sebagai **optional legacy identifier** di Person (nullable) tetapi tidak perlu di `peserta` setelah `peserta` dihapus. Untuk data baru, NIP bisa `null` (ManualParticipantRegistrationService sudah mulai dengan `'nip' => null`).

---

## 11. Legacy Commands & Backfill

### Daftar Command

| Command | Tujuan | Klasifikasi |
|---|---|---|
| `backfill:legacy-peserta` | Backfill peserta → Person + Participation + Mapping | ARCHIVE — hanya untuk migrasi DB lama |
| `backfill:legacy-participation` | Backfill LegacyParticipationMapping | ARCHIVE — hanya untuk migrasi DB lama |
| `backfill:person-kelompok` (app:backfill-person-kelompok) | Sync kelompok_id dari peserta ke Person | ARCHIVE — hanya untuk migrasi DB lama |
| `app:rebuild-legacy-mappings` | Rebuild LegacyPesertaMapping via NIP match | ARCHIVE — hanya untuk migrasi DB lama |
| `attendance:backfill` | Backfill Absensi/IzinAbsensi → EventAttendance | ARCHIVE — hanya untuk migrasi DB lama |
| `surat-izin:backfill` | Backfill participation_id ke SuratIzin | ARCHIVE — hanya untuk migrasi DB lama |
| `app:reset-event-data` | Reset data operasional, preserve master | KEEP — berguna untuk testing/reset |
| `attendance:diagnose` | Diagnose unmappable attendance records | KEEP — diagnostic tooling |
| `attendance:parity` | Compare legacy vs canonical attendance | KEEP — integrity check |
| `attendance:status` | Attendance status summary | KEEP — monitoring tool |
| `audit:legacy-data` | Read-only audit legacy data quality | KEEP — masih berguna |
| `diagnose:design-c` | Design C integrity diagnostic | MUST KEEP — integrity diagnostic |
| `app:create-desa-grant` (CreateDesaGrant) | Create DesaAccessGrant for Pengajian | KEEP — fitur aktif |
| `app:database-info` (DatabaseInfo) | Database info | KEEP — tool |
| `app:set-user-role` (UserSetRole) | Set user role | KEEP — admin tool |

### `diagnose:design-c`

Command ini memeriksa integrity bridge (LegacyPesertaMapping + LegacyParticipationMapping). Setelah legacy layer dihapus, command ini bisa disederhanakan menjadi participation integrity check saja. Untuk saat ini, **MUST KEEP**.

---

## 12. Legacy Tests

### Klasifikasi Test

| Kategori | Jumlah Test | Contoh | Konsekuensi |
|---|---|---|---|
| 1. Canonical regression | ~60% | PersonFoundation, ParticipationFoundation, EventFoundation, AttendanceCanonicalTest | KEEP — aman |
| 2. Legacy runtime compat | ~25% | LegacyParticipationBridgeFoundation, LegacyPesertaMappingFoundation, AttendanceDualWrite, HapusPesertaSafety | KEEP sementara — akan gagal jika legacy dihapus sebelum refactor caller |
| 3. Historical migration | ~10% | AttendanceBackfillFoundation, AttendanceMigrationVerification, PersonKelompokBackfill, LegacyPesertaBackfill | Kandidat ARCHIVE — tidak berguna untuk fresh DB |
| 4. Perlu ditulis ulang | ~5% | RegistrationServiceTest (return type peserta), SuratIzinCanonicalTest (masih via peserta) | Perlu update setelah canonical migration |
| 5. Obsolete | Minimal | - | Tidak ditemukan test yang sepenuhnya obsolete |

### Konsekuensi terhadap Baseline 1570 Tests

- Jika legacy layer dihapus tanpa refactor caller: ~25% test akan FAIL
- Jika hanya legacy commands dihapus: ~10% test akan FAIL
- Baseline aman jika refactor caller didahulukan

---

## 13. Dead Code Candidates

| File/Class/Method | Kategori | Caller Ditemukan | Runtime Aktif | Aman Dihapus Sekarang | Alasan |
|---|---|---|---|---|---|
| `app/Console/Commands/BackfillLegacyPeserta.php` | ARCHIVE | Tidak ada (hanya migrasi) | TIDAK | YA (untuk fresh DB) | Hanya backfill historical |
| `app/Console/Commands/BackfillLegacyParticipation.php` | ARCHIVE | Tidak ada | TIDAK | YA (untuk fresh DB) | Hanya backfill historical |
| `app/Console/Commands/BackfillPersonKelompok.php` | ARCHIVE | Tidak ada | TIDAK | YA (untuk fresh DB) | Hanya backfill historical |
| `app/Console/Commands/RebuildLegacyMappings.php` | ARCHIVE | Tidak ada | TIDAK | YA (untuk fresh DB) | Hanya rebuild historical |
| `app/Console/Commands/AttendanceBackfill.php` | ARCHIVE | Tidak ada | TIDAK | YA (untuk fresh DB) | Hanya backfill historical |
| `app/Console/Commands/SuratIzinBackfill.php` | ARCHIVE | Tidak ada | TIDAK | YA (untuk fresh DB) | Hanya backfill historical |
| `app/Services/Migration/LegacyPesertaBackfillService.php` | ARCHIVE | Hanya BackfillLegacyPeserta | TIDAK | YA | Service migrasi |
| `app/Services/Migration/LegacyParticipationBackfillService.php` | ARCHIVE | Hanya BackfillLegacyParticipation | TIDAK | YA | Service migrasi |
| `app/Services/Migration/BackfillReport.php` | ARCHIVE | Hanya LegacyPesertaBackfillService | TIDAK | YA | Report class |
| `app/Services/Migration/BackfillReportItem.php` | ARCHIVE | Hanya LegacyPesertaBackfillService | TIDAK | YA | Report class |
| `app/Services/Person/PersonLegacySyncService.php` | NEEDS REVIEW | EditPeserta Livewire, IdentityCorrectionService | YA | TIDAK | Aktif digunakan |
| `app/Services/Attendance/AttendanceBackfillService.php` | NEEDS REVIEW | AttendanceBackfill command | YA (command) | TIDAK | Command bisa dihapus, service integrity |
| `app/Services/Attendance/SuratIzinBackfillService.php` | NEEDS REVIEW | SuratIzinBackfill command | YA (command) | TIDAK | Command bisa dihapus, service integrity |
| `app/Services/Attendance/LegacyParticipationResolver.php` | ACTIVE | 10+ callers | YA | TIDAK | Inti bridge architecture |
| `app/Services/Attendance/ParticipationResolver.php` | ACTIVE | 5+ callers | YA | TIDAK | Wrapper untuk resolver |
| `app/Services/Attendance/AttendanceParityService.php` | ACTIVE | AttendanceParity command | YA | TIDAK | Integrity tool |
| `app/Services/Placement/PlacementService.php` (legacyNextNip) | ACTIVE | RegistrationService, SelfRegister, TambahPeserta | YA | TIDAK | NIP generation |
| `app/Models/Absensi.php` | ACTIVE | AttendanceService, AttendanceReadService | YA | TIDAK | Dual-write aktif |
| `app/Models/IzinAbsensi.php` | ACTIVE | AttendanceExceptionService, SuratIzinService | YA | TIDAK | Dual-write aktif |
| `config/features.php` (`attendance_legacy_write`) | ACTIVE | AttendanceService, AttendanceExceptionService | YA | TIDAK | Feature flag aktif |
| `app/Models/regu.php` | ACTIVE | PlacementService, EditPeserta, rekap | YA | TIDAK | Regu masih digunakan |
| `app/Exports/PesertaExport.php` | ACTIVE | RekapPeserta Livewire | YA | TIDAK | Export aktif |
| `app/Imports/PesertaImport.php` | ACTIVE | ImportDataController | YA | TIDAK | Import aktif |
| `resources/views/livewire/database/peserta/*` | ACTIVE | Semua views | YA | TIDAK | UI aktif |
| `resources/views/livewire/rekap/peserta/*` | ACTIVE | Views | YA | TIDAK | UI aktif |
| `tests/Feature/LegacyPesertaMapping/*` | ACTIVE | Test suite | YA (test) | TIDAK | Test aktif |
| `tests/Feature/Database/AttendanceDualWrite*` | ACTIVE | Test suite | YA (test) | TIDAK | Test aktif |
| `tests/Feature/Database/AttendanceBackfill*` | ARCHIVE backfill | Test suite | YA (test) | YA (jika command dihapus) | Test backfill |

### Kandidat SAFE TO REMOVE NOW (untuk fresh DB)

**6 Commands:**
1. `app/Console/Commands/BackfillLegacyPeserta.php`
2. `app/Console/Commands/BackfillLegacyParticipation.php`
3. `app/Console/Commands/BackfillPersonKelompok.php`
4. `app/Console/Commands/RebuildLegacyMappings.php`
5. `app/Console/Commands/AttendanceBackfill.php`
6. `app/Console/Commands/SuratIzinBackfill.php`

**4 Service classes:**
1. `app/Services/Migration/LegacyPesertaBackfillService.php`
2. `app/Services/Migration/LegacyParticipationBackfillService.php`
3. `app/Services/Migration/BackfillReport.php`
4. `app/Services/Migration/BackfillReportItem.php`

**~10 Test files** (terkait backfill/migration historical)

**Total: ~10 file/class SAFE TO REMOVE NOW** (hanya untuk fresh DB — jika DB masih berisi data lama, command ini MUNGKIN masih diperlukan)

### Catatan Penting

Semua kandidat di atas aman dihapus **hanya karena database kosong**. Jika ada data lama yang perlu di-backfill, command ini masih berguna. Keputusan final tergantung pada apakah data lama akan dipulihkan atau tidak.

---

## 14. Fresh Database Runtime Analysis

### Record yang Tercipta untuk Data Baru

**Daftar "Budi" ke Event A:**
```
1. pesertas          (nama, nip, participant_number, attendance_code, jenis_kelamin, ...)
2. people            (nama, nip, jenis_kelamin, desa_id, kelompok_id)
3. participations    (person_id, event_id, participant_number, attendance_code, jenis_peserta)
4. legacy_peserta_mappings (peserta_id, person_id, participation_id, event_id, ...)
5. legacy_participation_mappings (peserta_id, person_id, participation_id, event_id, ...)
```

**Budi ikut Event B:**
```
1. participations    (person_id, event_id=2, ...)
2. legacy_participation_mappings (peserta_id, person_id, participation_id, event_id=2, ...)
```

**Budi absen Event A sesi 1:**
```
1. event_attendances (participation_id, sesi_absensi_id, status='hadir', ...)
2. absensis          (nip, nama, jam_scan, sesi_id) [jika ATTENDANCE_LEGACY_WRITE=true]
```

**Budi dihapus dari Event A:**
```
1. DELETE event_attendances WHERE participation_id = X
2. DELETE legacy_participation_mappings WHERE participation_id = X
3. UPDATE legacy_peserta_mappings SET participation_id = surviving_id WHERE peserta_id = Y
   (hanya jika ini participation terakhir — jika tidak, skip)
4. DELETE participations WHERE id = X
```

### Kesimpulan

Database kosong **tidak mengubah arsitektur runtime**. Dual architecture tetap aktif. Manfaat utama database kosong adalah:

1. **Aman mengubah schema** — tidak ada data produksi yang perlu di-migrate
2. **Tidak perlu backfill** — command migrasi tidak diperlukan
3. **Testing lebih bersih** — test bisa dimulai dari keadaan bersih

---

## 15. Gap Menuju Canonical Architecture

### Target

```
Person
  ├── Participation Event A
  │     ├── participant_number
  │     ├── attendance_code
  │     ├── regu_id (event-scoped)
  │     ├── status_registrasi (event-scoped)
  │     └── EventAttendance[]
  │
  └── Participation Event B
        ├── participant_number
        ├── attendance_code
        ├── regu_id (event-scoped)
        ├── status_registrasi (event-scoped)
        └── EventAttendance[]

Attendance:
  Participation → EventAttendance (status: hadir/izin)

Izin:
  Participation → EventAttendance (status: izin)
  (tidak ada izin_absensis)
```

### Gap Saat Ini

| Aspek | Saat Ini | Target | Gap |
|---|---|---|---|
| Identity | peserta + Person | Person saja | peserta harus dihapus |
| NIP | Person.nip + peserta.nip | Person.nip nullable | Dual storage |
| participant_number | Participations + peserta.participant_number | Participation saja | Dual storage |
| attendance_code | Participations + peserta.attendance_code | Participation saja | Dual storage |
| regu_id | peserta.regu_id (global) | Participation.regu_id (event-scoped) | Field & migration |
| status_registrasi | peserta.status_registrasi (global) | Participation.status_registrasi (event-scoped) | Field & migration |
| Attendance write | EventAttendance + Absensi | EventAttendance saja | Feature flag & service |
| Attendance izin | EventAttendance + IzinAbsensi | EventAttendance saja | Service |
| Surat Izin FK | peserta_id + participation_id | participation_id saja | Form & service |
| QR resolve | attendance_code NIP legacy_code | attendance_code saja | Resolver |
| Registration return | peserta model | Participation model | Service |
| Bridge tables | LegacyPesertaMapping, LegacyParticipationMapping | Tidak ada | Full removal |
| Replacement flow | peserta sebagai slot holder | Person + Participation | Service redesign |
| Import | RegistrationService via peserta | ManualParticipantRegistrationService | Import adapter |
| Export | PesertaExport via mapping | ParticipationExport langsung | New export class |
| Rekap UI | Baca regu/kelompok via mapping | Baca dari Participation fields | Livewire & views |
| NIP generation | peserta::max('nip') | Person::max('nip') or auto-increment | PlacementService |
| Regu placement | peserta.regu_id → regu::withCount('peserta') | Participation.regu_id → regu::withCount('participations') | PlacementService |

### Gap Severity

| Gap | Severity | Effort |
|---|---|---|
| Registration return type | HIGH | 1 sprint |
| regu_id + status_registrasi migration | HIGH | 1 sprint |
| NIP dual storage | MEDIUM | 0.5 sprint |
| AttendanceResolve chain refactor | HIGH | 2 sprint |
| Surat Izin peserta_id removal | MEDIUM | 1 sprint |
| Bridge table removal | HIGH | 2 sprint |
| Replacement flow redesign | HIGH | 1 sprint |
| Export/Import canonicalization | MEDIUM | 1 sprint |
| Rekap UI canonicalization | MEDIUM | 1 sprint |
| Feature flag default false | LOW | 0.5 sprint |

---

## 16. Risk Matrix

| Legacy Component | Risiko Dihapus | Dampak | Prasyarat | Rekomendasi |
|---|---|---|---|---|
| `peserta` table | CRITICAL | Semua attendance, surat izin, rekap, dashboard, QR, export, import, replacement FAIL | Migrasi regu_id, status_registrasi ke Participation + refactor semua caller | JANGAN dihapus dulu |
| `LegacyPesertaMapping` | CRITICAL | Sama seperti peserta + resolver chain FAIL | Refactor resolver, attendance read, hapus logika surviving participation | JANGAN dihapus dulu |
| `LegacyParticipationMapping` | HIGH | Multi-event resolve, attendance scan, QR FAIL | Refactor resolver, attendance resolve via direct Participation | Bisa dihapus setelah resolver direfactor |
| `Absensi` table | HIGH | Attendance read rekap gagal jika masih fallback | Nonaktifkan `attendance_legacy_write`, refactor AttendanceReadService | Bisa mulai nonaktifkan write |
| `IzinAbsensi` table | HIGH | Izin read gagal | Sama seperti Absensi | Bisa mulai nonaktifkan write |
| NIP | MEDIUM | Attendance fallback, QR fallback, SuratIzin cek duplikat | Pindah fallback ke attendance_code, hapus Absensi dependency | Pertahankan sebagai Person.nip nullable |
| `regu_id` di peserta | HIGH | Regu placement, rekap, dashboard, export | Migrasi ke participations.regu_id | Harus migrasi dulu |
| `status_registrasi` di peserta | LOW | Hanya rekap dan export | Migrasi ke participations.status_registrasi | Migrasi mudah |
| Backfill commands | LOW | Tidak ada (fresh DB) | Tidak ada | Aman dihapus untuk fresh DB |
| `attendance_legacy_write` flag | MEDIUM | Jika dimatikan, Absensi stop ditulis | Pastikan semua read sudah canonical | Aman mulai dimatikan (default false) |

---

## 17. Komponen yang Aman Dihapus Sekarang

### Syarat: Database dalam keadaan kosong (reset)

| Komponen | Alasan |
|---|---|
| `app/Console/Commands/BackfillLegacyPeserta.php` | Hanya untuk migrasi data lama — tidak berguna untuk fresh DB |
| `app/Console/Commands/BackfillLegacyParticipation.php` | Sama |
| `app/Console/Commands/BackfillPersonKelompok.php` | Sama |
| `app/Console/Commands/RebuildLegacyMappings.php` | Sama |
| `app/Console/Commands/AttendanceBackfill.php` | Sama |
| `app/Console/Commands/SuratIzinBackfill.php` | Sama |
| `app/Services/Migration/LegacyPesertaBackfillService.php` | Service hanya dipanggil oleh command di atas |
| `app/Services/Migration/LegacyParticipationBackfillService.php` | Sama |
| `app/Services/Migration/BackfillReport.php` | Sama |
| `app/Services/Migration/BackfillReportItem.php` | Sama |
| `database/migrations/2026_07_01_000001_add_status_registrasi_to_pesertas_table.php` | Hanya untuk legacy — schema sudah include field ini |
| `database/migrations/2026_07_01_000002_make_jenis_kelamin_nullable_on_pesertas_table.php` | Hanya untuk legacy — sudah include |
| `database/migrations/2026_07_01_000003_add_jenis_kelamin_to_regus_table.php` | Hanya untuk legacy — sudah include |
| `database/migrations/2026_07_02_000001_add_unique_constraints_to_tables.php` | Hanya untuk legacy — sudah include |
| `database/migrations/2026_07_03_000001_add_unique_participant_identity_to_pesertas_table.php` | Hanya untuk legacy — sudah include |
| `database/migrations/2026_07_07_125113_add_jenis_peserta_to_pesertas_table.php` | Hanya untuk legacy — sudah include |
| `database/migrations/2026_07_15_000001_add_identity_columns_to_pesertas_table.php` | Hanya untuk legacy — sudah include |

**Catatan:** Migration di atas bisa dihapus karena field-field tersebut sudah menjadi bagian dari `create_peserta_table.php` asli (atau di-squash). Tapi perlu verifikasi bahwa `create_peserta_table.php` sudah memiliki field-field tersebut. Jika tidak, migration ini harus dipertahankan untuk fresh install.

### Migration yang Harus Dicek

File `2025_06_16_071812_create_peserta_table.php` perlu dicek apakah sudah memiliki:
- `regu_id` (FK)
- `status_registrasi`
- `jenis_kelamin` (nullable)
- `participant_number`
- `attendance_code`
- `jenis_peserta`
- Unique constraints

Jika belum, migration tambahan masih diperlukan untuk fresh install.

---

## 18. Komponen yang Harus Direfactor Sebelum Dihapus

| Komponen | Refactor yang Diperlukan | Prioritas |
|---|---|---|
| `RegistrationService::createParticipant()` | Ubah return type ke Participation, hentikan create peserta | HIGH |
| `AttendanceService::resolveIdentity()` | Hapus fallback ke peserta, hanya pakai Participation.attendance_code | HIGH |
| `AttendanceReadService::getSessionAttendance()` | Hanya baca EventAttendance, hapus fallback Absensi/IzinAbsensi | HIGH |
| `AttendanceExceptionService::recordIzin()` | Hanya tulis EventAttendance, hapus fallback IzinAbsensi | HIGH |
| `SuratIzinService` | Hapus dependency peserta_id, hanya pakai participation_id | HIGH |
| `Scan` Livewire | Hapus search legacy peserta, hanya pakai Participation | MEDIUM |
| `Database` Peserta Livewire | Baca regu/kelompok dari Participation langsung | MEDIUM |
| `EditPeserta` Livewire | Update regu_id di Participation, bukan peserta | MEDIUM |
| `HapusPeserta` Livewire | Hapus logika surviving LegacyPesertaMapping | MEDIUM |
| `GantiPeserta` Livewire + `CaiParticipantReplacementService` | Redesign tanpa peserta sebagai slot | HIGH |
| `RekapPeserta` Livewire + `PesertaExport` | Baca data dari Participation + Person langsung | MEDIUM |
| `RekapAbsensi` Livewire | Baca regu dari Participation langsung | MEDIUM |
| `SuratIzin/Create` Livewire | Hapus search legacy peserta | MEDIUM |
| `PesertaImport` | Adapter ke RegistrationService canonical | MEDIUM |
| `PlacementService::legacyNextNip()` | Generate NIP dari Person, bukan peserta | MEDIUM |
| `PlacementService::leastFilledRegu()` | Hitung dari Participation.regu_id, bukan peserta.regu_id | MEDIUM |
| `SelfRegister` Livewire | Hapus referensi peserta | MEDIUM |
| `Ulang` Livewire | Hapus search dan update peserta | LOW |
| `Dashboard` Livewire | Baca regu dari Participation | LOW |
| `PersonLegacySyncService` | Tidak diperlukan lagi | LOW |
| `IdentityCorrectionService::syncLegacyPeserta()` | Hapus sync ke peserta | LOW |
| `QRIdentityResolver` | Hapus fallback NIP dan legacy_code | MEDIUM |
| `DesignCDiagnostics` | Sederhanakan jadi participation integrity check | LOW |
| `AuditLegacyData` | Hapus atau sederhanakan | LOW |

---

## 19. Komponen yang Masih Wajib Dipertahankan

| Komponen | Alasan |
|---|---|
| `App\Models\Person` | Canonical identity — inti arsitektur |
| `App\Models\Participation` | Canonical keikutsertaan — inti arsitektur |
| `App\Models\EventAttendance` | Canonical attendance — inti arsitektur |
| `App\Models\Event` | Canonical event |
| `App\Models\SesiAbsensi` | Attendance session — masih canonical |
| `App\Models\SuratIzin` | Surat izin — masih digunakan, perlu refactor FK |
| `App\Models\desa` | Master data desa |
| `App\Models\kelompok` | Master data kelompok |
| `App\Models\regu` | Masih diperlukan — tapi perlu migrasi FK |
| `App\Models\CaiParticipantReplacement` | Audit trail replacement |
| `App\Models\IdentityCorrectionRequest` | Identity correction flow |
| `App\Models\DesaAccessGrant` | Pengajian access token |
| `App\Services\Registration\ManualParticipantRegistrationService` | Model target untuk canonical registration |
| `App\Services\Pengajian\PengajianAttendanceService` | Already canonical (hanya Person + Participation) |
| `App\Services\Attendance\AttendanceService` | Inti — perlu refactor bukan hapus |
| `App\Services\QR\QRService` | QR generation — canonical |
| `App\Services\QR\QRIdentityResolver` | QR resolve — perlu refactor fallback |
| `App\Console\Commands\DesignCDiagnostics` | Integrity diagnostic — tetap berguna |
| `App\Console\Commands\AuditLegacyData` | Audit tool — tetap berguna untuk data quality |
| `App\Console\Commands\ResetEventData` | Testing/reset tool |
| `App\Console\Commands\AttendanceDiagnose` | Diagnostic tool |
| `App\Console\Commands\AttendanceParity` | Integrity check |
| `App\Console\Commands\AttendanceStatus` | Monitoring tool |

---

## 20. Rekomendasi Scope PGM.18

### Keputusan: Opsi C — Gabungan

**PGM.18 — Legacy Code Removal & Canonical Architecture Completion**

Alasan:
1. **Database V2 Part 6 (Legacy Dependency Remediation)** terlalu sempit — hanya fokus pada Database V2
2. **Legacy Code Removal** terlalu luas — ada komponen legacy yang masih wajib dipertahankan (seperti regu, yang bukan Database V2 scope)
3. **Gabungan** memungkinkan kita menyelesaikan Database V2 sambil membersihkan arsitektur secara menyeluruh

### Scope PGM.18 yang Direkomendasikan

1. **Hapus backfill/migration commands** (6 commands, 4 services) — aman karena DB kosong
2. **Migrasi `regu_id` dan `status_registrasi`** dari `pesertas` ke `participations`
3. **Refactor RegistrationService** — hentikan create peserta untuk data baru
4. **Nonaktifkan `attendance_legacy_write`** (default false)
5. **Refactor AttendanceService** — hapus fallback ke peserta/Absensi
6. **Refactor AttendanceReadService** — hanya baca EventAttendance
7. **Refactor AttendanceExceptionService** — hanya tulis EventAttendance
8. **Refactor SuratIzinService** — hanya pakai participation_id
9. **Refactor Scan Livewire** — hapus legacy search
10. **Refactor rekap/export/dashboard** — baca dari Participation langsung
11. **Hapus LegacyPesertaMapping + LegacyParticipationMapping** (setelah caller direfactor)
12. **Hapus peserta table** (setelah semua caller direfactor)
13. **Hapus Absensi + IzinAbsensi table** (setelah write dihentikan)
14. **Sederhanakan NIP** — Person.nip nullable, hentikan dual storage
15. **Update test suite** — sesuaikan dengan arsitektur baru

---

## 21. Urutan Sprint PGM.18

### Sprint 1 — Dependency Audit & Preparation
- Hapus 6 backfill commands + 4 migration services (SAFE TO REMOVE NOW)
- Hapus migration legacy yang redundant (jika sudah di-squash)
- Nonaktifkan `ATTENDANCE_LEGACY_WRITE=false`
- Update dokumentasi

### Sprint 2 — Participation Event-Scoped Fields
- Migration: tambah `participations.regu_id` (nullable FK ke regus)
- Migration: tambah `participations.status_registrasi` (string, nullable)
- Migration: tambah `participations.nip` (string, nullable — untuk legacy compat)
- Update PlacementService: regu placement via participations
- Update model Participation dengan field baru

### Sprint 3 — Registration Canonicalization
- Ubah `RegistrationService::createParticipant()` return type ke `Participation`
- Hentikan pembuatan `peserta` untuk Case A
- Hentikan pembuatan `LegacyPesertaMapping` untuk Case A
- Hentikan pembuatan `LegacyParticipationMapping` untuk Case A dan B
- Update validasi unique constraint ke people + participations
- Update `ManualParticipantRegistrationService` sebagai model target
- Update `SelfRegister`, `TambahPeserta`, `PesertaImport` Livewire

### Sprint 4 — Attendance Canonicalization
- Refactor `AttendanceService::resolveIdentity()` — hanya pakai Participation
- Refactor `AttendanceService::processScan()` — hanya tulis EventAttendance
- Refactor `AttendanceExceptionService::recordIzin()` — hanya tulis EventAttendance
- Refactor `AttendanceReadService::getSessionAttendance()` — hanya baca EventAttendance
- Hapus fallback NIP di AttendanceIdentity
- Update Scan Livewire

### Sprint 5 — Surat Izin & QR Canonicalization
- Refactor `SuratIzinService` — hapus peserta_id dependency
- Refactor `SuratIzin/Create` Livewire — hapus legacy search
- Refactor `SuratIzin/Index` Livewire — hapus fallback
- Refactor `QRIdentityResolver` — hapus fallback NIP & legacy_code
- Update surat izin migration (hapus peserta_id FK)

### Sprint 6 — Rekap, Export, Dashboard Canonicalization
- Refactor `RekapPeserta` Livewire — baca dari Participation
- Refactor `RekapAbsensi` Livewire — baca dari Participation
- Refactor `PesertaExport` — baca dari Participation
- Refactor `Dashboard` Livewire — baca dari Participation
- Update views

### Sprint 7 — Legacy Table Removal
- Hapus `LegacyPesertaMapping` model + migration
- Hapus `LegacyParticipationMapping` model + migration
- Hapus `peserta` model + migration
- Hapus `Absensi` model + migration
- Hapus `IzinAbsensi` model + migration
- Hapus `LegacyParticipationResolver`
- Hapus `ParticipationResolver`
- Hapus `PersonLegacySyncService`
- Hapus `AttendanceBackfillService`
- Hapus `SuratIzinBackfillService`
- Migration: hapus FK yang refer ke peserta
- Migration: squash/create fresh migrations untuk clean install

### Sprint 8 — Test & Documentation Closure
- Update test suite — hapus test legacy dual-write
- Archive test backfill/migration
- Update test untuk arsitektur baru
- Update dokumentasi (INDEX, CURRENT_STATE, ROADMAP, dll)
- Update CI pipeline
- Run full regression: target 1570+ test tetap pass

---

## 22. Kesimpulan

### Jawaban atas 12 Pertanyaan

**1. Apakah `peserta` legacy masih diperlukan untuk runtime data baru?**
**YA.** `peserta` masih menjadi wajib runtime dependency untuk: RegistrationService (return type), regu_id, status_registrasi, NIP generation, attendance fallback, surat izin, replacement flow, export, rekap, dashboard.

**2. Apakah `LegacyPesertaMapping` masih diperlukan?**
**YA.** Setiap registrasi baru membuat mapping baru. Seluruh attendance read path bergantung padanya untuk resolve regu/kelompok/status_registrasi.

**3. Apakah `LegacyParticipationMapping` masih diperlukan?**
**YA.** Digunakan oleh LegacyParticipationResolver untuk resolve identity multi-event. Namun ini adalah kandidat REMOVE PERTAMA setelah resolver direfactor.

**4. Apakah `absensi` legacy masih diperlukan?**
**YA (read).** Write bisa dinonaktifkan via feature flag, tetapi read masih fallback ke absensi untuk attendance rekap. Setelah AttendanceReadService direfactor, absensi bisa dihapus.

**5. Apakah `izin_absensis` legacy masih diperlukan?**
**YA (read).** Sama seperti absensi — write bisa dinonaktifkan, read masih fallback.

**6. Apakah NIP masih diperlukan?**
**YA, sebagai optional legacy identifier.** NIP masih digunakan oleh: attendance fallback, QR fallback, surat izin cek duplikat, search UI. Bisa dikurangi perannya tapi tidak aman dihapus total sebelum attendance/surat-izin canonical selesai.

**7. Logic apa yang SAFE TO REMOVE NOW?**
- 6 backfill/migration commands
- 4 migration service classes
- ~10 test files terkait backfill
- Beberapa migration legacy redundant (jika sudah di-squash)

**8. Logic apa yang NEEDS MIGRATION FIRST?**
- RegistrationService (return type, hentikan create peserta)
- AttendanceService (fallback chain)
- AttendanceReadService (legacy fallback)
- AttendanceExceptionService (dual-write)
- SuratIzinService (peserta_id dependency)
- Scan Livewire (legacy search)
- Rekap/Export/Dashboard (baca via mapping)
- GantiPeserta/Replacement flow (redesign)
- PlacementService (NIP generation + regu placement)

**9. Berapa banyak file/class/method kandidat dead code?**
- **SAFE TO REMOVE NOW:** ~10 file (6 commands + 4 services)
- **NEEDS MIGRATION FIRST:** ~30+ file (models, services, Livewire, views, routes)
- **MUST KEEP:** ~50+ file (canonical models, services, commands)

**10. Apakah database kosong secara signifikan mempermudah PGM.18?**
**Tidak signifikan.** Manfaat utama adalah tidak perlu backfill data lama dan aman mengubah schema. Namun seluruh caller masih harus direfactor — jumlah pekerjaan sama dengan database penuh. Estimasi: database kosong menghemat ~15% effort (terutama di testing dan migration data).

**11. Scope PGM.18 yang direkomendasikan apa?**
**Opsi C — Gabungan: Legacy Code Removal & Canonical Architecture Completion.** Scope mencakup Database V2 Part 6 ditambah migrasi regu_id, status_registrasi, dan canonicalization seluruh chain.

**12. Apa sprint pertama yang harus dikerjakan?**
**Sprint 1 — Dependency Audit & Preparation:**
- Hapus 6 backfill commands
- Hapus 4 migration services
- Nonaktifkan `ATTENDANCE_LEGACY_WRITE=false`
- Squash/review migration legacy
- Update dokumentasi dengan hasil audit ini

### Estimasi Total Effort

| Sprint | Fokus | Estimasi |
|---|---|---|
| Sprint 1 | Dependency Audit & Preparation | 2 hari |
| Sprint 2 | Participation Event-Scoped Fields | 3 hari |
| Sprint 3 | Registration Canonicalization | 5 hari |
| Sprint 4 | Attendance Canonicalization | 5 hari |
| Sprint 5 | Surat Izin & QR Canonicalization | 3 hari |
| Sprint 6 | Rekap, Export, Dashboard Canonicalization | 3 hari |
| Sprint 7 | Legacy Table Removal | 3 hari |
| Sprint 8 | Test & Documentation Closure | 4 hari |
| **Total** | | **28 hari kerja** |

### Disclaimer

Laporan ini bersifat READ-ONLY. Tidak ada perubahan kode, schema, atau file yang dilakukan selama audit. Semua rekomendasi di atas memerlukan review dan persetujuan sebelum implementasi dimulai.
