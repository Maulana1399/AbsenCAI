# CHANGELOG

Semua perubahan penting pada KJA Event Manager dicatat pada dokumen ini.

Format changelog mengikuti prinsip **Keep a Changelog**.

---

# [Unreleased]

## Added (PGM.19 Sprint 8A — Legacy Regu Dependency Elimination)

### Refactored (PlacementService — strict eventId contract)
- **`leastFilledRegu()`** signature changed from `(?int $eventId = null)` to `int $eventId` — global fallback removed
- **`autoPlacement()`** signature preserved as `?int $eventId = null` but null returns null regu
- All production callers (`SelfRegister`, `TambahPeserta`, `PesertaImport`, `Ulang`) forward `$eventId`
- `leastFilledReguId()`, `leastFilledReguName()` updated to require `int $eventId`

### Stopped (RegistrationService dual-write)
- Dual-write to `pesertas.regu_id` **stopped** — line 73 comment documents removal
- `regu_id` written exclusively to `Participations.regu_id`

### Removed relationships
- `peserta::regu()` relationship — deleted
- `regu::peserta()` relationship — deleted

### Cleaned (peserta model)
- `regu_id` removed from `$fillable` (already done in Sprint 8A)
- `regu()` relationship removed

### Tests added
- 15 Sprint 8A contract tests (`Sprint8ALegacyReguDependencyEliminationTest.php`)
- All 14 Sprint 8A regression failures fixed (6 stale tests, 6 test bugs, 2 API contract bugs — 0 genuine production regressions)

## Added (PGM.19 Sprint 8B — Physical Regu Retirement)

### Migration executed
- **`2026_08_11_000001_drop_regu_id_from_pesertas_table.php`** — drops FK, index, and column from `pesertas` table
- SQLite-safe: uses explicit table rebuild when native `DROP COLUMN` is blocked by inline FK definition
- MySQL path: standard `dropForeign()` + `dropColumn()`
- Idempotent: guarded with `Schema::hasColumn()`

### Regressions fixed (5 total across test cycle)
1. **SQLite index name mismatch**: table rebuild renames indexes, `pesertas_new_regu_id_index` vs expected `pesertas_regu_id_index`
2. **SQLite FK blocks DROP COLUMN**: inline FK definition prevents column drop — explicit `CREATE+INSERT+DROP+RENAME` rebuild
3. **Str::random leak**: missing `Str::createRandomStringsNormally()` caused 50 cascading slug collisions
4. **null eventId in autoPlacement**: `TambahPeserta`/`SelfRegister` mount without event context — null guard added
5. **Stale test bugs**: `peserta_id` column lookup on participations, gender null on mount assertions

### Tests added
- 17 Sprint 8B contract tests (`Sprint8BPhysicalReguRetirementTest.php`)
  - Column absence, model without column, null eventId safety, mount without event, with-event placement, database route rendering
  - `leastFilledRegu` strict int contract enforced

### Final verified baseline
- **Full suite: 1581 passed / 3774 assertions / 0 failures**
- **Design C: problem_total = 0**
- Baseline increase from PGM.18 Sprint 3: +73 tests, +150 assertions

### Sprint 8B final architecture
```
Canonical:
  Participation.regu_id — PRESERVED (event-scoped)

Retired:
  pesertas.regu_id — column dropped
  peserta::regu() — relationship deleted
  regu::peserta() — relationship deleted
  regu dual-write — stopped
  global regu fallback — eliminated
```

### Refactored (LegacyPesertaMapping — peserta↔Person ONLY, columns dropped)
- **Migration executed**: `participation_id`, `event_id`, `backfill_batch_id` dropped from `legacy_peserta_mappings` table
- **Model cleanup**: Removed `participation()`, `event()` relationships; removed deprecated columns from `$fillable`
- **New inverse relationship**: `Participation::legacyParticipationMapping()` — canonical replacement for `Participation::legacyPesertaMapping()`
- **Event cleanup**: Removed `Event::legacyPesertaMappings()` relationship

### Refactored (10 Participation-rooted callers → LegacyParticipationMapping)
- `routes/web.php` (4 call sites): QR label filtered/A4 prints
- `app/Livewire/QRLabel/Index.php` (3 call sites): eager load + filter
- `app/Livewire/Database/Peserta/Database.php` (2 call sites): display data
- `app/Livewire/Dashboard/Scan.php` (1 call site): eager load Participation

### Removed stale imports
- `LegacyPesertaMapping` from `AttendanceService.php`
- `LegacyPesertaMapping` from `Scan.php`

### Refactored test fixtures
- `LegacyPesertaMappingFoundationTest.php` — removed column existence assertions for dropped columns
- `Sprint2MappingContractTest.php` — removed `participation_id`/`event_id` assertions (columns no longer exist)

### Added regression test
- `tests/Feature/LegacyPesertaMapping/Sprint3MappingFinalContractTest.php` — 9 tests covering:
  - Fillable only includes active contract columns
  - No `participation()`, `event()` relationships
  - Still has `peserta()`, `person()` relationships
  - `Participation` has `legacyParticipationMapping()`, not `legacyPesertaMapping()`
  - Schema has no deprecated columns; active contract columns present
  - Creation without deprecated fields succeeds
  - Full bridge chain via LegacyParticipationMapping

### Verified Baseline
- **Full suite**: 1508 passed / 3624 assertions / 0 failures
- **Design C diagnostic**: problem_total = 0
- Baseline increase from Sprint 2 (+9 tests, +32 assertions) from Sprint 3 regression test addition

## Added (PGM.18 Sprint 2 — Legacy Mapping Contract Refactoring)

