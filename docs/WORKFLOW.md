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
