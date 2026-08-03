# KJA Event Manager — Event Operating System

Platform Event Management berbasis web — modular, multi-event, dan scalable.

KJA Event Manager adalah **Event Operating System**: sebuah platform yang dapat mengelola semua jenis event melalui **Competition Engine**, **Scoring Engine**, dan **Blueprint Event**, tanpa perlu membuat modul khusus untuk setiap cabang event.

Dikembangkan dari sistem absensi CAI (Cinta Alam Indonesia) menjadi platform Event Management umum yang mendukung berbagai jenis event.

---

## Current Status

| Area | Status |
|------|--------|
| Sprint 1 | ✅ COMPLETE 100% |
| Sprint 2 | ✅ COMPLETE 100% |
| Sprint 3.1 (technical debt cleanup) | ✅ COMPLETE 100% |
| Sprint 3.2 (architecture hardening) | ✅ COMPLETE 100% |
| Sprint 3.3 | 🔲 NOT STARTED |
| Sprint 4 | 🔲 NOT STARTED |
| CAI Operational | ✅ Stable — all modules operational |
| S01–S04 Foundation | ✅ COMPLETE 100% |
| Multi Event Architecture | ✅ S3.0–S3.10 Complete |
| RBAC (S1–S7) | ✅ COMPLETE — 9 roles, 18 gate abilities, event-scoped |
| Person Master Data CRUD | ✅ COMPLETE |
| User Management | ✅ COMPLETE — Super Admin only |
| Pengajian Desa MVP | ✅ PGM.12–PGM.17 Complete |
| Competition V1 | ✅ COMPLETE — Sprint 7.0–10.0 |
| Reporting | ✅ COMPLETE — Summary, Registration, Schedule, Outcome, Statistics |
| Export | ✅ CSV — Registration, Schedule, Outcome |
| UI Bug Fix Sprint | ✅ Batch 1–4 VERIFIED. All resolved |
| UI Standardization | ✅ Phases 2–7 COMPLETE |
| PGM.18 Physical Mapping Cleanup | ✅ COMPLETE — `legacy_peserta_mappings` cleaned |
| PGM.19 Physical Regu Retirement | ✅ COMPLETE — `pesertas.regu_id` retired |
| PGM.20 Legacy NIP Retirement | ✅ COMPLETE — NIP retired from canonical architecture |
| Database V2 / Design C | ✅ problem_total = 0 |
| Competition V1 (Sprint 7.0–10.0) | ✅ COMPLETE — Match Status, Ready Detection, Match Center, Viewer, Result Dialog, Officials, Bracket |
| Public Portal (Sprint 9.0) | ✅ COMPLETE — Public homepage, event detail, schedule, bracket, announcements |
| Event Dashboard (Sprint 10.0) | ✅ COMPLETE — Overview cards, live matches, today's schedule, quick actions |
| Migration Stabilization | ✅ COMPLETE — All race conditions fixed for `migrate:fresh` |
| UI Audit & Standardization | ✅ COMPLETE — 19 files standardized across HIGH/MEDIUM consistency issues |
| MariaDB Migration | ✅ COMPLETE — SQLite → MariaDB as primary DB; migrations/seeders/tests green on both |
| Test Baseline | ✅ **1944 passed / 4648 assertions / 0 failures** |
| **Roadmap V1** (Foundation, Multi Event, RBAC, CAI, Pengajian) | ✅ **100% COMPLETE** |
| **Roadmap V2** — Competition V1 | ✅ **COMPLETE** — All 10 sprints |
| **Roadmap V2** — Event Operating System (remaining) | 📋 **Planned** — Blueprint Event, Venue Management (V2 hierarchy), Certificate Engine, Public API, Mobile |

---

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Livewire v3, Flux UI, Tailwind CSS v4
- **Database:** MariaDB (primary), SQLite (test baseline) — see `docs/DATABASE.md` and `docs/SETUP.md`
- **QR:** Internal PHP QR generator (PNG)

---

## Key Architecture

```
Person (canonical identity)
  → Participation (event-scoped membership)
    → EventAttendance (canonical attendance fact)

Event (type: cai | pengajian)
  → Participation (event-scoped enrollment)
  → DesaAccessGrant (token-based desa access)

V2 Direction (Event Operating System):
```
Person (canonical identity)
  → Participation (event-scoped membership)
    → EventAttendance (canonical attendance fact)
    → Competition (via Competition Engine)
    → Scoring (via Scoring Engine)
