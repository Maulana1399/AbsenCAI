# CURRENT STATE

> Current Development Status

---

# Project

## Name

KJA Event Manager

Current MVP:

CAI Operational

---

# Current Version

Version:

v1.0

Stage:

MVP Development

---

# Current Sprint

Sprint 2 — Secretariat Operational

Status:

🟡 In Progress

Focus:

Sprint 2 — Permission feature group completed. Activity Log Foundation completed. Remaining: Riwayat Izin, Scoring, Export/Print/QR Log, Storage.

---

# Current Goal

Melanjutkan pengembangan ke Sprint 2 sesuai `docs/ROADMAP.md`.

Completed foundation work:

* S01 Foundation completed.
* S02 Registration and Placement foundation completed.
* S03 Attendance architecture completed.
* S04 Identity & QR completed.
* S05 Document & Certificate deferred / skipped for now.

Current Sprint 1 attendance state:

* Attendance Code implemented.
* Internal QR Generator implemented.
* QR Regeneration supported through on-demand generation from `attendance_code`.
* Manual Attendance implemented.
* Manual Hadir implemented.
* Manual Izin implemented.
* Attendance Status Hadir/Izin/Alfa implemented.
* Hadir remains stored in `Absensi`.
* Izin is stored separately in `IzinAbsensi`.
* Alfa is derived and is not persisted as a database row.
* Hadir ↔ Izin conflicts are prevented.
* Rekap Hadir/Izin/Alfa implemented.
* Dashboard Hadir/Izin/Alfa summary implemented.
* `attendance_code` remains the primary scan and QR identifier.
* Legacy NIP fallback remains supported.

Deferred Sprint 1 backlog:

* QR PDF Export.
* Report PDF Export.
* Dashboard PJ Regu.
* Live Monitoring.

Verification:

* Full test suite passed.
* 146 tests passed.
* 344 assertions.
* 0 failures.

---

# Current Priority

Priority saat ini:

1. Sprint 2: Permission feature group completed (Surat Izin, Print Surat, Return Tracking).
2. Activity Log Foundation completed (model, service, read-only UI, Surat Izin integration, tests).
3. Remaining Sprint 2: Riwayat Izin, Scoring, Export/Print/QR Log, Storage.
3. Scoring: Master Point, Bonus, Penalty, Leaderboard, Riwayat Penilaian.
4. Audit: Activity Log, Export Log, Print Log, QR Log.
5. Storage planning: Nextcloud Integration and TrueNAS Integration.

Development follows `docs/ROADMAP.md` as the primary product roadmap.

# Project Status

## Documentation

🟢 Stable

---

## Architecture

🟢 Stable for CAI Operational

---

## Database

🟢 Stable

---

## Core Feature

🟢 Stable

* Import
* Registrasi
* Registrasi Ulang
* Scan QR
* Dashboard
* Rekap
* Surat Izin
* Activity Log

---

## UI

🟢 Stable for CAI Operational

Target:

* Dark Mode completed.
* Responsive Mobile completed.
* Menu Refactor completed.
* Reusable Components foundation completed through Flux UI and centralized layouts.

---

## Security

🟢 Stable

---

## Permission

🟢 Stable

---

# Current Technical Stack

Backend

* Laravel 12

Frontend

* Livewire
* Flux UI
* Tailwind CSS

Database

Current

* SQLite

Future

* MariaDB

Infrastructure

* Rocky Linux
* Proxmox
* TrueNAS
* Nextcloud

---

# Current Risks

## High

* SQLite belum cocok untuk concurrent access dalam skala besar.
* QR masih menggunakan NIP sebagai legacy fallback.

---

## Medium

* QR PDF Export deferred.
* Report PDF Export deferred.
* Dashboard PJ Regu deferred.
* Live Monitoring deferred.
* EditSesi page references sesi_id from SuratIzin as editable data — needs architectural review.
* Riwayat Izin belum diimplementasikan.
* Export Log, Print Log, QR Log belum diintegrasikan dengan Activity Log.

---

## Low

* API belum dibutuhkan.
* Mobile App masih tahap perencanaan.

---

# Current Technical Debt

* SVG QR generation remains deferred technical debt; active PNG QR runtime uses the internal QR service.
* Attendance masih menggunakan NIP sebagai legacy fallback, sementara attendance_code menjadi identifier utama scan.
* Struktur database masih berorientasi pada CAI.
* EditSesi page treats SuratIzin.sesi_id as editable session data — this needs architectural review.
* Activity Log's "failed submit" test has a dead assertion after `expectException`.

---

# Next Work

Current next task:

Sprint 2 — Riwayat Izin, Scoring, Export/Print/QR Log, Storage.

After Sprint 1 closure:

Continue development according to `docs/ROADMAP.md`.

Deferred Sprint 1 items remain in backlog until operationally required.

Permission feature group completed. Activity Log Foundation completed (model, service, read-only UI, Surat Izin integration, tests).
Remaining Sprint 2 scope: Riwayat Izin, Scoring (Master Point, Bonus, Penalty, Leaderboard, Riwayat Penilaian), Export Log, Print Log, QR Log, Storage (Nextcloud, TrueNAS).

Sprint 5 Document & Certificate remains deferred until required.

---

# Development Rules

# Development Rules

Current development rules:

* Follow `docs/ROADMAP.md` as the primary roadmap.
* Audit existing functionality before implementing new functionality.
* Do not duplicate features that already exist.
* Keep backward compatibility unless an explicit migration is designed.
* Business logic should remain centralized in service layers.
* Update documentation after verified changes.
* Run the full test suite before closing a feature scope.

---

# Success Criteria

Sprint 0 dianggap selesai apabila:

* Dokumentasi lengkap.
* Struktur proyek konsisten.
* Roadmap final.
* Security final.
* Permission final.
* AI dapat memahami proyek hanya dengan membaca dokumentasi.

---

# Notes

CURRENT_STATE.md adalah snapshot kondisi proyek.

Dokumen ini akan diperbarui setiap kali sprint selesai.

Dokumen ini **bukan** tempat mencatat roadmap, changelog, atau keputusan desain.

Gunakan dokumen lain sesuai fungsinya:

* ROADMAP.md → Rencana pengembangan.
* CHANGELOG.md → Riwayat perubahan.
* DECISION.md → Keputusan arsitektur.
* TODO.md → Pekerjaan aktif.
