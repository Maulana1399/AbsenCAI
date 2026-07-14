# CHANGELOG

Semua perubahan penting pada KJA Event Manager dicatat pada dokumen ini.

Format changelog mengikuti prinsip **Keep a Changelog**.

---

# [Unreleased]

## Planned

Belum ada perubahan.

Semua rencana pengembangan dicatat pada:

* ROADMAP.md
* TODO.md

---

# [v1.0.0] - 2026-07-14

## Project

### Added

* Rename project vision menjadi **KJA Event Manager**
* AbsenCAI ditetapkan sebagai MVP (Minimum Viable Product)
* Dokumentasi arsitektur awal proyek

---

## Documentation

### Added

* AGENTS.md
* API.md
* ARCHITECTURE.md
* CHANGELOG.md
* CONTEXT.md
* CURRENT_STATE.md
* DATABASE.md
* DATAFLOW.md
* DECISION.md
* FEATURE.md
* INDEX.md
* ROADMAP.md
* SECURITY.md
* TODO.md
* PERMISSION.md

---

## Architecture

### Added

* Long-term architecture planning
* Universal Person Database concept
* Multi Event architecture
* Future Multi Organization support
* Storage architecture menggunakan Nextcloud & TrueNAS

---

## Database

### Added

Concept:

* Person sebagai entitas utama
* Participation sebagai relasi Event
* Attendance terpisah dari Person
* Universal ID
* Attendance Code

---

## Decision

### Accepted

* Rename AbsenCAI menjadi KJA Event Manager
* Person sebagai entitas utama
* Attendance Code menggantikan QR berbasis NIP
* No Code Before Design
* CAI menjadi prioritas utama
* Storage menggunakan metadata + Nextcloud

---

## Security

### Added

* Security Standard
* Security Checklist
* Authentication Guideline
* Authorization Guideline
* QR Security
* Audit Logging Standard
* Backup Policy

---

## Permission

### Added

* Role Matrix
* Permission Matrix
* Future Role Planning
* Permission Naming Convention

---

## Development

### Added

Sprint Management:

* Sprint 0
* Documentation First
* Architecture First
* Planning Before Coding

---

# Versioning

Menggunakan Semantic Versioning.

Format:

MAJOR.MINOR.PATCH

Contoh:

* v1.0.0
* v1.1.0
* v1.2.3
* v2.0.0

---

# Changelog Rules

Tambahkan perubahan **hanya jika implementasi sudah selesai**.

Jangan mencatat:

* Ide
* Rencana
* Roadmap
* TODO

Semua rencana dicatat di:

* ROADMAP.md
* TODO.md

---

# Release Flow

Idea

↓

Discussion

↓

Documentation

↓

Design

↓

Development

↓

Testing

↓

Release

↓

Update CHANGELOG
