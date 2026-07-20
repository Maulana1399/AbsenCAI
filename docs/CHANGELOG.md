# CHANGELOG

Semua perubahan penting pada KJA Event Manager dicatat pada dokumen ini.

Format changelog mengikuti prinsip **Keep a Changelog**.

---

# [Unreleased]

## Added (UI Bug Fix Sprint — Batch 1 Branding & Navigation)

### Verification
- **Full test suite**: 908 passed, 2192 assertions, 0 failures
- **Runtime verification 1–5**: `/` (guest), `/` (auth), `/login`, sidebar CAI (no Pengajian), sidebar Pengajian (shows Pengajian) — all confirmed
- **Bug status**: #1, #2, #4, #6 → RESOLVED — VERIFIED ✅

## Added (UI Bug Fix Sprint — Batch 2 Access Token UI & Security)

### Bug #5 — Delete Revoked Access Token
- **Root cause**: `AccessIndex` Livewire + view hanya menyediakan tombol "Cabut" (revoke). Tidak ada mekanisme hard-delete untuk grant yang sudah di-revoke
- **Change**:
  - `DesaAccessService::deleteGrant()` — method baru, melempar `RuntimeException` jika grant belum di-revoke
  - `AccessIndex::confirmDelete()`, `cancelDelete()`, `delete()` — Livewire methods untuk delete flow
  - View: Tombol "Hapus" muncul hanya untuk status `revoked`. Delete confirmation modal dengan tombol "Batal"/"Ya, Hapus"
- **Authorization**: Delete hanya untuk revoked grant. Active/scheduled grant tidak memiliki opsi delete. UI update real-time via Livewire
- **Files**: `app/Services/Pengajian/DesaAccessService.php`, `app/Livewire/Pengajian/Admin/AccessIndex.php`, `resources/views/livewire/pengajian/admin/access-index.blade.php`

### Bug #7 — Raw Token Security Audit
- **Root cause**: Tidak ada security vulnerability. Raw token one-time reveal adalah desain yang benar
- **Audit findings**:
  - Database: hanya menyimpan `token_hash` (bcrypt) + `token_prefix` (16 karakter pertama) — ✅ AMAN
  - Raw token: `'kja-dgt-'.Str::random(60)` — di-generate, di-hash, lalu raw-nya di-return SATU KALI
  - Alpine.js state: `rawToken` di `x-data`, dibersihkan (`null`) saat modal ditutup via `closeModal()`
  - Tidak disimpan di: Livewire public property, session, logs, URL, query string
  - Tidak tampil di: table/list (hanya prefix + "..."), session, logs
- **Changes**: Ditambahkan class `select-all` pada code block untuk memudahkan seleksi teks. Warning "Token hanya ditampilkan sekali" tetap dipertahankan
- **Verdict**: Desain saat ini sudah sesuai security best practice untuk one-time token reveal

### Bug #8 — Token Overflow / UI Layout
- **Root cause**: Layout code block token terlalu sempit. Meskipun `break-all` sudah ada, padding dan line-height kurang optimal untuk token sepanjang ~68 karakter
- **Changes**:
  - `min-w-0` pada container code block — mencegah overflow flex/grid
  - `leading-7` dari `leading-6` — lebih lega untuk wrapped text
  - `p-4` dari `p-3` — padding lebih lega
  - `select-all` pada code element — memudahkan copy seluruh token
  - Modal tetap `max-w-lg` dengan `p-6` — tidak melebar berlebihan
- **Files**: `resources/views/livewire/pengajian/admin/access-index.blade.php`

### Tests Added
- `tests/Feature/Pengajian/PengajianAdminAccessTest.php` — 12 new test cases:
  - Active grant tidak memiliki tombol Hapus
  - Revoked grant memiliki tombol Hapus
  - Delete revoked grant berhasil (record benar-benar terhapus)
  - Delete active grant ditolak (RuntimeException)
  - Cancel delete membersihkan state
  - Delete non-existent grant
  - Revoke flow tetap bekerja setelah delete
  - DB hanya menyimpan token_hash (bukan raw token)
  - One-time modal clears state
  - Token prefix truncated di list view
  - Code block menggunakan break-all untuk overflow prevention

