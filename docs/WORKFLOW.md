# WORKFLOW

> Complete workflow diagrams for all major features.

---

## 1. Authentication Flow

```
Login Page (/login)
  │
  ├── Credentials valid?
  │   ├── YES → Redirect to /dashboard
  │   │           │
  │   │           ├── Has active event? → Show Event Dashboard / Platform Dashboard
  │   │           └── No active event → Show Platform Dashboard (event selection prompt)
  │   │
  │   └── NO → Show error, remain on login
  │
  ├── Forgot Password? → /forgot-password → Email link → /reset-password/{token} → New password
  ├── Register? → /register (SelfRegister for public) or /register-user (admin register)
  └── Remember Me? → Session persisted across browser close
```

**Middlewares applied:** `auth`, `verified`, throttle on login attempts

---

## 2. Platform Dashboard → Event Selection Flow

```
Login → /dashboard (PlatformDashboard)
  │
  ├── Select Event via EventSwitcher (sidebar dropdown)
  │   │
  │   ├── CAI Event → /events/{event}/dashboard (Event Dashboard)
  │   │                ├── Full CAI operational menu shown
  │   │                └── Dashboard stats scoped to this event
  │   │
  │   └── Pengajian Event → /pengajian/report (Regional Report)
  │                        ├── Pengajian menu shown
  │                        └── Report scoped to this event
  │
  └── No Event Selected
       ├── Master Data still accessible (global)
       ├── Settings still accessible
       └── Event management still accessible
```

---

## 3. Registration Flow (CAI)

### Manual Registration
```
/database → Tambah Peserta
  │
  ├── Fill: Nama, Jenis Kelamin, Desa, Kelompok, Tanggal Lahir
  ├── Auto-placement: System picks least-filled Regu
  ├── System generates: Participant Number (KL001/KP001), Attendance Code (KJA-XXXXXXXX)
  ├── Writes to: Person + Participation + LegacyPesertaMapping + peserta
  └── Success → Redirect back to database list
```

### Self Registration
```
/registrasi/self (SelfRegister)
  │
  ├── Search existing Person by name
  ├── If found → Link to existing Person (if not already in event)
  ├── If not found → Create new Person
  ├── Set: nama, jenis_kelamin, desa, kelompok
  ├── Auto-placement + identity generation
  └── Success → Redirect to success page
```

### Re-registration
```
/registrasi/ulang
  │
  ├── Search existing participant
  ├── Edit: status_registrasi, regu, kelompok
  ├── Syncs to: Person if edited via legacy path
  └── Success → Refresh list
```

### Import
```
/database → Import Peserta (Excel/CSV)
  │
  ├── Upload file → Parse rows
  ├── For each row: Create Person + Participation + peserta
  ├── Auto-placement + identity generation
  ├── Error tracking per row
  └── Success → Summary report
```

---

## 4. Attendance Flow (CAI)

### QR Scan
```
/absensi → Scan QR (via camera or input)
  │
  ├── QR contains: attendance_code (KJA-XXXXXXXX)
  ├── QRIdentityResolver resolves code → Participation
  │   ├── Canonical match (attendance_code on Participation)
  │   └── Legacy fallback (peserta mapping)
  │
  ├── Validate: Session active?
  │   ├── YES → Continue
  │   └── NO → Prompt to select session
  │
  ├── Check: Already attended this session?
  │   ├── YES → Show "Sudah absen" message
  │   └── NO → Continue
  │
  ├── Check: Has Izin for this session?
  │   ├── YES → Skip (already covered)
  │   └── NO → Continue
  │
  ├── Create EventAttendance (status: hadir, method: scan)
  └── Show success with participant name + time
```

### Manual Attendance (Hadir)
```
/absensi → Manual Hadir
  │
  ├── Search participant by name
  ├── Select participant
  ├── Same session validation + duplicate prevention
  └── Create EventAttendance (status: hadir, method: manual)
```

