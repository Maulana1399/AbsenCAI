# Import Framework — Audit & GAP Analysis (Phase IF-01)

> Status: **AUDIT / DESIGN** — IF-01 (2026-08-05). IF-02 menghidupkan skeleton.
> **IF-03 memigrasi Desa**, **IF-04 memigrasi Kelompok**, **IF-05 memigrasi Regu** ke
> framework (status inventori untuk Desa/Kelompok/Regu di bawah sudah kedaluwarsa: kini 🟢 framework).
> Bagian 4 di bawah (audit skeleton) kini historis: seluruh stage no-op sudah digantikan
> implementasi nyata.
> Golden Standard: **Import Massal Pengajian** (`ImportMassal` + `PengajianImportService`).
> Desain arsitektur & roadmap: `docs/import-framework.md`.

---

## 1. Ringkasan Eksekutif

Seluruh aplikasi memiliki **5 fitur import** yang berjalan di runtime, **1 kerangka import
(skeleton) yang belum fungsional**, dan **belum ada import** untuk modul Person, Competition,
Kategori, Kelas, Venue, Schedule, Committee, Attendance, Activity, Rundown, maupun Access Grant.

Saat ini import terpecah menjadi **3 gaya implementasi berbeda**:

1. **Golden Standard (Pengajian)** — wizard Livewire 3 langkah (Upload → Preview/Validasi → Hasil)
   + Service layer dengan validasi per-baris, duplicate detection, summary, dan transaction.
2. **Legacy Master Data (Desa/Kelompok/Regu/Peserta)** — form HTML POST ke `ImportDataController`
   → langsung `Excel::import` (Maatwebsite `ToModel`) tanpa preview, tanpa summary, tanpa rollback.
3. **Skeleton Framework (`app/Services/Import/`)** — struktur sudah ada (Contracts, Pipeline,
   Registry, Coordinator, Adapters, DTO) tetapi seluruh stage pipeline **masih stub/no-op**
   dan tidak dipakai oleh Pengajian maupun legacy import untuk flow bisnisnya.

---

## 2. Inventori Seluruh Import

Legend Status: 🟢 = Golden Standard | 🟡 = Berjalan tapi belum sesuai standar | 🔴 = Stub/tidak fungsional | ⚪ = Belum ada (kandidat).

| # | Modul | Route | Livewire | Controller | Upload | Preview | Validation | Summary | Commit | Template | Test | Status |
|---|-------|-------|----------|-----------|--------|---------|------------|---------|--------|----------|------|--------|
| 1 | **Desa** | `POST /import/desa` (`import.desa`) | `ImportDesa` (wizard IF-03) | `ImportDataController@desa` | HTML POST + Livewire wizard | ✅ | ✅ | ✅ | `DesaImportCommitter` (create + skip duplikat) | **generator** `DesaImportTemplateExport` (DATA/PETUNJUK/REFERENSI) | `ImportDataTest` (1) + `DesaImportFrameworkTest` (14) + unit | 🟢 **FRAMEWORK (IF-03)** |
| 2 | **Kelompok** | `POST /import/kelompok` (`import.kelompok`) | `ImportKelompok` (wizard IF-04) | `ImportDataController@kelompok` | Livewire wizard + HTML POST (desa_id) | ✅ | ✅ | ✅ | `KelompokImportCommitter` (create + skip duplikat, scoped desa) | **generator** `KelompokImportTemplateExport` (REFERENSI = desa) | `ImportDataTest` (1) + `KelompokImportFrameworkTest` (14) + unit | 🟢 **FRAMEWORK (IF-04)** |
| 3 | **Regu** | `POST /import/regu` (`import.regu`) | `ImportRegu` (wizard base IF-05) | `ImportDataController@regu` | Livewire wizard + HTML POST | ✅ | ✅ | ✅ | `ReguImportCommitter` (create + skip duplikat nama) | **generator** `ReguImportTemplateExport` (REFERENSI = enum gender) | `ImportDataTest` (3) + `ReguImportFrameworkTest` (11) + unit | 🟢 **FRAMEWORK (IF-05)** |
| 4 | **Peserta** | `POST /import/peserta` (`import.peserta`) | `ImportPeserta` (vestigial) | `ImportDataController@peserta` | HTML POST | ❌ | ❌ | ❌ | `Excel::import(new PesertaImport)` via `PesertaImportCommitter` | statis `template_peserta.xlsx` | `ImportDataTest` (1 happy path) | 🟡 C |
| 5 | **Pengajian (Import Massal)** | `GET /events/{event}/pengajian/admin/import-massal` (+ template route) | `ImportMassal` (wizard 3 langkah) | n/a (Livewire) | `WithFileUploads` (csv,txt,xlsx,xls, max 5MB) | ✅ tabel + error per baris | ✅ per-baris (nama, JK, TTL, desa, kelompok) | ✅ created/matched/participation/duplicate/failed + errors | `PengajianImportService` (transaction per-baris, duplicate detection) | **generator** `PersonImportTemplateExport` (3 sheet: Template/Petunjuk/Master Data + dropdown validasi) | `ImportMassalFeatureTest` (15), `ImportMassalUploadEndpointTest` (2), `PengajianImportTest` (service ±20) | 🟢 **GOLDEN** |

