# Import Framework — Arsitektur & Roadmap

> Status: **IF-07 COMPLETE — Participation = domain Design C kedua di atas framework.**
> IF-01 (audit & GAP) → `docs/import-audit.md`.
> Golden Standard: Import Massal Pengajian.
> IF-02 engine. IF-03 Desa. IF-04 Kelompok. IF-05 Regu. IF-06 Person.
> **IF-07 Participation** (Design C: Person → Participation → Attendance; commit via
> `ManualParticipantRegistrationService`).
> Import Pengajian/Peserta tetap memakai implementasi lama.

---

## 1. Tujuan

Satu standar Import Framework yang dipakai seluruh modul:

- **UI & UX sama** — wizard + komponen reusable.
- **Lifecycle sama** — pipeline satu alur.
- **Validation, Preview, Summary, Commit, Result sama** — satu kontrak per modul.
- **Testing sama** — unit (pipeline) + feature (wizard) + regression (parity golden).

Import Pengajian menjadi **golden standard** yang dipertahankan sebagai acuan parity.

---

## 2. Struktur Aktual

> Sesuai arahan "jangan membuat framework baru", implementasi mempertahankan skeleton yang
> sudah ada di `app/Services/Import/*`.

```
app/Services/Import/
├── Contracts/
│   ├── ImportDefinition.php         ← kontrak definition (collaborators + version + supports)
│   ├── ImportDefinitionMetadata.php ← IF-04: displayName/description/icon/parameters/columns/rules/template/summary/parameterOptions
│   ├── ImportParser / ImportValidator / ImportNormalizer
│   ├── ImportDuplicateDetector / ImportCommitter / ImportActivityLogger
│   ├── ImportPipelineStage.php      ← stage seragam: name()/supports()/handle()
│   ├── ImportTemplate.php           ← IF-03: fileName()/toExport()
│   └── ImportLogger.php             ← hook observasi pipeline (default silent)
├── Pipeline/
│   ├── ImportPipeline.php / DefaultImportPipeline.php  ← orkestrasi + stage order configurable
│   ├── ImportCoordinator.php        ← resolve definition + version guard + run
│   ├── ImportPipelineState.php      ← akumulator mutable
│   └── ParseStage / NormalizeStage / ValidateStage / DuplicateStage /
│       PreviewStage / CommitStage / SummaryStage / CleanupStage
├── Support/
│   ├── PipelineStageRunner.php / ArrayPipelineStageRunner.php
│   ├── FileParser.php               ← IF-03: parser CSV/Excel generik + kolom wajib + prune baris kosong
│   ├── ImportVersion.php / TemplateVersion.php
├── Registry/ImportRegistry.php      ← register/resolve/has/all
├── DTO/                             ← ImportContext, RawImportRow, NormalizedImportRow, ImportError, ImportWarning
├── Results/                         ← ImportSummary, ImportPreview, ImportCommit, ImportResult, ImportPipelineResult
├── Exceptions/                      ← ImportException (base) + 6 subclass
├── NullObjects/                     ← no-op defaults + NullImportLogger
├── Template/                        ← generator template framework
│   ├── ImportTemplateExport.php     ← base 3-sheet (DATA/PETUNJUK/REFERENSI)
│   ├── ImportDataSheet.php / ImportInstructionsSheet.php / ImportReferenceSheet.php
│   ├── DesaImportTemplateExport.php
│   └── KelompokImportTemplateExport.php   ← REFERENSI = daftar desa
├── Metrics/ImportMetrics.php
└── Adapters/
    ├── ImportAdapter.php            ← adapter reusable (preview/commit/definition/metadata/template + parameters)
    ├── Desa/                        ← REAL collaborator penuh
    ├── Kelompok/                    ← REAL collaborator penuh + metadata
    ├── Regu/                        ← REAL collaborator penuh + metadata
    ├── Person/                      ← REAL collaborator penuh + metadata + PersonDuplicateDetectionService
    ├── Participation/               ← REAL collaborator penuh (IF-07) + metadata + ManualParticipantRegistrationService
    ├── Peserta/                     ← committer legacy (belum dimigrasi)
    └── Pengajian/                   ← committer legacy (belum dimigrasi)
```

**Wiring DI:** `app/Providers/ImportServiceProvider.php` (didaftarkan di `bootstrap/providers.php`)
mengikat singleton `ImportRegistry`, `FileParser`, `PipelineStageRunner`, `ImportPipeline`,
`ImportCoordinator`, `ImportAdapter`, dan definisi `desa/kelompok/regu/peserta` (by class + by key).

