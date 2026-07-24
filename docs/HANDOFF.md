# HANDOFF

> Non-Technical Project Overview untuk developer/AI baru.

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

## Current Test Baseline

```
Full suite: 1574 passed, 3745 assertions, 0 failures
Design C:   problem_total = 0
```

---

## Pending Work (Immediate)

1. **Venue CRUD** — Event-scoped venue management UI (model & migration sudah ada)
2. **CategoryDefinition CRUD** — Event-scoped category management UI (model & migration sudah ada)
3. **Absensi table retirement** — Deferred: table masih ada untuk historical reads, tidak lagi ditulisi

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
