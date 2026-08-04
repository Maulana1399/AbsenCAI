# HANDOFF

> Non-Technical Project Overview untuk developer/AI baru.
>
> **Roadmap V1** = ✅ **100% COMPLETE**
> **Roadmap V2** = 🟡 **Partial** — Competition V1, Public Portal, Event Dashboard COMPLETE; generic engine Planned — Event Operating System

---

## Current Architecture

Canonical data flow:

```
Person (master identity)
  ↓ nama, desa, kelompok, tanggal_lahir
Participation (event-scoped membership)
  ↓ participant_number, attendance_code, regu
EventAttendance (canonical attendance)
```

Legacy compatibility (masih ada, tidak boleh dijadikan canonical):
- `peserta` table — legacy CAI participant data
- `Absensi` — legacy attendance (tidak lagi ditulis, hanya historical read)
- `LegacyPesertaMapping` — bridge peserta → Person
- `LegacyParticipationMapping` — bridge peserta → Participation

---

## Completed Major Work

| PGM/Sprint | Description |
|------------|-------------|
| Sprint 1 | Platform Consolidation — MariaDB Migration, Permission Engine, Competition V1, Public Portal, Event Dashboard |
| Sprint 2 | RBAC & Permission Engine (Design C) — User Management RBAC consistency, Event Role CRUD |
| Sprint 3.1 | Technical debt cleanup — dead code/views/imports removed, deduplication |
| Sprint 3.2 | Architecture hardening — Dashboard Presenter Factory, EventOwnership, Import helper, ManualEntry trait |
| PGM.12–17 | Pengajian Desa MVP — complete |
| PGM.18 | Physical mapping cleanup — columns dropped, model cleanup |
| PGM.19 Sprint 8A+8B | Physical Regu Retirement — `pesertas.regu_id` dropped, dual-write stopped |
| PGM.20 Phase 1–4B | Legacy NIP Retirement — NIP retired, `people.nip` & `pesertas.nip` dropped |
| S3.0–S3.10 | Multi Event Architecture — complete |
| RBAC S1–S7 | Full role-based access control — complete |

---

## Hal yang TIDAK BOLEH Dihidupkan Kembali

- **NIP sebagai canonical identity** — sudah diretire total
- **NIP fallback di attendance scan** — dihapus di PGM.20 Phase 1
- **NIP di Person** — kolom `people.nip` sudah dihapus
- **NIP di peserta** — kolom `pesertas.nip` sudah dihapus
- **Regu dual-write** — `pesertas.regu_id` sudah dihapus
- **Global regu fallback** — `leastFilledRegu` sudah require `eventId`
- **Absensi dual-write** — tidak lagi menulis ke `absensis`

---

## New Documentation Files

The following documentation has been added as part of Project Audit:

| Document | Description |
|----------|-------------|
| `PROJECT_STRUCTURE.md` | Full project architecture mapping |
| `FEATURE_INVENTORY.md` | Actual implemented features (code-based) |
| `CAPABILITY_MATRIX.md` | Undocumented capabilities |
| `ROLE_MATRIX.md` | Role & permission audit matrix |
| `WORKFLOW.md` | Complete workflow flowcharts |
| `HIDDEN_FEATURES.md` | Hidden/unused features |
| `DEAD_CODE.md` | Dead code report |
| `PROGRESS.md` | Implementation progress vs roadmap |
| `UAT_CHECKLIST.md` | UAT checklist (Platform, CAI, Competition, Pengajian, Permission, Import, Export, Print, Master Data) |
| `LEGACY_RETIREMENT_PLAN.md` | Legacy retirement plan |
| `VISION_V2.md` | V2 product vision — Event Operating System |

---

## Current Test Baseline

```
Full suite: 1944 passed, 4648 assertions, 0 failures
Design C:   problem_total = 0
```

**MariaDB Migration (COMPLETE):** primary DB switched from SQLite to MariaDB. Migrations, seeders, and the full test suite are green on both drivers. See `docs/CHANGELOG.md` (Unreleased → MariaDB Migration) for the list of driver-compat fixes. Test command for MariaDB: `DB_CONNECTION=mariadb DB_DATABASE=kja_event_manager_test ... vendor/bin/pest`.