### Bug #1 — Landing Page Branding
- **Root cause**: `resources/views/welcome.blade.php` masih menggunakan branding CAI
- **Change**: Diganti ke KJA Event Manager — logo "KJ", judul "KJA Event Manager", subtitle "Platform Manajemen Event Multi-Event", deskripsi baru, gradient dari emerald
- **Authenticated view**: Welcome page untuk user login menjadi KJA global home dengan link ke Dashboard Event, Kelola Event, dan Akses Pengajian Desa
- **Files**: `resources/views/welcome.blade.php`

### Bug #2 — Login Page Branding
- **Root cause**: `resources/views/livewire/auth/login.blade.php` masih menampilkan "CAI" dan "Cinta Alam Indonesia"
- **Change**: Logo diganti "KJ" (emerald-600), judul "KJA Event Manager", subtitle "Administrator", deskripsi "Masuk untuk mengakses dashboard manajemen event"
- **Files**: `resources/views/livewire/auth/login.blade.php`

### Bug #4 — Event-Aware Sidebar (Pengajian menu in CAI context)
- **Root cause**: Sidebar CAI (`@else` block) menampilkan grup "Pengajian" dengan link Akses Desa dan Regional Report
- **Change**: Grup "Pengajian" dihapus dari sidebar CAI. Menu Pengajian hanya muncul ketika `$isPengajian === true` (event bertipe Pengajian)
- **Mechanism**: Menggunakan `ActiveEventContext::current()->isPengajian()` yang sudah ada
- **Files**: `resources/views/components/layouts/app/sidebar.blade.php`

### Bug #6 — KJA Logo Global Navigation
- **Root cause**: Logo KJA di sidebar mengarah ke `route('dashboard')` yang merupakan dashboard CAI
- **Change**: Logo diubah arah ke `route('home')`. Untuk user terautentikasi, halaman home menampilkan KJA global home dengan pilihan navigasi — tidak auto-select event CAI
- **Event context safety**: ActiveEventContext tidak berubah saat mengklik logo. Event hanya dipilih melalui EventSwitcher
- **Files**: `resources/views/components/layouts/app/sidebar.blade.php`, `resources/views/welcome.blade.php`

### Tests Added
- `tests/Feature/Branding/BrandingTest.php` — 8 test cases untuk verifikasi branding dan navigasi

## Added (Documentation Audit & Bug Backlog — 2026-07-20)

### Audit Findings
- **11 UI areas audited** across codebase. 9 confirmed active bugs, 2 resolved.
- Bug backlog documented in TODO.md → **UI Bug Fix Sprint**
- Branding & Navigation: Landing page, login page, KJA logo route, Pengajian menu in CAI
- Access Token: Missing delete for revoked tokens, raw token display audit
- Regional Report filters: PGM.16 fixes verified as code-correct, need test verification
- Dark Mode: Verified resolved — all elements have proper `dark:text-*` classes
- Token overflow: Verified resolved — `break-all` + `whitespace-normal` prevents overflow

### Documentation Synchronized
- README.md: Updated sprint statuses, added bug backlog overview
- ROADMAP.md: S04 → COMPLETE 100%, S3.9 status → COMPLETE, added Bug Fix Sprint priority
- TODO.md: Added UI Bug Fix Sprint section, fixed S3.9 stale status, added test count notes
- HANDOFF.md: Added Known UI Bugs section, updated project name
- CURRENT_STATE.md: Updated sprint status, risks, technical debt, next work
- FEATURE.md: Updated all module statuses (Dashboard→Stable, QR→Stable, Event→Stable, Category→Stable, Venue→Stable)
- INDEX.md: Updated current sprint and priority, added document list

## Added (PGM.16 — Pengajian UX, Contextual Navigation & Bulk Import)

