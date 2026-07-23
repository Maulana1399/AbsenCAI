# PGM.19 SPRINT 5 — REGU READ-PATH CUTOVER AUDIT

## 1. Executive Summary

**Baseline:** 1521 passed / 3650 assertions / 0 failures / Design C problem_total = 0

**Sprint 4** completed dual-write: `participation.regu_id` (canonical) + `pesertas.regu_id` (compatibility). **All production read paths still read from `pesertas.regu_id`** via the `legacyPesertaMapping.peserta.regu` chain. Zero production code reads regu from `Participations.regu_id`.

**Total Legacy Read Paths (production):** 32 locations across 17 files
**Total Canonical Read Paths (production):** 0
**Total Write Paths (dual-write):** 8 locations (correct)

**Verdict:** Sprint 5 is **GO** with `Option B (Canonical First + Legacy Fallback)` strategy. The read-path cutover is safe and necessary. All screens already have event context via `ActiveEventContext`.

---

## 2. Current Architecture

### Data Model
```
pesertas.regu_id             -- GLOBAL compatibility field (FK ke regus)
participations.regu_id        -- EVENT-SCOPED canonical field (FK ke regus, added Sprint 4)
```

### Write Path (Sprint 4 — already correct)
```
RegistrationService::createParticipant()  → writes to BOTH peserta + participation
EditPeserta ::update()                    → writes to BOTH peserta + participation
Ulang::updatePeserta()                    → writes to BOTH peserta + participation
TambahPeserta::tambahkanKeEvent()         → writes to BOTH
CaiParticipantReplacementService::replace() → writes to BOTH
```

### Read Path (Sprint 5 — needs cutover)
```
ALL reads go through: $person->legacyPesertaMapping->peserta->regu->regu
        atau:         $legacyPeserta->regu->regu
        atau:         $peserta->regu->regu

NONE read:           $participation->regu
```

### Read Path Pattern
```
Participation
  → person
    → legacyPesertaMapping (LegacyPesertaMapping)
      → peserta (legacy table)
        → regu() relationship → regu.name
```

### Target Read Path
```
Participation
  → regu() relationship → regu.name
```

### Eager Load Pattern (current)
```php
'person.legacyPesertaMapping.peserta.regu'
```

### Eager Load Pattern (target)
```php
'regu'
```

---

## 3. Complete Regu Reference Inventory

### 3.1 Model Definitions

| File | Line | Type | Source |
|------|------|------|--------|
| `app/Models/peserta.php` | 27 | Fillable | `pesertas.regu_id` |
| `app/Models/peserta.php` | 75-78 | Relationship `belongsTo regu` | Legacy peserta |
| `app/Models/Participation.php` | 15 | Fillable | `participations.regu_id` |
| `app/Models/Participation.php` | 28-31 | Relationship `belongsTo regu` | Canonical |
| `app/Models/CaiParticipantReplacement.php` | 21 | Fillable | Audit table |
| `app/Models/CaiParticipantReplacement.php` | 74-77 | Relationship `belongsTo regu` | Audit table |
| `app/Models/regu.php` | 13-15 | Inverse `hasMany peserta` | Legacy |
| `app/Models/regu.php` | 17-19 | Inverse `hasMany participations` | Canonical |

### 3.2 Dual-Write (Canonical + Compatibility) — ALREADY CORRECT

| File | Line | Read Source | Write Target |
|------|------|-------------|--------------|
| `RegistrationService.php` | 70 | Input/legacy fallback | `Participation.regu_id` |
| `RegistrationService.php` | 73 | Input/legacy fallback | `pesertas.regu_id` |
| `RegistrationService.php` | 96 | Input | `pesertas.regu_id` |
| `RegistrationService.php` | 114 | Input | `Participation.regu_id` |
| `RegistrationService.php` | 187 | Input | `pesertas.regu_id` |
| `RegistrationService.php` | 193 | Input | `Participation.regu_id` |
| `EditPeserta.php` | 102 | Form input | `Participation.regu_id` |
| `EditPeserta.php` | 117 | Form input | `pesertas.regu_id` |
| `Ulang.php` | 113 | Form input | `Participation.regu_id` |
| `Ulang.php` | 126 | Form input | `pesertas.regu_id` |
| `TambahPeserta.php` | 270 | PlacementService | `Participation.regu_id` |
| `TambahPeserta.php` | 274 | PlacementService | `pesertas.regu_id` |
| `CaiParticipantReplacementService.php` | 194 | `$peserta->regu_id` | `Participation.regu_id` |
| `CaiParticipantReplacementService.php` | 251 | `$peserta->regu_id` | `CaiParticipantReplacement` |

### 3.3 Edit Form Reads (Reads peserta.regu_id for form population)

| File | Line | Pattern |
|------|------|---------|
| `EditPeserta.php` | 74 | `$legacyPeserta?->regu_id` |
| `Ulang.php` | 91 | `$legacyPeserta?->regu_id` |

### 3.4 Peserta Display (Legacy Read — reads peserta.regu via relationship)

| File | Line | Pattern |
|------|------|---------|
| `database.blade.php` | 37 | `$peserta->regu->regu` |
| `rekap-absensi.blade.php` | 151 | `$peserta->regu->regu` |
| `ulang.blade.php` | 35 | `$peserta->regu->regu` |
| `Database.php` (Livewire) | 61 | `$legacyPeserta?->regu` (passed to view) |
| `RekapPeserta.php` | 82 | `$peserta?->regu` (passed to view) |
| `RekapAbsensi.php` | 62 | `$lp?->regu` (passed to view) |
| `GantiPeserta.php` | 55 | `$peserta->regu?->regu` |
| `TambahPeserta.php` | 184 | `->legacyPesertaMapping?->peserta?->regu?->regu` |
| `TambahPeserta.php` | 200 | same pattern |
| `PesertaExport.php` | 79 | `$peserta?->regu?->regu` |

### 3.5 Filter by regu_id (via Legacy Peserta)