### Refactored (LegacyPesertaMapping contract — peserta↔Person ONLY)
- `LegacyPesertaMapping` foundation test: 22 → 14 tests (10 kept, 8 removed, 4 refactored)
- Factory/fixture `LPesertaMappingFactory_makeMapping()`: default hanya membuat peserta_id + person_id
- Removed: `participation_id`, `event_id`, `backfill_batch_id` from fixture defaults
- Removed stale test coverage: `belongsTo participation/event`, `hasOne participation`, `hasMany event`, `participation_id` unique, `participation_id`/`event_id` FK SET NULL, `backfill_batch_id` nullable
- Schema test converted to explicit transitional documentation (inert columns until Sprint 3)

### Fixed (6 regression failures from Sprint 2 fixture refactor)
- **4 Activity Foundation tests** (`CategoryFoundationTest:332`, `DomainFoundationTest:210`, `ReportingIntegrationTest:182`, `VenueRundownFoundationTest:254`): replaced `$mapping->participation`/`$mapping->event` assertions with `LegacyParticipationMapping`-based assertions
- **CaiParticipantReplacementTest:272**: replaced `$mapping->participation_id` with `LegacyParticipationMapping` assertion
- **PrintLogTest:246**: genuine production regression — route handler `$participant->legacyPesertaMapping()` returned null (depended on `participation_id` no longer written). Fix: route now resolves via `LegacyParticipationMapping`

### Fixed (ParseError)
- `CaiParticipantReplacementTest.php:272`: syntax error — expect chain not closed before assignment

### Fixed (Stale contract references in other tests)
- `PersonReuseTest:96` and `MultiEventValidationRoutingTest:152`: `LegacyPesertaMapping::where('event_id',…)` → `LegacyParticipationMapping`
- `DesignCDiagnosticsTest:66`: removed stale `participation_id => null, event_id => null` from `LegacyPesertaMapping::create()`

### Model Audit (LegacyPesertaMapping.php)
- `participation()`, `event()` relationships: **DEPRECATED** — zero production runtime access
- `participation_id`, `event_id`, `backfill_batch_id` in `$fillable`: **DEPRECATED** — dijadwalkan removal Sprint 3
- `Participation::legacyPesertaMapping()` (hasOne via `participation_id`): **DEPRECATED** — 6 implicit runtime references via eager loads, perlu migrasi ke `LegacyParticipationMapping` di Sprint 3

### Production Zero-Reference Audit
- `LegacyPesertaMapping->participation`: 0 production references ✅
- `LegacyPesertaMapping->event`: 0 production references ✅
- `LegacyPesertaMapping.participation_id` (direct): 0 production references ✅
- `LegacyPesertaMapping.event_id`: 0 production references ✅
- `LegacyPesertaMapping.backfill_batch_id`: 0 production references ✅

### Not Changed
- `LegacyPesertaMapping` model — deprecated relationships/fillable retained (Sprint 3 target)
- `LegacyParticipationMapping` — remains sole event-specific bridge
- No database migration
- No column dropped

### Verified Baseline
- **Full suite**: 1499 passed / 3592 assertions / 0 failures
- **Design C diagnostic**: problem_total = 0 (all 8 metrics 0)
- Increase from Sprint 1: +5 tests, +11 assertions (refactored test coverage added back)

## Added (PGM.18 Sprint 1 — Legacy Historical Tooling Removal)

### Removed (6 Artisan commands — zero production callers)
- `BackfillLegacyPeserta` (`backfill:legacy-peserta`)
- `BackfillLegacyParticipation` (`backfill:legacy-participation`)
- `BackfillPersonKelompok` (`app:backfill-person-kelompok`)
- `RebuildLegacyMappings` (`app:rebuild-legacy-mappings`)
- `AttendanceBackfill` (`attendance:backfill`)
- `SuratIzinBackfill` (`surat-izin:backfill`)

### Removed (4 service classes — zero production callers)
- `LegacyPesertaBackfillService`
- `LegacyParticipationBackfillService`
- `BackfillReport`
- `BackfillReportItem`

### Removed (4 pure historical tooling test files)
- `tests/Feature/LegacyPesertaMapping/LegacyPesertaBackfillTest.php` (850 lines)
- `tests/Feature/MasterData/PersonKelompokBackfillTest.php` (316 lines)
- `tests/Feature/Database/AttendanceBackfillFoundationTest.php` (588 lines)
- `tests/Feature/Database/AttendanceBackfillRegressionTest.php` (200 lines)

### Refactored (2 mixed test files — removed backfill-specific tests)
- `tests/Feature/Database/AttendanceMigrationVerificationTest.php` — removed 2 backfill tests, retained 9 runtime/parity tests
- `tests/Feature/Registrasi/LegacyParticipationBridgeFoundationTest.php` — removed 2 backfill tests, retained 7 runtime bridge tests

### Fixed (Design C diagnostic contract)
- `app/Console/Commands/DesignCDiagnostics.php` — `legacy_peserta_pointing_to_missing_participation` now excludes NULL participation_id. NULL is a valid forward-reference state (schema allows nullable FK with nullOnDelete).

### Added (diagnostic contract regression test)
- `tests/Feature/DesignCDiagnosticsTest.php` — 2 tests covering: (A) NULL participation_id not counted, (B) fully valid mappings produce problem_total=0. Test C (broken FK) documented as impossible due to FK nullOnDelete constraint.

### Updated
- `app/Console/Commands/AttendanceDiagnose.php` — removed informational string referencing removed `attendance:backfill` command.