- **Event type discriminator** — `event_type` column (`cai`/`pengajian`) on events table (migration `2026_08_02_000001`)
- **Contextual sidebar** — navigation changes based on active event type (CAI vs Pengajian menus)
- **KJA Event Manager branding** — replaced CAI branding in app logo
- **Pengajian bulk import** — dedicated CSV/Excel import at `/pengajian/admin/import-massal` with preview, row-level validation, identity matching, and summary counters
- **Desa-scoped kelompok lookup** — kelompok resolution now scoped to resolved desa (deterministic)
- **Event switcher redirect** — switching event navigates to appropriate landing page (CAI→dashboard, Pengajian→pengajian.report)
- **Filter fixes** — Regional/Desa report method filter disabled when status is Belum Hadir; search debounced at 300ms
- **Responsive UI** — Regional Report and Desa Dashboard layouts improved for mobile/tablet/desktop
- **`kelompok_id` on `people` table** — migration `2026_08_01_000001`

## Changed

- `EventSwitcher::switchTo()` now redirects to event-type landing page after successful switch
- RegionalReport and DesaDashboard method filter is cleared when status=belum
- PengajianImportService identity matching uses PHP-level date comparison (same as ManualParticipantRegistrationService)
- Sidebar is now event-type-aware with conditional menu groups

## Added (Previous)

* QR & Label module with individual QR, batch export preview, and label 4x4 UI
* Sprint 1 CAI Operational closed for current operational scope
* **Surat Izin module** — full create/submit/approve/reject/cancel/return flow with service layer, Livewire UI, database migrations, authorization gates, and test coverage
* **Print Surat** — A5 landscape template with Kop Surat (KJA/CAI logos) and `jenis_izin` (pulang/keluar), browser-native print
* **Return Tracking** — selectable return date via modal, attendance cleanup on return, IzinAbsensi end_time update
* `jenis_izin` column (`pulang`/`keluar`) on `surat_izins` table
* **Activity Log Foundation** — custom audit infrastructure with `ActivityLog` model, `ActivityLogService`, and read-only Livewire UI
* **Surat Izin Activity Logging** — lifecycle events (created, submitted, approved, rejected, returned) integrated through `SuratIzinService`
* **Activity Log UI** — `GET /activity-log` route with newest-first list, pagination, search, module/action filters, expandable properties, user fallback "Sistem"
* **Print Log** — 4 print views logged via `ActivityLogService`: Surat Izin print, single QR label, batch QR filtered, batch QR A4 (module: `print`, action: `print_viewed`)
* **Export Log** — participant data Excel export logged via `ActivityLogService` through `RekapPeserta::exportExcel()` (module: `export`, action: `exported`)
* **QR Log** — single QR PNG download and batch QR export to storage logged via `ActivityLogService` through `QRLabel\Index` (module: `qr`, actions: `downloaded`, `batch_exported`)

## Changed