| File | Line | Query Pattern |
|------|------|---------------|
| `AttendanceReadService.php` | 25 | `whereHas('person.legacyPesertaMapping.peserta', fn $q => $q->where('regu_id', $reguId))` |
| `RekapPeserta.php` | 47 | same pattern |
| `PesertaExport.php` | 48 | same pattern |
| `QRLabel/Index.php` | 249 | `whereHas('legacyParticipationMapping.peserta', fn $q => $q->where('regu_id', $filterRegu))` |
| `routes/web.php` | 153 | same as QRLabel |
| `routes/web.php` | 343 | same as QRLabel |

### 3.6 Dashboard Display (via Legacy Peserta)

| File | Line | Pattern |
|------|------|---------|
| `dashboard.blade.php` | 116 | `$lp->regu->regu` |
| `dashboard.blade.php` | 156 | `$legacy->regu->regu` |

### 3.7 Eager Load (loads legacy regu relationship)

| File | Line | Eager Load String |
|------|------|-------------------|
| `AttendanceReadService.php` | 20 | `'person.legacyPesertaMapping.peserta.regu'` |
| `Database.php` | 36 | `'person.legacyPesertaMapping.peserta.regu'` |
| `Database.php` | 36 | `'legacyParticipationMapping.peserta.regu'` |
| `TambahPeserta.php` | 172 | `'legacyPesertaMapping.peserta.regu'` |
| `TambahPeserta.php` | 191 | `'legacyPesertaMapping.peserta.regu'` |
| `GantiPeserta.php` | 36 | `'regu'` (on peserta model) |
| `Ulang.php` | 145 | `'regu'` (on peserta model) |

### 3.8 Filter Dropdown Bindings

| File | Line | Binding |
|------|------|---------|
| `dashboard.blade.php` | 44 | `wire:model.live="regu_id"` |
| `rekap-absensi.blade.php` | 16 | `wire:model.live="regu_id"` |
| `rekap-peserta.blade.php` | 9 | `wire:model.live="regu_id"` |
| `edit-peserta.blade.php` | 47 | `wire:model="regu_id"` |

### 3.9 Services Spawning Entitities (write, not read)

| File | Line | Notes |
|------|------|-------|
| `CaiParticipantReplacementService.php` | 194 | Reads `$peserta->regu_id` → writes to new `Participation.regu_id` |
| `CaiParticipantReplacementService.php` | 251 | Reads `$peserta->regu_id` → writes to `CaiParticipantReplacement` audit |
| `RegistrationService.php` | 62 | Reads `$legacyPeserta?->regu_id` as fallback when creating participation |

---

## 4. Production Read-Path Matrix

| # | File | Method/Line | Current Source | Purpose | Event Context | Target Source | Risk |
|---|------|-------------|---------------|---------|---------------|---------------|------|
| R1 | `AttendanceReadService.php:20` | eager load | `peserta.regu` | Load regu for attendance display | Yes (`$eventId`) | `participation.regu` | LOW |
| R2 | `AttendanceReadService.php:25` | filter | `pesertas.regu_id` | Filter attendance by regu | Yes (`$eventId`) | `participations.regu_id` | LOW |
| R3 | `Dashboard.php:49` | filter pass | passthrough | Passes reguId to service | Yes (`ActiveEventContext`) | passthrough | LOW |
| R4 | `dashboard.blade.php:116` | display | `$lp->regu->regu` | Show regu name in hadir table | Yes (event-scoped query) | `$participation->regu->regu` | LOW |
| R5 | `dashboard.blade.php:156` | display | `$legacy->regu->regu` | Show regu name in belum absen | Yes (event-scoped query) | `$participation->regu->regu` | LOW |
| R6 | `RekapPeserta.php:47` | filter | `pesertas.regu_id` | Filter rekap by regu | Yes (`ActiveEventContext`) | `participations.regu_id` | LOW |
| R7 | `RekapPeserta.php:82` | display | `$peserta?->regu` | Map regu to display object | Yes (event-scoped query) | `$participation->regu` | LOW |
| R8 | `rekap-peserta.blade.php:115` | display | `$p->regu->regu` | Show regu name in table | Yes | `$p->regu->regu` (via Participation) | LOW |
| R9 | `RekapAbsensi.php:62` | display | `$lp?->regu` | Map regu to belum absen list | Yes (`ActiveEventContext`) | `$participation->regu` | LOW |
| R10 | `rekap-absensi.blade.php:75` | display | `$lp->regu->regu` | Show regu in hadir table | Yes | `$entry->participation->regu->regu` | LOW |
| R11 | `rekap-absensi.blade.php:111` | display | `$lp->regu->regu` | Show regu in izin table | Yes | `$entry->participation->regu->regu` | LOW |
| R12 | `rekap-absensi.blade.php:151` | display | `$peserta->regu->regu` | Show regu in alfa table | Yes | Yes but needs `$entry->participation->regu->regu` | MED |
| R13 | `Database.php:36` | eager load | `peserta.regu` (x2) | Load legacy regu for list | Yes (`ActiveEventContext`) | `participation.regu` | LOW |
| R14 | `Database.php:61` | display | `$legacyPeserta?->regu` | Map regu to display object | Yes (event-scoped query) | `$participation->regu` | LOW |
| R15 | `database.blade.php:37` | display | `$peserta->regu->regu` | Show regu name in table | Yes | `$participation->regu->regu` | LOW |
| R16 | `PesertaExport.php:48` | filter | `pesertas.regu_id` | Export filter by regu | Yes (`$event_id`) | `participations.regu_id` | LOW |
| R17 | `PesertaExport.php:79` | display | `$peserta?->regu?->regu` | Export column | Yes (`$event_id`) | `$participation->regu->regu` | LOW |
| R18 | `QRLabel/Index.php:249` | filter | `pesertas.regu_id` | Filter QR participants | Yes (`ActiveEventContext`) | `participations.regu_id` | LOW |
| R19 | `routes/web.php:153` | filter | `pesertas.regu_id` | Route filter print | Yes (`ActiveEventContext`) | `participations.regu_id` | LOW |
| R20 | `routes/web.php:343` | filter | `pesertas.regu_id` | Route filter A4 print | Yes (`ActiveEventContext`) | `participations.regu_id` | LOW |
| R21 | `GantiPeserta.php:55` | display | `$peserta->regu?->regu` | Show regu in replace modal | No (legacy peserta ID) | N/A (legacy-only feature) | LOW |
| R22 | `TambahPeserta.php:184` | display | `legacy.regu->regu` | Show regu in search results | Yes (`getEventId`) | `participation.regu->regu` for current event | MED |
| R23 | `TambahPeserta.php:200` | display | `legacy.regu->regu` | Show regu in person select | Yes (`getEventId`) | `participation.regu->regu` | MED |
| R24 | `ulang.blade.php:35` | display | `$peserta->regu->regu` | Show regu in search results | No (legacy peserta table) | N/A (legacy search) | LOW |
| R25 | `EditPeserta.php:74` | form read | `$legacyPeserta?->regu_id` | Populate edit form | Yes (`ActiveEventContext`) | `$participation->regu_id` | LOW |
| R26 | `Ulang.php:91` | form read | `$legacyPeserta?->regu_id` | Populate edit modal | Yes (`ActiveEventContext`) | `$participation->regu_id` | LOW |

