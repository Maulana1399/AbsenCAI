# Database Design

## Database Engine

- **Primary:** MariaDB (default connection in `.env` / `config/database.php`)
- **Tests:** SQLite `:memory:` (phpunit.xml) — the committed test baseline; MariaDB test runs use `DB_DATABASE=kja_event_manager_test`
- Both drivers verified: **1944 passed / 4648 assertions / 0 failures** on SQLite and MariaDB

## Driver Compatibility Notes

- Laravel `foreignId()->constrained(...)->nullable()` ignores `nullable()` — the FK is created NOT NULL on MariaDB (masked on SQLite). All such columns use explicit `nullable()` before `constrained()`.
- MariaDB enforces a 64-char identifier limit — migrations with long composite unique indexes use explicit short names.
- `Schema::getColumnListing()` order differs: MariaDB honors `->after()`, SQLite appends at end. Tests asserting columns use order-independent (sorted) comparisons.
- InnoDB AUTO_INCREMENT persists across rolled-back transactions (SQLite's `sqlite_sequence` resets). Tests must not rely on coincidental id alignment between tables.
- Unique-constraint `QueryException` messages differ: SQLite `UNIQUE constraint failed: ...`, MariaDB `Duplicate entry ... for key ...`. Code must match on SQLSTATE `23000`, not the message string `'UNIQUE'`.

## Current Structure

Person (global identity)
│
├── desa
├── kelompok
│
└── Participation (event-scoped)
       │
       ├── Event
       ├── LegacyPesertaMapping → peserta (legacy)
       ├── Absensi
       ├── EventAttendance
       └── ActivityRegistration

Desa
│
└── Kelompok
      │
      └── Peserta (legacy)
              │
              ├── Absensi
              └── Registrasi

## Competition Tables (Competition V1 — Sprint 7–10)

| Table | Purpose |
|-------|---------|
| `competition_categories` | Event-scoped competition categories |
| `competition_classes` | Competition classes / lomba (gender: L/P/M, category-linked; `format` 5 format; `status` lifecycle) |
| `competition_registrations` | Competition registrations (participation-linked; satu Person bisa ikut banyak lomba) |
| `competition_schedules` | Match schedule (status: Scheduled/Ready/Playing/Waiting Result/Finished; required_participants; match result fields) |
| `competition_schedule_entries` | Schedule entries |
| `competition_outcomes` | Match outcomes / results |
| `competition_match_officials` | Officials assignment per match (referee/judge/scorer/supervisor) |
| `competition_brackets` | Single elimination bracket |
| `competition_bracket_matches` | Bracket matches |
| `competition_announcements` | Public announcements |

## Competition Foundation (Teams — additive 2026-08)

| Table | Purpose |
|-------|---------|
| `competition_teams` | Team per lomba (`event_id`, `competition_class_id`, `name`, `kelompok_id?`); satu kelompok = satu team per lomba |
| `competition_team_members` | Anggota team (`competition_team_id`, `competition_registration_id`, `is_substitute`, `sort_order`) — players + substitutes |

`competition_classes.format` = `individual_heat` / `individual_mass` / `team_vs_team` / `team_mass` / `individual_vs_individual` (default `individual_heat`).
`competition_classes.status` = `draft` / `registration_open` / `registration_closed` / `ready` / `running` / `finished` / `cancelled` (default `registration_open`).

> Competition **Team tidak memakai Regu**. Team berbasis Kelompok (`kelompok_id`) dan anggota menunjuk `competition_registration_id`.

## People Table Schema

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-increment |
| `nama` | string | Required |
| `jenis_kelamin` | string(1) nullable | `L` or `P` |
| `tanggal_lahir` | date nullable | Added PGM.14.5 |
| `desa_id` | FK → desas nullable | `nullOnDelete` |
| `kelompok_id` | FK → kelompoks nullable | Added PGM.16, `nullOnDelete` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Catatan:** `people.nip` sudah dihapus secara fisik di PGM.20 Phase 4B.
NIP sudah diretire penuh dari canonical architecture.

Current participant identity columns:
- `person.id` (Person internal ID)
- `participations.id` (Participation internal ID)
- `participant_number` (human-facing, format `KL001` / `KP001`)
- `attendance_code` (QR/attendance, format `KJA-XXXXXXXX`)

Identity rules:
- Canonical participant identity adalah **Person → Participation**
- `participant_number` adalah identitas human-readable dalam satu event
- `attendance_code` adalah primary QR attendance identifier
- **NIP sudah tidak digunakan** sebagai canonical identity, lookup, fallback, atau display

---

## Target Structure

Organization
│
├── Person
│
├── Event
│      │
│      ├── Venue
│      ├── Category
│      ├── Competition
│      └── Session
│
└── Participation
        │
        ├── Attendance
        ├── Registration
        ├── Permission
        ├── Score
        ├── Certificate
        └── Violation

---

## Master Data (Global — no event_id)

| Entity | Table | Route | Status |
|--------|-------|-------|--------|
| **Person** | `people` | `/person` | CRUD ✅ |
| **Desa** | `desas` | `/desa` | CRUD ✅ |
| **Kelompok** | `kelompoks` | `/kelompok` | CRUD ✅ |

## Legacy CAI Operational

| Entity | Table | Route | Status |
|--------|-------|-------|--------|
| **Regu** | `regus` | `/regu` | CRUD ✅ (legacy CAI, not global master data) |

## Event Membership (Access)

Event access di-resolve via `event_committee_assignments` — mendukung dua jalur:

```
User / Account
 ├── Person (optional) ──► person_id   (Person-based membership, existing)
 └── Event Membership ──► user_id      (User-based membership — Guest / Event Chair tanpa Person)
```

`event_committee_assignments` (perubahan additive):
- `user_id` nullable → `users` (nullOnDelete) — jalur User-based.
- `person_id` nullable (sebelumnya NOT NULL) — dipakai person-based.
- Unique: `eca_event_person_role_unique` (event, person, role) + `eca_event_user_role_unique` (event, user, role).

`users.person_id` tetap **nullable** — User tanpa Person = valid.

Future:

Organization

Role

Permission

Venue

Category

Competition

---

## Identity Sync Strategy

### Person → peserta (via PersonLegacySyncService)
Dipicu saat Person diedit melalui Master Data CRUD dan memiliki LegacyPesertaMapping.

| Field | Arah | Sync? |
|-------|------|-------|
| `nama` | Person → peserta | ✅ |
| `jenis_kelamin` | Person (L/P) → peserta (Laki - Laki/Perempuan) | ✅ via `PlacementService::normalizePersonGender()` |
| `desa_id` | Person → peserta | ✅ |
| `kelompok_id` | Person → peserta | ✅ |
| `regu_id`, `participant_number`, `attendance_code` | — | ❌ Event-scoped, tidak disinkronkan |

### peserta → Person (via RegistrationService::updateParticipant())
Dipicu saat peserta diedit melalui CAI Database UI.

| Field | Arah | Sync? |
|-------|------|-------|
| `nama` | peserta → Person | ✅ |
| `jenis_kelamin` | peserta → Person (konversi) | ✅ |
| `desa_id` | peserta → Person | ✅ |
| `kelompok_id` | peserta → Person | ✅ (fixed 2026-07-21) |
| `regu_id` | — | ❌ Event-scoped |

---

## Important Concept

Person adalah entitas utama.

Setiap orang hanya dibuat satu kali.

Event hanya membuat Participation.

---

## Identity

Universal ID

KJA-0000001

Attendance Code

Primary QR attendance identifier, format `KJA-XXXXXXXX`

Participant Number

Human-readable participant identifier, format `KL001` / `KP001`

~~NIP~~ (RETIRED)

Legacy operational identifier. **Sudah diretire penuh di PGM.20.**
Kolom `people.nip` dan `pesertas.nip` sudah dihapus.
`legacyNextNip()` sudah dihapus.
NIP sudah tidak digunakan di attendance scan, registration, reports, exports, atau Person CRUD.