**Wizard reusable (IF-05):** `app/Livewire/Import/ImportWizardBase.php` — base Livewire wizard
(state machine + metadata-driven render) dipakai `ImportKelompok` & `ImportRegu`; view generik
`resources/views/livewire/import/import-wizard.blade.php`.

---

## 3. Pipeline Standar

Stage order kanonik (`DefaultImportPipeline::DEFAULT_STAGES`), configurable via constructor:

```
1 Parse       — source → RawImportRow[] (FileParser: CSV/Excel, kolom wajib, prune baris kosong)
2 Normalize   — raw → NormalizedImportRow[] (trim/case/format, tanpa tulis DB)
3 Validate    — rules per-baris → ImportSummary (validation)
4 Duplicate   — duplicateKey → ImportSummary (duplicate: intra-file + DB)
5 Preview     — ImportPreview + canCommit (hanya jika definition supportsPreview)
6 Commit      — committer(rows) → ImportCommit (hanya mode 'execute' + supportsCommit)
7 Summary     — agregasi summary final (validation + duplicate + commit + failedRows)
8 Cleanup     — hook terminal (no-op default)
```

| Stage | Tanggung jawab | Keluaran |
|-------|----------------|----------|
| **Parse** | `FileParser` → `RawImportRow[]`; deteksi kolom wajib; error → `ImportParseException` | `RawImportRow[]` |
| **Normalize** | Bersihkan nilai, normalisasi format, resolusi FK tanpa menulis DB | `NormalizedImportRow[]` |
| **Validate** | Validator definition; `ImportError`; error → `ImportValidationException` | `ImportSummary` |
| **Duplicate** | Duplikat intra-file & DB via duplicateDetector | `ImportSummary` |
| **Preview** | `ImportPreview`; `canCommit = 0 error & rows > 0` | `ImportPreview` |
| **Commit** | `committer()->commit($state->rows, $context)` (IF-03: committer menerima rows); **exception bisnis diteruskan apa adanya** | `ImportCommit` |
| **Summary** | Agregasi total/valid/invalid/duplicate/created/updated/skipped/errors | `ImportSummary` |
| **Cleanup** | Hook pelepasan resource transient | void |

**Perubahan IF-03:** `ImportCommitter::commit(array $rows, ImportContext $context)` — committer
menerima baris ternormalisasi dari pipeline (bukan lagi hanya context). Committer legacy
(Kelompok/Regu/Peserta/Pengajian) mengabaikan `$rows` dan tetap membaca `options['file']`,
sehingga perilakunya tidak berubah.

---

## 4. Adapter Layer (IF-03/IF-04 — TERIMPLEMENTASI)

```
Controller lama / Wizard
        ↓
   ImportAdapter  (app/Services/Import/Adapters/ImportAdapter.php)  ← reusable
        ↓
   ImportCoordinator + ImportRegistry
        ↓
   DesaImportDefinition / KelompokImportDefinition
        ↓
   Framework Pipeline (Parse → Normalize → Validate → Duplicate → Preview → Commit → Summary)
        ↓
   Legacy Result (ImportPipelineResult)
```

`ImportAdapter` adalah satu-satunya pintu masuk bagi pemanggil. Controller/wizard **tidak
mengetahui pipeline**:

- `preview(key, file, parameters)` → pipeline mode `'preview'` (parse + validasi, tanpa commit).
- `commit(key, file, parameters)` → pipeline mode `'execute'` (parse + validasi + duplicate + commit).
- `definition(key)` → resolve definition dari registry.
- `metadata(key)` → metadata definition (displayName/description/icon/parameters/columns/rules/...).
- `template(key)` → template generator dari definition.

**Parameter engine (IF-04):** parameter wizard (mis. `desa_id`) dikirim melalui
`ImportContext.options['parameters']`. Collaborator (normalizer/validator/committer) membaca
parameter dari context — blade tidak hardcode apa pun.

Adapter bersifat **reusable** — Regu/Peserta nanti cukup memanggil adapter yang sama
dengan key masing-masing.

---

## 5. Import Definition & Metadata

### 5.1. Metadata Definition (IF-04 — `ImportDefinitionMetadata`)

Definition modul kini mendukung metadata via `Contracts/ImportDefinitionMetadata`:

```php
interface ImportDefinitionMetadata
{
    displayName(): string;             // branding
    description(): string;             // branding
    icon(): string;                    // branding
    parameters(): array;               // parameter wizard: [key => ['label','type','required']]
    parameterOptions($key, $ctx): array; // opsi select parameter (mis. daftar desa)
    columns(): array;                  // kolom preview: [key => ['label','required','example']]
    rules(): array;                    // rules validasi per baris
    template(): ImportTemplate;        // generator template
    summary($rows, $ctx): ImportSummary; // agregasi validasi + duplicate
}
```