### 2.1. Modul yang BELUM punya import (kandidat migrasi/baru)

| Modul | Model/Table | Keterangan |
|-------|-------------|------------|
| Person | `Person` (`people`) | Hanya CRUD (`MasterData/Person`); import tidak ada (Person import lama dihapus saat PGM.20). |
| Competition | `Competition*` | Tidak ada import peserta/cabang/kelas kompetisi. |
| Kategori | `CompetitionCategory` | CRUD `Competition/Category/Index`; tanpa import. |
| Kelas | `CompetitionClass` | CRUD `Competition/Class/Index`; tanpa import. |
| Venue | `Venue` | CRUD `Competition/Venue/Index`; tanpa import. |
| Schedule | `CompetitionSchedule` | CRUD `Schedule/Index`; tanpa import. |
| Committee | `EventCommitteeAssignment` / `EventRole` | CRUD `Event/CommitteeManagement`, `EventRoleManager`; tanpa import. |
| Attendance | `EventAttendance` / `Absensi` | Tanpa import; attendance via scan/manual. |
| Activity / Rundown | `Activity`, `Rundown`, `RundownItem` | Foundation S3.9; tanpa import. |
| Access Grant | `DesaAccessGrant` | `Pengajian/Admin/AccessIndex`; tanpa import. |

---

## 3. Perbandingan dengan Golden Standard

### 3.1. UI / UX

| Aspek | Golden (Pengajian) | Legacy (Desa/Kelompok/Regu/Peserta) |
|-------|--------------------|--------------------------------------|
| Layout | Wizard 3 langkah (step state) | Form inline satu baris |
| Button | Preview & Validasi → Import → Upload Ulang / Import Lagi | Pilih File + Import + Download Template |
| Upload | Dropzone/`wire:model="file"` + file terpilih + error upload | Input file tersembunyi + label nama file (vanilla JS `onchange`) |
| Preview | Tabel data + badge validasi sukses/gagal | ❌ tidak ada |
| Error | Per-baris, runcing (field + pesan) | Global session flash / error bag |
| Result | Summary grid (5 kartu statistik) + daftar error baris | Flash success saja |
| Template | Download tombol + link generator | `asset()` statis |
| Empty/no-event | Empty state khusus | Tidak ada |

### 3.2. Logic

| Aspek | Golden | Legacy | Skeleton |
|-------|--------|--------|----------|
| Parser | CSV/Excel manual + cek kolom wajib | Maatwebsite `WithHeadingRow` | Stub no-op |
| Normalisasi | trim/lowercase nama, JK uppercase | Hanya Regu (gender) | Stub no-op |
| Validasi | Validator per-baris + resolusi desa/kelompok | Regu via `WithValidation`; lainnya nol | Stub no-op |
| Duplicate detection | Person (nama+desa+TTL), Participation (person+event) | ❌ | Stub no-op |
| Transaction | Per-baris `DB::transaction` | Per-row Maatwebsite (tanpa rollback batch) | Stub no-op |
| Summary | Lengkap (5 metrik + errors) | ❌ | Struktur DTO ada |
| Rollback | ❌ (partial success, error per baris dilaporkan) | ❌ | ❌ |
| Activity Log | ❌ (belum) | ❌ | `ActivityLogStage` stub kosong |
| Auth | `Gate::authorize('manage-pengajian')` | `Gate::authorize` di controller + route middleware | n/a |

### 3.3. Template

| Aspek | Golden | Legacy |
|-------|--------|--------|
| Asal | Generator (`PersonImportTemplateExport`) | Hardcode statis di `public/templates/` |
| Sheet | 3 (Template, Petunjuk, Master Data tersembunyi) | 1 sheet |
| Data validation | Dropdown desa, kelompok dependen (INDIRECT), JK | ❌ |
| Kolom | nama, jenis_kelamin, tanggal_lahir, desa, kelompok | Sesuai tiap modul |
| Versi | `TemplateVersion` ada di skeleton tapi tidak dipakai | ❌ |

