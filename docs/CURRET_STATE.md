# CURRENT STATE

Last Update

2026-07-14

---

# Current Version

v1.0

(Project Name)

AbsenCAI

Target

KJA Event Manager

---

# Current Sprint

Sprint 0

Status

Planning

Documentation

Architecture

No Major Coding

---

# Current Goal

Finish CAI Operational.

Do NOT redesign the system yet.

Focus on documentation and architecture.

---

# Current Priority

1.

Documentation

2.

Dark Mode Refactor

3.

QR Generator

4.

Attendance Code

5.

Attendance Improvement

6.

Export

---

# Current Stack

Laravel 12

Livewire

Flux UI

Tailwind

SQLite

Rocky Linux

Proxmox

Nextcloud

TrueNAS

---

# Current Branch

main

---

# Current Database

SQLite

Migration to MariaDB planned after CAI.

---

# Current Main Entity

Person

(Current implementation still uses Peserta.)

Future refactor:

Person

↓

Participation

↓

Attendance

---

# Active Modules

Stable

- Authentication
- Registration
- Import
- Attendance
- Dashboard

Development

- QR Generator
- Attendance Code
- Export
- Dashboard

Planned

- Scoring
- Competition
- Certificate
- Multi Event

---

# Current Risks

- QR still depends on NIP.
- SQLite concurrency.
- Dark Mode inconsistency.
- Reusable UI components not complete.

---

# Known Technical Debt

- Business logic inside Livewire.
- Repeated UI components.
- Hardcoded terminology.
- Documentation incomplete.

---

# Do Not Change

Do NOT:

- Rename models.
- Change database schema.
- Replace QR logic.

Until Sprint 1 starts.

---

# Files Frequently Edited

app/

resources/

routes/

docs/

---

# Current Focus

CAI first.

Commercial product later.

---

# Next Sprint

Attendance Code

QR Generator

PDF QR

Manual Attendance

Dark Mode

Export

---

# Long Term Vision

Universal Person Database

↓

Event

↓

Competition

↓

Commercial Event Management Platform