`DesaImportDefinition` dan `KelompokImportDefinition` mengimplementasikan interface ini;
Regu/Peserta/Pengajian tidak diubah (belum menyediakan metadata).

### 5.2. Capability API Definition (pipeline + modul)

```php
// Contract (dipakai pipeline)
key() label() parser() validator() normalizer() duplicateDetector()
committer() activityLogger() supportedVersion() minimumVersion() currentVersion()
supportsPreview() supportsCommit()

// Capability API modul (dipakai adapter/wizard)
normalize($rows, $ctx)   // → NormalizedImportRow[] (delegasi normalizer)
validateRow($row, $ctx)  // → string[] error per baris
duplicate($rows, $ctx)   // → ImportSummary (delegasi duplicateDetector)
preview($rows, $ctx)     // → ImportPreview (canCommit)
commit($rows, $ctx)      // → ImportCommit (delegasi committer)
summary($rows, $ctx)     // → ImportSummary (validasi + duplicate)
template()               // → ImportTemplate (generator)
```

### 5.3. Collaborator per modul

**Desa (IF-03):**
- `DesaImportParser` — baca file via `FileParser`, header `desa`.
- `DesaImportNormalizer` — trim + `duplicateKey` (lowercase).
- `DesaImportValidator` — required `desa`.
- `DesaImportDuplicateDetector` — duplikat intra-file + DB.
- `DesaImportCommitter` — create `desa`, skip duplikat, catat `failedRows`.

**Kelompok (IF-04):**
- `KelompokImportParser` — baca file via `FileParser`, header `kelompok`.
- `KelompokImportNormalizer` — trim + `duplicateKey` = `desa_id|kelompok`; attach `desa_id` dari parameter.
- `KelompokImportValidator` — required `kelompok` + **cek Desa** (desa_id valid).
- `KelompokImportDuplicateDetector` — duplikat intra-file + DB **scoped per desa**.
- `KelompokImportCommitter` — create `kelompok` di bawah desa terpilih, skip duplikat.

**Regu (IF-05):** *regu global (tanpa FK) → tanpa parameter; business rule legacy dipertahankan.*
- `ReguImportParser` — baca file via `FileParser`, header `regu, jenis_kelamin`.
- `ReguImportNormalizer` — trim + normalisasi gender (`laki-laki`/`Laki - laki`/`Laki – Laki` → `Laki - Laki`, `perempuan` → `Perempuan`) + `duplicateKey` = nama regu.
- `ReguImportValidator` — required `regu` + required `jenis_kelamin` in `[Laki - Laki, Perempuan]`.
- `ReguImportDuplicateDetector` — duplikat **by unique regu name** (`unique:regus,regu`).
- `ReguImportCommitter` — create `regu` + `jenis_kelamin`, skip duplikat nama, catat `failedRows`.

**Person (IF-06):** *Design C — Person = identitas global; legacy peserta & NIP TIDAK dipakai; tanpa parameter.*
- `PersonImportParser` — baca file via `FileParser`, header `nama, jenis_kelamin` (wajib); `tanggal_lahir, desa, kelompok` opsional.
- `PersonImportNormalizer` — delegasi ke `Support/PersonIdentityNormalizer` (trim + collapse spasi/unicode + gender → `L`/`P` + tanggal + resolusi desa/kelompok).
- `PersonImportValidator` — required nama/gender, gender in `[L,P]`, tanggal valid, **desa/kelompok FK check**.
- `PersonImportDuplicateDetector` — **reuses `PersonDuplicateDetectionService`**; intra-file via duplicateKey; kandidat mirip → warning.
- `PersonImportCommitter` — duplicate via `PersonDuplicateDetectionService`; create via `Person::create()` (jalur golden).

**Participation (IF-07):** *Design C penghubung Person → Event; commit via `ManualParticipantRegistrationService`.*
- `ParticipationImportParser` — header `nama, jenis_kelamin, desa` (wajib); `tanggal_lahir, kelompok, jenis_peserta, status_registrasi, regu` opsional.
- `ParticipationImportNormalizer` — **reuses `PersonIdentityNormalizer`** (Person logic, tidak diduplikasi) + resolusi regu.
- `ParticipationImportValidator` — required nama/gender/desa + FK check (desa/kelompok/regu).
- `ParticipationImportDuplicateDetector` — **reuses `ManualParticipantRegistrationService::resolvePerson()`**; duplicate = Participation existing utk (person, event) → hanya event target.
- `ParticipationImportCommitter` — per-baris `ManualParticipantRegistrationService::register()` (lookup Person dulu; duplicate event → skip; ambiguous → warning).
- `ManualParticipantRegistrationService` di-extend: public `resolvePerson()` + param opsional `jenisPeserta/statusRegistrasi/reguId` (backward-compatible).