```

Build Engine, Not Module. Lihat `docs/VISION_V2.md`.

peserta (legacy compatibility only)
  → LegacyPesertaMapping (bridge peserta↔Person)
  → LegacyParticipationMapping (bridge peserta↔Participation)
```

---

## Navigation

Sidebar is event-type-aware with global Master Data:
- **CAI events** → full operational menu (Absensi, Registrasi, Database, Laporan, QR & Label, etc.)
- **Pengajian events** → clean Pengajian menu (Regional Report, Peserta, Import Massal, Akses Desa)
- **All authenticated users** → **Master Data** menu with Person, Desa, Kelompok cards

---

## Roadmap V1 — Completed

Semua pengembangan foundation, Multi Event, RBAC, CAI Operational, Pengajian Desa MVP, dan Documentation telah selesai 100%.

## Roadmap V2 — Event Operating System (Planned)

> **Competition V1** (Sprint 7.0–10.0), **Public Portal** (Sprint 9.0), dan **Event Dashboard** (Sprint 10.0) sudah COMPLETE.
> **Announcement Engine** — fondasi Competition announcements sudah ada (model + route publik), engine generic belum dibangun.

| # | Item | Status |
|---|------|--------|
| 1 | Blueprint Event | 📋 Planned |
| 2 | Competition Engine (generic) | 📋 Planned — Competition V1 (module-based) COMPLETE |
| 3 | Scoring Engine | 📋 Planned |
| 4 | Venue Management (Master/Event/Arena) | 📋 Planned — Venue CRUD V1 sudah ada, hierarki reusable belum |
| 5 | Live Schedule Engine | 📋 Planned — jadwal + status match sudah ada, estimasi realtime belum |
| 6 | Public Dashboard | ✅ COMPLETE (Sprint 9.0 — Public Portal) |
| 7 | Announcement Engine | 🟡 Partial — Competition announcements live, generic engine belum |
| 8 | Certificate Engine | 📋 Planned |
| 9 | Mobile | 📋 Planned |
| 10 | Public API | 📋 Planned |

---

## Modules
- Attendance (QR scan via EventAttendance, no legacy dual-write)
- Participant registration and import (canonical Person→Participation flow)
- Reports and exports (event-scoped)
- Permission letters (Surat Izin)
- Activity log

### Pengajian Desa MVP
- Token-based desa operator access (DesaAccessGrant)
- Self-attendance via QR
- Operator-assisted attendance
- Manual participant entry
- Bulk participant import (CSV/Excel)
- Regional and desa-level reports
- Identity correction workflow

### RBAC
- 9 roles: Super Admin, Admin, Ketua Event, Sekretariat, PJ Divisi, Operator Registrasi, Operator Scan, Juri, Viewer
- 18 Gate abilities: 4 platform (`view-master-data`, `manage-master-data`, `manage-events`, `manage-users`) + 14 event-scoped (Permission Engine: `User → Person → EventCommitteeAssignment → EventRole.permissions`)
- Super Admin bypass + Admin bypass (event abilities)
- All routes + Livewire mutations protected

---

## Documentation

See `docs/` directory for full documentation.

| Document | Description |
|----------|-------------|
| `PROJECT_STRUCTURE.md` | Complete project architecture, module, and component mapping |
| `FEATURE_INVENTORY.md` | All implemented features based on code |
| `CAPABILITY_MATRIX.md` | Undocumented capabilities in the codebase |
| `ROLE_MATRIX.md` | Role & permission audit matrix |
| `WORKFLOW.md` | Complete workflow flowcharts |
| `ROADMAP.md` | Product roadmap and sprint plan |
| `TODO.md` | Active task list + Bug Backlog |
| `HANDOFF.md` | Non-technical project overview |
| `DATABASE.md` | Database design, schema, and driver-compat notes |
| `SETUP.md` | Environment setup — MariaDB primary, test baseline |
| `PENGAJIAN_MVP_OPERATIONAL.md` | Pengajian module operational guide |
| `ai/CURRENT_STATE.md` | Current development snapshot |
| `ai/LAPORAN_STATUS_PROYEK_FINAL_20260724.md` | Final project status report |
| `DEAD_CODE.md` | Dead code report |
| `HIDDEN_FEATURES.md` | Hidden/unused features |
| `PROGRESS.md` | Implementation progress vs roadmap |
| `VISION_V2.md` | Event Operating System — V2 product vision |

---

## License

Proprietary — KJA Techno
