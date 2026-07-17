# SPRINT 3 MULTI EVENT ARCHITECTURE AUDIT

> Produced: 2026-07-17
> Source of truth: Actual code, migrations, models, services, tests
> No implementation. No migrations. No commits.

---

## 1. EXECUTIVE SUMMARY

The current AbsenCAI codebase is a **single-event application** with no awareness of multiple events anywhere in the architecture. Every entity — peserta, absensi, sesi, izin, surat izin, regu — exists in a global namespace with zero event scoping.

**Key finding: There is zero event infrastructure.** No `event_id` column exists on any table. No `Event` model exists. No event filtering exists in any query. The application is architected as "one deployment = one event."

**Key strengths for migration:**
- Identity infrastructure (participant_number, attendance_code, NIP) is already abstracted with unique constraints
- Services are moderately well-separated
- ActivityLog already uses polymorphic subjects (will adapt well)
- Registration and Placement services are centralized
- Credit: The Sprint 2 identity work (participant_number, attendance_code) laid groundwork for Person abstraction

**Critical risks:**
- NIP (globally unique INT with gender ranges) is the most deeply coupled identifier — used in Absensi model, QR lookup, PlacementService, dashboard queries, imports
- `peserta` table is monolithic: person identity + event participation + placement in one row
- No concept of "person across events"
- Absensi model uses `nip` (not `peserta_id`) for the relationship — this means attendance is tied to NIP, not to participant ID
- `surat_izins` tied directly to `peserta_id` with no event awareness
- Active session is a global boolean (`aktif` on `sesi_absensis`) — only one session can be active globally
- All tests operate in a single-event world

**Baseline:** 186 tests, 432 assertions. All currently pass in single-event mode.

---

## 2. CURRENT DATABASE MAP

### Table: `desas`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| desa_asal | varchar(255) | **UNIQUE** |
| created_at, updated_at | timestamp | nullable |

**Relationships:** `hasMany(kelompok)`, `hasMany(peserta)` via peserta
**Events:** No event_id — global master data

### Table: `kelompoks`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| kelompok_asal | varchar(255) | |
| desa_id | bigint unsigned | FK -> desas(id) ON DELETE CASCADE, **nullable** |
| created_at, updated_at | timestamp | nullable |
| **UNIQUE** | | (kelompok_asal, desa_id) |

**Relationships:** `belongsTo(desa)`, `hasMany(peserta)`
**Events:** No event_id — global

### Table: `regus`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| regu | varchar(255) | **UNIQUE** |
| jenis_kelamin | enum('Laki - Laki', 'Perempuan') | **nullable** |
| created_at, updated_at | timestamp | nullable |

**Relationships:** `hasMany(peserta)`
**Events:** No event_id — global. Regu names are globally unique (e.g., "Biru Laki - Laki").

### Table: `pesertas` — THE CORE TABLE (monolithic)
| Column | Type | Constraints | Classification |
|--------|------|-------------|---------------|
| id | bigint unsigned | PK | Internal |
| nama | varchar(255) | NOT NULL | **Person** |
| nip | int | **UNIQUE** | **Legacy/Person** |
| status_registrasi | varchar(255) | default 'Belum Registrasi' | **Participation** |
| participant_number | varchar(255) | **UNIQUE nullable** | **Event Participation** |
| attendance_code | varchar(255) | **UNIQUE nullable** | **Event Participation** |
| jenis_kelamin | varchar(255) | **nullable** | **Person** |
| jenis_peserta | varchar(255) | default 'Wajib' | **Participation** |
| kelompok_id | bigint unsigned | FK -> kelompoks(id) CASCADE, nullable | **Placement** |
| desa_id | bigint unsigned | FK -> desas(id) CASCADE, nullable | **Person/Placement** |
| regu_id | bigint unsigned | FK -> regus(id) CASCADE, nullable | **Placement** |
| created_at, updated_at | timestamp | nullable | |

**UNIQUE constraints:** `(nama, desa_id, kelompok_id)`
**Relationships:** `belongsTo(kelompok, desa, regu)`, `hasMany(SuratIzin)`
**Identity collision risk:** The composite unique `(nama, desa_id, kelompok_id)` treats same-name-from-same-village-and-group as duplicate person. This is a single-event assumption.

### Table: `absensis`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| nip | int | NOT NULL |
| nama | varchar(255) | NOT NULL |
| jam_scan | timestamp | NOT NULL |
| sesi_id | bigint unsigned | FK -> sesi_absensis(id) SET NULL, nullable |
| created_at, updated_at | timestamp | nullable |

**CRITICAL:** Uses `nip` (not `peserta_id`) as participant identifier. The `nama` column is denormalized. No event_id.

### Table: `sesi_absensis`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| nama_sesi | varchar(255) | NOT NULL |
| tanggal | date | NOT NULL |
| aktif | boolean | default true |
| created_at, updated_at | timestamp | nullable |

**CRITICAL:** `aktif` is a global boolean. No event_id. Only one session can be active system-wide.

### Table: `izin_absensis`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| peserta_id | bigint unsigned | FK -> pesertas(id) CASCADE |
| sesi_id | bigint unsigned | FK -> sesi_absensis(id) CASCADE |
| status | varchar(255) | default 'izin' |
| source | varchar(255) | default 'manual' |
| surat_izin_id | bigint unsigned | FK -> surat_izins(id) SET NULL, nullable |
| created_at, updated_at | timestamp | nullable |
| **UNIQUE** | | (peserta_id, sesi_id) |

**Relationships:** `belongsTo(peserta, sesi, suratIzin)`

### Table: `surat_izins`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| peserta_id | bigint unsigned | FK -> pesertas(id) CASCADE |
| nomor_surat | varchar(255) | **UNIQUE nullable** |
| alasan | text | NOT NULL |
| jenis_izin | varchar(20) | default 'pulang' |
| tanggal_mulai | date | NOT NULL |
| tanggal_selesai | date | NOT NULL |
| status | enum('draft','pending','approved','rejected') | default 'draft' |
| created_by | bigint unsigned | FK -> users(id) CASCADE |
| approved_by | bigint unsigned | FK -> users(id) SET NULL, nullable |
| approved_at | timestamp | nullable |
| returned_at | timestamp | nullable |
| created_at, updated_at | timestamp | nullable |

**CRITICAL:** `peserta_id` direct. No event_id. The approval creates izin_absensis across ALL sessions in date range with no event scope.

### Table: `activity_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK -> users(id) SET NULL, indexed |
| action | varchar(255) | NOT NULL, indexed |
| module | varchar(255) | NOT NULL, indexed |
| subject_type | varchar(255) | **nullable** |
| subject_id | bigint unsigned | **nullable** |
| description | varchar(255) | NOT NULL |
| properties | json | **nullable** |
| ip_address | varchar(45) | **nullable** |
| user_agent | text | **nullable** |
| created_at | timestamp | indexed |
| **INDEX** | | (subject_type, subject_id) |

