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

Sprint 0

Status:

🟡 In Progress

Focus:

Documentation & Architecture

---

# Current Goal

Menyelesaikan seluruh dokumentasi sebelum memulai implementasi Sprint 1.

---

# Current Priority

Priority saat ini:

1. Finalisasi Dokumentasi
2. Rapikan Arsitektur
3. Refactor UI
4. Stabilkan Operasional CAI

Tidak ada penambahan fitur besar sebelum Sprint 1 dimulai.

---

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
* QR masih menggunakan NIP.
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

* QR Generator masih menggunakan layanan pihak ketiga.
* Attendance masih menggunakan NIP sebagai identitas QR.
* Beberapa halaman belum menggunakan komponen UI yang konsisten.
* Struktur database masih berorientasi pada CAI.

---

# Next Sprint

Sprint 1

Target:

* Attendance Code
* Internal QR Generator
* Print QR
* Export
* Status Attendance
* Dashboard Improvement
* UI Refactor

---

# Development Rules

Selama Sprint 0:

✅ Boleh

* Perbaikan dokumentasi
* Perbaikan bug
* Refactor kecil
* Perbaikan UI

❌ Tidak boleh

* Breaking Change Database
* Refactor besar tanpa desain
* Menambah modul baru
* Mengubah arsitektur inti

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
