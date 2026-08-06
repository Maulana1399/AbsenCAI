# Import Framework — Arsitektur & Roadmap

> Status: **IF-02 COMPLETE — Import Engine infrastructure siap dipakai.**
> IF-01 (audit & GAP) → `docs/import-audit.md`.
> Golden Standard: Import Massal Pengajian.
> IF-02 menghidupkan skeleton yang sudah ada — **belum ada modul yang memakai framework ini**,
> UI/route/controller/service/database tidak berubah, seluruh import existing tetap berjalan.

---

## 1. Tujuan

Satu standar Import Framework yang dipakai seluruh modul:

- **UI & UX sama** — satu wizard reusable (IF-03).
- **Lifecycle sama** — pipeline satu alur.
- **Validation, Preview, Summary, Commit, Result sama** — satu kontrak per modul.
- **Testing sama** — unit (pipeline) + feature (wizard) + regression (parity golden).

Import Pengajian menjadi **golden standard** yang dipertahankan sebagai acuan parity.

---

## 2. Struktur Aktual (IF-02 — skeleton `app/Services/Import/*` dihidupkan)

> Sesuai arahan "jangan membuat framework baru", implementasi mempertahankan skeleton yang
> sudah ada di `app/Services/Import/*` (tidak dipindah ke `app/Import/*`).

```
app/Services/Import/
├── Contracts/                    ← kontrak
│   ├── ImportDefinition.php      ← parser/validator/normalizer/duplicateDetector/committer/activityLogger + version + supports*
│   ├── ImportParser.php
│   ├── ImportValidator.php
│   ├── ImportNormalizer.php
│   ├── ImportDuplicateDetector.php
│   ├── ImportCommitter.php
│   ├── ImportActivityLogger.php  ← hook audit (logPreview/logCommit) — dipanggil runner
│   ├── ImportPipelineStage.php   ← kontrak stage seragam: name()/supports()/handle()
│   └── ImportLogger.php          ← NEW: hook observasi pipeline (stageStarted/stageCompleted) — default silent
├── Pipeline/
│   ├── ImportPipeline.php        ← interface pipeline (run + stageOrder)
│   ├── DefaultImportPipeline.php ← orkestrasi: state + runner; stage order configurable
│   ├── ImportCoordinator.php     ← resolve definition + version guard + run
│   ├── ImportPipelineState.php   ← NEW: akumulator mutable (rows/summary/preview/commit/statistics)
│   ├── ParseStage.php            ← parse → RawImportRow[]
│   ├── NormalizeStage.php        ← normalize → NormalizedImportRow[]
│   ├── ValidateStage.php         ← validate → ImportSummary
│   ├── DuplicateStage.php        ← duplicate → ImportSummary
│   ├── PreviewStage.php          ← preview → ImportPreview (canCommit)
│   ├── CommitStage.php           ← commit (mode execute; exception bisnis diteruskan apa adanya)
│   ├── SummaryStage.php          ← agregasi summary final
│   └── CleanupStage.php          ← hook terminal (no-op default)
├── Support/
│   ├── PipelineStageRunner.php   ← interface runner
│   ├── ArrayPipelineStageRunner.php ← runner nyata: dispatch stage by name + skip unsupported + logging hook
│   ├── ImportVersion.php         ← rentang versi + accepts()
│   └── TemplateVersion.php
├── Registry/
│   └── ImportRegistry.php        ← register()/resolve()/has()/all()
├── DTO/                          ← DTO kanonik (duplikat di Results dihapus)
│   ├── ImportContext.php         ← input immutable (+ event, user, version)
│   ├── RawImportRow.php
│   ├── NormalizedImportRow.php
│   ├── ImportError.php
│   └── ImportWarning.php
├── Results/                      ← hasil pipeline (kanonik)
│   ├── ImportSummary.php
│   ├── ImportPreview.php
│   ├── ImportCommit.php
│   ├── ImportResult.php
│   └── ImportPipelineResult.php  (+ rows, statistics)
├── Exceptions/                   ← NEW: exception khusus import
│   ├── ImportException.php                 (base)
│   ├── ImportDefinitionNotFoundException.php
│   ├── ImportStageNotFoundException.php
│   ├── ImportParseException.php
│   ├── ImportValidationException.php
│   ├── ImportCommitException.php           (cadangan batch atomik)
│   └── ImportVersionNotSupportedException.php
├── NullObjects/                  ← no-op default (parser/validator/normalizer/dupe/logger)
│   └── NullImportLogger.php      ← NEW
├── Metrics/ImportMetrics.php
└── Adapters/                     ← definisi per-modul (belum dioptimalkan; diisi penuh saat migrasi IF-04+)
    ├── Desa/  (parser/validator/normalizer/dupe/committer/logger/definition — lengkap, trivial)
    ├── Kelompok/  (hanya committer + definition)
    ├── Regu/      (hanya committer + definition)
    ├── Peserta/   (hanya committer + definition)
    └── Pengajian/ (hanya committer + definition — adaptor lain BELUM ada; didaftarkan IF-04)
```

