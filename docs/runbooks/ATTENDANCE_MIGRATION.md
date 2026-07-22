# Attendance Migration — Production Runbook

## Overview

Migrate CAI attendance from legacy `Absensi`/`IzinAbsensi` (NIP-based) to canonical `EventAttendance` (Participation-based).

Current architecture:
- **Writes**: Dual-write to both legacy + canonical (since Sprint 6.2B)
- **Reads**: Canonical-first with legacy fallback (since Sprint 6.3)
- **Backfill**: `attendance:backfill` — copy existing legacy records to EventAttendance
- **Parity**: `attendance:parity` — compare legacy vs canonical

**Target state**: Legacy writes stopped, canonical-only. **Do not attempt until parity verified.**

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

**Always backup before running backfill with --force.**

---

## B. Dry Run

```bash
php artisan attendance:backfill
```

Review output:
- `total_scanned` — matched vs skipped
- `skip_no_participation` — legacy records with no Participation mapping
- `skip_ambiguous` — records matching multiple mappings (rare)
- `skip_existing` — already backfilled (idempotent)
- `mapped` — would be created

**Dry-run makes zero database writes.**

---

## C. Review Unmappable Records

Unmappable records (no Participation) will remain legacy-only. This is acceptable — they continue working through legacy fallback reads.

If unmappable count is significant:
1. Check whether `LegacyPesertaMapping` backfill was executed (see `php artisan backfill:legacy-peserta --dry-run`)
2. Execute mapping backfill before attendance backfill if needed

---

## D. Execute Backfill

```bash
php artisan attendance:backfill --force
```

Or per-event:

```bash
php artisan attendance:backfill --force --event=1
```

Backfill is **idempotent** — safe to rerun. Already-existing canonical records are skipped.

---

## E. Verify Parity

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

## I. Required Tools

| Command | Purpose |
|---------|---------|
| `php artisan attendance:backfill` | Dry-run (default) |
| `php artisan attendance:backfill --force` | Execute backfill |
| `php artisan attendance:parity` | Audit all events |
| `php artisan attendance:parity --event={id}` | Audit single event |
| `php artisan backfill:legacy-peserta --dry-run` | Dry-run mapping backfill |
| `php artisan backfill:legacy-peserta --execute` | Execute mapping backfill |

---

## J. Current Sprint Status

Sprint 6.4 = Verification readiness. Legacy writes still active. Canonical reads active with fallback.

Target Sprint 6.5 decision:
- **GO**: production parity verified → stop legacy writes, keep legacy tables read-only
- **NO-GO**: continue dual-write until parity issues resolved