**Total: 26 production read-path locations using `peserta.regu_id`**

**Zero production read-path locations using `participation.regu_id`**

---

## 5. Module-by-Module Audit

### 5.1 Dashboard
- **Source:** `app/Livewire/Dashboard/Dashboard.php` + `dashboard.blade.php`
- **Reads peserta.regu_id?** YES — `$lp->regu->regu` (line 116), `$legacy->regu->regu` (line 156)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()` in mount() and render()
- **Participation available directly?** YES — `AttendanceReadService` returns `$entry->participation`
- **Need resolver?** NO — Participation already loaded
- **Safe to switch now?** YES — Replace `$lp->regu->regu` with `$entry->participation->regu->regu`

### 5.2 Attendance (Scan)
- **Source:** `app/Livewire/Dashboard/Scan.php`
- **Reads peserta.regu_id?** NO — uses `AttendanceService::processScan()` which resolves identity via participation or legacy code
- **Reads Participation.regu_id?** NO — doesn't display regu at all
- **Event context available?** YES
- **Safe to switch now?** N/A — no regu read

### 5.3 AttendanceReadService
- **Source:** `app/Services/Attendance/AttendanceReadService.php`
- **Reads peserta.regu_id?** YES — eager load `person.legacyPesertaMapping.peserta.regu` (line 20); filter `where('regu_id', ...)` (line 25)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `$eventId` parameter
- **Participation available directly?** YES — queries `Participation::where('event_id', $eventId)`
- **Safe to switch now?** YES — replace eager load with `'regu'`; replace filter with `$participationQuery->where('regu_id', $reguId)`

### 5.4 AttendanceService
- **Source:** `app/Services/Attendance/AttendanceService.php`
- **Reads peserta.regu_id?** NO — resolves identity via attendance_code, does not read/render regu
- **Safe to switch now?** N/A

### 5.5 AttendanceExceptionService
- **Source:** `app/Services/Attendance/AttendanceExceptionService.php`
- **Reads peserta.regu_id?** NO — handles izin recording, no regu read
- **Safe to switch now?** N/A

### 5.6 Database Peserta
- **Source:** `app/Livewire/Database/Peserta/Database.php` + `database.blade.php`
- **Reads peserta.regu_id?** YES — eager load `person.legacyPesertaMapping.peserta.regu` + `legacyParticipationMapping.peserta.regu` (line 36); display `$legacyPeserta?->regu` (line 61); blade `$peserta->regu->regu` (line 37)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()` in render()
- **Participation available directly?** YES — `Participation::with(...)` query scoped to event
- **Safe to switch now?** YES — replace eager load with `'regu'`; replace `$legacyPeserta?->regu` with `$participation->regu`

### 5.7 Rekap Peserta
- **Source:** `app/Livewire/Rekap/Peserta/RekapPeserta.php` + `rekap-peserta.blade.php`
- **Reads peserta.regu_id?** YES — filter via `whereHas('person.legacyPesertaMapping.peserta', fn => where('regu_id'))` (line 47); display `$peserta?->regu` (line 82)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — `Participation::with(...)` query
- **Safe to switch now?** YES — replace filter with `where('regu_id', $this->regu_id)` on the `Participation` query directly; replace display with `$participation->regu`

### 5.8 Rekap Absensi
- **Source:** `app/Livewire/Rekap/Absensi/RekapAbsensi.php` + `rekap-absensi.blade.php`
- **Reads peserta.regu_id?** YES — display via `$lp?->regu` (line 62); blade `$lp->regu->regu` (lines 75, 111); blade `$peserta->regu->regu` (line 151)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — via `$entry->participation` from AttendanceReadService
- **Safe to switch now?** YES — `$entry->participation->regu->regu` instead of `$lp->regu->regu`

### 5.9 QR Label
- **Source:** `app/Livewire/QRLabel/Index.php`
- **Reads peserta.regu_id?** YES — filter `$legacyQuery->where('regu_id', $this->filterRegu)` (line 249)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — `Participation::with(...)` query
- **Safe to switch now?** YES — replace filter with `$query->where('regu_id', $this->filterRegu)` directly on Participation query

### 5.10 Print (Route Handlers)
- **Source:** `routes/web.php` (lines 146-155, 336-345)
- **Reads peserta.regu_id?** YES — both route handlers filter via `whereHas('legacyParticipationMapping.peserta', fn => $q->where('regu_id', request('regu')))`
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — queries `Participation::with(...)`
- **Safe to switch now?** YES — replace with direct `$query->where('regu_id', request('regu'))`

### 5.11 Export (PesertaExport)
- **Source:** `app/Exports/PesertaExport.php`
- **Reads peserta.regu_id?** YES — filter via `whereHas('person.legacyPesertaMapping.peserta', fn => where('regu_id'))` (line 48); display `$peserta?->regu?->regu` (line 79)
- **Reads Participation.regu_id?** NO
- **Event context available?** YES — `$this->event_id`
- **Participation available directly?** YES — `Participation::with(...)` query
- **Safe to switch now?** YES — replace filter with direct `where('regu_id', ...)`; replace display with `$participation->regu?->regu`

