# CURRENT STATE

> Current Development Status

---

# Project

## Name

KJA Event Manager

Current MVP:

CAI Operational + Pengajian Desa MVP

---

# Current Version

Version:

v1.5

Stage:

MVP Development + Pilot

---

# Current Sprint

Sprint 3 — Multi Event Architecture + Pengajian Desa MVP (COMPLETE)

Status:

✅ PGM.12–PGM.16 COMPLETE — PGM.17 PENDING
✅ S01–S04 Foundation COMPLETE 100%
✅ S3.0–S3.10 all COMPLETE/VERIFIED
🟡 UI Bug Fix Sprint — Backlog (11 items identified)

Target: August 2026 pilot release.

Focus:

Multi Event Foundation completed and verified. S3.0–S3.10 all COMPLETE/VERIFIED.

**Pengajian Desa MVP (PGM series)** implemented on top of S3 architecture:
- PGM.12 Functional Fix ✅ COMPLETE
- PGM.13 Token Management UI ✅ COMPLETE
- PGM.14 Event Context/Isolation ✅ COMPLETE
- PGM.14.1 Security & Reliability Closure ✅ COMPLETE
- PGM.14.5 Manual Participant Entry ✅ COMPLETE
- PGM.15 Dashboard & Report Optimization ✅ COMPLETE
- PGM.16 Pengajian UX, Contextual Navigation & Bulk Import ✅ COMPLETE
  - event_type architecture (cai / pengajian)
  - Contextual sidebar (CAI vs Pengajian menus)
  - KJA Event Manager branding
  - Pengajian bulk import (CSV/Excel with preview)
  - Regional/Desa report filter fixes
  - Responsive Regional Report and Desa Dashboard
  - Event switcher redirect/reload fix
  - Migration: kelompok_id to people table, event_type to events table
- PGM.17 Pilot Release — PENDING

**UI Bug Fix Sprint — Batch 1 (Branding & Navigation)** — Bug #1, #2, #4, #6: **RESOLVED — VERIFIED** ✅ (908 tests passed / 2192 assertions, runtime verification 1–5 berhasil). Bug #8 (token overflow) dan #10 (dark mode) sudah resolved. Batch 2+ menyusul.

**Runtime architecture unchanged for legacy compatibility** — `pesertas` and `LegacyPesertaMapping` remain intentional compatibility bridges.
Sprint 2 remaining scope (Riwayat Izin, Scoring, Storage) **DEFERRED to 2027**.

---

# Current Goal

Pengajian Desa MVP pilot is feature-complete. Next step: PGM.17 Pilot Release.

Completed foundation work:

* S01 Foundation completed.
* S02 Registration and Placement foundation completed.
* S03 Attendance architecture completed.
* S04 Identity & QR completed.
* S05 Document & Certificate deferred / skipped for now.
* S3.0 Architecture & Database Audit completed.
* S3.1 Event Foundation completed.
* S3.2 Universal Person completed.
* S3.3 Participation Foundation completed.
* S3.4 Active Event Context Hardening completed.
* S3.5 Legacy Data Backfill COMPLETE — PRODUCTION BACKFILL EXECUTED 2026-07-17.
* S3.6 Attendance Event Scoping COMPLETE.
* S3.7 Participant/QR Migration COMPLETE.
* S3.8 Dashboard & Report Scoping COMPLETE.
* S3.9A–S3.9E Multi Role/Venue/Category COMPLETE.
* S3.10 Regression & Production Readiness COMPLETE.
* PGM.12–PGM.16 Pengajian Desa MVP COMPLETE.

---

# Current Priority

Priority saat ini:

1. **UI Bug Fix Sprint — Batch 1 Complete** ✅ — Branding & Navigation bugs #1, #2, #4, #6 — RESOLVED VERIFIED (908 tests/2192 assertions).
2. **UI Bug Fix Sprint — Batch 2** — Access Token UI, remaining bugs.
3. **PGM.17 — Pilot Release** — Final validation, deployment, operator training.
3. Event switcher redirect/reload fix ✅ (PGM.16.3)
4. Pengajian bulk import ✅ (PGM.16)
5. Contextual navigation ✅ (PGM.16)
6. Remaining P2/P3 technical debt items.

