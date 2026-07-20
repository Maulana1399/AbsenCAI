# Pengajian Desa MVP — Operational Guide

## 1. Status

PGM.0–PGM.9 verified. PGM.10 production readiness closure. PGM.12 pilot bugfix COMPLETE.

**Limited operational pilot** — not commercial production readiness.

## 2. Architecture

```
Person ──→ Participation ──→ EventAttendance
```

- **Person** = canonical human/master identity
- **Participation** = Person participation in one Event
- **EventAttendance** = attendance fact for Participation

```
Event + Desa ──→ DesaAccessGrant ──→ Token ──→ Scoped Desa Session ──→ QR Public Attendance
```

## 3. Operator Daerah Workflow

### Login
- Login as authenticated user via `/login`

### Create or Select Event
- Open `/events`
- Create event or ensure existing event is active (`status = active`)

### Generate Desa Token

```bash
php artisan pengajian:create-desa-grant
```

#### Options

| Option | Description | Example |
|---|---|---|
| `--event` | Event ID or slug | `--event=1` |
| `--desa` | Desa ID | `--desa=3` |
| `--valid-from` | Start validity (default: now) | `--valid-from="2026-07-20 08:00:00"` |
| `--valid-until` | End validity | `--valid-until="2026-07-25 23:59:59"` |

**Example** (use your actual IDs, not these fake ones):

```bash
php artisan pengajian:create-desa-grant \
    --event=1 \
    --desa=3 \
    --valid-until="2026-07-25 23:59:59"
```

The command will ask for confirmation. Type `yes` to proceed.

#### Raw Token

The raw token is displayed **only once** after creation:

```
SAVE THIS TOKEN — IT WILL NEVER BE SHOWN AGAIN
```

- Copy the token immediately
- Database stores only a hash — token cannot be recovered
- If lost, create a new grant

#### After Token Creation

- **Distribute** the raw token to Operator Desa via official channel
- **View Regional Report** at `/pengajian/report`
- **Review identity corrections** at `/koreksi-data`

## 4. Operator Desa Workflow

1. Open `/pengajian` in browser
2. Enter the raw token received from Operator Daerah
3. Session is scoped to the assigned Event + Desa
4. Dashboard shows:
   - Event and Desa name
   - Summary cards (total, attended, not attended, method breakdown)
   - QR code for public attendance
   - Operator-assisted attendance form
   - Attendance list with filters

### Print / Share QR

- From dashboard QR tab, click "Cetak QR" to open print view
- QR encodes URL: `/pengajian/hadir/{nonce}`
- Valid for 24 hours by default (configurable at grant creation)
- After nonce rotation, old QR stops working

### Assist Participant Attendance

1. Search participant name (min 3 characters)
2. Select matching person
3. Confirm attendance
4. System records with `method = operator`

### View Desa Recap

- Summary cards on dashboard show real-time Desa stats
- Attendance list shows all Desa persons with their status

## 5. Participant Workflow

1. **Scan QR** — opens `/pengajian/hadir/{nonce}`
2. **No login required** — page is public (rate-limited: 30 requests/minute)
3. **Search identity** — type min 3 characters of your name
4. **Only own Desa population visible** — search scoped to grant's Desa
5. **Verify identity** — enter birth date (YYYY-MM-DD) or skip verification
6. **Self attendance** — click "Hadir" to record attendance
7. **Correction request** — if name/birth date is wrong, submit correction request

### Duplicate Attendance

If already recorded as present, system shows "Anda sudah tercatat hadir" — no duplicate created.

## 6. Token Lifecycle

| Stage | Description |
|---|---|
| **Create** | `DesaAccessService::createGrant()` — generates raw token + nonce |
| **Distribute** | Operator Daerah shares raw token with Operator Desa |
| **Validate** | `findGrantByToken()` checks hash, revoked_at, valid_from, valid_until |
| **Scoped Session** | Token establishes `pengajian_access` session with grant_id, event_id, desa_id |
| **Nonce QR** | Nonce is the public-facing QR identifier |
| **Expire** | Grant rejected after `valid_until` |
| **Revoke** | `revokeGrant()` sets `revoked_at` — immediate rejection |
| **Rotate** | `rotateNonce()` replaces nonce — old QR stops working |