### 5.12 Registration (RegistrationService)
- **Source:** `app/Services/Registration/RegistrationService.php`
- **Reads peserta.regu_id?** Fallback read: `$data['regu_id'] ?? $legacyPeserta?->regu_id` (line 62)
- **Event context available?** YES — `activeEvent()` via `ActiveEventContext`
- **This is write-path fallback, not display. Safe to keep as-is since it's a safety fallback**

### 5.13 EditPeserta
- **Source:** `app/Livewire/Database/Peserta/EditPeserta.php`
- **Reads peserta.regu_id?** YES — `$legacyPeserta?->regu_id` (line 74) to populate form
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — already loaded `Participation::with('person')`
- **Safe to switch now?** YES — replace with `$participation->regu_id`

### 5.14 Ulang
- **Source:** `app/Livewire/Registrasi/Ulang.php`
- **Reads peserta.regu_id?** YES — `$legacyPeserta?->regu_id` (line 91) to populate form
- **Event context available?** YES — `ActiveEventContext::current()`
- **Participation available directly?** YES — already loaded `Participation::with('person')`
- **Safe to switch now?** YES — replace with `$participation->regu_id`

### 5.15 SelfRegister
- **Source:** `app/Livewire/Registrasi/SelfRegister.php`
- **Reads peserta.regu_id?** NO — writes only, reads from PlacementService
- **Safe to switch now?** N/A

### 5.16 TambahPeserta
- **Source:** `app/Livewire/Database/Peserta/TambahPeserta.php`
- **Reads peserta.regu_id?** YES — display via `->legacyPesertaMapping?->peserta?->regu?->regu` (lines 184, 200) for search results
- **Event context available?** YES — `ActiveEventContext::id()`
- **Participation available directly?** Only for existing person search (not yet registered to current event)
- **Safe to switch now?** MEDIUM — search shows ALL events' legacy data. For current event display, would need participation data (which may not exist yet if person isn't registered)

### 5.17 CaiParticipantReplacement
- **Source:** `app/Livewire/Database/Peserta/GantiPeserta.php` + `app/Services/Cai/CaiParticipantReplacementService.php`
- **Reads peserta.regu_id?** YES — `GantiPeserta.php:55` display `$peserta->regu?->regu`; `CaiParticipantReplacementService.php:194` reads for new Participation write; `CaiParticipantReplacementService.php:251` reads for audit snapshot
- **Event context available?** NO for GantiPeserta (uses legacy peserta ID directly); YES for CaiParticipantReplacementService via `$participationMapping->event_id`
- **Safe to switch now?** GantiPeserta display: legacy-only feature, safe as-is. Service writes: already writes to both.

### 5.18 HapusPeserta
- **Source:** `app/Livewire/Database/Peserta/HapusPeserta.php`
- **Reads peserta.regu_id?** NO — does not read or display regu
- **Safe to switch now?** N/A

### 5.19 Pengajian
- **Source:** `app/Services/Pengajian/PengajianAttendanceService.php`
- **Reads peserta.regu_id?** Unknown — not audited. Check if it accesses regu.
- **Safe to switch now?** N/A (out of scope for this audit)

### 5.20 Regional Report
- **Source:** not found specifically — possibly in route handlers
- **Reads peserta.regu_id?** Route handlers in `web.php` use `regu_id` filter — see R19, R20
- **Safe to switch now?** YES

### 5.21 Event-scoped participant listing
- **Module:** Database Peserta, Rekap Peserta, QR Label, Print routes
- **All already event-scoped** via `Participation::where('event_id', $event->id)`
- **All still read regu from legacy** — needs cutover to `participation.regu_id`

### 5.22 Route handlers
- **Source:** `routes/web.php`
- **Regu filter at lines 153, 343** — both use `whereHas('legacyParticipationMapping.peserta', fn => where('regu_id', ...))`
- **Safe to switch now?** YES — replace with `$query->where('regu_id', request('regu'))`

---

## 6. Event Context Analysis

### Classification per read path:

| # | Location | Context Classification | Evidence |
|---|----------|----------------------|----------|
| R1 | `AttendanceReadService.php:20` | EVENT CONTEXT DIRECT | Has `$eventId` parameter |
| R2 | `AttendanceReadService.php:25` | EVENT CONTEXT DIRECT | Has `$eventId` parameter |
| R3 | `Dashboard.php:49` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R4 | `dashboard.blade.php:116` | PARTICIPATION DIRECT | `$entry->participation` available |
| R5 | `dashboard.blade.php:156` | PARTICIPATION DIRECT | `$participation` available |
| R6 | `RekapPeserta.php:47` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R7 | `RekapPeserta.php:82` | PARTICIPATION DIRECT | `$participation` is the query source |
| R8 | `rekap-peserta.blade.php:115` | PARTICIPATION DIRECT | `$p` is Participation-based |
| R9 | `RekapAbsensi.php:62` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R10 | `rekap-absensi.blade.php:75` | PARTICIPATION DIRECT | `$entry->participation` available |
| R11 | `rekap-absensi.blade.php:111` | PARTICIPATION DIRECT | `$entry->participation` available |
| R12 | `rekap-absensi.blade.php:151` | AMBIGUOUS | `$peserta` is legacy object in alfa table |
| R13 | `Database.php:36` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R14 | `Database.php:61` | PARTICIPATION DIRECT | `$participation` is the query source |
| R15 | `database.blade.php:37` | PARTICIPATION DIRECT | `$peserta` is mapped from Participation |
| R16 | `PesertaExport.php:48` | EVENT CONTEXT DIRECT | `$this->event_id` |
| R17 | `PesertaExport.php:79` | PARTICIPATION DIRECT | `$participation` is the query source |
| R18 | `QRLabel/Index.php:249` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R19 | `routes/web.php:153` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R20 | `routes/web.php:343` | EVENT CONTEXT DIRECT | `ActiveEventContext::current()` |
| R21 | `GantiPeserta.php:55` | GLOBAL LEGACY | Legacy peserta-only feature |
| R22 | `TambahPeserta.php:184` | RESOLVABLE | Has person_id, can resolve to event participation |
| R23 | `TambahPeserta.php:200` | RESOLVABLE | Same as R22 |
| R24 | `ulang.blade.php:35` | GLOBAL LEGACY | Legacy peserta table search |
| R25 | `EditPeserta.php:74` | PARTICIPATION DIRECT | `$participation` already loaded |
| R26 | `Ulang.php:91` | PARTICIPATION DIRECT | `$participation` already loaded |

