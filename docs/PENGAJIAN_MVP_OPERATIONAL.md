# Pengajian Desa MVP — Operational Guide

## 1. Status

PGM.0–PGM.9 verified. PGM.10 production readiness closure. PGM.12–PGM.16 COMPLETE. PGM.17 PENDING.

**Limited operational pilot** — not commercial production readiness.

### PGM.16 Key Changes
- Event type discriminator (`event_type: cai | pengajian`) added
- Contextual sidebar — Pengajian menus hide CAI-only items
- KJA branding throughout app
- **Bulk participant import** at `/pengajian/admin/import-massal`
- Responsive UI for Regional Report and Desa Dashboard
- Event switcher now navigates to event-type landing page on switch

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

- **Token rotation**: Login token rotation not yet implemented. Use revoke + recreate instead.
- **RBAC**: Not implemented. All authenticated users have equivalent access.
- **Email verification**: `verified` middleware is no-op (User does not implement `MustVerifyEmail`).
- **No permanent Person QR**: QR is session-bound, not person-bound.
- **No NFC / RFID**: QR-only attendance.
- **No multi-session attendance**: One Event per grant.
- **Limited activity logging**: Grant creation/revocation not audited.
- **Visual polish**: Minimal UI, future improvement.
- **Pengajian Kelompok/Daerah**: Future scope — not part of this MVP.
- **No import XLSX template download**: Users must know column format for bulk import.
- **No edit event_type after creation**: Event type must be set at creation time.
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

---

## 11. PGM.13 — Desa Access Token Management UI

Admin UI for managing Desa access grants (list, create with one-time token reveal, revoke).

| Item | Status |
|---|---|
| Route `/pengajian/admin/access` under `auth,verified` | ✅ PASS |
| Sidebar "Pengajian" → "Akses Desa" + "Regional Report" | ✅ PASS |
| Grant list (safe data, no token_hash, status badges) | ✅ PASS |
| Create grant form with validation | ✅ PASS |
| One-time raw token reveal via browser event | ✅ PASS |
| Copy-to-clipboard with success feedback | ✅ PASS |
| Token wrapping/layout in modal | ✅ PASS |
| Revoke grant with error handling | ✅ PASS |
| Duplicate active grants allowed | ✅ PASS |
| Security regression (no token in DB, no token in Livewire state, no token in session) | ✅ PASS |

**Full suite:** 784 passed, 1851 assertions, 0 failures. Duration: 12.31s.

**Files changed:**

| File | Change |
|---|---|
| `app/Livewire/Pengajian/Admin/AccessIndex.php` | **NEW** — Livewire component: list, create, revoke; safeGrant mapping; dispatch raw token via browser event |
| `resources/views/livewire/pengajian/admin/access-index.blade.php` | **NEW** — Blade view: table, form, Alpine modal with Clipboard API |
| `routes/web.php` | Add `GET /pengajian/admin/access` route under `auth,verified` |
| `resources/views/components/layouts/app/sidebar.blade.php` | Add "Pengajian" nav group with "Akses Desa" and "Regional Report" |
| `tests/Feature/Pengajian/PengajianAdminAccessTest.php` | **NEW** — 22 tests: route (3), grant list (4), create+validation (7), security (2), revoke (4), duplicates (2) |
| `docs/PENGAJIAN_MVP_OPERATIONAL.md` | Update status, known limitations, add PGM.13 section |

**Known limitations:**
- Login token rotation not yet implemented (use revoke + recreate)
- Multiple active grants for same Event+Desa still allowed (existing domain behavior)
- No RBAC on admin access page (all authenticated users can manage grants)

---

## 12. PGM.16 — Pengajian UX, Contextual Navigation & Bulk Import

PGM.16 makes Pengajian Desa feel like a native event type inside KJA Event Manager.

### 12.1 Event Type Architecture

Events now have an `event_type` column (`cai` / `pengajian`) via migration `2026_08_02_000001`.
- `Event::isCai()` / `Event::isPengajian()`
- `ActiveEventContext::isCurrentCai()` / `isCurrentPengajian()`
- Event creation form allows selecting event type

### 12.2 Contextual Sidebar

The sidebar now renders different navigation based on active event type:

