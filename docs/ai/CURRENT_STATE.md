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

Sprint 1 — CAI Operational

Status:

🟡 In Progress

Focus:

Menyelesaikan seluruh gap operasional CAI sesuai `docs/ROADMAP.md`.

---

# Current Goal

Menyelesaikan Sprint 1 sebelum melanjutkan ke Sprint berikutnya.

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

Verification:

* Full test suite passed.
* 70 tests passed.
* 198 assertions.
* 0 failures.

---

# Current Priority

Priority saat ini:

1. Attendance History hardening.
2. Complete remaining Sprint 1 operational gaps.
3. Dashboard PJ Regu.
4. Progress Absensi and Live Monitoring hardening.
5. UI hardening.
6. Close Sprint 1.

Development follows `docs/ROADMAP.md` as the primary product roadmap.

# Project Status

## Documentation

🟡 In Progress

---

## Architecture

🟡 In Progress

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

---

## UI

🟡 Needs Improvement

Target:

* Dark Mode
* Responsive Mobile
* Reusable Components

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
* Dark Mode belum konsisten.
* Komponen UI masih belum seragam.

---

## Medium

* Export masih terbatas.
* Dashboard belum lengkap.
* Dokumentasi modul belum seluruhnya tersedia.

---

## Low

* API belum dibutuhkan.
* Mobile App masih tahap perencanaan.

---

# Current Technical Debt

* SVG QR generation remains deferred technical debt; active PNG QR runtime uses the internal QR service.
* Attendance masih menggunakan NIP sebagai legacy fallback, sementara attendance_code menjadi identifier utama scan.
* Beberapa halaman belum menggunakan komponen UI yang konsisten.
* Struktur database masih berorientasi pada CAI.

---

# Next Work

Current next task:

Attendance History hardening.

After Sprint 1:

Continue development according to `docs/ROADMAP.md`.

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