### Not Changed
- Runtime `AttendanceBackfillService` and `SuratIzinBackfillService` — retained (active runtime services)
- `LegacyPesertaMapping`, `LegacyParticipationMapping` — retained
- `RegistrationService`, `AttendanceService` — unchanged
- Schema/migrations — unchanged
- `ATTENDANCE_LEGACY_WRITE` default — unchanged (deferred to Sprint 2)
- Database — no data mutation

### Verified Baseline
- **Full suite**: 1494 passed / 3581 assertions / 0 failures
- **Design C diagnostic**: problem_total = 0 (all 8 metrics 0)
- Test reduction from 1570 is EXPECTED — 76 historical tests removed, 1 regression test added (net -75). Not a regression.

## Added (PGM.17 — Pilot Release UI Remediation)

### Global UI Interaction + Mobile/Dark Mode Audit ✅
- **Finding 1 — Invisible clickable controls**: Fixed `app-logo-icon.blade.php` (SVG was fully commented out). Fixed User Management action buttons — replaced `variant="ghost"` (invisible) with `variant="outline"`/`variant="danger"` and always-visible text labels.
- **Finding 2 — Batal/Tutup close controls**: Added `flux:modal.close` buttons to all 30 modals. Standardized close mechanism across all modal implementations. Previously 16 modals had no close button whatsoever.
- **Finding 3 — Dark mode button contrast**: Removed custom `bg-emerald-600` overrides from `variant="primary"` buttons in Event Role and Committee Management modals that broke dark mode contrast.
- **Finding 4 — Mobile User Management**: Responsive action column with desktop (3 visible buttons) and mobile (single "Aksi" dropdown with Flux menu items) layouts. Email and "Dibuat" columns hidden on `<lg` viewports.
- **Flux native icons**: Replaced all 6 inline SVG instances in User Management actions (3 desktop + 3 mobile dropdown) with Flux `icon` attribute (`pencil`, `lock-closed`, `trash`), fixing oversized SVG rendering in flex layouts.
- **Dark mode row hover**: Fixed `dark:hover:bg-zinc-800/50` → `dark:hover:bg-zinc-900/50` matching project convention, preventing white row background on hover in dark mode.
- **Data-testid selectors**: Added `data-testid="user-row-{{ $id }}"` and `data-testid="delete-user-{{ $id }}"` for precise row-scoped test assertions.
- **RBAC preserved**: `manage-users` gate unchanged (SuperAdmin only). Self-delete protection preserved. Unauthorized roles correctly rejected.
- **Tests**: 22 UI regression tests (GlobalInteractionTest) covering modal close controls, action visibility, dispatch-to-modal triggers, RBAC enforcement, dark mode hover, responsive layout, and logo rendering.

### Files Changed (PGM.17)
- `resources/views/components/app-logo-icon.blade.php`
- `resources/views/livewire/master-data/user/index-user.blade.php`
- `resources/views/livewire/event/event-role-manager.blade.php`
- `resources/views/livewire/event/committee-management.blade.php`
- `resources/views/livewire/master-data/user/delete-user.blade.php`
- `resources/views/livewire/surat-izin/create.blade.php`
- `resources/views/livewire/database/peserta/ganti-peserta.blade.php`
- `resources/views/livewire/database/sesi/edit-sesi.blade.php`
- `resources/views/livewire/database/sesi/hapus-sesi.blade.php`
- `resources/views/livewire/database/sesi/tambah-sesi.blade.php`
- `resources/views/livewire/database/regu/edit-regu.blade.php`
- `resources/views/livewire/database/regu/tambah-regu.blade.php`
- `resources/views/livewire/database/kelompok/edit-kelompok.blade.php`
- `resources/views/livewire/database/kelompok/tambah-kelompok.blade.php`
- `resources/views/livewire/database/desa/edit-desa.blade.php`
- `resources/views/livewire/database/desa/tambah-desa.blade.php`
- `resources/views/livewire/database/peserta/tambah-peserta.blade.php`
- `resources/views/livewire/database/peserta/edit-peserta.blade.php`
- `resources/views/livewire/master-data/user/create-user.blade.php`
- `resources/views/livewire/master-data/user/edit-user.blade.php`
- `resources/views/livewire/master-data/user/reset-password-user.blade.php`
- `resources/views/livewire/master-data/person/create-person.blade.php`
- `resources/views/livewire/master-data/person/edit-person.blade.php`
- `resources/views/livewire/dashboard/dashboard.blade.php`
- `tests/Feature/Ui/GlobalInteractionTest.php` — NEW (22 tests)

### Verified Baseline
- Full suite: 1570 passed / 3803 assertions / 0 failures
- Design C diagnostic: problem_total = 0

## Added (Database V2 — Part 5 Closure)
- Closed Database V2 Part 5 documentation with semantic coverage audit
- Recorded final Design C boundary: Person, Participation, peserta, LegacyPesertaMapping, LegacyParticipationMapping
- Documented deletion limitation from `legacy_peserta_mappings.participation_id` NOT NULL + `restrictOnDelete`
- Verified baseline recorded as 1533 passed / 3677 assertions / 0 failures
- Historical test delta note added: exact -2/+16 reconstruction not possible because relevant Part 5 tests were untracked during development

## Added (S7 — Event-Scoped Authorization)

### S7.1 — User↔Person Foundation ✅
- Added `person_id` (nullable, UNIQUE, FK→people) to `users` table
- `User::person()` BelongsTo, `Person::user()` HasOne, `User::hasPerson()` helper
- Cross-event IDOR fixes: HapusSesi, DataSesi, EditSesi, SuratIzinService (session query scoped)