### Manual Attendance (Izin)
```
/absensi → Manual Izin
  │
  ├── Search participant by name
  ├── Select participant
  ├── Same session validation + duplicate prevention
  └── Create EventAttendance (status: izin, method: manual)
```

---

## 5. Surat Izin Flow

```
/surat-izin → Create (draft)
  │
  ├── Search participant
  ├── Fill: alasan, jenis_izin (pulang/keluar), tanggal_mulai, tanggal_selesai
  └── Save draft
       │
       ├── Submit → status: pending
       │           ├── Approve → status: approved
       │           │              ├── Auto-create IzinAbsensi for all sessions in date range
       │           │              ├── Activity Log: approved
       │           │              └── Print Surat Izin (A5 landscape)
       │           │
       │           └── Reject → status: rejected
       │                        └── Activity Log: rejected
       │
       └── Cancel (from draft/pending) → status: cancelled

Return Tracking:
  ├── Mark returned → Select return date
  ├── System cleans up attendance records after return date
  ├── Updates IzinAbsensi end_time
  └── Activity Log: returned
```

---

## 6. QR & Label Flow

### Individual QR Download
```
/qr-label → Search participant → Select → Download PNG
  │
  ├── QRService generates PNG from attendance_code
  ├── Activity Log: action=downloaded, module=qr
  └── Browser downloads PNG file
```

### Batch QR Export
```
/qr-label → Select filters (desa, kelompok, regu, gender) → Export Batch
  │
  ├── BatchQRExportService generates PNGs for all filtered participants
  ├── Saves to storage
  ├── Activity Log: action=batch_exported, module=qr
  └── Download ZIP or view in storage
```

### Print Label (Single)
```
/qr-label → Search participant → Select → Print Label
  │
  ├── PrintEngine renders label4x4 template with QR + name + number
  ├── Browser native print dialog opens
  ├── Activity Log: action=print_viewed, module=print
  └── Print on 4×4 cm label paper
```

### Print Label (Batch Filtered)
```
/qr-label → Set filters → Print Filtered
  │
  ├── Query participations matching filters
  ├── Render each as 4×4 label
  ├── Browser native print
  ├── Activity Log: action=print_viewed
  └── Print on 4×4 cm label paper
```

### Print Label (A4 Grid)
```
/qr-label → Set filters → Print A4
  │
  ├── Query participations matching filters
  ├── Chunk 35 per page (5 cols × 7 rows)
  ├── Render A4 grid
  ├── Browser native print
  ├── Activity Log: action=print_viewed
  └── Print on A4 paper, cut to 4×4 cm labels
```

---

## 7. Pengajian Desa Flow

### Token Generation (Admin)
```
/pengajian/admin/access → Create Grant
  │
  ├── Select Event + Desa
  ├── Set validity period
  ├── System generates raw token (kja-dgt-{random})
  ├── Token hashed (bcrypt) + encrypted and stored
  ├── Raw token shown ONCE (must be saved)
  └── Distribute to Operator Desa
```

### Token Entry (Operator Desa)
```
/pengajian → Enter Token
  │
  ├── Input raw token
  ├── System validates hash match + validity period
  ├── Rate limited: 5 failed attempts/min per IP
  ├── On success: Session created (grant_id, event_id, desa_id)
  └── Redirect to /pengajian/desa (Desa Dashboard)
```

### Self Attendance (Public QR)
```
Operator prints QR → Participant scans QR → /pengajian/hadir/{nonce}
  │
  ├── Nonce resolves to DesaAccessGrant
  ├── Validates: nonce not expired, grant active
  ├── Public page loads (rate limited: 30/min)
  │
  ├── Step 1: Search name (min 3 chars, scoped to desa)
  ├── Step 2: Select person from search results
  ├── Step 3: Identity verification (enter birth date) [optional]
  ├── Step 4: Click "Hadir"
  │
  ├── System records EventAttendance (method: self)
  ├── Duplicate prevention: "Sudah tercatat hadir" if already recorded
  └── If identity wrong → Submit correction request
```

