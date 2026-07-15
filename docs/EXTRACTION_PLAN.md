# EXTRACTION PLAN

## Purpose

This document identifies the smallest and safest business logic that can be extracted into a Service without changing current application behavior.

Ranking criteria:
1. Lowest risk
2. Least dependencies
3. Highest maintainability improvement

---

## Recommended Candidate Ranking

| Rank | Candidate | Current Location | Dependencies | Estimated Difficulty | Risk | Recommended Sprint Order |
|------|-----------|------------------|--------------|----------------------|------|--------------------------|
| 1 | Participant number generation (auto placement) | `app/Models/peserta.php` | `peserta`, `regu`, gender rules, existing participant data | Medium | Medium | Sprint 1.2 |
| 2 | Attendance duplicate check + store flow | `app/Livewire/Dashboard/Scan.php` | `peserta`, `Absensi`, `SesiAbsensi`, Carbon | Medium | Medium-High | Sprint 1.3 |
| 3 | Report filter assembly | `app/Livewire/Rekap/Peserta/RekapPeserta.php`, `app/Livewire/Rekap/Absensi/RekapAbsensi.php`, `app/Exports/PesertaExport.php` | `peserta`, `regu`, `kelompok`, `desa`, `Absensi`, `SesiAbsensi`, Excel | Medium | Low-Medium | Sprint 2 |
| 4 | Re-registration search and update flow | `app/Livewire/Registrasi/Ulang.php` | `peserta`, `desa`, `kelompok`, `regu` | Medium | Medium | Sprint 2 |
| 5 | Self-registration create flow | `app/Livewire/Registrasi/SelfRegister.php` | `peserta`, `desa`, `kelompok`, validation, unique rules | High | High | Sprint 3 |
| 6 | Import row mapping and duplicate prevention | `app/Imports/PesertaImport.php`, `app/Imports/ReguImport.php`, `app/Imports/KelompokImport.php`, `app/Imports/DesaImport.php`, `app/Http/Controllers/ImportDataController.php` | Excel headers, `peserta`, `regu`, `desa`, `kelompok` | High | High | Sprint 3 |

---

## Candidate Details

### 1) Participant Number Generation

**Candidate**
- Auto participant number generation and regu placement helper logic

**Current Location**
- `app/Models/peserta.php`
  - `nextAutoNip()`
  - `autoPlacement()`
  - `leastFilledRegu()`
  - `leastFilledReguId()`
  - `leastFilledReguName()`

**Dependencies**
- `App\Models\peserta`
- `App\Models\regu`
- Existing participant data in database
- Gender normalization rules

**Estimated Difficulty**
- Medium

**Risk**
- Medium
- This logic is reused by import and self-registration.
- It affects participant identity and placement, so it must preserve exact output.

**Why this is the best first extraction**
- The logic is already centralized.
- It has clear input and output.
- It improves maintainability immediately.
- It can be moved with minimal surface area if the service only returns a value and does not change UI or routes.

**Recommended Sprint Order**
- Sprint 1.2

---

### 2) Attendance Duplicate Check + Store Flow

**Candidate**
- Validate scan input, check active session, prevent duplicate attendance, create attendance record

**Current Location**
- `app/Livewire/Dashboard/Scan.php`
  - `scanPeserta()`

**Dependencies**
- `App\Models\peserta`
- `App\Models\Absensi`
- `App\Models\SesiAbsensi`
- `Carbon\Carbon`

**Estimated Difficulty**
- Medium

**Risk**
- Medium-High
- Attendance is a production-critical write path.
- Any logic change can affect operational attendance data.

**Recommended Sprint Order**
- Sprint 1.3

---

### 3) Report Filter Assembly

**Candidate**
- Build filtered participant and attendance lists for recap and export

**Current Location**
- `app/Livewire/Rekap/Peserta/RekapPeserta.php`
- `app/Livewire/Rekap/Absensi/RekapAbsensi.php`
- `app/Exports/PesertaExport.php`

**Dependencies**
- `App\Models\peserta`
- `App\Models\regu`
- `App\Models\kelompok`
- `App\Models\desa`
- `App\Models\Absensi`
- `App\Models\SesiAbsensi`
- Laravel Excel

**Estimated Difficulty**
- Medium

**Risk**
- Low-Medium
- Mostly query consolidation.
- Risk is lower than write flows, but filters must remain identical.

**Recommended Sprint Order**
- Sprint 2

---

### 4) Re-registration Search and Update Flow

**Candidate**
- Search participant by name or NIP, mark as re-registered, update participant details

**Current Location**
- `app/Livewire/Registrasi/Ulang.php`

**Dependencies**
- `App\Models\peserta`
- `App\Models\desa`
- `App\Models\kelompok`
- `App\Models\regu`

**Estimated Difficulty**
- Medium

**Risk**
- Medium
- Updates participant data directly.
- Search is simple, update is more sensitive.

**Recommended Sprint Order**
- Sprint 2

---

### 5) Self-Registration Create Flow

**Candidate**
- Validate input, generate participant number, assign regu, create participant

**Current Location**
- `app/Livewire/Registrasi/SelfRegister.php`

**Dependencies**
- `App\Models\peserta`
- `App\Models\desa`
- `App\Models\kelompok`
- Validation rules
- Unique constraints
- Query exception handling

**Estimated Difficulty**
- High

**Risk**
- High
- This is one of the most sensitive flows in the system.
- A mistake here can create duplicate participants or broken registrations.

**Recommended Sprint Order**
- Sprint 3

---

### 6) Import Row Mapping and Duplicate Prevention

**Candidate**
- Normalize imported rows, match desa/kelompok, prevent duplicate participant import

**Current Location**
- `app/Imports/PesertaImport.php`
- `app/Imports/ReguImport.php`
- `app/Imports/KelompokImport.php`
- `app/Imports/DesaImport.php`
- `app/Http/Controllers/ImportDataController.php`

**Dependencies**
- Excel headers and formatting
- `App\Models\peserta`
- `App\Models\regu`
- `App\Models\desa`
- `App\Models\kelompok`

**Estimated Difficulty**
- High

**Risk**
- High
- Import is batch data entry.
- A small mistake can affect many records at once.

**Recommended Sprint Order**
- Sprint 3

---

## Smallest and Safest Extraction for This Sprint

**Recommended Service to introduce first:** `PlacementService`

**Reason**
- It is the smallest useful extraction.
- It is reused by multiple flows.
- It has the highest maintainability improvement for the lowest acceptable risk.
- It keeps the current registration flow intact while moving only participant number generation into a dedicated service.

**Scope for this sprint**
- Move only participant number generation logic into `PlacementService`.
- Keep backward compatibility.
- Do not redesign registration.
- Do not move attendance logic.
- Do not touch imports, exports, UI, routes, or database structure.

---

## Recommended Sprint Order

1. `PlacementService` for participant number generation
2. Attendance duplicate check + store flow
3. Report filter assembly
4. Re-registration search/update flow
5. Self-registration flow
6. Import row mapping/duplicate prevention

---

## Notes

This plan keeps the first extraction narrow enough to preserve behavior while establishing the Service Layer pattern for later refactors.