**Sprint series (current):** Sprint 1 ✅, Sprint 2 ✅, Sprint 3.1 (cleanup) ✅, Sprint 3.2 (hardening) ✅, Sprint 3.3 (legacy retirement prep & UAT readiness) ✅. Sprint 4 — NOT STARTED.

---

## Product Vision

KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi.

**KJA Event Manager adalah Event Operating System.**

Filosofi: **Build Engine, Not Module** — jangan buat modul Silat, Voli, MTQ, PAUD. Sebagai gantinya, bangun Competition Engine + Scoring Engine + Blueprint Event yang bersifat generic.

---

## Roadmap V2 — Event Operating System (Planned)

| # | Item | Deskripsi | Status |
|---|------|-----------|--------|
| 1 | Blueprint Event | Konfigurasi awal event (Pengajian, Silat, Olahraga, Festival, Seminar, Custom) | 📋 Planned |
| 2 | Competition Engine | Generic competition engine — bracket, league, round robin, double elimination | 🟡 Partial (Competition V1 COMPLETE) |
| 3 | Scoring Engine | Generic scoring — Versus, Score, Time, Distance, Ranking, Pass/Fail | 📋 Planned |
| 4 | Venue Management | Master Venue → Event Venue → Arena/Room (reusable) | 🟡 Partial (Venue CRUD V1 ada) |
| 5 | Live Schedule Engine | Jadwal realtime mengikuti kondisi pertandingan | 🟡 Partial (jadwal + status match ada) |
| 6 | Public Dashboard | Portal publik tanpa login — jadwal, bracket, hasil, pengumuman | ✅ COMPLETE (Sprint 9.0) |
| 7 | Announcement Engine | Pengumuman resmi panitia | 🟡 Partial (Competition announcements) |
| 8 | Certificate Engine | Generate sertifikat otomatis berdasarkan hasil | 📋 Planned |
| 9 | Mobile | Aplikasi mobile | 📋 Planned |
| 10 | Public API | REST API untuk integrasi pihak ketiga | 📋 Planned |

Lihat `docs/VISION_V2.md` untuk dokumentasi lengkap.

---

## Pending Work (Immediate — V1 Scope)

1. **CategoryDefinition CRUD UI** — Event-scoped category management UI (`category_definitions`; note: `Competition/Category` menangani `competition_categories`, tabel berbeda)
2. **Absensi table retirement** — Deferred: table masih ada untuk historical reads, tidak lagi ditulisi

## Pending Work (Sprint 3.3 / Sprint 4 — Rekomendasi)

- Sprint 3.3 ✅ COMPLETE — legacy dependency audit, Platform Dashboard event-picker TODOs, import Gate hardening, `UAT_CHECKLIST.md`, `LEGACY_RETIREMENT_PLAN.md`
- Sprint 4: legacy retirement phases (lihat `LEGACY_RETIREMENT_PLAN.md`), Roadmap V2 (Blueprint Event, Scoring Engine, Certificate Engine)

## Pending Work (V2 — Future)

Item Roadmap V2 yang masih Planned. Lihat `docs/VISION_V2.md`.

---

## Legacy Components yang Masih Ada

| Component | Status | Runtime Impact |
|-----------|--------|----------------|
| `peserta` table | Legacy compatibility | Masih dibuat oleh RegistrationService |
| `LegacyPesertaMapping` | Bridge peserta↔Person | Read/write aktif |
| `LegacyParticipationMapping` | Bridge peserta↔Participation | Read/write aktif |
| `Absensi` model | Legacy historical | Tidak ditulisi, hanya historical read |
| `IzinAbsensi` | Legacy + canonical | Masih ditulisi untuk legacy path |
| `regu_id` di `participations` | Canonical CAI | Aktif untuk event-scoped placement |
| `legacy_nip` di mapping | Historical snapshot | Write-only, tidak dibaca runtime |
