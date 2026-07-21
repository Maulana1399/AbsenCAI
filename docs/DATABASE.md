# Database Design

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

## People Table Schema

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-increment |
| `nama` | string | Required |
| `jenis_kelamin` | string(1) nullable | `L` or `P` |
| `tanggal_lahir` | date nullable | Added PGM.14.5 |
| `desa_id` | FK → desas nullable | `nullOnDelete` |
| `kelompok_id` | FK → kelompoks nullable | Added PGM.16, `nullOnDelete` |
| `nip` | integer nullable unique | Legacy compatibility |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Current participant identity columns:
- `id`
- `nip`
- `participant_number` (active, nullable)
- `attendance_code` (active, nullable)

Identity transition status:
- `nip` adalah legacy operational identifier untuk backward compatibility
- `nip` laki-laki menggunakan range `1001+`
- `nip` perempuan menggunakan range `2001+`
- `participant_number` adalah identitas peserta yang human-readable dengan format `KL001` / `KP001`
- `attendance_code` adalah primary QR attendance identifier dengan format `KJA-XXXXXXXX`
- Lookup attendance mengutamakan `attendance_code` lalu temporary fallback ke `nip`

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
| `nip` | — | ❌ Immutable untuk mapped Person |
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

NIP

Legacy operational identifier. Laki-laki `1001+`, perempuan `2001+`.