| Event Type | Menu Items |
|---|---|
| **CAI** | Dashboard, Absensi, Registrasi, Database, Laporan, QR & Label, Pengajian, Event, Sekretariat (full CAI operational menu) |
| **Pengajian** | Pengajian (Regional Report), Peserta (Daftar Peserta, Import Massal), Operasional Desa (Akses Desa), Event (Kelola Event) |

CAI-only menus (Scan Absensi, Sesi Absensi, Regu, Registrasi Ulang, QR & Label, Surat Izin, Activity Log) are hidden.
**Hidden navigation is NOT authorization** — all routes remain server-side accessible.

### 12.3 Event Switcher Navigation

Switching active event now redirects to the event-type landing page:
- CAI → Dashboard (`/dashboard`)
- Pengajian → Regional Report (`/pengajian/report`)

The sidebar is fully re-rendered after the navigation.

### 12.4 Bulk Import

**Route:** `/pengajian/admin/import-massal` (auth, verified)

**Import flow:**
1. Upload CSV or Excel file (columns: nama, jenis_kelamin, tanggal_lahir, desa, kelompok)
2. Preview parsed rows with validation errors
3. Execute import

**Identity matching** (same as ManualParticipantRegistrationService):
- Match by normalized nama + desa_id + tanggal_lahir (PHP-level Carbon format comparison)
- Existing Person reused when identity matches
- Different birth date → new Person created
- Same identity in different Desa → no cross-Desa match

**Import summary counters:**
| Counter | Meaning |
|---|---|
| `created_persons` | New Person records created |
| `matched_persons` | Existing Person records matched (identity resolved) |
| `created_participations` | New Participation records created |
| `skipped_duplicates` | Participation already existed (person_id + event_id) |
| `failed_rows` | Rows that failed validation |

**No Regu / No CAI PlacementService** — Pengajian participants are not assigned to Regu.

### 12.5 Filter Fixes (Regional & Desa Reports)

| Status | Method | Behavior |
|---|---|---|
| Semua | Semua | All rows |
| Semua | Self | Only attended rows with method=self |
| Hadir | Semua | All attended rows |
| Hadir | Self | Only self-attended rows |
| Hadir | Operator | Only operator-attended rows |
| Belum | any | Method filter is cleared/ignored (semantically correct — belum hadir has no method) |

Search is debounced at 300ms.

### 12.6 Responsive UI

**Regional Report:**
- Mobile: compact cards, stacked filters, scrollable tabs
- Desktop: 4-column stat grid, 3-column desa breakdown
- Labels and tab active states more readable

**Desa Dashboard:**
- Centered max-w-4xl container
- Reduced header whitespace
- Responsive KJA logo sizing
- 3-column stat grid on desktop (was fixed 3-col)
- QR image responsive (`max-w-full`)

### 12.7 Key Files Changed in PGM.16

| File | Change |
|---|---|
| `database/migrations/2026_08_02_000001_add_event_type_to_events_table.php` | Add event_type column |
| `app/Models/Event.php` | Add event_type fillable, isCai/isPengajian methods, scopes |
| `app/Support/ActiveEventContext.php` | Add event-type helper methods |
| `resources/views/components/layouts/app/sidebar.blade.php` | Contextual CAI/Pengajian navigation |
| `resources/views/components/app-logo.blade.php` | KJA branding |
| `app/Services/Pengajian/PengajianImportService.php` | NEW — bulk import logic |
| `app/Livewire/Pengajian/Admin/ImportMassal.php` | NEW — import Livewire component |
| `resources/views/livewire/pengajian/admin/import-massal.blade.php` | NEW — import UI |
| `routes/web.php` | Add import-massal route |
| `app/Livewire/Event/EventSwitcher.php` | Redirect to event-type landing on switch |
| `app/Services/Pengajian/PengajianRegionalReportService.php` | Filter combination fix |
| `app/Services/Pengajian/PengajianDesaReportService.php` | Filter combination fix |
| `app/Livewire/Pengajian/RegionalReport.php` | Clear method on status=belum |
| `app/Livewire/Pengajian/DesaDashboard.php` | Clear method on status=belum |
| `resources/views/livewire/pengajian/regional-report.blade.php` | Responsive layout + filter UX |
| `resources/views/livewire/pengajian/desa-dashboard.blade.php` | Responsive layout