### 3.4. Testing

| Aspek | Golden | Legacy |
|-------|--------|--------|
| Feature test | 17 (wizard flow, upload endpoint) | 7 (1–4 per modul) |
| Service test | ±20 (`PengajianImportTest`) | ❌ |
| Unit test pipeline | ❌ | ❌ |
| Regression | Bagian dari baseline global | Bagian dari baseline global |

---

## 4. Audit Skeleton Framework (`app/Services/Import/`)

Struktur sudah didesain cukup baik, tetapi **belum fungsional**:

| Komponen | File | Kondisi |
|----------|------|---------|
| `Contracts/ImportDefinition` | import-definition | ✅ Desain interface baik (key, parser, validator, normalizer, dupe, committer, logger, version, supportsPreview/Commit) |
| `Pipeline/DefaultImportPipeline` | ✅ Alur urut benar (preflight→parser→normalize→validate→duplicate→preview→commit→activity-log) | ✅ |
| `Pipeline/*Stage` (8 class) | Preflight/Parser/Normalize/Validation/Duplicate/Preview/Commit/ActivityLogStage | 🔴 Semua **stub no-op** (hanya `return $payload`) |
| `Support/ArrayPipelineStageRunner` | 🔴 **no-op** — stage tidak pernah dieksekusi |
| `Registry/ImportRegistry` + `ImportCoordinator` | ✅ desain OK; ❌ **tidak ter-wire DI** — di-instantiate ad-hoc di `ImportDataController` & 4 Livewire import |
| `Adapters/Desa` | 🟡 Implementasi nyata tapi trivial (parser 1 row dummy, validator hitung jumlah) |
| `Adapters/Kelompok`, `Peserta`, `Regu` | 🔴 Parser/Validator/Normalizer/Dupe = `NullObject` (nol validasi, nol preview) |
| `Adapters/Pengajian` | 🔴 Hanya `PengajianImportCommitter` + `Definition`; **tidak ada** Parser/Validator/Normalizer/DuplicateDetector/ActivityLogger → definition tidak dapat di-instantiate; tidak dipakai runtime |
| `DTO/` vs `Results/` | 🔴 **Duplikasi** — `ImportSummary`, `ImportCommit`, `ImportPreview`, `ImportResult` ada di dua namespace berbeda → tipe data tidak konsisten |
| `ImportMassal` (golden) | 🔴 **Tidak memakai skeleton** — menggunakan `PengajianImportService` langsung |
| Livewire `ImportDesa/Kelompok/Peserta/Regu` | 🟡 Memakai coordinator tapi flow sebenarnya lewat form POST controller; method `import()` Livewire tidak pernah dipanggil oleh view |

**Kesimpulan:** Skeleton adalah **fondasi yang bagus namun mati**. Semua stage pipeline no-op,
runner tidak mengeksekusi apa pun, dan seluruh runtime import melewatinya.

---

## 5. GAP Analysis

### Kategori A — Sudah sesuai (pertahankan)

- **Pengajian import flow** sebagai golden standard: wizard 3 langkah, validasi per-baris, duplicate detection, summary, transaction per-baris.
- **Interface contracts** `ImportDefinition` + komponen parser/validator/normalizer/duplicateDetector/committer/logger/version.
- **Urutan pipeline** pada `DefaultImportPipeline` (8 tahap) sudah match kebutuhan golden UX dan pipeline yang diusulkan.
- **DTO baris** `RawImportRow` / `NormalizedImportRow` / `ImportError` / `ImportWarning` — model per-baris yang tepat.
- **Validasi file** (mimes csv,txt,xlsx,xls + max 5MB) dan `Gate::authorize` per aksi.
- **Generator template multi-sheet** (`PersonImportTemplateExport`) dengan dropdown validasi.

### Kategori B — Perlu sedikit penyesuaian