**Well-adapted for multi-event:** Uses polymorphic morphTo. Already supports any subject type.

---

## 3. CURRENT IDENTITY MODEL

### Identifier Contracts

#### `peserta.id` (internal, auto-increment)
- Database primary key, never exposed
- Used as FK in `surat_izins.peserta_id`, `izin_absensis.peserta_id`
- **Shared across all events** (currently global)

#### `nip` (UNIQUE INT, legacy operational)
- **Male range:** 1001-1999
- **Female range:** 2001-2999
- **Default:** max(nip) + 1
- **Deeply coupled:** Used in `Absensi` model as the relationship key (`belongsTo(peserta::class, 'nip', 'nip')`), in QR scan fallback, in dashboard queries, in `PlacementService::legacyNextNip()`
- **Must remain backward compatible** for existing QR codes that may contain NIP
- **Is NOT event-specific** currently — globally unique

#### `participant_number` (UNIQUE nullable varchar, human-facing)
- Pattern: `KL`+3digit for male, `KP`+3digit for female (e.g., KL001, KP001)
- Generated by `PlacementService::generateParticipantNumber()` — queries `max(participant_number)` globally with gender prefix
- **Currently globally unique** — will need to become event-scoped
- Used in label print, batch QR export, search in UI

#### `attendance_code` (UNIQUE nullable varchar, QR content)
- Format: `KJA-XXXXXXXX` (8 random uppercase alphanumeric)
- Generated by `RegistrationService::generateAttendanceCode()` — uniqueness-checked globally
- **Currently globally unique** — the primary scan identifier
- QR code content = attendance_code
- Used in `AttendanceService::findParticipant()` as first lookup strategy

### Field Ownership Classification

| Field | Person | Event Participation | Placement | Legacy |
|-------|--------|-------------------|-----------|--------|
| `pesertas.id` | ✓ (internal PK) | | | |
| `nama` | ✓ | | | |
| `nip` | | | | ✓ (legacy CAI) |
| `jenis_kelamin` | ✓ | | | |
| `desa_id` | ✓ (origin village) | | ✓ (grouping) | |
| `kelompok_id` | | | ✓ | |
| `regu_id` | | | ✓ | |
| `participant_number` | | ✓ (event-specific number) | | |
| `attendance_code` | | ✓ (QR per event) | | |
| `jenis_peserta` | | ✓ (Wajib/Kiriman/Person) | | |
| `status_registrasi` | | ✓ (registration status per event) | | |

### Critical Identity Issues

1. **Monolithic peserta table:** A person who participated in CAI 2025 and KJA 2026 would have TWO `peserta` rows currently. No way to link them as the same human.
2. **NIP coupling in Absensi model:** `Absensi::peserta()` uses `belongsTo(peserta::class, 'nip', 'nip')` — this means attendance lookup bypasses the participant ID and uses the legacy NIP directly.
3. **Composite unique `(nama, desa_id, kelompok_id)`:** This prevents duplicate registration of the same person within one event, but would also prevent the same person from being registered in a different event if they share name/village/group.
4. **Globally unique attendance_code:** Will conflict when events have overlapping participants.

---

## 4. SINGLE-EVENT COUPLING MAP

### CRITICAL (breaks immediately in multi-event)

| # | Location | Issue | File |
|---|----------|-------|------|
| C1 | **No Event model exists** | Cannot scope anything | — |
| C2 | **No event_id on any table** | Desas, kelompoks, regus, pesertas, sesi_absensis, absensis, izin_absensis, surat_izins — none have event scope | All migrations |
| C3 | **Global active session** | `SesiAbsensis::where('aktif', true)->first()` — only one session active system-wide | `Dashboard::mount()`, `Dashboard::activateSesi()`, `Scan::mount()`, `AttendanceService::processScan()` |
| C4 | **Dashboard queries ALL peserta** | `peserta::count()` — no event filter | `Dashboard/Dashboard.php:mount()` |
| C5 | **QR lookup is global** | `AttendanceService::findParticipant()` searches all participants regardless of event | `AttendanceService.php` |
| C6 | **Surat Izin approval creates izin across ALL sessions** | `SesiAbsensi::whereBetween('tanggal', [...])` — no event scope, could leak into other event's sessions | `SuratIzinService.php:approve()` |
| C7 | **SyncNewSession applies to ALL approved surat** | `SuratIzin::where('status', 'approved')` — no event filter on SuratIzin or SesiAbsensi | `SuratIzinService.php:syncNewSession()` |
| C8 | **Participant_number globally unique** | `peserta::whereNotNull('participant_number')->where('participant_number', 'like', $prefix.'%')->max()` — would collide across events | `PlacementService.php:generateParticipantNumber()` |
| C9 | **Attendance_code globally unique** | `peserta::where('attendance_code', $code)->exists()` in loop — would collide | `RegistrationService.php:generateAttendanceCode()` |
| C10 | **NIP globally unique** | `peserta::unique('nip')` + range-based generation — collision across events | Migration + `PlacementService.php:legacyNextNip()` |

### HIGH (functional issues, data corruption risk)

| # | Location | Issue | File |
|---|----------|-------|------|
| H1 | **Rekap Absensi queries ALL peserta** | `peserta::with([...])` then filters by sesi_id but not event_id | `Rekap/Absensi/RekapAbsensi.php` |
| H2 | **Rekap Peserta is global** | `peserta::with([...])` — no event filter at all | `Rekap/Peserta/RekapPeserta.php` |
| H3 | **Peserta CRUD is global** | Database, Tambah, Edit, Hapus, Import — all operate on global peserta | `Database/Peserta/*.php` |
| H4 | **Self-register creates global peserta** | Creates event-independent peserta record | `Registrasi/SelfRegister.php` |
| H5 | **Import creates global peserta** | `PesertaImport::model()` creates peserta with no event context | `Imports/PesertaImport.php` |
| H6 | **Sesi CRUD is event-unaware** | No event_id on sesi; global `aktif` flag | `Database/Sesi/*.php` |
| H7 | **QR label print queries ALL peserta** | `peserta::whereNotNull('attendance_code')` — no event filter | `routes/web.php` (3 print routes), `QRLabel/Index.php` |
| H8 | **Batch QR export has no event scope** | Exports QR codes for all participants globally | `BatchQRExportService.php` |
| H9 | **PesertaExport filters only by regu/desa/kelompok/gender** | No event filter | `Exports/PesertaExport.php` |
| H10 | **Surat Izin create/search is global** | `peserta::where(...)` search has no event context | `SuratIzin/Create.php` |