* Existing QR and print services are now connected to user-facing screens without new business logic
* Deferred non-critical Sprint 1 backlog: QR PDF Export, Report PDF Export, Dashboard PJ Regu, and Live Monitoring
* Test suite expanded: 186 tests, 432 assertions (up from 70 tests, 198 assertions)
* Sprint 2 status changed to Operational Stable / Partially Deferred — Riwayat Izin, Scoring, Storage deferred to 2027
* Sprint 3 promoted to ACTIVE / HIGHEST PRIORITY
* `SuratIzinService` now depends on `ActivityLogService` via constructor injection — logs are written after successful business operations only
* `routes/web.php`: all 4 print route closures now inject `ActivityLogService::log()` after authorization/validation
* `App\Livewire\QRLabel\Index`: `downloadPng()` and `generateBatchExport()` now inject `ActivityLogService::log()` after successful generation
* `App\Livewire\Rekap\Peserta\RekapPeserta`: `exportExcel()` now injects `ActivityLogService::log()` before returning download
* **Event model and `events` table** — foundation for Multi Event architecture
* **Legacy CAI Event seeder** — idempotent bootstrap creates `cai-operational` event
* **ActiveEventContext service** — session-based active event management with singleton binding
* **Event switcher UI** — sidebar dropdown to select active event
* **Event management CRUD** — index, create, edit, archive/activate via `/events` Livewire page
* **Sprint 3 ACTIVE** — Multi Event highest priority for August 2026
* **Sprint 2 remaining features deferred** — Riwayat Izin, Scoring, Storage deferred to 2027
* `docs/SPRINT3_MULTI_EVENT_AUDIT.md` — full architecture audit and migration plan
* 30+ dedicated tests for Event Foundation
* **Person model and `people` table** — Universal Person foundation (S3.2)
* `Person` model with `desa()` relationship and `jenis_kelamin_label` accessor
* 15+ dedicated tests for Person Foundation
* Sprint 3.2 status updated to COMPLETE
* **Participation model and `participations` table** — Participation Foundation (S3.3)
* `Participation` model with `person()` and `event()` relationships
* `Person` model: `participations()` hasMany, `events()` belongsToMany
* `Event` model: `participations()` hasMany, `people()` belongsToMany
* `UNIQUE(event_id, person_id)` — one participation per person per event
* `UNIQUE(event_id, participant_number)` — participant number unique within event
* Globally unique `attendance_code` — unambiguous QR code resolution
* `jenis_peserta` default `'Wajib'` — matches existing peserta convention
* 20+ dedicated tests for Participation Foundation
* Sprint 3.3 status updated to COMPLETE
* **ActiveEventContext hardened** — requireCurrent(), resolveDefault(), stale/inactive event safety
* 25+ dedicated tests for ActiveEvent Context Hardening (S3.4)
* Route middleware deferred — no concrete multi-event routes yet
* Sprint 3.4 status updated to COMPLETE
* **ActiveEventContext cache removed** — stale `$cached` property caused deleted/archived event retention; `current()` now queries DB every call
* **Fallback behavior added** — `current()` falls back to first active event when session is stale, `id()` delegates to `current()?->id`, `hasActiveEvent()` checks `current() !== null`
* **`$cleared` flag added** — explicit `clear()` prevents fallback, preserving "no event" state
* **6 test assertions updated** — 3 stale-session tests realigned with fallback contract, `requireCurrent` test changed to expect default instead of throw, `resolveDefault` id() assertion corrected, stale-session-with-other-active test renamed and updated
* **LegacyPesertaMapping model and `legacy_peserta_mappings` table** — S3.5 mapping infrastructure foundation
* `LegacyPesertaMapping` model with `peserta()`, `person()`, `participation()`, `event()` belongsTo relationships
* `UNIQUE(peserta_id)` — one mapping per legacy peserta
* `UNIQUE(participation_id)` — one mapping per participation
* Snapshot columns: `legacy_nip`, `legacy_participant_number`, `legacy_attendance_code`, `migrated_at`, `backfill_batch_id`
* All FKs use `restrictOnDelete` — prevents cascade deletion of mapped entities
* Inverse relationships: `Peserta.legacyPesertaMapping()`, `Person.legacyPesertaMapping()`, `Participation.legacyPesertaMapping()`, `Event.legacyPesertaMappings()`
* 20+ dedicated tests for S3.5 mapping infrastructure
* S3.5 status updated to IN PROGRESS — mapping infrastructure complete, backfill command remaining
* **LegacyPesertaBackfillService** — S3.5C backfill engine with per-peserta analysis, NIP-based Person matching, identity signal validation, conflict detection, dry-run projection, and transactional execute mode
* **BackfillLegacyPeserta Artisan command** — `php artisan backfill:legacy-peserta` with `--dry-run` (default), `--execute` (writes), `--event` (slug target). `--dry-run` + `--execute` mutual exclusion. Event existence and active status validation
* `BackfillReport` and `BackfillReportItem` value objects for structured service output
* 37+ dedicated tests for backfill engine: command contract, dry-run guarantees, execute mode, NIP matching rules, conflict detection (name/gender/desa/participant_number/attendance_code), Participation resolution, idempotency, bulk determinism, transaction isolation
* S3.5 status updated to COMPLETE. **Real backfill NOT YET EXECUTED** — pending manual `--dry-run` review against 144 real peserta records
* **S3.5C backfill engine fixes** — dry-run reporting contract (Total Legacy Peserta, Database Writes), BROKEN_MAPPING test (valid FK with logical inconsistency), transaction isolation test (real conflict fixture), actual write counters (peopleCreated/participationsCreated/mappingsCreated). Test suite: 368 passed, 964 assertions
* **S3.5E Production Backfill EXECUTED 2026-07-17** — 144 Person, 144 Participation, 144 LegacyPesertaMapping created. 0 conflicts, 0 errors. Full idempotency verified. Pre-backfill backup: `database/database.pre-s3.5e-backfill-20260717-172802.sqlite`
* **S3.5 status updated** — ALL sub-phases COMPLETE (A=Audit, B=Mapping, C=Engine, D=Copy verification, E=Production). **Runtime architecture unchanged** — `pesertas` table remains active source. People/participations populated but not yet runtime-migrated