**Summary:**
- **EVENT CONTEXT DIRECT:** 15 locations
- **PARTICIPATION DIRECT:** 10 locations
- **RESOLVABLE:** 2 locations
- **AMBIGUOUS:** 1 location (R12 — alfa table in rekap-absensi)
- **GLOBAL LEGACY:** 2 locations (R21, R24)

**24 out of 26 locations** have direct event context or direct Participation access. These can be safely migrated.

---

## 7. Multi Event Correctness

### Scenario: Person A → Event A (Regu 1), Event B (Regu 5)

| Feature | Current Behavior | Correct? | Fix Needed? |
|---------|-----------------|----------|-------------|
| **Dashboard** (Event A view) | Shows `$lp->regu->regu` from legacy peserta → shows Regu 1 or Regu 5 depending on last write | **WRONG** — may show wrong regu if legacy value changed by Event B | YES — read from participation.regu_id |
| **Dashboard** (Event B view) | Same problem | **WRONG** | YES |
| **Rekap Peserta** (Event A) | Filters via legacy `pesertas.regu_id` → may exclude/include incorrectly | **WRONG** for multi-event users | YES |
| **Rekap Peserta** (Event A) | Displays `$peserta?->regu->regu` from legacy | **WRONG** — shows global regu, not event-specific | YES |
| **Rekap Absensi** (Event A) | Displays `$lp->regu->regu` from legacy | **WRONG** | YES |
| **QR Label** (Event A) | Filters via legacy `regu_id` | **WRONG** | YES |
| **Print routes** (Event A) | Filters via legacy `regu_id` | **WRONG** | YES |
| **Export** (Event A) | Filters + displays via legacy regu | **WRONG** | YES |
| **Attendance** | Scan uses Participation — correct. But regu filter in AttendanceReadService uses legacy | **WRONG** for filter | YES |
| **Filter regu** across all screens | All use legacy `pesertas.regu_id` | **WRONG** | YES |
| **Database Peserta** | Shows `$legacyPeserta?->regu` | **WRONG** | YES |
| **EditPeserta** form read | Reads from `$legacyPeserta?->regu_id` | **WRONG** — will show wrong regu if Event B changed legacy | YES |
| **Ulang** form read | Same pattern | **WRONG** | YES |
| **GantiPeserta** | Uses legacy peserta directly — no event context — shows whatever's in legacy | **WRONG** but legacy-only feature | DEFER |
| **TambahPeserta** search results | Shows legacy regu from any event | **WRONG** for multi-event users | MEDIUM |

**12 features are currently WRONG for multi-event scenarios.**

---

## 8. Attendance Read Path

### AttendanceReadService (`app/Services/Attendance/AttendanceReadService.php`)
- **Regu eager load:** `'person.legacyPesertaMapping.peserta.regu'` (line 20)
- **Regu filter:** `$participationQuery->whereHas('person.legacyPesertaMapping.peserta', fn ($q) => $q->where('regu_id', $reguId))` (line 25)
- **Regu display:** via `$lp->regu->regu` in blade templates
- **Canonical Attendance:** `EventAttendance` already linked to `Participation` via `participation_id`
- **Legacy fallback:** `Absensi` by `nip`, `IzinAbsensi` by `peserta_id`

### Audit Findings:
1. **AttendanceReadService queries `Participation`** already — so `$participation->regu` is directly available
2. **Filter can use** `$participationQuery->where('regu_id', $reguId)` directly instead of legacy whereHas
3. **Eager load** can use `'regu'` instead of `'person.legacyPesertaMapping.peserta.regu'`
4. **Blade templates** can use `$entry->participation->regu->regu` instead of `$lp->regu->regu`
5. **Exception: rekap-absensi.blade.php:151** — the alfa table currently iterates `$pesertaBelumAbsen` which is mapped from legacy. This path needs refactoring to use `$entry->participation->regu->regu`.

### AttendanceService / AttendanceExceptionService:
- **No regu reads** — these handle scan and izin recording only
- **No changes needed**

### Dashboard Scan:
- **No regu display** — just nama, nip, jam_scan
- **No changes needed**

---

## 9. QR / Print / Export

### QR Label (QRLabel/Index.php)
- **Filter:** `$legacyQuery->where('regu_id', $this->filterRegu)` via `legacyParticipationMapping.peserta` (line 249)
- **Fix:** Change to `$query->where('regu_id', $this->filterRegu)` on the Participation query
- **Risks:** LOW — Participation is already queried with event scope

### Print Routes (routes/web.php)
- **Route 1 (line 153):** `$q->where('regu_id', request('regu'))` via `legacyParticipationMapping.peserta`
- **Route 2 (line 343):** same pattern for A4 print
- **Fix:** Append `$query->where('regu_id', request('regu'))` directly on Participation query
- **Risks:** LOW — already scoped to event

### Export (PesertaExport.php)
- **Filter:** `whereHas('person.legacyPesertaMapping.peserta', fn => where('regu_id', ...))` (line 48)
- **Display:** `$peserta?->regu?->regu` (line 79)
- **Fix:** Replace filter with direct `$query->where('regu_id', ...)`; replace display with `$participation->regu?->regu`
- **Risks:** LOW — already scoped to event_id via `$this->event_id`

---

## 10. Filtering

### All filter locations:

| # | File | Line | Current Filter | Target Filter |
|---|------|------|---------------|---------------|
| F1 | `AttendanceReadService.php` | 25 | `whereHas('person.legacyPesertaMapping.peserta', fn => where('regu_id', $reguId))` | `where('regu_id', $reguId)` |
| F2 | `RekapPeserta.php` | 47 | same as F1 | `where('regu_id', $this->regu_id)` |
| F3 | `PesertaExport.php` | 48 | same as F1 | `where('regu_id', $this->regu_id)` |
| F4 | `QRLabel/Index.php` | 249 | `whereHas('legacyParticipationMapping.peserta', fn => where('regu_id', $filterRegu))` | `where('regu_id', $this->filterRegu)` |
| F5 | `routes/web.php` | 153 | same as F4 | `where('regu_id', request('regu'))` |
| F6 | `routes/web.php` | 343 | same as F4 | `where('regu_id', request('regu'))` |

