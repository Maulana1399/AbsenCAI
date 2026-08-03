# HANDOFF

> Non-Technical Project Overview untuk developer/AI baru.
>
> **Roadmap V1** = ✅ **100% COMPLETE**
> **Roadmap V2** = 📋 **Planned** — Event Operating System

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
| `VISION_V2.md` | V2 product vision — Event Operating System |

---

## Current Test Baseline

```
Full suite: 1792 passed, 4219 assertions, 0 failures (SQLite & MariaDB)
Design C:   problem_total = 0
```

**MariaDB Migration (COMPLETE):** primary DB switched from SQLite to MariaDB. Migrations, seeders, and the full test suite are green on both drivers. See `docs/CHANGELOG.md` (Unreleased → MariaDB Migration) for the list of driver-compat fixes. Test command for MariaDB: `DB_CONNECTION=mariadb DB_DATABASE=kja_event_manager_test ... vendor/bin/pest`.

---

## Product Vision

KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi.

**KJA Event Manager adalah Event Operating System.**

Filosofi: **Build Engine, Not Module** — jangan buat modul Silat, Voli, MTQ, PAUD. Sebagai gantinya, bangun Competition Engine + Scoring Engine + Blueprint Event yang bersifat generic.

---

## Roadmap V2 — Event Operating System (Planned)

| # | Item | Deskripsi |
|---|------|-----------|
| 1 | Blueprint Event | Konfigurasi awal event (Pengajian, Silat, Olahraga, Festival, Seminar, Custom) |
| 2 | Competition Engine | Generic competition engine — bracket, league, round robin, double elimination |
| 3 | Scoring Engine | Generic scoring — Versus, Score, Time, Distance, Ranking, Pass/Fail |
| 4 | Venue Management | Master Venue → Event Venue → Arena/Room (reusable) |
| 5 | Live Schedule Engine | Jadwal realtime mengikuti kondisi pertandingan |
| 6 | Public Dashboard | Portal publik tanpa login — jadwal, bracket, hasil, pengumuman |
| 7 | Announcement Engine | Pengumuman resmi panitia |
| 8 | Certificate Engine | Generate sertifikat otomatis berdasarkan hasil |
| 9 | Mobile | Aplikasi mobile |
| 10 | Public API | REST API untuk integrasi pihak ketiga |

Lihat `docs/VISION_V2.md` untuk dokumentasi lengkap.

---

## Pending Work (Immediate — V1 Scope)

1. **Venue CRUD** — Event-scoped venue management UI (model & migration sudah ada)
2. **CategoryDefinition CRUD** — Event-scoped category management UI (model & migration sudah ada)
3. **Absensi table retirement** — Deferred: table masih ada untuk historical reads, tidak lagi ditulisi

## Pending Work (V2 — Future)

Semua item Roadmap V2 masih Planned. Belum ada yang diimplementasikan. Lihat `docs/VISION_V2.md`.

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