**Wiring DI:** `app/Providers/ImportServiceProvider.php` (didaftarkan di `bootstrap/providers.php`)
mengikat singleton `ImportRegistry`, `PipelineStageRunner`, `ImportPipeline`, `ImportCoordinator`,
dan definisi `desa/kelompok/regu/peserta` ke registry. Legacy controller tetap memakai
instansiasi manual yang sudah ada — perilaku tidak berubah.

---

## 3. Pipeline Standar (Terimplementasi)

Stage order kanonik (`DefaultImportPipeline::DEFAULT_STAGES`), configurable via constructor:

```
1 Parse       — source → RawImportRow[] (deteksi kolom wajib, baris kosong)
2 Normalize   — raw → NormalizedImportRow[] (trim/case/format, tanpa tulis DB)
3 Validate    — rules per-baris → ImportSummary (validation)
4 Duplicate   — duplicateKey → ImportSummary (duplicate)
5 Preview     — ImportPreview + canCommit (hanya jika definition supportsPreview)
6 Commit      — committer → ImportCommit (hanya mode 'execute' + supportsCommit)
7 Summary     — agregasi summary final (validation + duplicate + commit + failedRows)
8 Cleanup     — hook terminal (no-op default)
```

Tanggung jawab detail:

| Stage | Tanggung jawab | Keluaran |
|-------|----------------|----------|
| **Parse** | Baca CSV/Excel/TXT → `RawImportRow[]`; deteksi header/kolom wajib; potong baris kosong; error dibungkus `ImportParseException` | `RawImportRow[]` |
| **Normalize** | Bersihkan nilai, normalisasi format (gender/date/number), resolusi FK referensi tanpa menulis DB | `NormalizedImportRow[]` |
| **Validate** | Jalankan validator definition; hasil `ImportError`/`ImportWarning`; error dibungkus `ImportValidationException` | `ImportSummary` |
| **Duplicate** | Tandai duplikat intra-file & terhadap DB via duplicateDetector | `ImportSummary` |
| **Preview** | Bangun `ImportPreview`; `canCommit = 0 error & rows > 0` | `ImportPreview` |
| **Commit** | Panggil committer (per-baris transaction, dedup skip); **exception bisnis (mis. Maatwebsite ValidationException) diteruskan apa adanya** | `ImportCommit` |
| **Summary** | Agregasi summary final (total/valid/invalid/duplicate/created/updated/skipped/errors) | `ImportSummary` |
| **Cleanup** | Hook pelepasan resource transient (temp file) — no-op default | void |

Fitur infrastruktur:
- **Configurable order:** `new DefaultImportPipeline($runner, ['parse', 'commit'])` — tidak hardcode.
- **Skip stage:** stage yang `supports()` false dilewati (dicatat di `ImportPipelineState::$skippedStages`).
- **Preview-only vs execute:** mode `'preview'` melewati CommitStage; mode `'execute'` menjalankan commit.
- **Activity log hook:** runner memanggil `definition->activityLogger()->logCommit()/logPreview()`
  (adapter legacy masih no-op — tidak ada perubahan perilaku).
- **Observability hook:** `ImportLogger` (stageStarted/stageCompleted) — default `NullImportLogger` (senyap).

---

## 4. Kontrak Stage & Definition

### ImportPipelineStage (kontrak seragam, sudah dipakai semua stage)