---

# Project Status

## Documentation

🟢 Stable — updated for PGM.16

## Architecture

🟢 Stable — Multi Event architecture complete with event_type discriminator

## Database

🟢 Stable — 2 new migrations in PGM.16 (kelompok_id on people, event_type on events)

## Core Feature

🟢 Stable — all CAI + Pengajian features operational

## Security

🟢 Stable — contextual sidebar is navigation UX, NOT route-level authorization

---

# Current Technical Stack

Backend: Laravel 12
Frontend: Livewire, Flux UI, Tailwind CSS
Database: SQLite

---

# Current Risks

## High
- SQLite not suitable for concurrent large-scale access
- Multi Event is context-only — modules must be migrated one by one
- Raw access token ditampilkan penuh di modal creation — potensi security issue jika pengguna tidak menyalin token dengan aman

## Medium
- P2: Admin ManualEntry no RBAC
- P2: Two parallel identity correction submission paths
- P2: No XLSX template download for Pengajian import
- Landing page dan login page masih menggunakan branding CAI — membingungkan pengguna baru KJA Event Manager

## Low
- API not yet needed
- Mobile App still planning
- KJA logo navigasi ke dashboard CAI — perlu event-aware routing

---

# Current Technical Debt

- Legacy `pesertas` table remains operational — intentional compatibility bridge
- `RegistrationService` still creates legacy `peserta` records alongside Person/Participation
- `PlacementService` has mixed responsibilities (legacy NIP + participant_number generation)
- Two parallel identity correction paths (PengajianIdentityService vs IdentityCorrectionService)
- Sidebar is static Blade — doesn't live-render on event switch (page navigation resolves)
- No RBAC system for admin UI modules
- Pengajian import template XLSX not yet downloadable from UI
- Event edit form does not allow changing event_type after creation
- No dedicated Pengajian admin dashboard (Regional Report serves as landing)
- Landing page (`/`) still uses CAI branding — needs KJA Event Manager rebrand
- Login page still uses CAI branding — needs KJA Event Manager rebrand
- No hard-delete for revoked DesaAccessGrants — only soft revocation
- `DashboardService` not yet implemented — dashboard stats computed inline in Livewire
- Test suite count stale — last documented 459/1140 (S3.9E), actual needs `php artisan test`

---

# Next Work

**1. UI Bug Fix Sprint — Batch 1 (DONE — PENDING RUNTIME VERIFICATION)**
- Branding: Landing page ✅ Implemented (welcome.blade.php → KJA Event Manager)
- Branding: Login page ✅ Implemented (login.blade.php → KJA Event Manager)  
- Navigation: KJA logo → home ✅ route('home'), no auto-select CAI
- Navigation: Pengajian menu hidden in CAI ✅ sidebar.blade.php conditional

**2. UI Bug Fix Sprint — Batch 2** — Remaining bugs:
- Access Token: Add hard-delete for revoked tokens, audit raw token display (AccessIndex)
- Verify: Regional Report filter fixes, Dashboard "Alfa" stat behavior

**2. PGM.17 — Pilot Release.** Final checks before deployment:
- Pilot data verification and end-to-end simulation
- Data quality documentation
- Final go/no-go decision
- Production deployment
- Operator training documentation

After bug sprint: Penetration testing, performance optimization, CAI 2027 feature cycle.

Architecture source: `docs/ROADMAP.md`, `docs/SPRINT3_MULTI_EVENT_AUDIT.md`.

---

# Development Rules

* Follow `docs/ROADMAP.md` as the primary roadmap.
* Audit existing functionality before implementing new functionality.
* Do not duplicate features that already exist.
* Keep backward compatibility unless an explicit migration is designed.
* Business logic should remain centralized in service layers.
* Update documentation after verified changes.
* Run the full test suite before closing a feature scope.

---

# Success Criteria

PGM.17 dianggap selesai apabila:
- Pilot data verified
- End-to-end simulation passes
- Production deployment successful
- Operator training materials complete

---

# Notes

CURRENT_STATE.md adalah snapshot kondisi proyek.
Dokumen ini akan diperbarui setiap kali sprint selesai.