### Important Distinction

- **RAW TOKEN** = Operator Desa access credential (long, secret)
- **NONCE** = public QR access identifier (short-lived, rotateable)

## 7. Attendance Contract

| Rule | Detail |
|---|---|
| Person + Event | Max 1 Participation |
| Participation | Max 1 EventAttendance |
| First wins | First successful attendance sets `method` — never overwritten |
| `method = self` | Participant recorded own attendance |
| `method = operator` | Operator assisted attendance |
| `participant_number` | Event-scoped, immutable after creation |
| `attendance_code` | Event-scoped (globally unique), immutable after creation |
| NIP | Legacy compatibility only — not used for Pengajian flows |

## 8. Reporting Definitions

### Desa Report (scoped: one Event + one Desa)

| Metric | Definition |
|---|---|
| `total_warga` | Person count with matching `desa_id` |
| `sudah_hadir` | Unique Person with EventAttendance for this Event + Desa |
| `belum_hadir` | `total_warga - sudah_hadir` |
| `self` | EventAttendance count where `method = self` |
| `operator` | EventAttendance count where `method = operator` |

### Regional Report (scoped: one Event + all Desa)

Same metrics aggregated across all Desa. Additional metrics:

| Metric | Definition |
|---|---|
| `total_desa` | Desa count with at least one Person |
| `desa_hadir` | Desa count with at least one attendance |

Per-desa breakdown shows progress bar with percentage.

## 9. Identity Correction

### Flow

```
Public submission
  → status = pending
  → Trusted reviewer reviews at /koreksi-data
  → Approve or Reject
  → Only on approval: canonical Person updated
```

### Allowed Correction Fields

- `requested_name` — updates `Person.nama`
- `requested_birth_date` — updates `Person.tanggal_lahir`

**Identifiers are not editable** — `person_id`, `event_id`, `desa_id` are immutable through correction flow.

### Legacy Sync

On approval, if `LegacyPesertaMapping` exists for this Person, sync `nama` to legacy `peserta.nama`.

## 10. Security Boundaries

| Route | Access | Middleware |
|---|---|---|
| `/pengajian` | Public | none |
| `/pengajian/hadir/{nonce}` | Public (nonce-protected, rate-limited) | `throttle:30,1` |
| `/pengajian/desa` | Scoped session | Session check |
| `/pengajian/desa/qr/print` | Scoped session | Session check |
| `/events` | Authenticated | `auth, verified` |
| `/pengajian/report` | Authenticated | `auth, verified` |
| `/koreksi-data` | Authenticated | `auth, verified` |

### Known Security Limitations

1. **Verified middleware is no-op** — User model does not implement `MustVerifyEmail`. Routes with `verified` middleware are effectively `auth` only.
2. **No RBAC** — all authenticated users have equivalent access to event management, regional reports, and correction review. Acceptable only for limited pilot with trusted operators.
3. **Password-only auth** — no MFA, no SSO.
4. **QR is not bound per participant** — one QR can be used by all participants during its validity window.

## 11. Generate Desa Token

### Interactive

```bash
php artisan pengajian:create-desa-grant
```

The command will:
1. List available Events (auto-select if only one)
2. List available Desa (auto-select if only one)
3. Ask for valid-until date (default: now + 1 day)
4. Show summary and ask for confirmation
5. On confirm: create grant and display raw token once

### Non-interactive

```bash
php artisan pengajian:create-desa-grant \
    --event=1 \
    --desa=3 \
    --valid-until="2026-07-25 23:59:59"
```

Confirmation prompt still appears. To automate: pipe `yes` or use `expect` wrapper.

## 12. SQLite Backup

```bash
# Back up current database
cp database/database.sqlite database/database.sqlite.backup.$(date +%Y%m%d%H%M%S)

# Verify backup exists
ls -la database/database.sqlite.backup.*

# Before running migrations
php artisan migrate --force
```

Verify your actual `DB_DATABASE` path from `.env` if not using the default SQLite path.

## 13. Deployment Checklist