### S7.2 — Event-Scoped Gates + EventSwitcher ✅
- `EventAccessService` with `isUserAssignedToEvent()` and `getAssignedEventIds()`
- 7 Gate abilities event-scoped for KetuaEvent: view-dashboard, manage-registration, manage-participants, manage-attendance, manage-sessions, manage-secretariat, view-reports
- EventSwitcher: filtered dropdown for KetuaEvent + server-side `switchTo()` enforcement throws AuthorizationException for unassigned events
- All other roles preserve global behavior

### S7.3 — Assignment Management UI ✅
- User Create/Edit: searchable Person selection with uniqueness enforcement
- EventRole Manager: create EventRole records per Event (name, code, description, sort_order)
- Committee Management: list/create/delete EventCommitteeAssignment with Person search + EventRole select
- All mutations gated with `manage-events`
- Event Role and Panitia buttons added to Event Management table

### Files Changed
- `app/Services/Event/EventAccessService.php` — NEW
- `app/Services/User/UserManagementService.php` — person_id create/update support
- `app/Livewire/MasterData/User/CreateUser.php` — Person search/select
- `app/Livewire/MasterData/User/EditUser.php` — Person search/select
- `app/Providers/AppServiceProvider.php` — 7 gates refactored for KetuaEvent scoping
- `app/Livewire/Event/EventSwitcher.php` — KetuaEvent filtering + enforcement
- `app/Livewire/Event/EventRoleManager.php` — NEW
- `app/Livewire/Event/CommitteeManagement.php` — NEW
- `resources/views/livewire/event/index.blade.php` — Role/Panitia buttons
- `resources/views/livewire/master-data/user/*.blade.php` — Person selection UI
- `database/migrations/2026_08_04_000001_add_person_id_to_users_table.php` — NEW

### Tests Added
- 95 new tests across S7.1–S7.3 (22 + 45 + 28)
- Full suite: 1324 tests passed, 3135 assertions

## Added (S6 — Sidebar Visibility RBAC)

### Sidebar @can Directives
- All sidebar menu items now gated with `@can()` or `@canany()` directives matching backend Gate abilities:
  - **CAI Navigation**: Dashboard (`view-dashboard`), Absensi group (`manage-attendance`, `manage-sessions`), Registrasi (`manage-registration`), Peserta CAI (`manage-participants`), Laporan (`view-reports`), QR & Label (`manage-qr-labels`), Sekretariat group (`manage-secretariat`, `view-activity-log`), Master Data (`view-master-data`), User Management (`manage-users`)
  - **Pengajian Navigation**: Regional Report (`view-reports`), Peserta group + Operasional Desa (`manage-pengajian`)
- **Parent group gating**: Absensi and Sekretariat groups use `@canany()` — shown if user has ANY sub-ability; child items independently gated with `@can()`
- **EventSwitcher**: intentionally ungated — visible to all authenticated users (navigation UX, not permission gate)
- **Kelola Event**: intentionally ungated — server-side `manage-events` protection
- **Contextual consistency**: CAI and Pengajian event contexts both use the same gate mechanism

### Files Changed
- `resources/views/components/layouts/app/sidebar.blade.php` — added `@can()` / `@canany()` directives to all menu items

### Documentation Updated
- `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/ai/CURRENT_STATE.md`, `docs/CHANGELOG.md`, `README.md`

---

## Added (S3 — Event Management & CAI Operational Protection)

### Route Protection
- **11 routes** protected with `can:*` middleware:
  - `view-dashboard` → `/dashboard`
  - `manage-registration` → `/registrasi`, `/registrasi/ulang`
  - `manage-participants` → `/database`
  - `manage-sessions` → `/sesi-absensi`
  - `view-reports` → `/rekap-peserta`, `/rekap-absensi`
  - `manage-qr-labels` → `/qr-label`
  - `manage-attendance` → `/absensi`
  - `manage-secretariat` → `/surat-izin`
  - `view-activity-log` → `/activity-log`

### Livewire Mutation Protection
- **16 components** gated with `Gate::authorize()`:
  - `manage-events`: Event\Index (render, archive, activate), Event\EditStatus (update)
  - `manage-participants`: Database\Peserta\TambahPeserta, EditPeserta, HapusPeserta, ImportPeserta; Registrasi\Ulang
  - `manage-sessions`: Database\Sesi\TambahSesi, EditSesi, HapusSesi; Dashboard\Dashboard (setSesiAktif)
  - `manage-attendance`: Dashboard\Scan (scanQR, manualHadir, manualIzin)
  - `manage-qr-labels`: QRLabel\Index (downloadPng, printSelected, generateBatchExport, exportBatch)
  - `view-reports`: Rekap\Peserta\RekapPeserta (exportExcel)
  - `manage-secretariat`: SuratIzin\Index (submit, approve, reject, cancel, return); SuratIzin\Create (simpan, submit)

### Access Matrix Verified
- All 10 operational abilities verified per role against permission matrix
- Unauthorized route access returns HTTP 403
- Unauthorized Livewire mutation returns 403 without state mutation

### Sidebar Visibility
- NOT YET implemented for S3 modules (deferred to S6)

### Files Changed
- `routes/web.php` — added `can:*` middleware to 11 operational routes

### Documentation Updated
- `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/ai/CURRENT_STATE.md`, `docs/CHANGELOG.md`, `README.md`

---

## Added (Security & Data Integrity — NIP Enforcement + Kelompok Sync)