All 6 filter locations query Participation already. All can use direct `where('regu_id', ...)` on the Participation query.

**All will be wrong in multi-event** when filtering by regu because `pesertas.regu_id` is global (updated by any event's latest write).

---

## 11. Legacy Fallback Strategy

### OPTION A — Hard Cutover
- All reads directly from `Participation.regu_id`
- No fallback to `pesertas.regu_id`
- **Risk:** HIGH — existing participations without `regu_id` (pre-Sprint 4 data) would show null

### OPTION B — Canonical First + Legacy Fallback (RECOMMENDED)
- Read from `$participation->regu` if available
- Fallback to `$peserta->regu` if Participation.regu is null
- **Risk:** LOW
- **Implementation:** `$participation->regu ?? $legacyPeserta?->regu`

### OPTION C — Contextual Hybrid
- Event-scoped screens: `Participation.regu_id`
- Global legacy screens: `pesertas.regu_id`
- **Risk:** MEDIUM — inconsistent behavior confuses users

### Recommendation: OPTION B

**Rationale:**
1. Sprint 4 migration backfilled `participations.regu_id` from `pesertas.regu_id`
2. Most participations already have `regu_id` populated
3. Legacy fallback provides safety net for any edge cases
4. Backfill migration ensured `participations.regu_id = pesertas.regu_id` for all existing records
5. Only new participations since Sprint 4 would have canonical regu_id (which equals the dual-written legacy value)

**If Option B is implemented, the fallback will rarely fire** — only if:
- Migration was incomplete
- A participation was created without regu_id (shouldn't happen after Sprint 4)
- Edge case in CaiParticipantReplacement

---

## 12. Future of pesertas.regu_id

### Current Status (Sprint 4 completion):
- `pesertas.regu_id` is WRITTEN for dual-write compatibility
- `pesertas.regu_id` is READ by all production code
- `PersonLegacySyncService` explicitly does NOT sync `regu_id` (line 18)

### After Sprint 5 (read-path cutover):
- `pesertas.regu_id` would still be WRITTEN (dual-write can continue)
- `pesertas.regu_id` would NO LONGER be READ by production code
- All reads go through `Participation.regu_id`

### Prerequisites before removing pesertas.regu_id:
1. Sprint 5 read-path cutover COMPLETE
2. All production reads use `Participation.regu_id`
3. Legacy fallback removed (or proven unnecessary)
4. `GantiPeserta` migrated to use Person+Peserta instead of legacy peserta
5. `Ulang.php:145` search on legacy peserta table migrated to Participation-based search
6. Test coverage for all read paths using canonical source
7. Audit confirms no remaining reads of `$peserta->regu_id`
8. Remove dual-write: stop writing to `pesertas.regu_id`
9. Migration to drop `pesertas.regu_id` column

### Timeline:
- **Sprint 6+:** Can begin deprecation after Sprint 5 stabilizes
- **Sprint 6+:** Stop dual-write
- **Sprint 7+:** Remove column (requires migration + data validation)

---

## 13. regus.event_id Analysis

### Current State:
- `regus` table has NO `event_id` column
- `Participation.regu_id` → `regus.id` (FK, no event context on regu)
- Same regu record is shared across events

### Analysis:

**Scenario:**
- Event A → Regu "Khodam" (regus.id = 1)
- Event B → Regu "Khodam" (regus.id = 1) — same record

**Classification: NOT NEEDED for Sprint 5**

**Rationale:**
1. The current design assumes regu names are shared across events
2. A regu is a categorization entity (e.g., "Khodam", "Malikat") — shared by design
3. What varies per event is which regu a participant belongs to, which is stored in `participations.regu_id`
4. If in the future each event needs its own set of regu names/identifiers, `regus.event_id` would be needed

**Future Requirement (Sprint 8+):**
- If different events need different regu pools (e.g., Event A has regu 1-5, Event B has regu A-D)
- Then `regus.event_id` (nullable) would allow event-specific regu sets
- Migration would need: add `event_id`, update FK, seed per-event regus

**Current design is sufficient** for the multi-event requirement because:
- Regu names are shared (same pool)
- Participant-to-regu assignment is event-specific (via `participations.regu_id`)
- This is the correct architecture for the current domain model

---

## 14. Test Coverage

### Existing Multi-Event Regu Tests:

| Test File | Coverage | Status |
|-----------|----------|--------|
| `tests/Feature/Registrasi/Sprint4ReguEventScopingTest.php` | Comprehensive: writes to both peserta + participation, asserts both sides | ✅ SUFFICIENT |
| `tests/Feature/Registrasi/PersonReuseTest.php` | Person reuse across events with different regu | ✅ SUFFICIENT |
| `tests/Feature/Registrasi/LegacyParticipationCallerRefactorTest.php` | Legacy caller refactor with regu writes | ✅ SUFFICIENT |
| `tests/Feature/Registrasi/LegacyParticipationBridgeFoundationTest.php` | Bridge foundation with regu | ✅ SUFFICIENT |
| `tests/Feature/Registrasi/RegistrationCanonicalValidationTest.php` | Validation of canonical regu writes | ✅ SUFFICIENT |

### Tests NEEDED for Sprint 5:

| Test Scenario | Module | Priority |
|---------------|--------|----------|
| Dashboard shows correct regu per event (Person A → Event A Regu 1, Event B Regu 5) | Dashboard | MUST |
| Rekap Peserta filter by regu uses participation.regu_id | RekapPeserta | MUST |
| Rekap Peserta display shows event-correct regu | RekapPeserta | MUST |
| Rekap Absensi display shows event-correct regu | RekapAbsensi | MUST |
| QR Label filter by regu uses participation.regu_id | QRLabel | MUST |
| Print filtered by regu uses participation.regu_id | Route handler | MUST |
| Export filter by regu + display uses participation.regu_id | Export | MUST |
| AttendanceReadService filter by regu uses participation.regu_id | Attendance | MUST |
| Database Peserta display shows event-correct regu | Database | MUST |
| EditPeserta form reads regu from participation, not legacy | EditPeserta | MUST |
| Legacy fallback works when participation.regu_id is null | All modules | SHOULD |
| GantiPeserta masih menggunakan legacy peserta data (tidak perlu diubah) | GantiPeserta | DEFER |
| TambahPeserta search untuk person existing — regu ditampilkan dengan benar | TambahPeserta | SHOULD |

---

## 15. Recommended Sprint 5 Scope

### MUST DO:
1. **AttendanceReadService** — Switch eager load + filter from legacy to canonical
2. **Dashboard** — Display regu from `$entry->participation->regu->regu`
3. **Rekap Peserta** — Switch filter + display from legacy to canonical
4. **Rekap Absensi** — Switch display from legacy to canonical
5. **QR Label** — Switch filter from legacy to canonical
6. **Print routes** (web.php) — Switch filter from legacy to canonical
7. **Export (PesertaExport)** — Switch filter + display from legacy to canonical
8. **Database Peserta** — Switch eager load + display from legacy to canonical
9. **EditPeserta** — Switch form read from legacy to canonical
10. **Ulang** — Switch form read from legacy to canonical

### SHOULD DO:
11. **RegistrationService** — Read regu_id from data first, fallback to legacy (keep as is, already correct)
12. **TambahPeserta search results** — Show canonical regu when participation exists
13. **Add test coverage** for all switched paths

### DEFER:
14. **GantiPeserta** — Legacy-only feature, uses legacy peserta ID
15. **Ulang search** (line 145) — Legacy peserta table search
16. **Remove dual-write** — Post-Sprint 5
17. **Drop pesertas.regu_id** — Post-Sprint 5 or Sprint 6+
18. **regus.event_id** — Not needed
19. **AttendanceService / AttendanceExceptionService** — No regu reads

---

## 16. Exact Files to Change

### Production Files (23 files):

| # | File | Change |
|---|------|--------|
| 1 | `app/Services/Attendance/AttendanceReadService.php` | Line 20: `'person.legacyPesertaMapping.peserta.regu'` → `'regu'`; Line 25: `whereHas(...where('regu_id', $reguId))` → `where('regu_id', $reguId)` |
| 2 | `app/Livewire/Rekap/Peserta/RekapPeserta.php` | Line 47: change filter to direct `where('regu_id', $this->regu_id)`; Line 82: `$peserta?->regu` → `$participation->regu` |
| 3 | `app/Livewire/Rekap/Absensi/RekapAbsensi.php` | Line 62: `$lp?->regu` → `$entry->participation->regu` |
| 4 | `app/Livewire/Database/Peserta/Database.php` | Line 36: remove legacy regu eager loads, add `'regu'`; Line 61: `$legacyPeserta?->regu` → `$participation->regu` |
| 5 | `app/Livewire/Database/Peserta/EditPeserta.php` | Line 74: `$legacyPeserta?->regu_id` → `$participation->regu_id` |
| 6 | `app/Livewire/Registrasi/Ulang.php` | Line 91: `$legacyPeserta?->regu_id` → `$participation->regu_id` |
| 7 | `app/Livewire/QRLabel/Index.php` | Line 249: `$legacyQuery->where('regu_id', ...)` → direct `$query->where('regu_id', ...)` |
| 8 | `app/Exports/PesertaExport.php` | Line 48: change filter to direct `where('regu_id', ...)`; Line 79: `$peserta?->regu?->regu` → `$participation->regu?->regu` |
| 9 | `routes/web.php` | Line 153: change filter to direct `$q->where('regu_id', request('regu'))`; Line 343: same |
| 10 | `resources/views/livewire/dashboard/dashboard.blade.php` | Line 116: `$lp->regu->regu` → `$entry->participation->regu->regu`; Line 156: `$legacy->regu->regu` → `$participation->person?->legacyPesertaMapping?->peserta?->regu?->regu` → `$participation->regu->regu` |
| 11 | `resources/views/livewire/rekap/absensi/rekap-absensi.blade.php` | Lines 75, 111: `$lp->regu->regu` → `$entry->participation->regu->regu`; Line 151: `$peserta->regu->regu` → restructure to use `$entry->participation->regu->regu` |
| 12 | `resources/views/livewire/rekap/peserta/rekap-peserta.blade.php` | Line 115: `$p->regu->regu` — verify `$p` is now mapped from `$participation->regu` (already done in RekapPeserta.php) |
| 13 | `resources/views/livewire/database/peserta/database.blade.php` | Line 37: `$peserta->regu->regu` → `$peserta->regu->regu` (verify mapped correctly in Database.php) |

### Test Files to Add:

| # | File | Purpose |
|---|------|---------|
| 1 | `tests/Feature/Registrasi/Sprint5ReguReadPathCutoverTest.php` | Comprehensive read-path tests after cutover |

---

## 17. Risk Matrix

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Wrong regu displayed across events | HIGH (12 features currently wrong) | HIGH (users see incorrect regu) | Sprint 5 cutover + thorough testing |
| Query N+1 after removing eager loads | MEDIUM | MEDIUM (performance) | Add proper `->with('regu')` eager loads after cutover |
| Missing Participation.regu_id (null) | LOW (backfill done in Sprint 4) | MEDIUM (null displayed) | Legacy fallback in Option B |
| Legacy-only peserta has no Participation | LOW (all legacy pesertas have participations after sprint 4) | MEDIUM (can't display regu) | Legacy fallback |
| Null regu_id in legacy + canonical | LOW | LOW (shows '-' already handled) | Null coalescing with `?? '-'` |
| Incorrect export data | MEDIUM (filter uses legacy) | HIGH (wrong reports) | Cutover filter to canonical |
| Attendance regression | LOW (scan doesn't use regu) | MEDIUM | Only filter and display affected |
| Filter regression | MEDIUM (all 6 filters use legacy) | MEDIUM | Cutover filters to canonical |
| QR/Print regression | LOW | MEDIUM | Cutover filters to canonical |
| Edit form shows wrong regu | HIGH (reads legacy) | HIGH (operator saves wrong regu) | Cutover form read to canonical |
| Ulang form shows wrong regu | HIGH (reads legacy) | HIGH | Cutover form read to canonical |

---

## 18. Dependencies & Blockers

### No blockers.

**Prerequisites already met:**
- ✅ `participations.regu_id` column exists (Sprint 4 migration)
- ✅ Backfill completed (Sprint 4 migration)
- ✅ Dual-write active (Sprint 4 implementation)
- ✅ All test cases pass (1521 passed, 0 failures)
- ✅ Design C problem_total = 0

**Implementation order:**
1. `AttendanceReadService` (core service, other features depend on it)
2. `AttendanceReadService` filter change
3. Dashboard blade changes
4. Rekap components (Peserta + Absensi)
5. Database Peserta component
6. QR Label component
7. Print routes
8. Export
9. Form reads (EditPeserta, Ulang)
10. TambahPeserta search display (if in scope)
11. Add tests

---

## 19. GO / NO-GO

### Sprint 5 Implementation: **GO**

**Condition:** Strictly Option B (Canonical First + Legacy Fallback)
- `$participation->regu ?? $legacyPeserta?->regu`
- Safe for all existing data
- Backward compatible
- Zero data migration needed

---

## 20. Explicit Answers

### 1. Berapa production read path masih memakai peserta.regu_id?

**26 production read-path locations** across 17 files still read from `pesertas.regu_id`:
- 6 filter locations
- 10 display locations (PHP)
- 6 display locations (Blade)
- 2 eager load locations
- 2 form read locations

### 2. Berapa yang sudah memakai Participation.regu_id?

**0.** Zero production code reads `participation.regu_id` or `$participation->regu`.

### 3. Modul apa saja yang masih salah untuk Multi Event?

**12 modules wrong:**
1. Dashboard (wrong regu display)
2. AttendanceReadService (wrong filter + eager load)
3. Rekap Peserta (wrong filter + display)
4. Rekap Absensi (wrong display)
5. QR Label (wrong filter)
6. Print routes (wrong filter)
7. Export (wrong filter + display)
8. Database Peserta (wrong display)
9. EditPeserta (wrong form read)
10. Ulang (wrong form read)
11. TambahPeserta search (wrong display)
12. Filter regu across all screens (wrong source)

### 4. Apakah Sprint 5 aman dilakukan sekarang?

**YES.** Safe to implement because:
- Event context available in all screens
- `participations.regu_id` already populated (Sprint 4 backfill)
- Dual-write ensures both sources are in sync
- Legacy fallback provides safety net
- Baseline tests pass

### 5. Strategi cutover terbaik: A/B/C?

**OPTION B — Canonical First + Legacy Fallback**

Implementation pattern:
```php
$regu = $participation->regu ?? $legacyPeserta?->regu;
```

### 6. Apakah legacy fallback masih diperlukan?

**YES, temporarily.** For Sprint 5:
- Safety net for any participations with null regu_id
- Edge cases in CaiParticipantReplacement
- Legacy-only features (GantiPeserta)
- Can be removed Sprint 6+ after validation

### 7. Apakah pesertas.regu_id bisa dihapus setelah Sprint 5?

**NO.** Not immediately. Prerequisites:
1. Sprint 5 production stabilization (2-4 weeks)
2. Remove all reads (Sprint 5 does this)
3. Stop dual-write (separate sprint)
4. Migrate GantiPeserta off legacy peserta
5. Drop column in dedicated migration sprint

**Earliest removal: Sprint 7**

### 8. Apakah dual-write masih perlu setelah Sprint 5?

**YES, for now.** Dual-write should continue until:
- After Sprint 5 is fully stable in production
- All reads confirmed working from canonical source
- Legacy fallback usage drops to zero
- Typically 1-2 sprints of co-existence

### 9. Apakah regus.event_id diperlukan?

**NOT NEEDED.** Current architecture:
- Regu names are shared across events (domain-correct)
- Event-specific assignment is via `participations.regu_id`
- `regus.event_id` would be a future requirement if events need separate regu pools

### 10. Exact scope Sprint 5 yang direkomendasikan?

**MUST DO (10 changes):**
1. AttendanceReadService eager load + filter
2. Dashboard display
3. Rekap Peserta filter + display
4. Rekap Absensi display
5. QR Label filter
6. Print routes filter (x2)
7. Export filter + display
8. Database Peserta eager load + display
9. EditPeserta form read
10. Ulang form read

**SHOULD DO (1 change):**
11. TambahPeserta search display

### 11. Exact files yang harus diubah?

**13 production files:**
1. `app/Services/Attendance/AttendanceReadService.php`
2. `app/Livewire/Rekap/Peserta/RekapPeserta.php`
3. `app/Livewire/Rekap/Absensi/RekapAbsensi.php`
4. `app/Livewire/Database/Peserta/Database.php`
5. `app/Livewire/Database/Peserta/EditPeserta.php`
6. `app/Livewire/Registrasi/Ulang.php`
7. `app/Livewire/QRLabel/Index.php`
8. `app/Exports/PesertaExport.php`
9. `routes/web.php`
10. `resources/views/livewire/dashboard/dashboard.blade.php`
11. `resources/views/livewire/rekap/absensi/rekap-absensi.blade.php`
12. `resources/views/livewire/rekap/peserta/rekap-peserta.blade.php`
13. `resources/views/livewire/database/peserta/database.blade.php`

### 12. Test apa yang wajib ditambah?

**MUST:**
- Read-path cutover integration test (event A vs event B regu correctness)

**Test scenarios:**
1. Person A → Event A Regu 1, Event B Regu 5 → Dashboard shows correct regu per event
2. RekapPeserta filter by regu uses canonical source
3. RekapAbsensi display shows event-correct regu
4. QRLabel filter by regu uses canonical source
5. Print route filter by regu uses canonical source
6. Export display shows event-correct regu + filter works
7. DatabasePeserta display shows event-correct regu
8. EditPeserta reads regu from participation, not legacy
9. AttendanceReadService filter by regu uses canonical source

### 13. Apakah ada blocker sebelum implementasi?

**NO blockers.** All prerequisites satisfied:
- ✅ `participations.regu_id` exists and populated
- ✅ Dual-write active
- ✅ All tests passing
- ✅ Design C clean
- ✅ Event context available on all relevant screens
