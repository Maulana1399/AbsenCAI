# Architecture

## Vision

KJA Event Manager adalah platform Event Management berbasis Web yang dapat digunakan oleh banyak organisasi.

---

## Current Infrastructure

Internet
│
Cloudflare Tunnel
│
Mini PC
│
Proxmox
├── Debian Router
├── Rocky Linux
├── TrueNAS
└── Windows

---

## Production

Internet

↓

Cloudflare

↓

Laravel

↓

MariaDB

↓

Storage (Nextcloud / TrueNAS)

---

## Client

- Desktop
- Laptop
- Android
- iPhone

---

## User

- Super Admin
- Admin
- Sekretariat
- Ketua
- PJ Divisi
- Operator
- Juri
- Peserta
- Viewer

---

## Current Sprint Status

After PGM.19 (Physical Regu Retirement) and PGM.20 (Legacy NIP Retirement):

```
✅ PGM.12–PGM.17 Pengajian Desa MVP — COMPLETE
✅ PGM.18 Physical Mapping Cleanup — COMPLETE
✅ PGM.19 Physical Regu Retirement — COMPLETE
✅ PGM.20 Legacy NIP Retirement (Phase 1–4B) — COMPLETE
✅ S01–S04 Foundation — COMPLETE
✅ S3.0–S3.10 Multi Event — COMPLETE
✅ RBAC S1–S7 — COMPLETE
```

## Canonical Data Architecture

```
Person (master identity)
├── nama
├── desa
├── kelompok
├── tanggal_lahir
│
└── Participation (event-scoped membership)
        ├── event
        ├── regu (CAI only, nullable)
        ├── participant_number
        ├── attendance_code
        │
        ├── EventAttendance (canonical attendance)
        │       ├── status (hadir/izin)
        │       └── method (scan/manual/surat_izin)
        │
        └── ActivityRegistration (optional)
```

**Retired components:**
- `pesertas.regu_id` — physical column removed (Sprint 8B)
- `pesertas.nip` — physical column removed (PGM.20)
- `people.nip` — physical column removed (PGM.20)
- NIP — retired as canonical identity, lookup, fallback, display, and generation
- Regu dual-write — stopped
- Legacy Absensi dual-write — stopped (absensi table retained for historical reads)

## Authentication

Login

Role Based Access

Token Based Scan (Future)

---

## QR Generation

Current direction:

- QR content will use `attendance_code`
- QR generation should be reusable for ID Card, export, API, and mobile app
- QR rendering logic should live in a dedicated service layer

Batch export direction:

- QR assets can be generated in bulk for mass printing workflows
- File naming should use `participant_number`
- Export should return summary data, not a UI-specific response

Print direction:

- Print templates should be isolated from business logic
- A print engine should compose participant data, QR rendering, and printable output
- First template is `label-4x4`

---

## File Storage

Database

↓

Metadata

↓

Nextcloud

↓

TrueNAS

---

## Development Rule

Development Server

Mini PC

Production

Dedicated Server / VPS

Backup

TrueNAS