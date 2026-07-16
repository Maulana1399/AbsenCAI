# Backlog

## S01 Step 2 (Foundation Service Tests)

- [x] Audit existing tests for service behavior coverage
- [x] Add direct service tests for PlacementService
- [x] Add direct service tests for RegistrationService
- [x] Add direct service tests for AttendanceService
- [x] Add direct service test for implemented QRService PNG output
- [x] Verify new service tests in PHP runtime

Status: VERIFIED.

---

## S01 Step 1 (Config Runtime Audit)

- [x] Audit config/feature.php status and runtime usage
- [x] Audit config/kjam.php status and safe runtime usage
- [x] Sync relevant documentation to codebase reality
- [x] Keep behavior unchanged

Status: VERIFIED.

---

## Sprint 1 (CAI Operational)

Status: IN PROGRESS

### Attendance

- [x] Attendance Code
- [x] Internal QR Generator
- [x] QR Regeneration
- [x] Manual Attendance
- [x] Manual Hadir
- [x] Manual Izin
- [x] Attendance Status
- [x] Hadir summary
- [x] Izin summary
- [x] Alfa derivation
- [x] Hadir ↔ Izin conflict protection
- [ ] Attendance History hardening

### QR

- [x] Generate QR
- [x] Batch Generate
- [x] PDF Export
- [x] Print 4x4 cm
- [x] Batch Print
- [x] QR regeneration via attendance_code

### Report

- [x] Export Excel
- [ ] Export PDF
- [x] Rekap Per Regu
- [x] Rekap Per Desa
- [x] Rekap Per Kelompok
- [x] Rekap Belum Hadir
- [x] Rekap Hadir / Izin / Alfa

### Dashboard

- [x] Dashboard Divisi
- [ ] Dashboard PJ Regu
- [x] Progress Registrasi
- [ ] Progress Absensi hardening
- [ ] Live Monitoring hardening

### UI

- [ ] Dark Mode hardening
- [ ] Responsive Mobile hardening
- [ ] Menu Refactor
- [ ] Reusable Components
- [ ] Fix low-contrast gray text on bright blue background

### Verification

- [x] Manual Attendance verified
- [x] Izin attendance flow verified
- [x] Hadir/Izin/Alfa summary verified
- [x] Full regression suite verified

Last verified test suite:

70 tests passed, 198 assertions, 0 failures.

### Current Next Task

- [ ] Audit Attendance History existing implementation
- [ ] Determine whether Rekap Absensi already satisfies Attendance History requirements
- [ ] Harden existing flow instead of creating duplicate functionality
---

## Sprint 2.5.1 (QR & Label UI)

- [ ] Create QR & Label menu
- [ ] Individual QR search and download
- [ ] Batch QR export UI
- [ ] Print label UI
- [ ] Keep existing modules unchanged

---

## Sprint Bugfix (Identity Repair)

- [ ] Backfill legacy participant_number placeholder values
- [ ] Backfill legacy attendance_code placeholder values
- [ ] Keep valid records unchanged
- [ ] Verify command summary output

---

## Sprint 2.8 (Print Foundation)

- [ ] Create print engine
- [ ] Create label 4x4 template
- [ ] Use QRService in print flow
- [ ] Prepare printable participant labels

---

## Sprint 2.7 (Batch QR Export Foundation)

- [ ] Create batch QR export service
- [ ] Reuse QRService
- [ ] Support PNG and SVG output
- [ ] Return export summary

---

## Sprint 2.6 (QR Foundation)

- [ ] Create reusable QR service
- [ ] Support SVG generation
- [ ] Support PNG generation
- [ ] Keep service reusable for future modules

---

## Sprint 2.5 (Attendance Identity Transition)

- [x] Lookup attendance by attendance_code first
- [x] Keep legacy NIP fallback
- [x] Preserve duplicate attendance prevention
- [x] Preserve session validation
- [x] Add Livewire attendance orchestration coverage
- [x] Close S03 as complete
- [x] Close S04 as complete
- [x] Defer SVG QR as technical debt

---

## Sprint 2.4 (Registration Identity Transition)

- [ ] Generate participant_number on new registration
- [ ] Generate attendance_code on new registration
- [ ] Keep legacy nip flow for backward compatibility
- [ ] Verify unique participant_number and attendance_code

---

## Sprint 2.3 (Participant Number Foundation)

- [x] Generate participant_number with KL/KP prefix
- [x] Keep legacy nip wrapper for backward compatibility
- [x] Prepare participant number transition docs
- [x] Verify deterministic running sequence

Status: VERIFIED.

---

## Sprint 2.5 (Registration Foundation)

- [x] Centralize participant create/update persistence in RegistrationService
- [x] Refactor database participant create/update callers
- [x] Refactor import participant persistence path
- [x] Add regression tests for registration service

Status: VERIFIED.

S02 status: COMPLETED.

---

## Sprint 2.4 (Placement Foundation)

- [x] Centralize auto placement logic in PlacementService
- [x] Centralize least-filled regu selection in PlacementService
- [x] Update callers to use PlacementService as source of truth
- [x] Add regression tests for placement service

Status: VERIFIED.

---

## Sprint 2.2 (Identity Foundation)

- [ ] Add participant_number column
- [ ] Add attendance_code column
- [ ] Keep legacy nip compatibility
- [ ] Prepare identity transition docs
- [ ] Defer identity cleanup to S04

---

## Sprint 1 (CAI Priority)

### Registration Refactor

- [ ] RegistrationService
- [ ] Participant persistence extraction
- [ ] Update participant status service
- [ ] Re-registration service cleanup

---

### UI

- [ ] Dark Mode
- [ ] Responsive Mobile
- [ ] Menu Refactor

---

### Attendance

- [ ] Attendance Code
- [ ] Internal QR Generator
- [ ] Manual Input
- [ ] History
- [ ] Izin
- [ ] Alfa

---

### QR

- [ ] Generate QR
- [ ] Print PDF
- [ ] Print 4x4
- [ ] Batch Print
- [ ] Regenerate QR

---

### Report

- [ ] Export Excel
- [ ] Export PDF
- [ ] Rekap Belum Hadir
- [ ] Rekap Per Regu
- [ ] Rekap Per Desa

---

### Dashboard

- [ ] Dashboard Divisi
- [ ] Dashboard PJ
- [ ] Progress Registrasi
- [ ] Progress Absensi

---

## Sprint 2

- [ ] Penalty
- [ ] Bonus
- [ ] Leaderboard
- [ ] Print Surat Izin
- [ ] Riwayat Pelanggaran

---

## Sprint 3

- [ ] Multi Event
- [ ] Multi Venue
- [ ] Multi Category
- [ ] Multi Role
- [ ] Universal Person

---

## Sprint 4

- [ ] Competition Module
- [ ] Jadwal
- [ ] Bracket
- [ ] Penilaian
- [ ] Sertifikat

---

## Future

- [ ] Mobile App
- [ ] API
- [ ] SaaS
- [ ] White Label
- [ ] Offline Mode
