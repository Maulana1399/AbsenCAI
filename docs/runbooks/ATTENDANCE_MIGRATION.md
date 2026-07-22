# Attendance Migration — Production Runbook (OBSOLETE)

> **⚠️ OBSOLETE NOTICE (PGM.18 Sprint 1)**
>
> Semua backfill commands (`attendance:backfill`, `backfill:legacy-peserta`, dll) telah dihapus pada PGM.18 Sprint 1 karena tidak memiliki production caller. Database saat ini sudah dalam kondisi canonical-first.
>
> Backfill hanya diperlukan untuk legacy database yang memiliki data Absensi/IzinAbsensi historis. Jika database saat ini adalah fresh install atau sudah menggunakan canonical EventAttendance, runbook ini tidak diperlukan.
>
> Documents retained for historical reference only.

## Overview

Migrate CAI attendance from legacy `Absensi`/`IzinAbsensi` (NIP-based) to canonical `EventAttendance` (Participation-based).

Current architecture:
- **Writes**: Dual-write to both legacy + canonical (since Sprint 6.2B)
- **Reads**: Canonical-first with legacy fallback (since Sprint 6.3)
- **Backfill**: `attendance:backfill` — **REMOVED** (PGM.18 Sprint 1)
- **Parity**: `attendance:parity` — compare legacy vs canonical (still available)

**Target state**: Legacy writes stopped, canonical-only.

---

## A. Backup Database

Production uses SQLite. Locate the active database:

```bash
php artisan tinker --execute="echo config('database.connections.sqlite.database') . PHP_EOL;"
```

Typical path: `database/database.sqlite`

Backup command:

```bash
cp database/database.sqlite database/database.sqlite.backup.$(date +%Y%m%d_%H%M%S)
```

---

## B. Verify Parity (only remaining action)

```bash
php artisan attendance:parity
```

```bash
php artisan attendance:parity
```

Per-event detail:

```bash
php artisan attendance:parity --event=1
```

---

## F. Interpret Parity Results

| Metric | Meaning | Acceptable |
|--------|---------|------------|
| `matched` | Legacy + canonical agree | Target: all |
| `missing_canonical` | Legacy record without canonical | Must be 0 for GO |
| `orphan_canonical` | Canonical without legacy | Must be 0 for GO |
| `status_conflicts` | Hadir vs izin disagreement | Must be 0 for GO |
| `unmappable` | Legacy record with no Participation | Acceptable (legacy fallback) |
| `parity_percentage` | matched / expected_mappable | Target: 100% |

### GO / WARNING / NO-GO

- **GO**: missing=0, orphan=0, conflicts=0, parity=100%
- **WARNING**: parity >= 95% but not GO (investigate before stopping legacy writes)
- **NO-GO**: parity < 95% (do not stop legacy writes)

---

## G. Rollback Strategy

If canonical reads produce incorrect results:

1. **Revert read switch**: Restore `Dashboard` and `RekapAbsensi` to legacy-only reads
2. **Revert write switch**: Remove EventAttendance writes from `AttendanceService` and `AttendanceExceptionService`
3. **Restore database**: `cp database/database.sqlite.backup.* database/database.sqlite`

Rollback preserves all legacy data — no attendance records are lost.

---

## H. Hard Rules

| Rule | Reason |
|------|--------|
| Do NOT truncate `absensis` | Historical data + legacy fallback |
| Do NOT truncate `izin_absensis` | Historical data + legacy fallback |
| Do NOT delete `LegacyPesertaMapping` | Required for legacy fallback resolution |
| Do NOT stop dual-write before production parity verified | Premature cutover loses EventAttendance for new scans |
| Do NOT run `--force` without backup | Irreversible if backfill creates wrong data |
| Do NOT guess event from NIP | NIP is not event-scoped; only `SesiAbsensi.event_id` is authoritative |

---

## I. Available Commands

| Command | Purpose |
|---------|---------|
| `php artisan attendance:parity` | Audit all events |
| `php artisan attendance:parity --event={id}` | Audit single event |
| `php artisan diagnose:design-c` | Design C integrity diagnostic (READ-ONLY) |

> Backfill commands (`attendance:backfill`, `backfill:legacy-peserta`) were removed in PGM.18 Sprint 1.

---

## J. Current Status (PGM.18)

Legacy dual-write still active (`ATTENDANCE_LEGACY_WRITE=false` in `.env`). Canonical-first reads active with fallback.

Legacy backfill tooling removed. Fresh databases do not need backfill.