- **Wiring Registry/Coordinator** — ganti instansiasi ad-hoc di controller/Livewire dengan binding container (`AppServiceProvider`) / singleton `ImportRegistry`.
- **Propagasi `eventId`** — `ImportContext` sudah punya `eventId`; pastikan seluruh pemanggil (controller legacy, wizard) mengisinya konsisten.
- **Versioning** — `ImportVersion`/`TemplateVersion` sudah ada di contract; aktifkan (cek `accepts()` di parser/preview) + flag di template export.
- **Pesan validasi & Gate** — unifikasi pesan Bahasa Indonesia dan ability (`manage-master-data` / `manage-participants` / `manage-pengajian`); `manage-import` saat ini terdefinisi namun tidak dipakai route — putuskan retire atau aktifkan.
- **Empty state / no-active-event** — terapkan ke seluruh import, tidak hanya Pengajian.

### Kategori C — Perlu refactor sedang

- **Legacy import (Desa/Kelompok/Peserta)** — pindahkan dari `Excel::import` + Maatwebsite `ToModel` ke adapter framework (Parser + Validator + Normalizer + DuplicateDetector + Committer) dengan commit service-layer.
- **Regu import** — pertahankan normalizer gender-nya tapi jadikan `ImportNormalizer` framework (ganti `prepareForValidation`).
- **Template statis → generator** — `public/templates/*.xlsx` digantikan `ImportTemplate` generator per modul.
- **Livewire wrapper vestigial** — `ImportDesa/ImportKelompok/ImportPeserta/ImportRegu` dihapus setelah migrasi ke Wizard.

### Kategori D — Perlu dibangun ulang

- **Seluruh stage pipeline** (8 class) — implementasi nyata + `PipelineStageRunner` yang benar.
- **Reusable Import Wizard** — satu komponen Livewire universal (5 langkah) menggantikan wizard Pengajian yang ditulis manual.
- **Konsolidasi DTO/Results** — satukan namespace `DTO` dan `Results` menjadi satu set DTO kanonik.
- **Adaptor Kelompok/Peserta/Regu** — ganti `NullObject` dengan implementasi nyata.
- **Adaptor Pengajian** — lengkapi parser/validator/normalizer/duplicate/logger agar definition instantiable.
- **Import Activity Logger** — implementasi `logPreview`/`logCommit` → `ActivityLogService` (pattern sama dengan export/print log).
- **Rollback & partial-commit contract** — definisikan semantik "partial success" (per-baris, non-atomik) secara eksplisit.
- **Testing framework** — unit test pipeline, wizard test, regression parity test golden vs migrasi.
- **Import modul baru** — Person, Competition, Kategori, Kelas, Venue, Schedule, Committee, Attendance, Activity, Rundown, Access Grant.

---

## 6. Service (Logic di Livewire yang Harus Dipindah — CATATAN, BELUM DIPINDAHKAN)

> ❌ Tidak dipindahkan pada fase ini. Hanya inventaris lokasi untuk migrasi IF-02+.

| Lokasi Sekarang | Logic | Target Framework |
|-----------------|-------|------------------|
| `ImportMassal::parseFile/parseCsv/parseExcel` | Parsing + deteksi kolom wajib | `Engine` → Parser + `Support/FileParser` |
| `ImportMassal::preview()` | Validasi per-baris, resolusi desa/kelompok, state step | Wizard (Step 2 Preview) + Validator adapter |
| `ImportMassal::executeImport()` | Orchestrasi commit, hasil summary | `ImportCoordinator` / Engine |
| `PengajianImportService` | (Sudah di Service layer ✅) commit + dupe detection + transaction | Dijadikan Committer + DuplicateDetector adapter |
| `ImportDataController::executeImport()` | Boilerplate registry+coordinator+context | `ImportCoordinator` ter-wire DI (facade) |
| `ImportDesa/Kelompok/Peserta/Regu::import()` | Boilerplate serupa (tidak terpanggil runtime) | Hapus setelah migrasi ke Wizard |
| `DesaImport/KelompokImport/PesertaImport/ReguImport` (Maatwebsite) | Commit langsung `ToModel` | Digantikan Committer service-layer |
| `PersonImportTemplateExport` | Generator template multi-sheet | Dijadikan `ImportTemplate` abstraction |

---

## 7. Ringkasan Angka

- Import aktif: **5** (Desa, Kelompok, Regu, Peserta, Pengajian-golden)
- Skeleton framework: **±35 file** (Contracts 9, Pipeline 9, Support 3, DTO 8, Results 4, Adapters 18, Registry 1, Metrics 1, NullObjects 5)
- Stage pipeline berfungsi: **0 / 8**
- Adapter berfungsi penuh: **1 / 4** (Desa — parsial trivial); **0 / 4** untuk Peserta/Kelompok/Regu
- Kandidat modul baru tanpa import: **11**
- Test import golden: **±39** | Test import legacy: **7**