### Operator Attendance
```
/pengajian/desa → Operator-assisted
  │
  ├── Search participant by name or participant_number
  ├── Select matching person
  ├── Confirmation step
  ├── System records EventAttendance (method: operator)
  └── Success message
```

### Identity Correction
```
Public: /pengajian/hadir/{nonce} → Submit Correction
  │
  ├── Fill: requested_name, requested_birth_date
  ├── Status: pending
  └── (Person unchanged until approved)
       │
       Admin: /koreksi-data
         │
         ├── Review pending corrections
         ├── Approve → Person.nama / Person.tanggal_lahir updated
         │              └── If LegacyPesertaMapping exists → sync to peserta
         │
         └── Reject → Status: rejected, Person unchanged
```

### Bulk Import (Admin)
```
/pengajian/admin/import-massal
  │
  ├── Upload CSV/Excel
  │   Columns: nama, jenis_kelamin, tanggal_lahir, desa, kelompok
  │
  ├── Preview: parsed rows with validation errors
  │
  ├── Identity matching per row:
  │   ├── Match by normalized nama + desa_id + PHP-level tanggal_lahir
  │   ├── Match → reuse existing Person
  │   ├── No match → create new Person
  │   └── Duplicate Participation → skip
  │
  ├── Execute import
  └── Summary: created_persons, matched_persons, created_participations, skipped_duplicates, failed_rows
```

---

## 8. Report Flow

### Rekap Peserta
```
/rekap-peserta
  │
  ├── Filters: desa, kelompok, regu, gender, search
  ├── Data source: Participation (event-scoped) + Person
  ├── Export Excel: PesertaExport
  │   └── Activity Log: action=exported, module=export
  └── Paginated table display
```

### Rekap Absensi
```
/rekap-absensi
  │
  ├── Filters: session, desa, regu, kelompok
  ├── Shows: Hadir / Izin / Alfa per participant
  ├── Data source: EventAttendance scoped to event sessions
  └── Event-scoped (active event context)
```

### Regional Report (Pengajian)
```
/pengajian/report
  │
  ├── Stats: total_desa, total_warga, sudah_hadir, belum_hadir
  ├── Method breakdown: self vs operator
  ├── Per-desa breakdown with progress bars
  ├── Filters: Status (Semua/Hadir/Belum) + Method (Semua/Self/Operator)
  │   ├── Belum → method filter cleared (semantically correct)
  │   └── Hadir + Method → filter applies
  └── Debounced search (300ms)
```

### Desa Report (Pengajian)
```
/pengajian/desa → Desa Dashboard
  │
  ├── Stats: total_warga, sudah_hadir, belum_hadir, self, operator
  ├── Attendance list with filters
  ├── QR code for public attendance (nonce-based)
  └── Operator-assisted attendance form
```

---

## 9. Activity Log Flow

```
System actions → ActivityLogService::log()
  │
  ├── Parameters: action, module, description, subject (optional), properties (optional)
  ├── Auto-captures: user_id (current auth), ip_address, user_agent
  └── User fallback: "Sistem" when user_id is null or user deleted

User view: /activity-log
  ├── Newest-first list
  ├── Search by description
  ├── Filter by module
  ├── Filter by action
  ├── Expandable properties JSON
  └── Pagination
```

### Logged Actions by Module

| Module | Actions |
|--------|---------|
| `surat_izin` | created, submitted, approved, rejected, returned |
| `print` | print_viewed |
| `export` | exported |
| `qr` | downloaded, batch_exported |
| `grant` | created, revoked, deleted |
| `user` | created, updated, role_changed, password_reset, deleted |

---

## 10. Master Data Flow

### Person CRUD
```
/person
  │
  ├── Index: table with search (nama), pagination
  ├── Create: modal form → nama, jenis_kelamin, desa, kelompok, tanggal_lahir
  │   └── Creates Person only (no Participation)
  ├── Edit: modal form → updates Person
  │   └── If LegacyPesertaMapping exists → syncs to peserta (nama, gender, desa, kelompok)
  └── Delete: safety-guarded
       ├── Has Participations? → BLOCKED
       ├── Has LegacyPesertaMapping? → BLOCKED
       ├── Has CommitteeAssignments? → BLOCKED
       └── No dependencies → ALLOWED
```

