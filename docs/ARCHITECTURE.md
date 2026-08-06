# Architecture

## Vision

KJA Event Manager adalah **Event Operating System** — platform Event Management berbasis Web yang dapat digunakan oleh banyak organisasi.

Filosofi: **Build Engine, Not Module**. Semua jenis event dikonfigurasi melalui Competition Engine, Scoring Engine, dan Blueprint Event — bukan melalui modul khusus.

Lihat `docs/VISION_V2.md` untuk detail arsitektur V2.

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

After PGM.19 (Physical Regu Retirement), PGM.20 (Legacy NIP Retirement), Competition V1 (Sprint 7–10), dan Sprint series 1–3.2:

```
✅ Sprint 1 — Platform Consolidation — COMPLETE
✅ Sprint 2 — RBAC & Permission Engine (Design C) — COMPLETE
✅ Sprint 3.1 — Technical Debt Cleanup — COMPLETE
✅ Sprint 3.2 — Architecture Hardening — COMPLETE
✅ Sprint 3.3 — Legacy Retirement Prep & UAT Readiness — COMPLETE
🔲 Sprint 4 — NOT STARTED
✅ PGM.12–PGM.17 Pengajian Desa MVP — COMPLETE
✅ PGM.18 Physical Mapping Cleanup — COMPLETE
✅ PGM.19 Physical Regu Retirement — COMPLETE
✅ PGM.20 Legacy NIP Retirement (Phase 1–4B) — COMPLETE
✅ S01–S04 Foundation — COMPLETE
✅ S3.0–S3.10 Multi Event — COMPLETE
✅ RBAC S1–S7 — COMPLETE
✅ Competition V1 (Sprint 7–10) — COMPLETE
✅ Public Portal (Sprint 9.0) — COMPLETE
✅ Event Dashboard (Sprint 10.0) — COMPLETE
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

Permission Engine (Design C): ability event-scoped di-resolve dari `User → Person → EventCommitteeAssignment → EventRole.permissions`. `users.role` hanya menentukan hak platform (SuperAdmin/Admin).

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

## Import Framework

> Status: 🔲 **IF-01–IF-09 COMPLETE** — **SELURUH import memakai SATU Import Framework** (backend + frontend). Peserta (legacy terakhir) dimigrasi; tidak ada lagi `Excel::import`/manual coordinator/registry/pipeline/bypass.
> Audit & GAP: `docs/import-audit.md` | Arsitektur & roadmap: `docs/import-framework.md`

Seluruh 7 fitur import (Desa, Kelompok, Regu, Person, Participation, Pengajian, Peserta)
distandarisasi: UI/UX, lifecycle, validation, preview, summary, commit flow, dan testing yang sama.
**Import Massal Pengajian** adalah golden standard yang berjalan di atas framework.

```
app/Services/Import/
├── Contracts/        (definition, metadata, parser, validator, normalizer, dupe, committer,
│                      activityLogger, pipelineStage, logger, template)
├── Pipeline/         (coordinator, pipeline, state, 8 stage nyata:
│                      parse→normalize→validate→duplicate→preview→commit→summary→cleanup)
├── Support/          (runner, file parser, personIdentityNormalizer, versions)
├── Registry/         (register/resolve/has/all)
├── DTO/              (context, raw/normalized row, error, warning)
├── Results/          (summary, preview, commit [+metrics], result, pipelineResult)
├── Exceptions/       (typed import exceptions)
├── NullObjects/      (no-op defaults)
├── Template/         (generator framework DATA/PETUNJUK/REFERENSI + wrapper)
└── Adapters/         (desa/kelompok/regu/person/participation/pengajian/peserta — ALL REAL)
```

Wizard reusable: `ImportWizardBase` (lifecycle base) + komponen `<x-import.*>` + partial form Peserta.
Wiring DI: `ImportServiceProvider`. Baseline hijau — tidak ada jalur import di luar `ImportAdapter`.

---

## Development Rule

Development Server

Mini PC

Production

Dedicated Server / VPS

Backup

TrueNAS