## Planned

Belum ada perubahan.

Semua rencana pengembangan dicatat pada:

* ROADMAP.md
* TODO.md

---

## [Unreleased]

### Added

* **S3.6 Attendance Event Scoping** — event-scoped attendance sessions, event-safe participant resolution via LegacyPesertaMapping -> Participation -> Event, and cross-event attendance persistence prevention
* **S3.6 regression coverage** — tests for event-scoped explicit sessions, duplicate isolation, historical legacy attendance compatibility, and cross-event rejection

### Changed

* Attendance now derives event ownership through `sesi_absensis.event_id` while preserving legacy `absensis` storage (`nip`, `nama`, `jam_scan`, `sesi_id`)
* Current priority advanced to Sprint 3.7 Participant/QR Migration

---

# [v1.0.0] - 2026-07-14

## Project

### Added

* Rename project vision menjadi **KJA Event Manager**
* AbsenCAI ditetapkan sebagai MVP (Minimum Viable Product)
* Dokumentasi arsitektur awal proyek

---

## Documentation

### Added

* AGENTS.md
* API.md
* ARCHITECTURE.md
* CHANGELOG.md
* CONTEXT.md
* CURRENT_STATE.md
* DATABASE.md
* DATAFLOW.md
* DECISION.md
* FEATURE.md
* INDEX.md
* ROADMAP.md
* SECURITY.md
* TODO.md
* PERMISSION.md

---

## Architecture

### Added

* Long-term architecture planning
* Universal Person Database concept
* Multi Event architecture
* Future Multi Organization support
* Storage architecture menggunakan Nextcloud & TrueNAS

---

## Database

### Added

Concept:

* Person sebagai entitas utama
* Participation sebagai relasi Event
* Attendance terpisah dari Person
* Universal ID
* Attendance Code

---

## Decision

### Accepted

* Rename AbsenCAI menjadi KJA Event Manager
* Person sebagai entitas utama
* Attendance Code menggantikan QR berbasis NIP
* No Code Before Design
* CAI menjadi prioritas utama
* Storage menggunakan metadata + Nextcloud

---

## Security

### Added

* Security Standard
* Security Checklist
* Authentication Guideline
* Authorization Guideline
* QR Security
* Audit Logging Standard
* Backup Policy

---

## Permission

### Added

* Role Matrix
* Permission Matrix
* Future Role Planning
* Permission Naming Convention

---

## Development

### Added

Sprint Management:

* Sprint 0
* Documentation First
* Architecture First
* Planning Before Coding

---

# Versioning

Menggunakan Semantic Versioning.

Format:

MAJOR.MINOR.PATCH

Contoh:

* v1.0.0
* v1.1.0
* v1.2.3
* v2.0.0

---

# Changelog Rules

Tambahkan perubahan **hanya jika implementasi sudah selesai**.

Jangan mencatat:

* Ide
* Rencana
* Roadmap
* TODO

Semua rencana dicatat di:

* ROADMAP.md
* TODO.md

---

# Release Flow

Idea

↓

Discussion

↓

Documentation

↓

Design

↓

Development

↓

Testing

↓

Release

↓

Update CHANGELOG