### MEDIUM (visible UI issues, misattribution)

| # | Location | Issue | File |
|---|----------|-------|------|
| M1 | **Dashboard regu filter is global** | `regu::all()` and peserta filtered by regu_id with no event scope | `Dashboard/Dashboard.php` |
| M2 | **Dashboard "Belum Absen" computed from all peserta** | Uses global peserta list filtered by active session | `Dashboard/Dashboard.php` |
| M3 | **Desa/Kelompok/Regu data pages are global** | These are shared master data — will need per-event or global decision | `Database/Desa/*`, `Database/Kelompok/*`, `Database/Regu/*` |
| M4 | **Regu names globally unique** | `regus.regu` has UNIQUE constraint — two events cannot share regu names | Migration |
| M5 | **Config `kjam.php` has single event name** | Single event_name, event_logo, org_logo | `config/kjam.php` |
| M6 | **SesiAbsensi->aktif is a single boolean** | Cannot have simultaneously active sessions across events | Migration + all Sesi Livewire |
| M7 | **Surat Izin filter/search by peserta name** | No event scope could show peserta from wrong event | `SuratIzin/Index.php`, `SuratIzin/Create.php` |
| M8 | **ActivityLog has no event_id** | Logs cannot be filtered by event | `ActivityLog.php` model + `ActivityLogIndex.php` |
| M9 | **PlacementService auto-assigns globally** | `leastFilledRegu()` picks regu with fewest peserta globally — no event scope | `PlacementService.php` |
| M10 | **Scan page manual search is global** | `peserta::where('nama', 'like', ...)` — no event filter | `Scan.php` |

### LOW (cosmetic, lower priority)

| # | Location | Issue | File |
|---|----------|-------|------|
| L1 | **Dashboard.dashboard.blade.php** | May display event name statically | View |
| L2 | **Seeders create global data** | `DatabaseSeeder.php` creates peserta without event | Seeder |
| L3 | **ExportLog, PrintLog, QrLog** | Log-based features without event filter | Tests |
| L4 | **Welcome page** | Static content | `welcome.blade.php` |
| L5 | **Surat Izin print view** | Uses static event config from `kjam.php` | `surat-izin/print.blade.php` |

---

## 5. PROPOSED TARGET DOMAIN

### Core Entities

```
Person ──< Participation >── Event
  │                           │
  │                           ├── EventSession
  │                           ├── Venue (future)
  │                           └── Category (future)
  │
  ├── desa (origin)
  ├── jenis_kelamin
  └── tanggal_lahir (future)
```

### Relationship Diagram (text)

```
people
├── id (PK, bigint unsigned)
├── nama (varchar)
├── jenis_kelamin (varchar, nullable)
├── desa_id (FK -> desas, nullable)
├── created_at
├── updated_at
│ UNIQUE: (normalized_nama, desa_id, jenis_kelamin) — for dedup

events
├── id (PK, bigint unsigned)
├── name (varchar)
├── slug (varchar, UNIQUE)
├── date_start (date, nullable)
├── date_end (date, nullable)
├── is_active (boolean)
├── config (json, nullable) — event-specific settings
├── created_at
├── updated_at

participations
├── id (PK, bigint unsigned)
├── person_id (FK -> people, NOT NULL)
├── event_id (FK -> events, NOT NULL)
├── participant_number (varchar, nullable)
├── attendance_code (varchar, nullable)
├── jenis_peserta (varchar, default 'Wajib')
├── status_registrasi (varchar, default 'Belum Registrasi')
├── kelompok_id (FK -> kelompoks, nullable)
├── regu_id (FK -> regus, nullable)
├── created_at
├── updated_at
│ UNIQUE: (event_id, participant_number)
│ UNIQUE: (event_id, attendance_code)
│ UNIQUE: (event_id, person_id) — one participation per person per event
│ INDEX: (event_id, kelompok_id)
│ INDEX: (event_id, regu_id)

event_sessions (renamed from sesi_absensis)
├── id (PK)
├── event_id (FK -> events)
├── nama_sesi (varchar)
├── tanggal (date)
├── aktif (boolean, default false) — PER-EVENT active session
├── created_at, updated_at
│ UNIQUE: (event_id, nama_sesi, tanggal) — prevent duplicate sessions

attendances (renamed from absensis)
├── id (PK)
├── participation_id (FK -> participations)
├── event_session_id (FK -> event_sessions)
├── jam_scan (timestamp)
├── created_at, updated_at

attendance_exceptions (renamed from izin_absensis)
├── id (PK)
├── participation_id (FK -> participations)
├── event_session_id (FK -> event_sessions)
├── status (varchar, default 'izin')
├── source (varchar, default 'manual')
├── permit_id (FK -> permits, nullable)
├── created_at, updated_at
│ UNIQUE: (participation_id, event_session_id)

permits (renamed from surat_izins)
├── id (PK)
├── participation_id (FK -> participations)
├── nomor_surat (varchar, UNIQUE nullable — can keep globally unique or make event-scoped)
├── alasan (text)
├── jenis_izin (varchar, default 'pulang')
├── tanggal_mulai (date)
├── tanggal_selesai (date)
├── status (enum)
├── created_by (FK -> users)
├── approved_by (FK -> users, nullable)
├── approved_at (timestamp, nullable)
├── returned_at (timestamp, nullable)
├── created_at, updated_at
```

### NOT Recommended for Sprint 3

These are deliberately excluded from the initial multi-event scope:
- **venues** — No venue support needed yet
- **categories** — No competition module yet
- **competitions** — Deferred to later sprint
- **roles** — RBAC is a separate concern
- **organizations** — Multi-org is deferred

### Tables that remain GLOBAL (shared across events)

- `desas` — Desa is a geographical master data, not event-specific
- `kelompoks` — Kelompok is a grouping within desa, shared
- `regus` — Regu names are placement groups — NEEDS discussion: per-event or global?
  - **Recommendation:** Make regus per-event. Event C has Regu Biru, Event D has Regu Biru independently. Remove global unique constraint on `regu`, add `event_id`.
- `users` — System users, not event-specific

---

## 6. FIELD OWNERSHIP MATRIX

