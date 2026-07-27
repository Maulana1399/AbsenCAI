# KJA Event Manager

Platform Event Management berbasis web — modular, multi-event, dan scalable.

Dikembangkan dari sistem absensi CAI (Cinta Alam Indonesia) menjadi platform Event Management umum yang mendukung berbagai jenis event.

---

## Current Status

| Area | Status |
|------|--------|
| CAI Operational | ✅ Stable — all modules operational |
| S01–S04 Foundation | ✅ COMPLETE 100% |
| Multi Event Architecture | ✅ S3.0–S3.10 Complete |
| RBAC (S1–S7) | ✅ COMPLETE — 9 roles, 15 gates, event-scoped |
| Person Master Data CRUD | ✅ COMPLETE |
| User Management | ✅ COMPLETE — Super Admin only |
| Pengajian Desa MVP | ✅ PGM.12–PGM.17 Complete |
| UI Bug Fix Sprint | ✅ Batch 1–4 VERIFIED. All resolved |
| UI Standardization | ✅ Phases 2–6 COMPLETE |
| PGM.19 Physical Regu Retirement | ✅ COMPLETE — `pesertas.regu_id` retired |
| PGM.20 Legacy NIP Retirement | ✅ COMPLETE — NIP retired from canonical architecture |
| Database V2 / Design C | ✅ problem_total = 0 |
| Test Baseline | ✅ 1574 passed / 3745 assertions / 0 failures |

---

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Livewire v3, Flux UI, Tailwind CSS v4
- **Database:** SQLite (current), MariaDB (future)
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

## Modules

### CAI Operational
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
- 15 Gate abilities with Super Admin bypass
- Event-scoped KetuaEvent authorization
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
| `PENGAJIAN_MVP_OPERATIONAL.md` | Pengajian module operational guide |
| `ai/CURRENT_STATE.md` | Current development snapshot |
| `ai/LAPORAN_STATUS_PROYEK_FINAL_20260724.md` | Final project status report |
| `DEAD_CODE.md` | Dead code report |
| `HIDDEN_FEATURES.md` | Hidden/unused features |
| `PROGRESS.md` | Implementation progress vs roadmap |

---

## License

Proprietary — KJA Techno