### Server-side NIP Enforcement
- **Vulnerability fixed**: NIP lock was UI-only (disabled HTML input). Livewire state manipulation could bypass NIP immutability for mapped Persons.
- **`PersonLegacySyncService::resolveNip()`** — new server-side guard: for mapped Persons, always returns the existing database NIP regardless of submitted value. For standalone Persons, returns the submitted value.
- **`EditPerson::update()`** — now uses `resolveNip()` instead of relying on the front-end `nipLocked` boolean. The Person model update always includes `nip` (resolved server-side), eliminating the split `if (! nipLocked)` code path.
- Tests confirm: manipulated `nip` and `nipLocked` Livewire state cannot change NIP for mapped Persons.

### RegistrationService kelompok_id Sync (Data Consistency Fix)
- **`createParticipant()`** — now sets `kelompok_id` on the newly created Person (was missing, causing Person.kelompok_id to always be null for event-registered participants).
- **`updateParticipant()`** — now syncs `kelompok_id` to Person when a legacy peserta is edited via CAI Database UI (was missing, causing two-way identity inconsistency where Person.kelompok_id could differ from peserta.kelompok_id).
- This closes a real two-way sync gap: now both `desa_id` and `kelompok_id` are consistently synced in both directions (Person ↔ peserta).

### Tests Added
- 12 new tests covering: NIP server-side enforcement, mapped Person NIP immutability, peserta.nip unchanged, standalone NIP change still works, identity sync after enforcement, no Participation created, RegistrationService createParticipant kelompok_id sync, RegistrationService updateParticipant kelompok_id sync.

---

## Added (Person-Legacy Sync)

### PersonLegacySyncService
- **New service** `App\Services\Person\PersonLegacySyncService` — handles safe one-way sync from Person → legacy peserta
- **Sync boundary**: Only `nama`, `jenis_kelamin`, `desa_id`, `kelompok_id` are synced
- **NOT synced**: `nip`, `regu_id`, `participant_number`, `attendance_code`, `status_registrasi`
- **Gender normalization**: Person's `L`/`P` is converted to `Laki - Laki`/`Perempuan` via `PlacementService::normalizePersonGender()`
- **Transaction safety**: Uses `DB::transaction` for atomic updates
- Only runs when Person has a valid `LegacyPesertaMapping` — standalone Persons are not affected

### EditPerson (Master Data)
- **Auto-sync**: When editing a mapped Person, identity fields are automatically synced to the legacy peserta record
- **NIP locked**: For mapped Persons, NIP field is disabled/read-only to protect attendance history and legacy compatibility. Standalone Persons can still set NIP.
- **Legacy indicator**: Badge "Terhubung dengan data peserta legacy" shown in edit modal for mapped Persons
- **No Participation created**: Edit Person does not create new Participation records

### Files Created
- `app/Services/Person/PersonLegacySyncService.php`

### Files Changed
- `app/Livewire/MasterData/Person/EditPerson.php` — injected sync service, NIP lock, legacy mapping detection
- `resources/views/livewire/master-data/person/edit-person.blade.php` — NIP disabled state, legacy mapping badge

### Tests Added
- 14 new tests in `tests/Feature/MasterData/PersonMasterDataTest.php`:
  - Sync nama, desa, kelompok, gender (L/P) to peserta
  - participant_number, attendance_code, regu_id unchanged
  - No new Participation created
  - Standalone Person does not create peserta
  - NIP locked for mapped; NIP changeable for standalone
  - Delete guard still works

---

## Added (Sprint S2 — Master Data Protection)

### Route Protection
- Routes `/master-data`, `/person`, `/desa`, `/kelompok` protected with `can:view-master-data` middleware
- Import routes `/import/desa`, `/import/kelompok` protected with `can:manage-master-data`
- Authorized roles (super_admin, admin, sekretariat) → HTTP 200
- Unauthorized roles → HTTP 403
- Guest → redirect login
- Null role → HTTP 403
- `/regu` explicitly excluded from Master Data protection

### Livewire Mutation Protection
- `Gate::authorize('manage-master-data')` added to ALL mutation methods:

**Person**: `CreatePerson::simpan()`, `EditPerson::update()`, `DeletePerson::destroy()`

**Desa**: `TambahDesa::simpan()`, `EditDesa::update()`, `HapusDesa::destroy()`, `ImportDesa::import()`

**Kelompok**: `TambahKelompok::simpan()`, `EditKelompok::update()`, `HapusKelompok::destroy()`, `ImportKelompok::import()`

- Authorization occurs BEFORE any database write
- Unauthorized Livewire invocation returns 403 without mutating state

### Sidebar Visibility
- Master Data menu wrapped in `@can('view-master-data')`
- Hidden for: ketua_event, pj_divisi, operator_registrasi, operator_scan, juri, viewer, null role

### Files Changed
- `routes/web.php` — `can:view-master-data` middleware on 4 routes, `can:manage-master-data` on 2 import routes
- `app/Livewire/MasterData/Person/CreatePerson.php` — +Gate::authorize()
- `app/Livewire/MasterData/Person/EditPerson.php` — +Gate::authorize()
- `app/Livewire/MasterData/Person/DeletePerson.php` — +Gate::authorize()
- `app/Livewire/Database/Desa/TambahDesa.php` — +Gate::authorize()
- `app/Livewire/Database/Desa/EditDesa.php` — +Gate::authorize()
- `app/Livewire/Database/Desa/HapusDesa.php` — +Gate::authorize()
- `app/Livewire/Database/Desa/ImportDesa.php` — +Gate::authorize()
- `app/Livewire/Database/Kelompok/TambahKelompok.php` — +Gate::authorize()
- `app/Livewire/Database/Kelompok/EditKelompok.php` — +Gate::authorize()
- `app/Livewire/Database/Kelompok/HapusKelompok.php` — +Gate::authorize()
- `app/Livewire/Database/Kelompok/ImportKelompok.php` — +Gate::authorize()
- `resources/views/components/layouts/app/sidebar.blade.php` — `@can('view-master-data')`