```php
interface ImportPipelineStage
{
    public function name(): string;
    public function supports(ImportContext $context, ImportDefinition $definition): bool;
    public function handle(mixed $payload, ImportContext $context, ImportDefinition $definition, ImportPipelineState $state): mixed;
}
```

### ImportDefinition (kontrak existing, dipertahankan)

```php
interface ImportDefinition
{
    public function key(): string;
    public function label(): string;
    public function parser(): ImportParser;
    public function validator(): ImportValidator;
    public function normalizer(): ImportNormalizer;
    public function duplicateDetector(): ImportDuplicateDetector;
    public function committer(): ImportCommitter;
    public function activityLogger(): ImportActivityLogger;
    public function supportedVersion(): string;
    public function minimumVersion(): string;
    public function currentVersion(): string;
    public function supportsPreview(ImportContext $context): bool;
    public function supportsCommit(ImportContext $context): bool;
}
```

Catatan desain (ekspansi untuk modul masa depan, **tanpa mengubah contract**):
- Skema kolom/rules/rollback/template akan ditambahkan sebagai kemampuan definition
  bertahap saat migrasi modul (IF-03+); contract saat ini sudah mencakup seluruh tahapan pipeline.
- Default mode **non-atomik** (partial success); `ImportCommitException` disiapkan untuk
  batch atomik di masa depan.

---

## 5. DTO/Results (Konsolidasi IF-02)

Duplikat dihapus agar tidak ada class dengan fungsi sama:
- `DTO/ImportSummary`, `DTO/ImportCommit`, `DTO/ImportPreview`, `DTO/ImportResult` — **dihapus**
  (canonical berada di `Results/*`; `DTO/ImportSummary` lama bahkan punya import rusak).
- `DTO/` kini hanya berisi data baris & konteks: `ImportContext`, `RawImportRow`,
  `NormalizedImportRow`, `ImportError`, `ImportWarning`.
- `Results/` berisi hasil pipeline: `ImportSummary`, `ImportPreview`, `ImportCommit`,
  `ImportResult`, `ImportPipelineResult` (kini + `rows`, `statistics`).

`ImportContext` (immutable) kini membawa: `type, eventId, userId, fileName, source, mode,
options, definitionKey, transactionId, event, user, version`. Output eksekusi (rows/preview/
errors/warnings/summary/statistics) dibawa oleh `ImportPipelineState` yang mutable.

---

## 6. Import Wizard — Komponen UI Universal (IF-03)

Belum dibangun. Desain target:

```
Step 1  Upload        — dropzone/input file, info kolom, download template, tombol Preview
Step 2  Preview       — tabel data + status per baris + ringkasan validasi (badge sukses/gagal)
Step 3  Validation    — daftar error/warning per baris yang bisa difilter; tombol Upload Ulang
Step 4  Import        — konfirmasi ringkasan (N baris siap) + tombol Import (proses spinner)
Step 5  Result        — grid statistik (created/matched/skipped/duplicate/failed) + daftar error
```

Golden parity: wizard Pengajian yang ada saat ini dipertahankan tampilannya identik
(hanya sumber logic yang dipindah ke engine).

---

## 7. Standar Template (IF-09)

Belum dibangun. Target:

| Sheet | Isi |
|-------|-----|
| **DATA** | Header kolom (sesuai `columns()`) + baris contoh |
| **PETUNJUK** | Panduan pengisian per kolom, format, contoh, catatan |
| **REFERENSI** | Daftar nilai valid (desa, kelompok, kategori, venue, dll) — **tersembunyi** |

Aturan: generator otomatis (pattern `PersonImportTemplateExport`), Data Validation untuk
kolom referensi/enumerasi, header lowercase-underscore sama dengan parser, `TemplateVersion`
di sheet PETUNJUK. `public/templates/*` statis di-retire setelah semua modul punya generator.

---

## 8. Wiring & Integrasi (IF-02 — TERIMPLEMENTASI)

- **DI / Container:** `ImportServiceProvider` mengikat singleton registry, runner, pipeline,
  coordinator, dan definisi `desa/kelompok/regu/peserta`. `app(ImportCoordinator::class)` siap.
- **Versioning:** `ImportCoordinator` memeriksa `context->version` terhadap
  `minimumVersion()/currentVersion()` definition → `ImportVersionNotSupportedException`.