### Desa / Kelompok CRUD
```
Standard CRUD patterns: List → Create → Edit → Delete
  ├── Global master data (no event_id)
  ├── Import: Excel/CSV
  └── All mutations gated with manage-master-data
```

---

## 11. Event Management Flow

```
/events → Event Index
  │
  ├── List all events with status badges (active/archived)
  ├── Create: name, slug (auto), type (CAI/Pengajian), dates
  ├── Edit: name, dates, status
  ├── Archive: set status to archived
  ├── Activate: set status to active
  │
  ├── Event Role Manager: create roles (name, code, sort_order)
  └── Committee Management: assign Person → Role → Event
       ├── Search Person
       ├── Select EventRole
       └── Create EventCommitteeAssignment
```

---

## 12. Activity & Committee (S3.9) Flow

```
Activity Groups → Create group (event-scoped)
  └── Activities → Create activity in group (event-scoped)
                    └── Activity Registration → Register Participation → Activity
                         └── Optional: Category assignment

Venue → Create venue (event-scoped)
  └── Rundown → Create daily schedule
       └── Rundown Items → Time slots with activities + optional venue

Event Role → Create operational role
  └── Committee Assignment → Person → Role → Event
       └── Optional: scope to ActivityGroup, Activity, or Venue
```

---

## 13. Competition Heat Manager (Heat Format Builder + Generation + Rebuild)

Route: `/events/{event}/competition/heat` (`competition.heat.index`, `can:manage-events`). Format yang didukung: `individual_heat` / `team_heat`.

### 13.1 Format Builder (per round)
```
/competition/heat → select heat class → "Buat Format"
  ├── Set: Round, Peserta per Heat (participants_per_heat), Lolos per Heat (qualifiers_per_heat)
  ├── Validated: participants ≥ 1, qualifiers ≥ 1, qualifiers ≤ participants
  └── Upsert per (class, round) → competition_heat_formats
```

### 13.2 Auto-Generate Heat (Round 1)
```
Round card → "Generate Heat" (only when round has no heats yet)
  ├── Kapasitas heat = participants_per_heat (source of truth) → required_participants
  ├── Kompetitor di-chunk per participants_per_heat → competition_schedule_entries
  ├── Idempotent: menolak bila round sudah punya heat (round_exists)
  └── Heat penuh → Ready (canAutoReady); heat sisa → Scheduled
```

### 13.3 Rebuild Existing Round (legacy / misconfigured heats)
```
Round card → amber banner (needs_rebuild) tampil saat ada heat yang
    required_participants ≠ participants_per_heat
  └── Operator klik "Generate Ulang Babak Ini" (explicit action, NEVER automatic)
        ├── Guard 1: ada heat Playing/Waiting Result/Finished?  → TOLAK (round_started)
        ├── Guard 2: sudah ada competition_heat_results?        → TOLAK (has_results)
        ├── Hapus heat round yang belum dimulai
        └── Generate ulang dari format (13.2)
```

### 13.4 Round Advancement (top-N)
```
Heat selesai → Rank → "Advance Top N" PER-HEAT (qualifyHeat)
  ├── heat tsb lengkap? tidak → TOLAK (heat_incomplete)
  ├── top-N heat tsb = qualified (position <= topN, exclude status) → disimpan
  └── TIDAK menunggu sibling heat, TIDAK membuat/mengisi round berikutnya

"Generate Round Berikutnya" (generateNextRound) — round-level
  ├── hitung qualified pool = union top-N dari SEMUA heat yang selesai
  ├── pool < participants_per_heat format berikutnya → TOLAK (qualified_pool_insufficient, round belum dibuat)
  ├── wajib ada format round berikutnya, jika tidak → TOLAK (no_next_format, tanpa fabrikasi babak)
  ├── Buat heat round berikutnya dari participants_per_heat format tsb
  └── Isi qualifier → entries (identitas tidak pernah ditukar)
```