---

## 6. Wizard & Komponen UI (TERIMPLEMENTASI)

**Komponen reusable** (dipakai Desa & Kelompok):

| Komponen | Fungsi |
|----------|--------|
| `<x-import.wizard>` | Layout card + step indicator |
| `<x-import.progress>` | Spinner/loading section |
| `<x-import.upload-section>` | Upload section (input file, format info, download template) |
| `<x-import.preview-table>` | Preview table (generik by columns) |
| `<x-import.validation-errors>` | Daftar error validasi per baris |
| `<x-import.summary-card>` | Stat card reusable |
| `<x-import.result-card>` | Result card (Berhasil/Duplicate/Gagal/Warning/Total) |

**`ImportDesa`** (IF-03): 3 langkah (Upload → Preview & Validasi → Hasil).

**`ImportWizardBase`** (IF-05): base Livewire wizard reusable — 5 langkah
`Upload → Preview → Validation → Import → Result`, metadata-driven (parameter & kolom dari
definition, template route `route('import.{key}.template')`). Dipakai oleh:
- **`ImportKelompok`** (IF-04): parameter `desa_id`.
- **`ImportRegu`** (IF-05): tanpa parameter (regu global).
- **`ImportPerson`** (IF-06): tanpa parameter (Person global).
- **`ImportParticipation`** (IF-07): parameter `event_id` (default = active event); dipasang di halaman Registrasi.

View generik: `resources/views/livewire/import/import-wizard.blade.php` (parameter select dari
`meta['parameters']`, kolom preview dari `meta['columns']`, summary Total/Valid/Invalid/
Duplicate/**Warning**/Akan Dibuat). Blade **tidak hardcode** detail modul.

File: `resources/views/components/import/*.blade.php` + `livewire/database/{desa,kelompok}/import-*.blade.php`.

Golden parity: Import Pengajian **tidak diubah**.

---

## 7. Standar Template (generator framework terimplementasi)

Generator framework `ImportTemplateExport` (base) + sheet `DATA`/`PETUNJUK`/`REFERENSI`:

| Sheet | Isi |
|-------|-----|
| **DATA** | Header kolom + baris contoh |
| **PETUNJUK** | Cara import, contoh, aturan duplicate |
| **REFERENSI** | Nilai referensi (Desa: kosong; Kelompok: desa; Regu: gender; Person: gender+desa; **Participation: daftar event**) |

- `DesaImportTemplateExport` → `template_import_desa.xlsx` (kolom `desa`).
- `KelompokImportTemplateExport` → `template_import_kelompok.xlsx` (kolom `kelompok`, REFERENSI = daftar desa).
- `ReguImportTemplateExport` → `template_import_regu.xlsx` (kolom `regu, jenis_kelamin`, REFERENSI = enum gender).
- `PersonImportTemplateExport` → `template_import_person.xlsx` (kolom identity Person, REFERENSI = gender + desa).
- `ParticipationImportTemplateExport` → `template_import_participation.xlsx` (kolom identity + jenis_peserta/status/regu, REFERENSI = daftar event).
- Route `GET /import/{desa|kelompok|regu|person}/template` + `GET /events/{event}/registrasi/import-participation/template`.
- Template statis `public/templates/*` **tidak lagi dipakai** untuk modul yang dimigrasi (retire menyeluruh saat IF-09).

---

## 8. Wiring & Integrasi

- **DI / Container:** `ImportServiceProvider` mengikat singleton registry, file parser, runner,
  pipeline, coordinator, adapter, dan definisi `desa/kelompok/regu/peserta`
  (`app(DesaImportDefinition::class)`, `app(KelompokImportDefinition::class)`, `app(ImportAdapter::class)` siap).
- **Parameter engine:** parameter wizard dikirim via `ImportContext.options['parameters']`;
  collaborator membaca dari context (tidak ada hardcode di blade).
- **Versioning:** `ImportCoordinator` memeriksa `context->version` → `ImportVersionNotSupportedException`.
- **Exception:** seluruh kesalahan framework memakai `ImportException` subclass; commit-stage
  **tidak** membungkus exception bisnis (mis. Maatwebsite ValidationException) agar perilaku legacy tetap.

---

## 9. Roadmap Implementasi (IF series)

> Setiap fase **independen & dapat di-commit sendiri**. Tiap fase wajib: kode hijau,
> test, dokumentasi, CHANGELOG.

