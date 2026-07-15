# Database Design

## Current Structure

Desa
│
└── Kelompok
      │
      └── Peserta
              │
              ├── Absensi
              └── Registrasi

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

## Master Data

Organization

Person

Desa

Kelompok

Role

Permission

Venue

Category

Competition

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
