# KJA Event Manager

Platform Event Management berbasis web — modular, multi-event, dan scalable.

Dikembangkan dari sistem absensi CAI (Cinta Alam Indonesia) menjadi platform Event Management umum yang mendukung berbagai jenis event.

---

## Current Status

| Area | Status |
|------|--------|
| CAI Operational | ✅ Stable — all modules operational |
| S01 Foundation | ✅ COMPLETE 100% |
| S02 Placement & Registration | ✅ COMPLETE 100% |
| S03 Attendance | ✅ COMPLETE 100% |
| S04 Identity & QR | ✅ COMPLETE 100% |
| Multi Event Architecture | ✅ S3.0–S3.10 Complete |
| S03 RBAC (Event & CAI Protection) | ✅ COMPLETE — routes + Livewire protected |
| Pengajian Desa MVP | ✅ PGM.12–PGM.17 Complete — UI remediated |
| S04 RBAC (Pengajian Admin Protection) | ✅ COMPLETE — routes + Livewire protected |
| User Management | ✅ COMPLETE — Super Admin only, Master Data card |
| S05 RBAC (Remaining Security Protection) | ✅ COMPLETE — all routes + mutations protected |
| S06 RBAC (Sidebar Visibility) | ✅ COMPLETE — all menus gated with @can() directives |
| PGM.19 Physical Regu Retirement | ✅ COMPLETE — `pesertas.regu_id` dropped, regu dual-write stopped |
| PGM.20 Legacy NIP Retirement | ✅ COMPLETE — NIP retired from canonical architecture, `people.nip` and `pesertas.nip` dropped |
| Test Baseline | ✅ 1574 passed / 3745 assertions / 0 failures — Design C problem_total = 0 |

---

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Livewire v3, Flux UI, Tailwind CSS v4
- **Database:** SQLite (current), MariaDB (future)
- **QR:** Internal PHP QR generator (PNG)

---

## Key Architecture

```
Event (type: cai | pengajian)
  → Participation (event-scoped enrollment)
    → Person (canonical identity)
    → EventAttendance (attendance fact)

Event
  → DesaAccessGrant (token-based desa access)
    → EventAttendance (scoped per event + desa)

peserta (legacy runtime)
  → LegacyPesertaMapping (compatibility bridge)
    → Person → Participation → Event
```

---

## Navigation

Sidebar is event-type-aware with global Master Data:
- **CAI events** → full operational menu (Absensi, Registrasi, Database, Laporan, QR & Label, etc.)
- **Pengajian events** → clean Pengajian menu (Regional Report, Peserta, Import Massal, Akses Desa)
- **All authenticated users** → **Master Data** menu → landing page (`/master-data`) with Person, Desa, Kelompok cards — always visible regardless of event context

---

## Modules

### CAI Operational
- Attendance (QR scan, manual, self-register)
- Participant registration and import
- Reports and exports
- Permission letters (Surat Izin)
- Activity log

### Pengajian Desa MVP
- Token-based desa operator access
- Self-attendance via QR
- Operator-assisted attendance
- Manual participant entry
- **Bulk participant import** (CSV/Excel)
- Regional and desa-level reports
- Identity correction workflow

---

## UI Bug Backlog

Teridentifikasi 11 area perbaikan UI yang perlu ditangani di sprint mendatang. Lihat `docs/TODO.md` → **UI Bug Fix Sprint** untuk detail.

| Kategori | Item |
|----------|------|
| Branding & Navigation | Landing page, Login, KJA logo default, "Pengajian" menu in CAI |
| Access Token UI & Security | Raw token display, token overflow, missing delete button |
| Functional/UI Logic | Dashboard "Belum Absen" stat, "Pengajian" menu in CAI event |
| Filter/Regional Report | Report filter combinations (Hadir/Tidak/Metode) |
| Dark Mode | Text contrast on Akses Desa / Kelola Event pages |
| Responsive Layout | /pengajian page on desktop |

---

## Documentation

See `docs/` directory for full documentation.

| Document | Description |
|----------|-------------|
| `ROADMAP.md` | Product roadmap and sprint plan |
| `TODO.md` | Active task list + Bug Backlog |
| `HANDOFF.md` | Non-technical project overview |
| `PENGAJIAN_MVP_OPERATIONAL.md` | Pengajian module operational guide |
| `ai/CURRENT_STATE.md` | Current development snapshot |

---

## License

Proprietary — KJA Techno