| Fase | Konten | Keluaran | Status |
|------|--------|----------|--------|
| **IF-01** | Audit, GAP, desain arsitektur, roadmap | `import-audit.md`, `import-framework.md`, update ROADMAP/TODO/ARCHITECTURE/MODULES | ✅ COMPLETE |
| **IF-02** | Revive engine: stage nyata, runner dispatch, DTO konsolidasi, exceptions, DI, version guard, logging hook | Engine + 37 unit test | ✅ COMPLETE |
| **IF-03** | **Migrasi Desa** — adapter reusable, definition penuh, template generator, wizard + komponen UI | Desa di atas framework + 36 test | ✅ COMPLETE |
| **IF-04** | **Migrasi Kelompok** — metadata definition (`ImportDefinitionMetadata`), parameter engine (desa_id), wizard 5-langkah otomatis, template REFERENSI desa | Kelompok via framework + 25 test | ✅ COMPLETE |
| **IF-05** | **Migrasi Regu** — collaborator nyata (normalisasi gender sesuai business rule), duplicate by unique name, reusable wizard base `ImportWizardBase`, template REFERENSI gender | Regu via framework + 19 test | ✅ COMPLETE |
| **IF-06** | **Migrasi Person (Design C)** — identitas global (bukan peserta/NIP), reuses `PersonDuplicateDetectionService`, normalisasi gender/date/spasi, template REFERENSI gender+desa | Person via framework + 21 test | ✅ COMPLETE |
| **IF-07** | **Migrasi Participation (Design C)** — parameter event_id, lookup Person (tanpa create sembarangan), duplicate Participation per event, commit via `ManualParticipantRegistrationService` (+ `resolvePerson` & param opsional), template REFERENSI event | Participation via framework + 17 test | ✅ COMPLETE |
| **IF-08** | Migrasi Pengajian ke framework (UX identik, parity golden; Pengajian jadi orchestrator saja) | Pengajian via framework | 🔲 |
| **IF-09** | Template Engine lanjutan (REFERENSI dropdown/data validation) + retire `public/templates/*` | Generator universal | 🔲 |
| **IF-10** | Import Activity Log (preview + commit) + gate/ability audit (`manage-import`) | Logging import | 🔲 |
| **IF-11** | Import Competition (cabang & kelas kompetisi) | Competition via framework | 🔲 |
| **IF-12** | Import Venue | Venue via framework | 🔲 |
| **IF-13** | Import Schedule | Schedule via framework | 🔲 |
| **IF-14** | Import Committee (Event Role / Committee Assignment) | Committee via framework | 🔲 |
| **IF-15** | Import Activity / Rundown | Activity/Rundown via framework | 🔲 |
| **IF-16** | Import Attendance | Attendance via framework | 🔲 |
| **IF-17** | Import Access Grant | Access Grant via framework | 🔲 |
| **IF-18** | Regression penuh + parity audit seluruh import vs golden standard | Baseline hijau | 🔲 |

**Urutan prioritas:** IF-08 (Pengajian = orchestrator) → IF-09–10 (template & audit trail)
→ IF-11+ (modul baru sesuai kebutuhan operasional).

---

## 10. Acceptance Criteria

- [x] (IF-02) Engine pipeline nyata, runner dispatch, stage order configurable, exception khusus, DI.
- [x] (IF-03) **Desa memakai framework production** (adapter + definition + pipeline + template + wizard).
- [x] (IF-04) **Kelompok memakai framework production** (metadata + parameter + template + wizard).
- [x] (IF-05) **Regu memakai framework production** (metadata + reusable wizard base).
- [x] (IF-06) **Person memakai framework production (Design C)** — identitas global, tanpa peserta/NIP.
- [x] (IF-07) **Participation memakai framework production (Design C)** — penghubung Person → Event.
- [x] (IF-07) Person **dicari dulu** (via `resolvePerson`), tidak pernah create sembarangan; ambiguous → warning.
- [x] (IF-07) Duplicate = Participation existing utk (person, event) — event lain boleh.
- [x] (IF-07) Commit via `ManualParticipantRegistrationService` (service canonical, backward-compatible extension).
- [x] (IF-07) Normalisasi reuse `PersonIdentityNormalizer` (tidak diduplikasi).
- [x] (IF-07) Perilaku modul lain tidak berubah — baseline hijau (2120 passed, 5 failure pre-existing).
- [ ] Validasi, duplicate detection, transaction, dan partial-success konsisten di semua modul.
- [ ] Activity log tercatat untuk setiap import yang sukses (IF-10).
- [ ] Test: unit pipeline + feature wizard + regression parity — baseline hijau.