### Tests Added
- `tests/Feature/Security/MasterDataProtectionTest.php` — 36 tests covering:
  - Route access: guest, all 9 roles + null role × 4 routes
  - Sidebar visibility: authorized see, unauthorized hidden, null role hidden
  - Livewire mutations: Person create/edit/delete blocked; Desa create/edit/delete/import blocked; Kelompok create/edit/delete/import blocked
  - Authorized mutations still work: admin create person, sekretariat edit desa, super_admin delete kelompok
  - Regression: Regu not affected, Person-Legacy sync works, ActiveEventContext unchanged, Pengajian public flow, S1 Gates intact

---

## Added (Sprint S1 — RBAC Foundation)

### Role Enum & Migration
- **`app/Enums/Role.php`** — PHP backed string enum with 9 roles:
  - `super_admin`, `admin`, `ketua_event`, `sekretariat`, `pj_divisi`, `operator_registrasi`, `operator_scan`, `juri`, `viewer`
- **Migration** `2026_08_03_000001` — adds nullable `role` string column to `users` table
- **Cast**: `User::role` casts to `Role` enum (null-safe)
- **Helpers**: `hasRole()`, `hasAnyRole()` on User model

### Gate Foundation (15 abilities)
- Defined in `AppServiceProvider::boot()` via `Gate::define()`
- **Super Admin bypass**: `Gate::before()` returns `true` for Super Admin (all abilities granted)
- Abilities: `view-dashboard`, `view-master-data`, `manage-master-data`, `manage-events`, `manage-registration`, `manage-participants`, `manage-attendance`, `manage-sessions`, `manage-qr-labels`, `manage-secretariat`, `manage-import`, `view-reports`, `manage-pengajian`, `view-activity-log`, `manage-users`

### Artisan Command
- **`php artisan user:set-role {email} {role}`** — sets role for existing user
- Validates role against enum, rejects invalid roles, preserves existing user data

### Important — S1 Scope Only
- Gates are DEFINED but NOT YET attached to routes, Livewire components, or sidebar
- All existing routes remain accessible as baseline
- Future sprints (S2–S7) will apply authorization checks

### Files Created
- `app/Enums/Role.php`
- `app/Console/Commands/UserSetRole.php`
- `database/migrations/2026_08_03_000001_add_role_to_users_table.php`
- `tests/Feature/Security/RbacFoundationTest.php`

### Files Changed
- `app/Models/User.php` — `$fillable` + `role` cast + `hasRole()`/`hasAnyRole()` helpers
- `app/Providers/AppServiceProvider.php` — 15 Gate definitions + Super Admin bypass

### Tests Added
- `tests/Feature/Security/RbacFoundationTest.php` — 35 tests covering:
  - Role enum values, labels, invalid values
  - User model: role cast, null role, hasRole, hasAnyRole
  - Super Admin bypass (all 15 abilities)
  - Admin, Sekretariat, Ketua Event, PJ Divisi, Operator Registrasi, Operator Scan, Viewer permissions
  - Null role denied all privileged abilities
  - Guest denied all privileged abilities
  - Artisan command: valid, invalid role, unknown email, preserves data
  - Regression: existing routes still work in S1
  - Guest behavior unchanged
  - Pengajian public flow unchanged

---

## Added (Master Data Landing Page & Navigation Refactor)

### Master Data Landing Page
- **New route** `GET /master-data` (named `master-data.index`) — authenticated, global, no event dependency
- **Landing page** at `/master-data` with navigation hub design:
  - 3 clickable cards: Person, Desa, Kelompok
  - Each card has unique icon + description + named route link
  - Responsive: 1-col mobile, 2-col tablet, 3-col desktop
  - Dark mode, hover/focus states

### Sidebar Refactor
- **Before**: Expandable group "Master Data" with 4 sub-items (Person, Desa, Kelompok, Regu)
- **After**: Single `flux:navlist.item` "Master Data" pointing to `/master-data`
- Active state covers `/master-data`, `/person`, `/desa`, `/kelompok` (Regu excluded)
- `wire:navigate` preserved for SPA navigation

### Reclassification: Regu
- **Removed** from Master Data navigation and landing page
- **Preserved**: route `/regu`, Livewire components, model, table — fully backward compatible
- Reclassified as **Legacy CAI Operational Structure** (not global master data)
- Regu will be moved to CAI/event-scoped configuration in a future refactor

### Files Created
- `resources/views/master-data/index.blade.php` — Master Data landing page

### Files Changed
- `routes/web.php` — added `master-data.index` route
- `resources/views/components/layouts/app/sidebar.blade.php` — Master Data → single link

### Tests Updated
- `tests/Feature/MasterData/MasterDataNavigationTest.php` — rewritten for new architecture: landing page cards, sidebar link, Regu backward compat, guest/auth access, event context isolation
- `tests/Feature/MasterData/PersonMasterDataTest.php` — sidebar submenu tests replaced with landing page card tests

---

## Added (Person Master Data CRUD)

### Person CRUD (Global Master Data)
- **New Person CRUD** at `/person` — dedicated management page for Person global identity
- **Index** — table with search, pagination, fields: Nama, Jenis Kelamin, Desa, Kelompok, NIP, Tanggal Lahir
- **Create** — modal form with validation; creates Person only (no Participation, no auto-placement, no NIP generation)
- **Edit** — modal form with validation; preserves relationships
- **Delete** — safety-guarded: blocks deletion if Person has Participations, LegacyPesertaMapping, or CommitteeAssignments; safe deletion for unattached Persons
- **Sidebar** — "Person" added as first submenu under Master Data
- **Route** — `GET /person` (named `person.index`) with `auth` + `verified` middleware
- **ActiveEventContext** — Person page is fully global; no event dependency; does not modify context
- **No database migration** — uses existing `people` table schema