1. `git log --oneline -5` — verify revision
2. `cp database/database.sqlite database/database.sqlite.backup.$(date +%Y%m%d%H%M%S)` — backup DB
3. `php artisan down --retry=60` — maintenance mode (optional)
4. `php artisan migrate --force` — run migrations
5. `php artisan optimize:clear` — clear cache
6. `php artisan config:cache` — cache config
7. `php artisan route:cache` — cache routes
8. `php artisan view:cache` — cache views
9. `php artisan up` — disable maintenance mode
10. Smoke test (see Section 14)

## 14. Manual Pilot Smoke Test

1. Login as Operator Daerah
2. Create or select an active Event at `/events`
3. Generate token for Desa A:
   ```bash
   php artisan pengajian:create-desa-grant --event=1 --desa=1 --valid-until="2026-08-01 23:59:59"
   ```
4. Copy the raw token shown
5. Open private/incognito browser tab
6. Go to `/pengajian` and enter the token
7. Verify Desa dashboard loads with correct Event + Desa name
8. Click QR tab, then "Cetak QR" — verify QR page opens
9. On phone (or second browser), scan/visit the QR URL
10. Verify public attendance page shows Event + Desa name
11. Search your own name (min 3 chars) — verify results appear
12. Confirm a Person from another Desa does NOT appear in search
13. Complete self-attendance for Person A — verify success
14. Repeat attendance for Person A — verify "sudah tercatat hadir" shown
15. Return to Desa dashboard, verify summary counts updated
16. Search and assist attendance for Person B — verify "Kehadiran berhasil dicatat"
17. Check Desa recap tab — verify both attendances shown
18. Open `/pengajian/report` as authenticated user — verify Regional report
19. Open `/koreksi-data` — verify correction review page
20. Submit correction via public QR flow — verify Person NOT immediately changed
21. Approve correction at `/koreksi-data` — verify Person updated
22. Verify `participant_number` and `attendance_code` unchanged after correction
23. Revoke grant via:
    ```php
    php artisan tinker
    $grant = App\Models\DesaAccessGrant::find(1);
    app(App\Services\Pengajian\DesaAccessService::class)->revokeGrant($grant);
    ```
24. Verify token entry at `/pengajian` rejects the same token

## 15. Known Pilot Limitations

- **Token management**: CLI only, no admin UI. Use `php artisan pengajian:create-desa-grant`.
- **RBAC**: Not implemented. All authenticated users have equivalent access.
- **Email verification**: `verified` middleware is no-op (User does not implement `MustVerifyEmail`).
- **No permanent Person QR**: QR is session-bound, not person-bound.
- **No NFC / RFID**: QR-only attendance.
- **No multi-session attendance**: One Event per grant.
- **Limited activity logging**: Grant creation/revocation not audited.
- **Visual polish**: Minimal UI, future improvement.
- **Pengajian Kelompok/Daerah**: Future scope — not part of this MVP.
- **N+1 query in Desa report list**: Acceptable for pilot-level volume.
- **No offline support**: Internet connection required for all flows.

---

## 10. PGM.12 — Pilot Bugfix Verification

PGM.12 addresses UX/binding issues discovered during manual pilot verification of the Desa Dashboard.

| Item | Status |
|---|---|
| QR/self attendance flow | ✅ PASS |
| Operator attendance (success + sequential) | ✅ PASS |
| Dashboard/statistik refresh | ✅ PASS |
| Filter Status (Hadir/Belum) | ✅ PASS |
| Filter Metode (Self/Operator) | ✅ PASS |
| Search nama (partial, empty, no results) | ✅ PASS |
| Kombinasi search + filter | ✅ PASS |
| UI auto-reset after operator attendance | ✅ PASS |
| Sequential operator tanpa Batal manual | ✅ PASS |

**Full suite:** 762 passed, 1802 assertions, 0 failures. Duration: 11.85s.

**Files changed:**

| File | Change |
|---|---|
| `app/Livewire/Pengajian/DesaDashboard.php` | Add `$showingConfirmation` boolean; `searchList()` method; `setFilterStatus/setFilterMethod` methods; `wire:input`/`wire:change` event bindings |
| `resources/views/livewire/pengajian/desa-dashboard.blade.php` | Replace `wire:model` with `wire:input`/`wire:change` for search/filter; use `$showingConfirmation` for state toggle |
| `tests/Feature/Pengajian/PengajianAccessTest.php` | 13 new tests: filter (5), search (5), operator attendance UX (3) |