- **Exception:** seluruh kesalahan framework memakai `ImportException` subclass
  (definition/stage/parse/validation/commit/version). Commit-stage **tidak** membungkus
  exception bisnis agar perilaku legacy (mis. Maatwebsite ValidationException) tidak berubah.
- **Engine Facade** (`ImportEngine`) — dijadwalkan IF-02 lanjutan/IF-04 bila dibutuhkan
  wizard; controller/Livewire cukup memanggil coordinator.

---

## 9. Roadmap Implementasi (IF series)

> Setiap fase **independen & dapat di-commit sendiri**. Tiap fase wajib: kode hijau,
> test, dokumentasi, CHANGELOG.

| Fase | Konten | Keluaran | Status |
|------|--------|----------|--------|
| **IF-01** | Audit, GAP, desain arsitektur, roadmap | `import-audit.md`, `import-framework.md`, update ROADMAP/TODO/ARCHITECTURE/MODULES | ✅ COMPLETE |
| **IF-02** | Revive engine: stage nyata, runner dispatch, DTO konsolidasi, exceptions, DI registry/coordinator, version guard, logging hook | Engine + 37 unit test | ✅ COMPLETE |
| **IF-03** | Bangun Import Wizard: `ImportWizard` reusable 5 langkah + blade | Wizard + feature test | 🔲 |
| **IF-04** | Migrasi Pengajian Import ke framework (UX identik; lengkapi adapter pengajian; daftarkan definition) | Parity test golden vs wizard | 🔲 |
| **IF-05** | Migrasi Desa Import (adapter penuh + template generator) | Desa via wizard | 🔲 |
| **IF-06** | Migrasi Kelompok Import | Kelompok via wizard | 🔲 |
| **IF-07** | Migrasi Regu Import (pertahankan normalizer gender) | Regu via wizard | 🔲 |
| **IF-08** | Migrasi Peserta Import | Peserta via wizard | 🔲 |
| **IF-09** | Template Engine standar (DATA/PETUNJUK/REFERENSI) + retire `public/templates/*` | Generator universal | 🔲 |
| **IF-10** | Import Activity Log (preview + commit) + gate/ability audit (`manage-import`) | Logging import | 🔲 |
| **IF-11** | Import Person (master data) | Person via wizard | 🔲 |
| **IF-12** | Import Competition (cabang & kelas kompetisi) | Competition via wizard | 🔲 |
| **IF-13** | Import Venue | Venue via wizard | 🔲 |
| **IF-14** | Import Schedule | Schedule via wizard | 🔲 |
| **IF-15** | Import Committee (Event Role / Committee Assignment) | Committee via wizard | 🔲 |
| **IF-16** | Import Activity / Rundown | Activity/Rundown via wizard | 🔲 |
| **IF-17** | Import Attendance | Attendance via wizard | 🔲 |
| **IF-18** | Import Access Grant | Access Grant via wizard | 🔲 |
| **IF-19** | Regression penuh + parity audit seluruh import vs golden standard | Baseline hijau | 🔲 |

**Urutan prioritas:** IF-03–04 (fondasi UI + golden migration) → IF-05–08 (legacy) → IF-09–10
(template & audit trail) → IF-11+ (modul baru sesuai kebutuhan operasional).

---

## 10. Acceptance Criteria

- [x] (IF-02) Engine pipeline nyata, runner dispatch, stage order configurable.
- [x] (IF-02) DTO/Results tunggal, exception khusus import, DI registry/coordinator/definition.
- [x] (IF-02) Perilaku aplikasi tidak berubah — baseline hijau (2002 passed, 5 failure pre-existing).
- [ ] Seluruh flow import pada modul menggunakan Wizard + Engine (IF-03+).
- [ ] UI/UX identik dengan golden standard (wizard 5 langkah, summary, error per baris).
- [ ] Validasi, duplicate detection, transaction, dan partial-success konsisten.
- [ ] Template selalu generator multi-sheet (DATA/PETUNJUK/REFERENSI).
- [ ] Activity log tercatat untuk setiap import yang sukses.
- [ ] Test: unit pipeline + feature wizard + regression parity — baseline hijau.