### Files Created
- `app/Livewire/MasterData/Person/IndexPerson.php`
- `app/Livewire/MasterData/Person/CreatePerson.php`
- `app/Livewire/MasterData/Person/EditPerson.php`
- `app/Livewire/MasterData/Person/DeletePerson.php`
- `resources/views/livewire/master-data/person/index-person.blade.php`
- `resources/views/livewire/master-data/person/create-person.blade.php`
- `resources/views/livewire/master-data/person/edit-person.blade.php`
- `resources/views/livewire/master-data/person/delete-person.blade.php`
- `resources/views/master-data/person/index.blade.php`

### Files Changed
- `routes/web.php` — added `person.index` route
- `resources/views/components/layouts/app/sidebar.blade.php` — added Person to Master Data

### Tests Added
- `tests/Feature/MasterData/PersonMasterDataTest.php` — 26 tests covering route integrity, guest/auth access, sidebar visibility, no-active-event rendering, context isolation, CRUD create/edit/delete safety, search, empty state, and regression on Desa/Kelompok/Regu

### Documentation Updated
- `docs/TODO.md`, `docs/ROADMAP.md`, `docs/CHANGELOG.md`, `docs/HANDOFF.md`, `docs/FEATURE.md`, `docs/MODULES.md`, `docs/ai/CURRENT_STATE.md`

---

## Added (Master Data Navigation)

### Master Data Menu
- **New "Master Data" navigation group** added to sidebar — appears for all authenticated users regardless of event context
- Submenu items: **Desa** (`/desa`), **Kelompok** (`/kelompok`), **Regu** (`/regu`) — all are global master data (no `event_id`)
- **Administrasi** group removed from sidebar (Desa and Kelompok moved to Master Data)
- **Regu** removed from "Peserta CAI" group (moved to Master Data as global data)
- Parent menu "Master Data" replaces former "Administrasi" section with added Regu entry
- Global master data pages render correctly with or without active event context
- ActiveEventContext is not affected by Master Data navigation

### Files Changed
- `resources/views/components/layouts/app/sidebar.blade.php` — added Master Data group, removed Administrasi group, moved Regu from Peserta CAI

### Tests Added
- `tests/Feature/MasterData/MasterDataNavigationTest.php` — 21 tests covering visibility, accessibility, guest restriction, route integrity, ActiveEventContext isolation, and submenu rendering

### Documentation Updated
- `docs/TODO.md`, `docs/ROADMAP.md`, `docs/CHANGELOG.md`, `docs/HANDOFF.md`, `docs/MODULES.md`, `docs/FEATURE.md`, `docs/ai/CURRENT_STATE.md`, `docs/TERMINOLOGY.md`, `docs/DATAFLOW.md`, `docs/DATABASE.md`

---

## Added (UI Bug Fix Sprint — Batch 1 Branding & Navigation)

### Verification
- **Full test suite**: 908 passed, 2192 assertions, 0 failures
- **Runtime verification 1–5**: `/` (guest), `/` (auth), `/login`, sidebar CAI (no Pengajian), sidebar Pengajian (shows Pengajian) — all confirmed
- **Bug status**: #1, #2, #4, #6 → RESOLVED — VERIFIED ✅

## Added (UI Bug Fix Sprint — Batch 4 Final UI Polish)

### Bug #11 — Responsive `/pengajian` Layout
- **Root cause**: Layout `simple.blade.php` memiliki `max-w-sm` permanen yang berlaku untuk semua breakpoint, menyebabkan halaman terlihat seperti mobile pada desktop
- **Changes**:
  - `simple.blade.php`: `max-w-sm` → `max-w-sm md:max-w-xl lg:max-w-2xl`
  - `enter-token.blade.php`: tambah `max-w-sm md:max-w-lg mx-auto`
- **Responsive behavior**:
  - Mobile (<768px): `max-w-sm` (384px) — compact, nyaman digunakan
  - Tablet (768px-1024px): `max-w-xl` (576px) — sedikit lebih lebar
  - Desktop (>1024px): `max-w-2xl` (672px) — memanfaatkan ruang desktop
- **Files**: `resources/views/components/layouts/auth/simple.blade.php`, `resources/views/livewire/pengajian/enter-token.blade.php`
- **Status**: IMPLEMENTED — PENDING RUNTIME VERIFICATION

### Bug #10 — Dark Mode
- **Audit result**: Semua elemen sudah memiliki dark mode classes yang tepat (`dark:text-white`, `dark:text-zinc-300`, `dark:bg-zinc-900`, dll)
- **Status**: IMPLEMENTED — PENDING RUNTIME VERIFICATION
- **Note**: Runtime verification diperlukan untuk memastikan tidak ada masalah spesifik di device/browser tertentu

---

## Added (UI Bug Fix Sprint — Batch 3 Functional/UI Logic)

### Bug #3 — Dashboard "Belum Absen" Statistics
- **Root cause**: Tabel "Peserta yang Belum Absen" di `dashboard.blade.php` mengakses properti legacy `peserta` (`$peserta->nama`, `$peserta->nip`, dll) padahal `$pesertaBelumAbsen` adalah koleksi `Participation` model
- **Changes**:
  - Eager loading ditambah: `person.legacyPesertaMapping.peserta.regu`, `person.legacyPesertaMapping.peserta.kelompok`
  - View: `$peserta->nama` → `$participation->person?->nama`, `$peserta->nip` → `$participation->person?->nip`, dll
