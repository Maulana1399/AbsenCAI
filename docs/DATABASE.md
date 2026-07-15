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
- `nip` masih dipakai untuk backward compatibility dan scan lama
- `participant_number` sudah disiapkan untuk identitas manusiawi
- `attendance_code` sudah disiapkan untuk QR identity

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

Random Unique String

NIP

Nomor Peserta Event