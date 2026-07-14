# REFACTOR PLAN

> Architecture Refactor Roadmap for KJA Event Manager

Author: Lead Software Architect

Version: 1.0

Date: 2026-07-14

---

# Overview

## Current Architecture Summary

KJA Event Manager (formerly AbsenCAI) is a Laravel 12 web application built on the Livewire + Flux UI + Tailwind CSS stack. The application was originally developed as a simple attendance system for CAI (Cinta Al-Qur'an Indonesia) events, and has since grown to encompass registration, participant database management, attendance sessions, and reporting.

The current architecture follows a Livewire-centric monolith where each UI feature has a dedicated Livewire component that directly queries Eloquent Models and performs all business logic inline. There is no Service Layer, no Enum classes, no Repository pattern, and no DTO layer. The application is currently using SQLite as its database engine, with a planned migration to MariaDB.

The codebase is in an early MVP stage (v1.0 / v1.5 in progress), with most core modules stable but lacking architectural discipline that will be required for the long-term goal of becoming a commercial multi-event platform.

---

## Strengths

- Laravel 12 and Livewire 3 are modern, well-supported frameworks appropriate for the application type.
- Flux UI provides a consistent, accessible component foundation.
- The project already has well-structured documentation (AGENTS.md, ROADMAP.md, FEATURE.md, DATABASE.md, DECISION.md, SECURITY.md, PERMISSION.md).
- The Livewire component folder structure is logically organized by feature domain.
- Import and Export classes are properly separated into dedicated folders (`app/Imports/`, `app/Exports/`).
- The `peserta` model already uses class constants for status values, which is a step toward enum-like patterns.
- The routing file is clean and manageable.
- The project has a clear roadmap from CAI MVP to a commercial multi-event platform.
- Tests are set up using Pest, with a Feature test structure already in place.
- Security principles are well documented (Attendance Code, RBAC, audit, etc.).
- The "Person First" architectural decision (ADR-002) is strategically sound and will prevent duplicate data problems at scale.

---

## Weaknesses

- No Service Layer exists. All business logic lives inside Livewire components and Models.
- No Enum classes. Status and type values are scattered as string constants and raw string literals.
- Model naming convention is inconsistent. Some models use PascalCase (`Absensi`, `SesiAbsensi`, `User`) while others use lowercase (`desa`, `kelompok`, `peserta`, `regu`).
- Business logic is mixed into Livewire components (NIP generation, auto-placement, duplicate prevention, attendance validation).
- Hardcoded string values for `jenis_kelamin` and `jenis_peserta` appear in multiple Livewire components and Models without a single source of truth.
- The `peserta` model carries business logic methods (`autoPlacement`, `leastFilledRegu`, `nextAutoNip`) that belong in a Service class.
- The database structure is still CAI-centric (`desa`, `kelompok`, `regu`, `peserta`) and not yet aligned with the target universal `Person` / `Participation` model.
- The QR system still uses NIP as the QR identifier (a known security issue documented in ADR-003 and SECURITY.md), though this is acknowledged and planned for Sprint 1.
- The `Absensi` table stores both `nip` and `nama` as denormalized columns instead of using a foreign key to the `peserta` table cleanly.
- No Role-Based Access Control (RBAC) implementation exists in code despite being extensively documented.
- The `Ulang.php` Livewire component performs database updates without validation, and dispatches a raw `$refresh` event.
- The `Dashboard.php` Livewire component contains a `logger()` debug call that should not be in production code.
- No global configuration file exists for application-specific constants (NIP ranges, default statuses, etc.).
- There is a leftover file `bkpwelcome.blade.php` in the views directory, indicating uncommitted cleanup debt.
- The `database/backup-sebelum-reset.sqlite` file is committed to the repository, which is a database security risk.
- No `app/Services/` directory exists despite RULES.md explicitly requiring a Service Layer when business logic grows.
- No `app/Enums/` directory despite RULES.md explicitly requiring Enum usage.
- No `app/Policies/` or `app/Gates` implementation despite an extensive PERMISSION.md defining the RBAC matrix.
- No `app/Actions/` directory for single-responsibility operations (only `app/Livewire/Actions/Logout.php` which is Livewire-specific).
- The `composer.json` still identifies the project as `laravel/livewire-starter-kit` rather than the actual project name.

---

## Risks

- The CAI-centric database schema (`desa`, `kelompok`, `peserta`, `regu`) will require a significant migration when transforming to the universal KJA Event Manager model. The longer this migration is deferred, the harder it becomes.
- Business logic embedded in Livewire components is difficult to unit test, reuse, and reason about. As the project grows, this creates compounding maintenance cost.
- The absence of RBAC in code means all routes are currently accessible to any authenticated user, creating a security gap between documentation intent and implementation reality.
- SQLite lacks concurrent write performance for multi-user scenarios, which is explicitly noted as a high-risk item in CURRET_STATE.md.
- Hardcoded string values (jenis kelamin, jenis peserta, status registrasi) distributed across multiple files create a maintenance risk where changing a value requires hunting across the entire codebase.
- No audit log implementation currently exists despite SECURITY.md requiring it.

---

# Current Folder Structure

## app/

### app/Exports/

**Status: Good structure, limited scope.**

Contains `PesertaExport.php`. The folder is correctly placed and the concern is properly separated. However, only one export class exists. As more reports are added, this folder will need sub-organization (e.g., by domain).

### app/Http/Controllers/

**Status: Acceptable, but minimal.**

Contains `ImportDataController.php` and the standard `Auth/` controllers. The controller is appropriately thin — it delegates to Import classes. This is good. However, as the application grows, more controllers may be needed to handle non-Livewire scenarios (e.g., file download endpoints, webhooks).

### app/Imports/

**Status: Good structure.**

Contains import classes for Desa, Kelompok, Peserta, and Regu. The separation is correct. Import logic is appropriately isolated. The `PesertaImport` uses raw SQL in `whereRaw()` for case-insensitive matching, which is acceptable but should be noted.

### app/Livewire/

**Status: Needs improvement.**

This is the core of the current architecture. The folder structure is logically organized by domain:

- `Actions/` — Only contains Logout. Good pattern but underutilized.
- `Auth/` — Standard auth scaffolding. Good.
- `Dashboard/` — Contains Dashboard and Scan. Business logic is mixed in.
- `Database/` — Contains CRUD Livewire components for Desa, Kelompok, Peserta, Regu, and Sesi. Pattern is consistent but repetitive.
- `Registrasi/` — Contains SelfRegister and Ulang. Contains business logic that should be in a Service.
- `Rekap/` — Contains Absensi and Peserta recap components. Query logic should be in a Service or Repository.
- `Settings/` — Standard user settings. Good.

The main issue is that Livewire components are acting as Controllers + Services + sometimes partial Models. This violates single responsibility.

### app/Models/

**Status: Needs improvement.**

Contains 7 models. Issues:

- `desa.php`, `kelompok.php`, `peserta.php`, `regu.php` — lowercase class names violate PHP PSR-4 and Laravel naming conventions. All models should use PascalCase.
- `peserta.php` — Contains auto-placement and NIP generation business logic that belongs in a dedicated Service class.
- `Absensi.php` — Stores `nama` as a denormalized column alongside `nip`. This is data duplication.
- `SesiAbsensi.php` — The name is Indonesian. Code-layer naming should follow English convention per AGENTS.md.

### app/Providers/

**Status: Standard. No issues.**

### app/Exports/ and app/Imports/

**Status: Good, properly separated.**

---

## resources/

### resources/views/

**Status: Needs improvement.**

- `bkpwelcome.blade.php` — Backup file committed to the repository. Should be removed.
- `components/` — Contains layout components. Good.
- `livewire/` — Mirrors the Livewire class structure. Consistent and acceptable.
- `dashboard/` — A separate top-level dashboard view folder exists alongside `livewire/dashboard/`. This creates ambiguity.
- `flux/` — Contains Flux UI component overrides. This is correct but should be clearly documented.

### resources/css/ and resources/js/

**Status: Standard Laravel/Vite structure. No issues.**

---

## routes/

### routes/web.php

**Status: Good, but will need organization as routes grow.**

Currently 78 lines with flat route definitions. No route grouping by module. As new modules are added (Competition, Certificate, API), route files should be split into module-level route files and included from `web.php`.

---

## database/

### database/migrations/

**Status: Functional but shows organic growth.**

15 migration files. The early migrations (2025) reflect the original CAI design. Several later migrations (2026) patch the original schema (adding `status_registrasi`, `jenis_kelamin` nullability, `jenis_peserta`, unique constraints). This is a sign of schema evolution without a clear upfront design. The target design (Organization → Person → Event → Participation) will require a structured migration strategy.

**Critical issue:** `database/backup-sebelum-reset.sqlite` is committed to the repository. This must be removed and added to `.gitignore`.

### database/seeders/ and database/factories/

**Status: Minimal. Development-only. No issues.**

---

## config/

**Status: Only standard Laravel config files present.**

No application-specific configuration file exists. NIP ranges, default statuses, and other application constants are hardcoded in the `peserta` model and Livewire components. An `app/config/kjam.php` (or similar) should be created to centralize these values.

---

## tests/

**Status: Partially established, needs expansion.**

- `tests/Feature/` — Contains DashboardTest, Auth tests, Database tests, and Registrasi tests. A good starting point.
- `tests/Unit/` — Contains only ExampleTest. No real unit tests exist. This is a significant gap given the amount of business logic in Models and Livewire components.

---

# Refactor Objectives

- Extract all business logic from Livewire components into dedicated Service classes
- Extract all business logic from Models into Service classes
- Introduce PHP 8.1 Enum classes to replace hardcoded string constants
- Standardize Model naming to PascalCase across the entire codebase
- Introduce a Permission / Policy layer to implement the RBAC defined in PERMISSION.md
- Introduce a dedicated configuration file for application-level constants
- Introduce Action classes for single-responsibility operations
- Introduce DTO (Data Transfer Object) classes for complex data passing
- Improve test coverage with Unit tests targeting Service and Action classes
- Organize routes by module
- Remove technical debt (backup SQLite file, bkpwelcome.blade.php, debug logger calls)
- Prepare the architecture to support the future multi-event, multi-organization model
- Standardize English naming at the code layer (Models, Services, Enums)
- Make the project AI-friendly by ensuring architecture is predictable and documented

---

# Technical Debt

## 1. Business Logic in Livewire Components

**Location:** `app/Livewire/Dashboard/Scan.php`, `app/Livewire/Registrasi/SelfRegister.php`, `app/Livewire/Registrasi/Ulang.php`, `app/Livewire/Dashboard/Dashboard.php`, `app/Livewire/Rekap/Absensi/RekapAbsensi.php`

**Problem:** Livewire components should only be responsible for UI state management and delegating to the service layer. Currently they contain: attendance validation logic, duplicate detection, session activation, data query construction, and percentage calculation.

**Impact:** Cannot be unit tested without a full Livewire context. Logic cannot be reused across components. Any change to business rules requires finding all Livewire components that implement the rule.

---

## 2. Business Logic in Models

**Location:** `app/Models/peserta.php` (methods: `autoPlacement`, `leastFilledRegu`, `nextAutoNip`, `leastFilledReguId`, `leastFilledReguName`)

**Problem:** Models should represent data entities and their relationships, not execute business algorithms. The NIP generation algorithm and the regu auto-placement logic are business rules that belong in a `RegistrationService` or `PlacementService`.

**Impact:** Cannot be tested independently. Violates Single Responsibility Principle. As the registration process evolves, the model becomes increasingly bloated.

---

## 3. Missing Service Layer

**Location:** Entire `app/` directory.

**Problem:** No `app/Services/` directory exists. RULES.md explicitly states: "Use Service Layer when business logic grows." The business logic has clearly grown beyond what belongs in Livewire components and Models.

**Impact:** Business rules are scattered, duplicated, and untestable. This is the single largest architectural debt in the project.

**Required Services (initial list):**
- `AttendanceService` — QR scan, manual attendance, session management, status tracking
- `RegistrationService` — Person creation, auto-placement, NIP assignment, status management
- `ReportService` — Data aggregation for dashboard and rekap views
- `ImportService` — Orchestration of import operations
- `ExportService` — Orchestration of export operations
- `QrService` — QR code generation, regeneration, invalidation (Sprint 1)

---

## 4. Missing Enums

**Location:** Multiple files.

**Problem:** String values for `jenis_kelamin` (`'Laki - Laki'`, `'Perempuan'`), `jenis_peserta` (`'Wajib'`, `'Kiriman'`, `'Person'`), and `status_registrasi` (`'Belum Registrasi'`, `'Self Register'`, `'Registrasi Ulang'`) are repeated across Models, Livewire components, Exports, and Imports. They are partially centralized as constants in `peserta.php` but inconsistently used — for example, `TambahPeserta.php` hardcodes `'Wajib'` and `'Laki - Laki'` as raw strings instead of referencing the constants.

**Impact:** A typo in a status string causes silent bugs. Renaming or adding a value requires updating every occurrence. IDE autocomplete and static analysis cannot help catch errors.

**Required Enums (initial list):**
- `JenisKelaminEnum` (Laki-Laki, Perempuan)
- `JenisPesertaEnum` (Wajib, Kiriman, Person)
- `StatusRegistrasiEnum` (BelumRegistrasi, SelfRegister, RegistrasiUlang)
- `AttendanceStatusEnum` (Hadir, Izin, Alfa) — for Sprint 1
- `UserRoleEnum` — for RBAC implementation

---

## 5. Inconsistent Model Naming Convention

**Location:** `app/Models/desa.php`, `app/Models/kelompok.php`, `app/Models/peserta.php`, `app/Models/regu.php`

**Problem:** PHP and Laravel convention require class names to be PascalCase. These models use lowercase class names, which breaks IDE static analysis, confuses autoloading tooling, and violates AGENTS.md naming conventions which specify code should use English and follow Laravel Best Practice.

**Impact:** IDE tooling is degraded. Code is harder to scan. Future developers (or AI assistants) will have inconsistent expectations about class naming.

---

## 6. Missing RBAC Implementation

**Location:** `routes/web.php`, all Livewire components.

**Problem:** PERMISSION.md defines a comprehensive 10-role permission matrix across 18 feature areas. None of this is implemented in code. All routes are protected only by `auth` and `verified` middleware, meaning any authenticated user can access any route. No Gate, Policy, or Spatie Permission integration exists.

**Impact:** A security gap between the documented intent and the running system. Any authenticated user (including a Viewer or Juri) can currently access the master data and registration pages.

---

## 7. Hardcoded String Values

**Location:** `app/Livewire/Database/Peserta/TambahPeserta.php` (line 27: `'Wajib'`), `app/Livewire/Dashboard/Scan.php` (inline string messages), `app/Livewire/Registrasi/SelfRegister.php` (line 80: `'Laki - Laki'`, `'Perempuan'`).

**Problem:** Raw string values that should reference Enum cases or constants are hardcoded directly in Livewire component logic and validation rules.

**Impact:** A change to any canonical value breaks multiple files. No single source of truth.

---

## 8. Denormalized Data in Absensi Table

**Location:** `database/migrations/2025_06_23_031629_create_absensis_table.php`, `app/Models/Absensi.php`

**Problem:** The `absensis` table stores both `nip` (integer) and `nama` (string). The `nama` column is a denormalized copy of the person's name at the time of attendance. This means if a person's name is corrected, the attendance history will show the old name. The relationship back to `peserta` exists via `nip`, making `nama` redundant.

**Impact:** Data consistency issues. Wasted storage. Confusion about which `nama` is authoritative.

---

## 9. QR Based on NIP

**Location:** `app/Livewire/Dashboard/Scan.php` (line 42: `peserta::where('nip', $nip)`)

**Problem:** The QR scanner currently reads the NIP directly from the QR code and looks up the peserta by NIP. This is a known security issue (ADR-003, SECURITY.md): NIP is predictable, cannot be safely rotated, and exposes internal identifiers. The planned fix (Attendance Code) is documented but not yet implemented.

**Impact:** Any person who knows another person's NIP can forge attendance. Lost cards cannot be effectively invalidated.

---

## 10. Debug Code in Production Path

**Location:** `app/Livewire/Dashboard/Dashboard.php` (line 84: `logger('REGU FILTER: '.$this->regu_id)`)

**Problem:** A debug `logger()` call is present in the `updatedReguId()` method of the Dashboard component. This logs user filter selections to the application log on every filter change.

**Impact:** Log pollution. Potential information disclosure in log files. Minor performance overhead.

---

## 11. Committed Database Backup File

**Location:** `database/backup-sebelum-reset.sqlite`

**Problem:** A SQLite database backup file is committed to the Git repository. This likely contains real event data (participant names, attendance records).

**Impact:** Sensitive data exposure. Repository size growth. Violation of RULES.md: "Never commit database backup."

---

## 12. Orphaned View File

**Location:** `resources/views/bkpwelcome.blade.php`

**Problem:** A backup copy of the welcome view is committed to the repository. This is dead code with no reference.

**Impact:** Confusion for developers reading the codebase. Minor pollution.

---

## 13. Missing Application Configuration File

**Location:** `config/` directory.

**Problem:** No application-specific configuration file exists. NIP range boundaries (1000–1999 for male, 2000–2999 for female), default statuses, and other domain-specific constants are embedded in model code.

**Impact:** Cannot be changed without editing model source code. Cannot be environment-specific. Difficult for AI and developers to locate application constants.

---

## 14. Inconsistent Language at Code Layer

**Location:** `app/Models/` (desa, kelompok, peserta, regu), `app/Livewire/Database/` (DataDesa, DataKelompok, etc.), `app/Livewire/Registrasi/Ulang.php`

**Problem:** AGENTS.md specifies that code-layer naming should use English. Several model names, some Livewire component names, and class-level variable names (`$daftarDesa`, `$daftarKelompok`, `$nama`, `$jam_scan`) use Indonesian. While UI labels should be Indonesian (per AGENTS.md), the underlying class names, method names, and property names should be English.

**Impact:** Inconsistency makes onboarding harder and confuses AI tooling. Mixed-language code is harder to read.

---

## 15. Missing project name in composer.json

**Location:** `composer.json` (line 2: `"name": "laravel/livewire-starter-kit"`)

**Problem:** The project is still identified as the Laravel Livewire starter kit in `composer.json`, not as KJA Event Manager.

**Impact:** Minor. Misleading project identification.

---

# Refactor Priority

## Phase 1 — Safe Refactor (No Business Logic Change)

These changes improve structure and hygiene without altering how the application behaves.

- Remove `database/backup-sebelum-reset.sqlite` from repository and add to `.gitignore`
- Remove `resources/views/bkpwelcome.blade.php`
- Remove the `logger()` debug call from `Dashboard.php`
- Rename `composer.json` project name to `kja-event-manager/app`
- Create `app/Services/` directory structure
- Create `app/Enums/` directory structure
- Create `app/Actions/` directory structure
- Create `app/DTO/` directory structure
- Create `app/Policies/` directory structure
- Create `config/kjam.php` for application constants
- Standardize Model class names to PascalCase (coordinated with namespace and reference updates)

---

## Phase 2 — Architecture Improvement

These changes introduce the missing architectural layers.

- Implement `JenisKelaminEnum`, `JenisPesertaEnum`, `StatusRegistrasiEnum`
- Implement `RegistrationService` extracting logic from `peserta` model and Livewire registration components
- Implement `AttendanceService` extracting logic from `Scan.php` and `Dashboard.php`
- Implement `ReportService` extracting query logic from `RekapAbsensi.php` and `RekapPeserta.php`
- Implement `PlacementService` extracting NIP generation and regu auto-placement from `peserta` model
- Remove denormalized `nama` column from `absensis` table (migration required, coordinated)
- Implement Gate / Policy classes for the RBAC matrix defined in PERMISSION.md
- Apply middleware-level authorization to routes

---

## Phase 3 — Code Cleanup

These changes improve code quality and consistency.

- Standardize all Model property and method names to English
- Replace all raw string status/type values with Enum references throughout the codebase
- Replace hardcoded NIP range magic numbers with `config('kjam.nip_range_male')` references
- Add missing validation to `Ulang.php` update operation
- Add pagination to all list-rendering Livewire components (currently fetching all records)
- Standardize Livewire event naming from Indonesian mixed strings to English convention
- Write Unit tests for all Service classes
- Expand Feature tests to cover attendance flow, registration flow, and import flow

---

## Phase 4 — Scalability

These changes prepare the application for the KJA Event Manager multi-event transformation.

- Database migration from SQLite to MariaDB
- Introduce Event model and architecture scaffolding
- Introduce Participation model linking Person to Event
- Migrate current `peserta` records to the `Person` → `Participation` model
- Introduce queue support for batch import and export operations
- Implement Audit Log for critical actions (login, scan, import, export)
- Introduce route organization by module (attendance routes, registration routes, admin routes)
- Prepare API route structure for future mobile app integration
- Implement multi-event context awareness in Dashboard

---

# Recommended Folder Structure

The following is the target folder structure. This is a proposal and must not be implemented without following the documentation-first workflow.

```
app/
├── Actions/
│   ├── Attendance/
│   │   ├── ScanQrAction.php
│   │   ├── ManualAttendanceAction.php
│   │   └── ActivateSessionAction.php
│   ├── Registration/
│   │   ├── RegisterPersonAction.php
│   │   └── ReRegistrationAction.php
│   └── Import/
│       └── ImportPersonAction.php
│
├── DTO/
│   ├── AttendanceData.php
│   ├── RegistrationData.php
│   └── PersonData.php
│
├── Enums/
│   ├── GenderEnum.php
│   ├── ParticipantTypeEnum.php
│   ├── RegistrationStatusEnum.php
│   ├── AttendanceStatusEnum.php
│   └── UserRoleEnum.php
│
├── Exceptions/
│   ├── AttendanceException.php
│   ├── RegistrationException.php
│   └── ImportException.php
│
├── Exports/
│   ├── Person/
│   │   └── PersonExport.php
│   └── Attendance/
│       └── AttendanceExport.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── ImportController.php
│   │   └── ExportController.php
│   ├── Middleware/
│   │   └── CheckPermission.php
│   └── Requests/
│       ├── StorePersonRequest.php
│       └── StoreAttendanceRequest.php
│
├── Imports/
│   ├── PersonImport.php
│   ├── DesaImport.php
│   └── GroupImport.php
│
├── Livewire/
│   ├── Actions/
│   │   └── Logout.php
│   ├── Auth/
│   ├── Dashboard/
│   │   ├── Dashboard.php
│   │   └── Scan.php
│   ├── MasterData/
│   │   ├── Person/
│   │   ├── Desa/
│   │   ├── Kelompok/
│   │   └── Regu/
│   ├── Registration/
│   │   ├── SelfRegister.php
│   │   └── ReRegistration.php
│   ├── Attendance/
│   │   ├── AttendanceSession/
│   │   └── AttendanceHistory/
│   ├── Report/
│   │   ├── PersonReport.php
│   │   └── AttendanceReport.php
│   └── Settings/
│
├── Models/
│   ├── User.php
│   ├── Person.php
│   ├── Desa.php
│   ├── Kelompok.php
│   ├── Regu.php
│   ├── Attendance.php
│   ├── AttendanceSession.php
│   └── (Future)
│       ├── Event.php
│       ├── Participation.php
│       ├── Venue.php
│       └── Organization.php
│
├── Policies/
│   ├── PersonPolicy.php
│   ├── AttendancePolicy.php
│   ├── RegistrationPolicy.php
│   └── ReportPolicy.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   └── AuthServiceProvider.php
│
├── Services/
│   ├── AttendanceService.php
│   ├── RegistrationService.php
│   ├── PlacementService.php
│   ├── ReportService.php
│   ├── ImportService.php
│   ├── ExportService.php
│   └── QrService.php
│
└── Support/
    ├── Helpers/
    │   └── NipHelper.php
    └── Traits/
        ├── HasAuditLog.php
        └── HasSoftDelete.php
```

### Why Each Folder Exists

**Actions/** — Single-responsibility operations that represent one user intent. Actions call Services and return results. Livewire components call Actions, not Services directly. Actions are easily unit-testable.

**DTO/** — Data Transfer Objects carry structured, typed data between layers. They replace loosely-typed arrays passed between Livewire components and Services, improving IDE support and reducing bugs from missing keys.

**Enums/** — PHP 8.1 Enum classes replace all hardcoded string constants. One source of truth for all valid values. IDE autocomplete works. Static analysis can catch invalid usages.

**Exceptions/** — Domain-specific exception classes allow callers to catch meaningful exceptions (e.g., `AttendanceException::alreadyScanned()`) rather than generic Laravel exceptions.

**Policies/** — Laravel Policy classes enforce the RBAC permission matrix defined in PERMISSION.md at the model level. Each major entity has a corresponding Policy.

**Services/** — The business logic layer. Services are plain PHP classes with no HTTP or Livewire dependencies. They can be called from Livewire components, Controllers, console commands, and tests equally.

**Support/Helpers/** — Pure utility functions with no side effects. NIP generation arithmetic, string formatting utilities, etc.

**Support/Traits/** — Reusable behaviors applied to multiple Models via PHP traits. `HasAuditLog` would add automatic audit recording on model events. `HasSoftDelete` would standardize soft delete configuration.

---

# Migration Plan

This plan describes the sequence of steps to move from the current structure to the target structure. Each step is isolated and safe unless otherwise noted.

## Step 1 — Cleanup Dead Files

Remove committed files that do not belong in the repository:

- `database/backup-sebelum-reset.sqlite`
- `resources/views/bkpwelcome.blade.php`

Add `database/*.sqlite` to `.gitignore` (excluding `database/.gitignore` itself).

Update `composer.json` project name to `kja-event-manager/app`.

---

## Step 2 — Create Folder Structure

Create the new empty directories:

- `app/Services/`
- `app/Enums/`
- `app/Actions/`
- `app/DTO/`
- `app/Policies/`
- `app/Exceptions/`
- `app/Support/Helpers/`
- `app/Support/Traits/`

No code is moved at this stage. The goal is to establish the skeleton.

---

## Step 3 — Create Application Configuration File

Create `config/kjam.php` with initial constants extracted from the codebase:

- NIP ranges by gender
- Default registration status
- Default participant type
- Application name constants

Update all references to these values to use `config('kjam.*')`.

---

## Step 4 — Create Enum Classes

Create Enum classes replacing string constants in `peserta.php`:

- `GenderEnum` replacing the `jenis_kelamin` string literals
- `ParticipantTypeEnum` replacing the `JENIS_*` constants
- `RegistrationStatusEnum` replacing the `STATUS_*` constants

Do not yet replace all usages — this will be done module by module in later steps.

---

## Step 5 — Extract PlacementService

Extract the NIP generation and regu auto-placement methods from `peserta.php` into `app/Services/PlacementService.php`:

- `nextAutoNip()`
- `autoPlacement()`
- `leastFilledRegu()`
- `leastFilledReguId()`
- `leastFilledReguName()`

Update `peserta.php` to delegate to `PlacementService`. Update all Livewire components that currently call these methods on the Model.

---

## Step 6 — Extract RegistrationService

Extract registration business logic from `SelfRegister.php` and `TambahPeserta.php` into `app/Services/RegistrationService.php`:

- Person creation logic
- Duplicate detection logic
- Status assignment logic
- Session flash logic

Livewire components become thin wrappers that call `RegistrationService` and handle UI state.

Replace raw string literals in these components with Enum references.

---

## Step 7 — Extract AttendanceService

Extract attendance business logic from `Scan.php` into `app/Services/AttendanceService.php`:

- QR lookup logic
- Session validation logic
- Duplicate attendance detection
- Attendance record creation

This prepares the service to accept Attendance Code (Sprint 1) without requiring Livewire component changes.

---

## Step 8 — Extract ReportService

Extract query and aggregation logic from `RekapAbsensi.php`, `RekapPeserta.php`, and `Dashboard.php` into `app/Services/ReportService.php`:

- Attendance percentage calculation
- Present / absent person lists
- Person count by gender, type, status

Dashboard and Rekap components become thin wrappers that call `ReportService`.

---

## Step 9 — Standardize Model Naming

Rename lowercase Model classes to PascalCase:

- `desa` → `Desa`
- `kelompok` → `Kelompok`
- `peserta` → `Person` (or `Peserta` as interim step)
- `regu` → `Regu`

This requires updating all `use` statements, Livewire components, Services, Imports, and Exports that reference these classes. Run the full test suite after this step.

---

## Step 10 — Implement Policies

Create Policy classes for each major model:

- `PersonPolicy`
- `AttendancePolicy`
- `RegistrationPolicy`
- `ReportPolicy`

Register policies in `AuthServiceProvider`. Apply `can()` checks to Livewire component methods. Add route-level middleware where appropriate.

---

## Step 11 — Enum Propagation

Replace all remaining raw string usages of status, gender, and type values throughout the codebase with their corresponding Enum cases:

- All Livewire components
- All Imports
- All Exports
- All Models
- All Services
- All Migrations (for documentation purposes — do not alter existing migrations)

---

## Step 12 — Remove Denormalized Absensi Nama

Plan and implement the removal of the `nama` column from the `absensis` table. This requires:

- A migration to drop the `nama` column
- Updating `Absensi.php` model
- Updating all views that render `$absensi->nama` to use `$absensi->peserta->nama`
- Updating `AttendanceService` to not pass `nama` on create

This step requires coordination with DATABASE.md documentation update and ADR entry.

---

## Step 13 — Write Unit Tests

Write Unit tests for all Service classes created in Steps 5–8. Target coverage:

- `PlacementService` — NIP range logic, regu selection logic
- `RegistrationService` — Duplicate detection, status assignment
- `AttendanceService` — Already-scanned detection, session validation
- `ReportService` — Percentage calculation correctness

---

## Step 14 — Route Organization

Split `routes/web.php` into module-level route files:

- `routes/web/dashboard.php`
- `routes/web/registration.php`
- `routes/web/attendance.php`
- `routes/web/master-data.php`
- `routes/web/report.php`
- `routes/web/settings.php`

Include all from `routes/web.php` using `require`.

---

# Risk Analysis

## R-001 — Model Rename (Step 9)

**Risk:** Renaming `peserta` to `Peserta` (or `Person`) will affect every file in the codebase that references the model.

**Impact:** High. If any file is missed, a PHP fatal error will occur at runtime.

**Mitigation:** Use a comprehensive IDE refactor tool or `rg` to find all occurrences before beginning. Run the full test suite after the rename. Deploy to a staging environment before production.

**Estimated Difficulty:** Medium. Mechanical but wide-reaching.

---

## R-002 — Removing Denormalized Nama Column (Step 12)

**Risk:** The `nama` column may be referenced in views, reports, or export templates that are not immediately obvious from code search. Removing it without updating all references will cause runtime errors.

**Impact:** High. Missing `nama` will cause blank or broken report output.

**Mitigation:** Search all Blade views and PHP files for `->nama` and `$absensi->nama` before creating the migration. Update all references first, verify with tests, then run the migration.

**Estimated Difficulty:** Low to Medium. The change is localized to the attendance domain.

---

## R-003 — Service Extraction Introducing Regressions (Steps 5–8)

**Risk:** Extracting logic from Livewire components into Services may introduce subtle behavioral differences if the extraction is not exact.

**Impact:** Medium. Registration or attendance behavior could change in edge cases (e.g., duplicate detection conditions, session selection).

**Mitigation:** Write characterization tests against the existing Livewire components before extraction to document current behavior. Run these tests against the Service implementations after extraction to verify parity.

**Estimated Difficulty:** Medium.

---

## R-004 — RBAC Implementation Locking Out Existing Users (Step 10)

**Risk:** Introducing Policy-based authorization may inadvertently block operations that existing users currently perform, if roles are not yet assigned to all users.

**Impact:** High. If not phased correctly, the system becomes unusable for operators.

**Mitigation:** Implement Policies in audit/log-only mode initially (logging violations without blocking). Assign roles to all existing users before switching Policies to enforcement mode. Test with each role on a staging system.

**Estimated Difficulty:** High. Requires careful rollout planning.

---

## R-005 — SQLite to MariaDB Migration (Phase 4)

**Risk:** SQLite and MariaDB have different behaviors for certain SQL features (e.g., `whereRaw` case sensitivity, enum column types, foreign key enforcement). Several existing Import queries use `whereRaw` with `LOWER(TRIM(...))` which may behave differently.

**Impact:** High. Data migration and query behavior changes could cause data loss or silent failures.

**Mitigation:** Run the full test suite against a MariaDB instance before migrating production data. Create a staging environment with MariaDB. Review all `whereRaw` usages for database-specific syntax. Document the migration in DATABASE.md before execution.

**Estimated Difficulty:** High.

---

## R-006 — Enum Introduction Breaking Existing Validation (Step 4 and 11)

**Risk:** Current validation rules use raw strings (`'required|in:Wajib,Kiriman,Person'`). When Enums are introduced, the validation rules must be updated to use `Rule::enum(ParticipantTypeEnum::class)`. If any validation rule is missed, invalid values could be accepted or valid values rejected.

**Impact:** Medium. Incorrect validation could silently accept bad data.

**Mitigation:** Use a comprehensive grep for all `in:` validation rules containing domain values. Update all occurrences when introducing Enums. Add a test for each enum validation path.

**Estimated Difficulty:** Low to Medium.

---

## R-007 — Route Splitting Causing Named Route Conflicts (Step 14)

**Risk:** Splitting routes into multiple files could cause naming conflicts if not done carefully, or could break existing `route()` helper calls in Blade views.

**Impact:** Low to Medium. Broken links in the UI.

**Mitigation:** Treat route splitting as a pure move operation — no route name changes, no URL changes. Verify all `route()` calls still resolve after splitting.

**Estimated Difficulty:** Low.

---

# Estimated Sprints

## Sprint 0 — Documentation and Architecture

**Current Sprint**

Goal: Finalize all documentation and architecture planning before any implementation.

Deliverables:

- REFACTOR_PLAN.md (this document)
- AGENTS.md finalized
- ROADMAP.md finalized
- SECURITY.md finalized
- PERMISSION.md finalized
- DATABASE.md finalized
- FEATURE.md finalized
- DECISION.md finalized
- All ADR entries current

---

## Sprint 1 — Infrastructure Refactor

Goal: Establish the architectural foundation without changing business behavior.

Deliverables:

- Remove dead files (backup SQLite, bkpwelcome.blade.php)
- Remove debug logger call from Dashboard
- Create `app/Services/`, `app/Enums/`, `app/Actions/`, `app/DTO/`, `app/Policies/`, `app/Exceptions/`, `app/Support/` directories
- Create `config/kjam.php`
- Create `GenderEnum`, `ParticipantTypeEnum`, `RegistrationStatusEnum`
- Create `PlacementService` (extract from peserta model)
- Create `RegistrationService` (extract from Livewire registration components)
- Update `composer.json` project name
- Write Unit tests for PlacementService and RegistrationService

---

## Sprint 2 — Attendance Refactor

Goal: Stabilize and secure the attendance module. Implement Sprint 1 attendance features from ROADMAP.md alongside the architectural improvements.

Deliverables:

- Create `AttendanceService`
- Implement Attendance Code (replacing NIP-based QR) per ADR-003
- Implement internal QR generator
- Implement QR regeneration
- Implement manual attendance
- Implement attendance status (Hadir, Izin, Alfa) with `AttendanceStatusEnum`
- Implement attendance history view
- Remove `nama` denormalized column from `absensis` table
- Write Unit tests for AttendanceService

---

## Sprint 3 — Report and Dashboard

Goal: Improve reporting and dashboard capabilities while extracting report logic into the Service layer.

Deliverables:

- Create `ReportService`
- Implement Dashboard per Divisi
- Implement Dashboard per PJ Regu
- Implement Export Excel for attendance
- Implement Export PDF for attendance
- Implement Rekap Per Regu, Per Desa, Per Kelompok, Belum Hadir
- Write Feature tests for all report exports

---

## Sprint 4 — Database and RBAC

Goal: Implement authorization layer and prepare for database engine migration.

Deliverables:

- Standardize all Model names to PascalCase
- Implement Policy classes for all major models
- Implement Gate registrations in AuthServiceProvider
- Apply authorization to all Livewire components and routes
- Assign roles to all existing system users
- Test each role's access against the PERMISSION.md matrix
- Begin MariaDB migration preparation (staging environment setup)

---

## Sprint 5 — Multi Event Foundation

Goal: Begin the transformation from CAI-centric to KJA Event Manager architecture.

Deliverables:

- Create `Event` model and migration
- Create `Participation` model and migration
- Create `Organization` model and migration
- Implement Universal Person Database concept
- Migrate existing CAI `peserta` data to Person → Participation structure
- Implement Multi Event context in Dashboard
- Update routing to be event-scoped
- Update all documentation to reflect new data model

---

## Sprint 6 — Commercial Preparation

Goal: Prepare the platform for multi-organization use and commercial deployment.

Deliverables:

- Implement Multi Organization support
- Implement White Label configuration
- Implement Organization Management interface
- Implement REST API foundation
- Implement queue-based import and export
- Implement Audit Log for all critical actions
- Implement Notification foundation (email)
- Prepare deployment documentation for VPS / Dedicated Server

---

# Future Architecture

## From CAI Operational to KJA Event Manager

The current application is a single-event, single-organization attendance system built for CAI. The path to KJA Event Manager requires the following architectural shifts without breaking the existing CAI operational capability:

### Data Model Evolution

The current model is:

```
Desa → Kelompok → Peserta → Absensi
             └── Regu  ↑
                       SesiAbsensi
```

The target model is:

```
Organization
    ├── Person (Universal)
    └── Event
          ├── Venue
          ├── Category
          ├── Session
          └── Participation
                ├── Attendance
                ├── Registration
                ├── Permission
                ├── Score
                └── Certificate
```

### Transition Strategy

The transition must preserve all existing CAI data. The recommended approach is to:

1. Introduce the new model alongside the old model (additive migration).
2. Create an Organization record representing CAI.
3. Migrate existing `peserta` records into `Person` records linked to the CAI Organization.
4. Migrate existing `Absensi` records into `Attendance` records linked through `Participation`.
5. Migrate the CAI-specific hierarchy (`Desa`, `Kelompok`, `Regu`) into CAI-specific category/group structures within the Event model.
6. Once all CAI data is represented in the new model, the old tables can be deprecated (not deleted immediately) to allow rollback if needed.

### Feature Continuity

All existing CAI features must continue to function during and after the migration:

- QR Scan attendance must work as before (using Attendance Code by Sprint 2)
- Registration and Re-registration must work as before
- All existing reports and exports must produce identical output
- All user sessions must remain valid

### Multi-Event Architecture

Once the Event model exists, new events can be created without writing code. An event is a configuration, not a database schema. Event-specific settings (session schedules, categories, capacity limits) are stored as data, not as columns.

The Dashboard will become event-scoped. Users will select an active event context, and all data (attendance, registration, scoring) will be filtered to that event automatically.

### RBAC in Multi-Event Context

The current RBAC model is global (a user is Admin or Operator for the whole system). In the future, RBAC must become event-scoped:

- A user can be Admin for Event A and Viewer for Event B.
- An Operator Scan only has access to the specific session they are assigned to.
- A Judge only has access to the competition categories assigned to them.

This requires a `user_role_event` pivot table or equivalent, and a permission check that considers the active event context in addition to the user's role.

### Commercial Readiness

The path from internal tool to commercial platform requires:

- Multi-Organization isolation (one organization cannot see another's data)
- White Label support (organization-specific branding)
- License-gated feature modules (e.g., Competition module is a paid add-on)
- Billing-aware permission checks (features disabled if license expired)
- Self-service organization onboarding

These features should be designed as a layer on top of the existing multi-event architecture, not replacing it. The Livewire + Laravel stack is well-suited for this purpose and does not need to be replaced.

---

# Acceptance Criteria

This document is accepted when:

- The Lead Software Architect has reviewed and approved the plan.
- All stakeholders understand the phased approach.
- No code changes are made as a result of this document alone.
- Each Sprint's work is subsequently documented in TODO.md before implementation begins.
- Each architectural decision made during implementation is recorded in DECISION.md.
- CHANGELOG.md is updated after each Sprint is completed.
- This document is revisited and updated at the end of each Sprint to reflect what was completed and what changed.

---

> This document was generated during Sprint 0.
> It is a living document and must be updated as the project evolves.
> Last updated: 2026-07-14