| Current Field | Target Location | Rationale |
|--------------|----------------|-----------|
| `pesertas.nama` | `people.nama` | Person identity |
| `pesertas.jenis_kelamin` | `people.jenis_kelamin` | Person identity |
| `pesertas.desa_id` | `people.desa_id` | Person origin (village) |
| `pesertas.nip` | `people.nip` (nullable, UNIQUE) | Legacy identifier — keep on person for CAI backward compat |
| `pesertas.participant_number` | `participations.participant_number` | Per-event participant number |
| `pesertas.attendance_code` | `participations.attendance_code` | Per-event QR code |
| `pesertas.jenis_peserta` | `participations.jenis_peserta` | Per-event participant type |
| `pesertas.status_registrasi` | `participations.status_registrasi` | Per-event registration status |
| `pesertas.kelompok_id` | `participations.kelompok_id` | Per-event grouping |
| `pesertas.regu_id` | `participations.regu_id` | Per-event placement |
| `absensis.nip` | REMOVE — use `participation_id` | Denormalized legacy |
| `absensis.nama` | REMOVE — use `participation.person.nama` | Denormalized |
| `absensis.sesi_id` | `attendances.event_session_id` | Straight rename |
| `sesi_absensis.aktif` | `event_sessions.aktif` scoped per event | Was global, now per-event |
| `izin_absensis.peserta_id` | `attendance_exceptions.participation_id` | Now links to participation |
| `surat_izins.peserta_id` | `permits.participation_id` | Now links to participation |

### Legacy Compatibility Fields

| Field | Keep On | Duration |
|-------|---------|----------|
| `pesertas.nip` | people (or legacy pivot) | Until all CAI operational use ends (2027+) |
| `absensis.nip` | absensis | Until attendance history migration complete |
| `absensis.nama` | absensis | Until attendance history migration complete |
| `pesertas.regu_id` | pesertas (kept as view) | Read-only after migration |

---

## 7. MULTI EVENT ATTENDANCE ARCHITECTURE

### Current Flow

```
Scan QR → AttendanceService::processScan()
  → findParticipant(identifier)  [global lookup]
    → by attendance_code (global)
    → fallback by NIP (global)
  → get active session (SesiAbsensi::where('aktif', true)->first())  [GLOBAL SINGLETON]
  → check IzinAbsensi (participant_id + sesi_id)
  → check duplicate Absensi (nip + sesi_id)
  → create Absensi (nip, nama, jam_scan, sesi_id)
```

### Target Flow

```
Scan QR → AttendanceService::processScan()
  → resolve active event from context (session, route, or global)
  → findParticipantInEvent(identifier, event_id)
    → by attendance_code WHERE event_id = X
    → fallback by NIP where person has participation in event X
  → get active session for event (EventSession::where('event_id', X)->where('aktif', true)->first())
  → check attendance_exception (participation_id + event_session_id)
  → check duplicate attendance (participation_id + event_session_id)
  → create Attendance (participation_id, event_session_id, jam_scan)
```

### Design Decisions

1. **Attendance belongs to Participation, not Person**
   - `Attendance` → `participation_id` + `event_session_id`
   - This inherently isolates attendance by event

2. **Duplicate detection changes**
   - Currently: `Absensi::where('nip', $nip)->where('sesi_id', $id)`
   - Target: `Attendance::where('participation_id', $pid)->where('event_session_id', $sid)`
   - This also correctly handles the case where a person participates in multiple events with sessions on the same date

3. **Izin isolation**
   - `AttendanceException` scoped to `(participation_id, event_session_id)`
   - `Permit` scoped to `participation_id`
   - `SuratIzinService::approve()` queries sessions within date range **scoped to the participation's event**
   - `SuratIzinService::syncNewSession()` queries approved permits **scoped to the new session's event**

4. **Active session becomes per-event**
   - Delete global `aktif` from `sesi_absensis`
   - Add `aktif` to `event_sessions` scoped per event
   - Dashboard shows sessions for current event

5. **QR lookup is event-scoped**
   - `findParticipant(identifier, eventId)` queries participations in that event only
   - Legacy NIP fallback cross-references person's participation in given event

6. **Regu assignment is per-event**
   - `leastFilledRegu(eventId, gender)` queries participations within event, not all peserta
   - Regu names can repeat across events

### Session Entity

```php
event_sessions
  id (PK)
  event_id (FK -> events)
  nama_sesi
  tanggal
  aktif (boolean, per-event, not global)
  UNIQUE(event_id, nama_sesi, tanggal)
```

The `syncNewSession` mechanism from SuratIzinService should be adapted:
- `SuratIzinService::syncNewSession($eventSession)` → queries permits where `participation.event_id == $eventSession->event_id`

---

## 8. ACTIVE EVENT CONTEXT RECOMMENDATION

### Recommendation: Route-based Event Context with Session Fallback

**Hybrid approach:**

1. **Primary:** URL-based context — `/events/{event}/...`
   - All management routes live under `/events/{event}` prefix
   - Middleware resolves `Event` model and injects into view/components
   - Livewire components receive `$eventId` prop

2. **Secondary:** Session-based active event for convenience
   - Operator can set an "active event" in session
   - Dashboard redirects to `/events/{activeEvent}/dashboard`
   - No forced re-selection on each page load

3. **Global fallback for CAI backward compat:**
   - Route group without `/events/{event}` prefix for CAI operational routes
   - These routes assume the "Legacy CAI Event" (seeded during Stage A)
   - After CAI is fully migrated, routes can be updated

### Implementation Sketch

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    // Legacy CAI routes (backward compatible)
    Route::get('/dashboard', ...)->name('dashboard');
    
    // Multi-event routes
    Route::prefix('/events/{event}')->group(function () {
        Route::get('/dashboard', ...);
        Route::get('/attendance', ...);
        Route::get('/participants', ...);
        Route::get('/reports/attendance', ...);
        Route::get('/reports/participants', ...);
        Route::get('/qr-labels', ...);
        Route::get('/permits', ...);
        Route::get('/sessions', ...);
        Route::get('/activity-log', ...);
    });
});
```

**Why not session-only?**
- Session-based context breaks when operators have multiple tabs open for different events
- Route-based is RESTful, bookmarkable, and testable

**Why not pure route-based?**
- Operator convenience — re-selecting event on every page load is tedious
- Dashboard should default to operator's "active event"

### Livewire Integration

```php
class Dashboard extends Component
{
    public int $eventId;
    
    public function mount(int $eventId)
    {
        $this->eventId = $eventId;
        // All queries scoped to $eventId
    }
}
```

Route binding:
```php
Route::get('/events/{event}/dashboard', [Dashboard::class, '__invoke'])
    ->name('events.dashboard');
```

Or as Livewire full-page:
```php
Route::get('/events/{event}/dashboard', \App\Livewire\Dashboard\Dashboard::class)
    ->name('events.dashboard');