- **Files**: `app/Livewire/Dashboard/Dashboard.php`, `resources/views/livewire/dashboard/dashboard.blade.php`
- **Status**: IMPLEMENTED — PENDING RUNTIME VERIFICATION

### Bug #9 — Regional Report Filter Reactivity
- **Root cause**: Filter logic di `PengajianRegionalReportService::attendanceList()` sudah benar (dibuktikan 12 test PGM.16 lolos), namun binding Livewire `wire:model` tanpa modifier tidak menjamin reaktivitas segera
- **Changes**: `wire:model` → `wire:model.live` untuk kedua `<select>` filter (Status dan Metode)
- **Files**: `resources/views/livewire/pengajian/regional-report.blade.php`
- **Tests added**: 6 Livewire component filter tests (PGM.17)
- **Status**: IMPLEMENTED — PENDING RUNTIME VERIFICATION

---

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

## Added (S4 — Pengajian Admin Protection)

### Route Protection
- **4 routes** protected with `can:manage-pengajian` middleware:
  - `/koreksi-data` → `manage-pengajian` (Identity Correction Review)
  - `/pengajian/admin/access` → `manage-pengajian` (Access Token Management)
  - `/pengajian/admin/manual-entry` → `manage-pengajian` (Manual Participant Entry)
  - `/pengajian/admin/import-massal` → `manage-pengajian` (Bulk Import)
- Previously accessible by ALL authenticated users — now gated to super_admin, admin, sekretariat

### Livewire Mutation Protection
- **4 components** gated with `Gate::authorize('manage-pengajian')`:
  - `Pengajian\Admin\AccessIndex` (create, revoke, delete)
  - `Pengajian\Admin\ManualEntry` (submit, confirmMatch, createNewPerson)
  - `Pengajian\Admin\ImportMassal` (preview, executeImport)
  - `Pengajian\IdentityCorrectionReview` (approve, reject)

### Pre-S4 Audit
- Confirmed all 4 routes were previously unprotected (any authenticated user could access)
- Public Pengajian flows (token entry, self-attendance) remain unchanged

### Files Changed
- `routes/web.php` — added `can:manage-pengajian` middleware to 4 routes

### Documentation Updated
- `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/ai/CURRENT_STATE.md`, `docs/CHANGELOG.md`, `README.md`

---

## Added (S5 — CAI Module Permissions)

### Route Protection
- **2 routes** protected with `can:manage-import` middleware:
  - `/import/peserta` → `manage-import` (CAI Participant Import)
  - `/import/regu` → `manage-import` (CAI Regu Import)

### Livewire Mutation Protection
- **2 components** gated with `Gate::authorize('manage-import')`:
  - `Database\Peserta\ImportPeserta::import()`
  - `Database\Regu\ImportRegu::import()`

### Access Matrix Verified
- `manage-import` ability verified per role: super_admin, admin, sekretariat
- Unauthorized route access returns HTTP 403
- Unauthorized Livewire mutation returns 403 without state mutation

### Full CAI Permission Matrix Complete
- All 15 Gate abilities now applied across routes and/or Livewire mutations
- S1: 15 abilities defined ✅
- S2: view-master-data, manage-master-data ✅
- S3: 10 operational abilities ✅
- S4: manage-pengajian ✅
- S5: manage-import ✅
- S0: manage-users ✅

### Files Changed
- `routes/web.php` — added `can:manage-import` middleware to 2 import routes

### Documentation Updated
- `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/ai/CURRENT_STATE.md`, `docs/CHANGELOG.md`

---

## [Unreleased]

### Added (S0 — User Management)

#### Route Protection
- `/users` protected with `can:manage-users` middleware — Super Admin only

#### Livewire Components
- `User\Index` — searchable user list with pagination
- `User\Create` — new user form (name, email, password, role)
- `User\Edit` — edit user profile and role
- `User\ResetPassword` — admin-initiated password reset
- `User\Delete` — delete user with safety guard

#### Service Layer
- `UserManagementService` — centralized CRUD business logic for users

#### Safety Rules
- Cannot delete self — user cannot delete their own account
- Cannot delete last Super Admin — at least one Super Admin must remain

#### Activity Log Integration
- `created` — new user account
- `updated` — user profile changes
- `role_changed` — role assignment changes
- `password_reset` — password reset by admin
- `deleted` — user account deleted

#### Security
- Password always hashed via Laravel `Hash::make()`
- Password never exposed in responses, views, or logs

#### Files Changed
- `routes/web.php` — added `/users` route with `can:manage-users` middleware
- `resources/views/components/layouts/app/sidebar.blade.php` — added User Management menu with `@can('manage-users')`

#### Files Created
- `app/Livewire/User/Index.php`
- `app/Livewire/User/Create.php`
- `app/Livewire/User/Edit.php`
- `app/Livewire/User/ResetPassword.php`
- `app/Livewire/User/Delete.php`
- `app/Services/User/UserManagementService.php`
- `resources/views/livewire/user/index.blade.php`
- `resources/views/livewire/user/create.blade.php`
- `resources/views/livewire/user/edit.blade.php`
- `resources/views/livewire/user/reset-password.blade.php`
- `resources/views/livewire/user/delete.blade.php`
- `resources/views/user/index.blade.php`

#### Documentation Updated
- `docs/PERMISSION.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`, `docs/TODO.md`, `docs/HANDOFF.md`, `docs/FEATURE.md`, `docs/MODULES.md`, `docs/ai/CURRENT_STATE.md`, `docs/CHANGELOG.md`

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
