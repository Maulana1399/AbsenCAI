# Architecture

## Vision

KJA Event Manager adalah platform Event Management berbasis Web yang dapat digunakan oleh banyak organisasi.

---

## Current Infrastructure

Internet
│
Cloudflare Tunnel
│
Mini PC
│
Proxmox
├── Debian Router
├── Rocky Linux
├── TrueNAS
└── Windows

---

## Production

Internet

↓

Cloudflare

↓

Laravel

↓

MariaDB

↓

Storage (Nextcloud / TrueNAS)

---

## Client

- Desktop
- Laptop
- Android
- iPhone

---

## User

- Super Admin
- Admin
- Sekretariat
- Ketua
- PJ Divisi
- Operator
- Juri
- Peserta
- Viewer

---

## Authentication

Login

Role Based Access

Token Based Scan (Future)

---

## QR Generation

Current direction:

- QR content will use `attendance_code`
- QR generation should be reusable for ID Card, export, API, and mobile app
- QR rendering logic should live in a dedicated service layer

Batch export direction:

- QR assets can be generated in bulk for mass printing workflows
- File naming should use `participant_number`
- Export should return summary data, not a UI-specific response

---

## File Storage

Database

↓

Metadata

↓

Nextcloud

↓

TrueNAS

---

## Development Rule

Development Server

Mini PC

Production

Dedicated Server / VPS

Backup

TrueNAS