```

Passing event via `wire:key` and component parameters.

---

## 9. BACKWARD COMPATIBILITY & MIGRATION STRATEGY

### Stage A — Event Foundation (Non-destructive)

**Goal:** Introduce Event table and seed Legacy CAI Event without changing existing functionality.

**Actions:**
1. Create `events` migration with columns: `id`, `name`, `slug`(unique), `date_start`, `date_end`, `is_active`, `config`(json), timestamps
2. Create `Event` Eloquent model
3. Seed "CAI Operational" as the legacy event
4. Add `event_id` column (nullable) to these tables via new migrations:
   - `pesertas` — nullable initially
   - `sesi_absensis` — nullable initially
   - `desas` — nullable initially (optional, for future)
   - `kelompoks` — nullable initially (optional)
   - `regus` — nullable initially (CRITICAL: remove global unique on regu name)
   - Remove global UNIQUE on `regus.regu`, replace with `UNIQUE(event_id, regu)`
5. Backfill: Set `event_id = 1` (Legacy CAI) on all existing rows
6. Make `event_id` NOT NULL on all tables
7. **Existing code continues to work** — event_id is present but code doesn't use it yet

**Verification:** All 186 tests pass without modification.

### Stage B — Person + Participation Tables

**Goal:** Introduce normalized Person and Participation tables alongside existing peserta.

**Actions:**
1. Create `people` migration:
   - `id`, `nama`, `jenis_kelamin` (nullable), `desa_id` (FK nullable), `nip` (nullable, UNIQUE — legacy), timestamps
   - INDEX on `(nama, desa_id, jenis_kelamin)` for dedup matching
2. Create `Person` Eloquent model
3. Create `participations` migration:
   - `id`, `person_id` (FK), `event_id` (FK), `peserta_id` (FK nullable — link back to legacy)
   - `participant_number` (nullable), `attendance_code` (nullable)
   - `jenis_peserta`, `status_registrasi`
   - `kelompok_id` (FK nullable), `regu_id` (FK nullable)
   - UNIQUE `(event_id, participant_number)`
   - UNIQUE `(event_id, attendance_code)`
   - UNIQUE `(event_id, person_id)`
4. Create `Participation` Eloquent model with relationships

**At this stage:**
- New participants can be created as Person + Participation
- Existing `pesertas` table is untouched
- Legacy services continue using `pesertas`
- New services use `Person`/`Participation`

### Stage C — Legacy Data Backfill

**Goal:** Create Person + Participation rows from existing peserta data.

**Actions:**
1. Artisan command: `php artisan backfill:people-from-pesertas`
   - For each existing `peserta` row, create a `Person` record
   - Create a `Participation` linked to the legacy event (event_id=1)
   - Set `participations.peserta_id` back to the source `pesertas.id`
   - Handle duplicates: if two peserta rows have the same normalized name + desa + gender, they MIGHT be the same person — flag for review rather than auto-merge

**WARNING:** No automatic merging. The composite unique `(nama, desa_id, kelompok_id)` means the same person registered in multiple events would have separate peserta rows. Backfill creates one Person per peserta row initially, then dedup is handled separately.

### Stage D — Compatibility Layer

**Goal:** Legacy services can continue operating while new architecture is active.

**Actions:**
1. Add a `Visibility` scope or helper that adds default `event_id` filter
2. `AttendanceService` compatibility mode:
   - If `event_id` provided, use Participation/Attendance
   - If not provided, fall back to existing peserta/Absensi logic
3. `RegistrationService` compatibility:
   - New method: `createPersonParticipation()` creates Person + Participation
   - Old method: `createParticipant()` continues to work on peserta table
4. `PlacementService` compatibility:
   - Add `event_id` parameter to `leastFilledRegu(eventId, gender)` — queries participations
   - Old behavior preserved when eventId is null
5. `AttendanceExceptionService` compatibility:
   - Allow both `peserta_id` + `participation_id` routes
6. QR scan compatibility:
   - First try: lookup by attendance_code in participations (event-scoped)
   - Fallback: lookup by NIP in people -> participation in current event
   - Legacy fallback: lookup by NIP in peserta (global, for CAI)

### Stage E — Module-by-Module Migration

**Order of migration (each module is switched independently):**

| Order | Module | Strategy |
|-------|--------|----------|
| 1 | **Sessions** | Migrate `sesi_absensis` → `event_sessions`. Simple rename + event_id. |
| 2 | **Registration** | New creates use Person+Participation. Old reads from peserta kept for dashboard. |
| 3 | **Placement** | Switch `leastFilledRegu` to use participations. Regu becomes per-event. |
| 4 | **Attendance (Scan)** | Switch to Participation+Attendance tables. Keep Absensi as read-only for history. |
| 5 | **Attendance (Izin)** | Switch to Participation+AttendanceException. Keep IzinAbsensi read-only. |
| 6 | **Surat Izin (Permits)** | Switch to Permits linked to Participation. Keep SuratIzin read-only. |
| 7 | **Dashboard** | Add event filter. Queries participation counts instead of peserta counts. |
| 8 | **Reports** | Add event filter. |
| 9 | **QR Labels** | Query participations within event instead of global peserta. |
| 10 | **Imports** | Import creates Person+Participation within the target event. |

### Stage F — Deprecate Direct Peserta Access

**Goal:** No new code uses `pesertas` table directly.

**Actions:**
1. Mark `pesertas` table as read-only source for legacy history
2. All write operations go through `Person` + `Participation`
3. All read operations default to `Participation` scoped by event
4. Remove `peserta_id` FK from `surat_izins`, `izin_absensis` after migration
5. Remove `pesertas` table only after full verification (not in Sprint 3)

**When:** After all CAI operational activity in 2027.

### SQLite → MariaDB Migration

The current database is SQLite. Future target is MariaDB (as configured in `config/database.php`).

**Strategy:**
- Keep SQLite during development and Sprint 3
- Before production deployment for August 2026 Multi Event:
  1. Run Stage A-C on SQLite (data already exists)
  2. Export to MariaDB using Laravel's built-in migration
  3. Verify all data integrity
  4. Switch `DB_CONNECTION` to `mysql` in production
- MariaDB-specific features (enum, full-text indexes) adopted after migration

**Existing QR codes:** Old QR codes contain `attendance_code` (KJA-XXXXXXXX). These remain valid IF:
- The participant with that attendance_code participates in the active event
- OR global fallback lookup remains until CAI deprecation

---

## 10. UNIVERSAL PERSON DEDUPLICATION STRATEGY

### Problem

When a person registers for Event D who was already a participant in Event C (CAI Legacy), they will have:
- Two `peserta` rows (current system)
- Two `Person` rows (after Stage C backfill)

We need to detect and reconcile these.

### Matching Signals

| Signal | Weight | Notes |
|--------|--------|-------|
| Normalized full name | HIGH | Case-insensitive, trim whitespace, remove punctuation |
| Desa (origin village) | HIGH | Same desa_asal |
| Gender | MEDIUM | M/L/P match |
| NIP | HIGH (if exists) | Legacy unique identifier |
| Kelompok | LOW | Can change between events |
| Regu | LOW | Placement changes per event |

### Matching Strategy

#### Phase 1: Automated Exact Match

Two Person records are considered "likely same" if ALL of:
- Normalized names match exactly (case-insensitive, trimmed)
- Desa matches (same desa_id OR both null)
- Gender matches (both same OR one is null)

**Action:** Create a "merge suggestion" record. No automatic merge.

#### Phase 2: Probable Match (Manual Review Queue)

Two Person records match on name + desa but gender differs → flag for manual review.
Two Person records match on name but desa differs → flag for manual review.

#### Phase 3: Manual Review UI

A dedicated Livewire component shows suggested merges:
- Side-by-side person comparison
- Shows participation history for each
- **Merge** button: merges two Person records into one
- **Not same** button: dismisses suggestion
- **Merge flow:** Move all participations from source person to target person. Delete source person.

#### Phase 4: Replace/Link Flow

During self-registration or import:
1. Search existing Person records by name + desa
2. If exact match found → show "Is this you?" prompt with desa and gender info
3. If user confirms → link new participation to existing Person
4. If user declines → create new Person (flagged for review)

**Important safeguards:**
- Never auto-merge solely by name
- Always require at least name + desa match for suggestion
- Manual review is mandatory for any merge action
- Merge is one-way: source person is soft-deleted or marked as duplicate

---

## 11. TEST MIGRATION STRATEGY

### Current Baseline: 186 tests / 432 assertions

All tests create data in a global (single-event) context using `RefreshDatabase`.

### Phase 1: No-Change Zone (tests that remain untouched initially)

These tests operate on services/models that will keep backward compatibility:

| Test File | Reason |
|-----------|--------|
| `tests/Unit/Services/ActivityLogServiceTest.php` | ActivityLog polymorphic — no event coupling |
| `tests/Unit/Services/QRServiceTest.php` | Pure QR generation, no database |
| `tests/Unit/Services/PlacementServiceTest.php` | Can be extended, existing tests valid |
| `tests/Unit/Services/RegistrationServiceTest.php` | Can be extended, existing tests valid |
| `tests/Feature/Auth/*` | Auth unrelated to event |
| `tests/Feature/Settings/*` | Settings unrelated to event |
| `tests/Feature/ExampleTest.php` | Trivial |

### Phase 2: Tests Requiring Event Fixtures

These tests will need event fixtures added (seeded event, participation backfill):

| Test File | Change Required |
|-----------|-----------------|
| `tests/Unit/Services/AttendanceServiceTest.php` | Add event context to scan process |
| `tests/Unit/Services/SuratIzinServiceTest.php` | Permits linked to participation + event |
| `tests/Feature/Attendance/ScanTest.php` | Event-scoped scan |
| `tests/Feature/Attendance/IzinStatusTest.php` | Event-scoped izin |
| `tests/Feature/Attendance/AttendanceStatusSummaryTest.php` | Event-scoped dashboard/rekap |
| `tests/Feature/SuratIzin/UiTest.php` | Event-scoped permits |
| `tests/Feature/ActivityLog/IntegrationTest.php` | May need event context in properties |
| `tests/Feature/DashboardTest.php` | May need event context |
| `tests/Feature/Database/ImportDataTest.php` | Event-scoped import |
| `tests/Feature/Registrasi/OtomatisasiRegistrasiTest.php` | Event-scoped registration |
| `tests/Feature/Identity/QRIdentityIntegrationTest.php` | Event-scoped QR |
| `tests/Feature/ExportLog/ExportLogTest.php` | May need event context |
| `tests/Feature/PrintLog/PrintLogTest.php` | May need event context |
| `tests/Feature/QrLog/QrLogTest.php` | May need event context |

### Phase 3: New Architecture Tests Required

| Test | Priority | Description |
|------|----------|-------------|
| Person can join multiple events | CRITICAL | Create Person → 2 Participations in 2 events → verify both exist |
| Same person in different events has independent attendance | CRITICAL | Same Person, 2 Events, 2 Sessions → attend in Event A → verify Event B unaffected |
| Event data isolation | CRITICAL | Create Event A + Event B → add data to A → verify B is empty |
| Attendance cannot leak between events | CRITICAL | Scan in Event A → db has no record in Event B |
| Sessions belong to correct event | CRITICAL | Create session for Event A → query Event B sessions → empty |
| Participant number uniqueness per event | HIGH | `KL001` in Event A and `KL001` in Event B are both valid |
| Attendance code uniqueness per event | HIGH | `KJA-XXXXXXXX` in Event A and Event B are independent |
| QR lookup isolation | HIGH | QR code from Event A only resolves in Event A |
| Active session per event | HIGH | Event A has active session S1, Event B has active session S2 → each dashboard shows correct |
| Dashboard report event filtering | HIGH | Dashboard counts match only participants in selected event |
| Permit creates izin in correct event | HIGH | Approved permit creates izin only for sessions in the permit's event |
| Legacy CAI data remains accessible | CRITICAL | Old peserta/absensi/surat_izin data is still queryable after migration |
| Person dedup: exact match suggestion | MEDIUM | Two Person records with same name+desa → flagged |
| Person dedup: merge operation | MEDIUM | Merge two persons → participations transferred |
| Participation has correct back-reference to peserta | MEDIUM | After backfill, participation.peserta_id points to original peserta row |

### Testing Approach

1. Create a `EventTestHelper` trait or class:
   ```php
   trait MultiEventTestHelper {
       protected function createEvent(string $name = 'Test Event'): Event { ... }
       protected function createPerson(string $name = 'Test Person'): Person { ... }
       protected function createParticipation(Person $person, Event $event): Participation { ... }
   }
   ```

2. Each test that needs multi-event creates its own events → no global state dependencies

3. Legacy tests continue using `peserta` factory directly (no change needed)

---

## 12. PROPOSED SPRINT 3 BREAKDOWN

### Dependencies

```
S3.0 ──> S3.1 ──> S3.2 ──> S3.3 ──> S3.5 ──> S3.6 ──> S3.7 ──> S3.10
           │                  │                              │
           │                  └──> S3.4 ──────────────────────┘
           │                                                 │
           └─────────────────────────────────────────────────┘
                                            │
                                          S3.8 ──> S3.9 (independent, parallel)
```

### Phase S3.0 — Architecture & Database Design (DESIGN ONLY, CURRENT)
- This audit document ✓
- Finalize schema decisions
- Review with team
- **No code changes**

### Phase S3.1 — Event Foundation (Stage A)
**Dependencies:** None
**Estimated:** 3-5 days

1. Create `events` migration + Event model
2. Seed Legacy CAI Event
3. Create `event_sessions` migration (rename from sesi_absensis with event_id)
4. Add `event_id` to: pesertas, sesi_absensis, desas, kelompoks, regus
5. Remove global UNIQUE on `regus.regu`, add UNIQUE `(event_id, regu)`
6. Backfill event_id = 1 (Legacy CAI) on all existing rows
7. Verify all existing 186 tests still pass
8. Register routes with `/events/{event}` prefix

### Phase S3.2 — Universal Person (Stage B Part 1)
**Dependencies:** S3.1
**Estimated:** 3-5 days

1. Create `people` migration + Person model
2. Create `participations` migration + Participation model
3. Add relationships: Person hasMany Participation, Event hasMany Participation
4. Create factories for Person, Participation
5. Write core tests: person creation, participation linking

### Phase S3.3 — Participation (Stage B Part 2)
**Dependencies:** S3.2
**Estimated:** 3-5 days

1. Create new `RegistrationService::createPersonParticipation()` method
2. Create new `PlacementService` methods scoped to event
3. Generate participant_number per-event
4. Generate attendance_code per-event (UNIQUE per event, not globally)
5. Migrate `AttendanceService::findParticipant()` to support event-scoped lookup
6. Tests: participant number per-event, attendance code per-event

### Phase S3.4 — Active Event Context
**Dependencies:** S3.2
**Estimated:** 2-3 days

1. Add `active_event_id` to session (optional convenience)
2. Create middleware to resolve event from route or session
3. Wire Livewire components to accept `$eventId`
4. Add helper: `activeEvent()` or `EventScope::forCurrentEvent()`
5. Tests: route binding, session fallback

### Phase S3.5 — Legacy Data Backfill (Stage C)
**Dependencies:** S3.2
**Estimated:** 2-3 days

1. Artisan command `backfill:people-from-pesertas`
2. Creates Person + Participation for each existing peserta
3. Links participation back to source peserta via `peserta_id`
4. Tests: backfill produces correct Person/Participation rows
5. Tests: legacy data remains queryable

### Phase S3.6 — Attendance Event Scoping
**Dependencies:** S3.1, S3.3
**Estimated:** 5-7 days

1. Create `attendances` table (renamed from absensis, with participation_id + event_session_id)
2. Create `attendance_exceptions` table (renamed from izin_absensis, with participation_id)
3. Create `permits` table (renamed from surat_izins, with participation_id)
4. Update `AttendanceService::processScan()` to work with new tables when event_id provided
5. Update `AttendanceExceptionService::recordIzin()` for event scope
6. Update `SuratIzinService` for event-scoped approval (sessions filtered by event)
7. Update `syncNewSession()` for event scope
8. Tests: event isolation, no attendance leak, permit scope

### Phase S3.7 — Participant/QR Migration
**Dependencies:** S3.3, S3.4
**Estimated:** 3-5 days

1. Migrate QR label UI to use participations within event
2. Migrate batch QR export to use participations within event
3. Migrate individual QR print routes to use participations
4. QR scan endpoint: event-scoped lookup with legacy fallback
5. Tests: QR lookup isolation, batch export scoping

### Phase S3.8 — Dashboard & Report Scoping
**Dependencies:** S3.4, S3.5
**Estimated:** 3-5 days

1. Dashboard: queries scoped to active event
2. Dashboard counts: participations within event instead of global peserta
3. Rekap Absensi: scoped to event sessions
4. Rekap Peserta: scoped to participations within event
5. Dashboard session activation: per-event active session
6. Tests: dashboard counts match event participants

### Phase S3.9 — Multi Role/Venue/Category (DESIGN ONLY for now)
**Dependencies:** S3.1
**Estimated:** 1-2 days (design, not implementation)

1. Document role requirements per event
2. Document venue/category future needs
3. Update schema design docs
4. **Defer implementation** — not needed for August 2026 go-live

### Phase S3.10 — Regression & Production Readiness
**Dependencies:** All above
**Estimated:** 5-7 days

1. Full test suite: verify all 186 existing + new tests pass
2. Manual QA on critical flows:
   - CAI operational (legacy routes) still work
   - Multi-event flows (event-isolated) work
3. Performance test: event-scoped queries vs global queries
4. Data integrity check: backfill verification
5. Documentation update
6. Deployment checklist

---

## 13. RISKS / BLOCKERS

### CRITICAL

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| R1 | **NIP is deeply coupled — Absensi model uses `belongsTo(peserta, 'nip', 'nip')`** | Breaking this relationship breaks ALL attendance history queries. Any migration that changes peserta ID schema must preserve this view. | Keep `pesertas` table as read-only data source during transition. Add a VIEW or computed relationship. |
| R2 | **Absensi table stores `nip` and `nama` denormalized** | Cannot simply drop these columns. Legacy attendance records lose meaning without participant context. | Keep denormalized columns. New attendance records use `participation_id`. Legacy records remain queryable. |
| R3 | **Existing QR codes contain globally unique `attendance_code`** | If event-scoping makes attendance_code unique per-event, old QR codes may fail in multi-event context. | Old QR codes must include event context OR a global fallback lookup must remain until CAI fully deprecated. |
| R4 | **`surat_izins.approve()` queries ALL sessions in date range** | If different events have sessions on the same date, approving a permit could leak IzinAbsensi into wrong event. | Highest priority fix. Scoping `syncNewSession` and `approve` to the participation's event is mandatory. |
| R5 | **`sesi_absensis.aktif` is a global boolean** | Only one session can be active system-wide. Two events with concurrent sessions cannot operate. | Session activation must become per-event. |

### HIGH

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| R6 | **Composite unique `(nama, desa_id, kelompok_id)`** | Prevents same-name-from-same-village in same kelompok. Survives multi-event but may block legitimate same-person-in-different-event scenario. | After Person/Participation migration, this constraint should be modified or removed. |
| R7 | **`regus.regu` globally unique** | Event A and Event B cannot both have a "Biru" regu. | Remove global unique, add per-event unique. |
| R8 | **SQLite during development, MariaDB in production** | Different SQL dialects, different constraint handling, different migration capabilities. Test MariaDB-specific features early. | Run CI on both SQLite and MariaDB. Test MariaDB at least weekly. |
| R9 | **All test fixtures create global data** | Tests will need significant refactoring to support event context. | Use `EventTestHelper` trait. Run legacy tests first, then new tests. |
| R10 | **No role/permission system yet** | Multi-event implies different operators for different events. Without RBAC, any user can access any event's data. | Accept for Sprint 3 — all authenticated users can access any event. RBAC is Sprint 4+. |

### MEDIUM

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| R11 | **Desa/Kelompok model — per-event or global?** | If Desa is per-event, existing data needs duplication. If global, two events cannot independently manage their village lists. | Keep Desa/Kelompok as global master data for now. Per-event desa is a future enhancement. |
| R12 | **Config `kjam.php` has single event name** | Printed documents will have wrong event name. | Event name comes from `Event::name` model, not config. Config becomes default fallback. |
| R13 | **No `tanggal_lahir` on peserta** | Person dedup cannot use date of birth as matching signal. | Add to `people` table. Nullable. Not urgent for Sprint 3. |
| R14 | **ActivityLog polymorphic subjects will reference old model classes** | After migration, log entries may reference `App\Models\peserta` instead of `App\Models\Person`. | Keep subject_type/subject_id as-is. Old logs reference old models. New logs reference new models. Both classes coexist. |

### LOW

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| R15 | **Static event logo in config** | Low priority — cosmetic. | Make configurable per-event via Event model. |
| R16 | **Seeder uses hardcoded data** | Only affects development. | Update seeder for multi-event after migration. |
| R17 | **Welcome page is static** | Cosmetic. | Update after multi-event core is stable. |

---

## 14. ROADMAP UPDATE PROPOSAL

### Sprint 2 Changes

| Item | Status Change | Reason |
|------|---------------|--------|
| Surat Izin (Permission) | **Keep as-is, operationally stable** | Working in production since Sprint 2 |
| Activity Log (Audit) | **Keep as-is, operationally stable** | Verified: 186 tests pass |
| Riwayat Izin (Scoring) | **DEFERRED** | Not needed until next CAI use in 2027 |
| Scoring | **DEFERRED** | Not needed until next CAI use in 2027 |
| Storage (Nextcloud/TrueNAS) | **DEFERRED** | Not needed until next CAI use in 2027 |
| S2 remaining items | **DEFERRED** | Multi Event is highest priority |

### Sprint 3: Multi Event Architecture

Status: **ACTIVE / HIGHEST PRIORITY**

| Phase | Status | Dependencies | Target |
|-------|--------|--------------|--------|
| S3.0 Design & Audit | **IN PROGRESS** | None | This document |
| S3.1 Event Foundation | PENDING | S3.0 | Sprint 3 Week 1 |
| S3.2 Universal Person | PENDING | S3.1 | Sprint 3 Week 2 |
| S3.3 Participation | PENDING | S3.2 | Sprint 3 Week 2-3 |
| S3.4 Active Event Context | PENDING | S3.2 | Sprint 3 Week 3 |
| S3.5 Legacy Data Backfill | PENDING | S3.2 | Sprint 3 Week 3 |
| S3.6 Attendance Event Scoping | PENDING | S3.1, S3.3 | Sprint 3 Week 4 |
| S3.7 Participant/QR Migration | PENDING | S3.3, S3.4 | Sprint 3 Week 4-5 |
| S3.8 Dashboard & Report Scoping | PENDING | S3.4, S3.5 | Sprint 3 Week 5 |
| S3.9 Multi Role/Venue/Category | **DEFERRED (design only)** | S3.1 | Post-Sprint 3 |
| S3.10 Regression & Readiness | PENDING | All above | Sprint 3 Week 6 |

### Proposed ROADMAP.md Changes

1. Sprint 2 → Mark as "MIXED — features operational, partial deferral"
   - Surat Izin: ✅ Verified
   - Activity Log: ✅ Verified
   - Riwayat Izin: 🔴 Deferred → 2027
   - Scoring: 🔴 Deferred → 2027
   - Storage: 🔴 Deferred → 2027

2. Sprint 3 → Update to "Multi Event Architecture — HIGHEST PRIORITY"
   - Replace existing Sprint 3 placeholder with phases above
   - Add explicit "Target: August 2026 operational use"

---

## 15. RECOMMENDED FIRST IMPLEMENTATION STEP

### Start with: S3.1 — Event Foundation

**Why first:**
1. Zero risk to existing functionality — only adds new tables and nullable columns
2. All 186 existing tests continue to pass without modification
3. Seeds a "Legacy CAI Event" that existing data maps to
4. Creates the `event_id` column infrastructure that ALL subsequent phases depend on
5. The route prefix `/events/{event}` can be registered without breaking existing routes

**First concrete action:**

```
1. php artisan make:model Event -m
2. Define migrations:
   - create_events_table
   - add_event_id_to_pesertas_table
   - add_event_id_to_sesi_absensis_table
   - add_event_id_to_regus_table (remove global unique on regu)
   - (optionally) add_event_id_to_desas_table, add_event_id_to_kelompoks_table
3. php artisan db:seed --class=LegacyEventSeeder
4. Backfill event_id on existing rows
5. Run full test suite → all 186 tests pass
```

**After S3.1 is verified:**
- The application still works exactly as before
- But `event_id` infrastructure is in place
- All subsequent phases build on this foundation
- Route prefixes can be registered for new multi-event endpoints
- Existing CAI routes remain unchanged

---

## APPENDIX: KEY FILE REFERENCE

| File | Purpose |
|------|---------|
| `database/migrations/*` | All table schemas (21 files) |
| `app/Models/peserta.php` | Core participant model — monolithic |
| `app/Models/Absensi.php` | Attendance record — uses `nip` as FK |
| `app/Models/SesiAbsensi.php` | Session — global active flag |
| `app/Models/IzinAbsensi.php` | Attendance exception — links to peserta |
| `app/Models/SuratIzin.php` | Permit — links to peserta directly |
| `app/Models/ActivityLog.php` | Audit log — polymorphic, well-designed |
| `app/Services/Attendance/AttendanceService.php` | Scan processing — global lookup |
| `app/Services/Attendance/AttendanceExceptionService.php` | Izin recording |
| `app/Services/Attendance/SuratIzinService.php` | Permit lifecycle — queries all sessions |
| `app/Services/Placement/PlacementService.php` | NIP/participant_number/regu — all global |
| `app/Services/Registration/RegistrationService.php` | Participant creation — all global |
| `app/Livewire/Dashboard/Dashboard.php` | Dashboard — global counts |
| `app/Livewire/Dashboard/Scan.php` | QR scan UI |
| `app/Livewire/Rekap/Absensi/RekapAbsensi.php` | Attendance report |
| `app/Livewire/Database/Peserta/TambahPeserta.php` | Participant creation form |
| `routes/web.php` | All routes — no event prefix |
| `config/kjam.php` | Single event config |
| `tests/Unit/Services/*` | 5 unit test files |
| `tests/Feature/*` | 19 feature test files |