### 13.6 Per-Heat Qualification + Qualified Pool
```
Round 1: Heat 01 selesai, Heat 02 berlangsung
  └── "Advance Top 2" Heat 01 → 2 qualified (pool = 2) — Heat 02 tidak tersentuh
Round 1: Heat 02 selesai
  └── "Advance Top 2" Heat 02 → 2 qualified (pool = 4)
pool 4 >= kapasitas format Round 2 (4) → "Generate Round Berikutnya" → Round 2 = 1 heat 4/4
```

### 13.5 Behavior — format 5/2 (peserta per heat 5, lolos 2)
| Competitors | Hasil | required_participants | Entries |
|---|---|---|---|
| 5 | 1 heat | 5 | 5 |
| 9 | 2 heat | 5, 5 | 5, 4 |
| 10 | 2 heat | 5, 5 | 5, 5 |
| 4 | 1 heat | 5 | 4 (tidak pernah 2+2) |

Top-N per heat selalu `qualifiers_per_heat` → 5 peserta menghasilkan 2 lolos; 9 peserta menghasilkan 2+2 = 4 lolos.

## 14. Competition Bracket — Perebutan Juara 3 (Bronze Match — 2026-08-27)

Route: `competition.bracket-manager` — generate bracket dengan opsi **Perebutan Juara 3** (checkbox, default OFF = `competition_brackets.third_place_match = false`). Dukungan: Individual (Individual vs Individual) & Team/Futsal (Team vs Team).

### 14.1 Bronze OFF (default — perilaku legacy)
```
Generate (opsi OFF) → bracket biasa (tanpa Bronze Match)
SF selesai → SF winner advance ke Final
Final selesai → Final winner = Juara 1, Final loser = Juara 2
Semifinal losers → tied Juara 3 (tanpa perebutan, tanpa posisi 4)
```

### 14.2 Bronze ON
```
Generate (opsi ON, totalRounds >= 2)
  └── Bracket + Bronze Match dibuat
      round=1, position=2, is_third_place=true, source_match_a/b = SF1 & SF2
      (Bronze = CompetitionSchedule normal; badge BRACKET; hasil via Official Panel)
SF selesai (submitResult / submitTeamResult)
  ├── advanceWinner / advanceWinnerTeam: SF winner → Final (lookup exclude is_third_place)
  └── advanceLoser / advanceLoserTeam: SF loser → Bronze (slot source side), hanya bila ON
  └── Bronze: 1 loser → Scheduled; 2 loser → Ready (canAutoReady)
Bronze selesai → Bronze winner = Juara 3, Bronze loser = Juara 4 (Bronze tidak advance ke mana pun)
Final selesai → Final winner = Juara 1, Final loser = Juara 2
Podium akhir = 1,2,3,4 — urutan selesai Final vs Bronze bebas (Bronze dulu atau Final dulu)
```

### 14.3 Reset / Rollback (semifinal)
```
Reset semifinal → rollback winner (Final) + rollback loser (Bronze)
  ├── Bronze BELUM dimainkan (Scheduled/Ready):
  │     losernya dihapus dari Bronze; Bronze match tetap ada → kembali Scheduled
  └── Bronze SUDAH dimainkan (Playing / Waiting Result / Finished):
        playedBronzeEntries() → entry + outcome Juara 3/4 Bronze DILINDUNGI
        (tidak dihapus / tidak dirusak oleh reset semifinal)
Bronze match sendiri tidak pernah dihapus oleh reset
```

### 14.4 Interaksi Match Center / Official Panel
Bronze & Final adalah bracket match normal: berbadge **BRACKET**, `requiresOfficial()` = true → hasil disubmit lewat **Buka Official Panel**, dan tidak pernah memakai tombol Input Hasil (untuk heat). Lifecycle Heat (individual_heat / team_heat) tidak tersentuh.
