# KJA Event Manager — Event Operating System

> Modern Event Management Platform built with Laravel & Livewire.

> **Roadmap V1** = ✅ **100% COMPLETE**
> **Roadmap V2** = 📋 **Planned** — Event Operating System

---

# Overview

KJA Event Manager adalah platform Event Management yang dikembangkan untuk membantu organisasi mengelola seluruh proses kegiatan dalam satu sistem.

Awalnya proyek ini dibuat sebagai **AbsenCAI**, namun berkembang menjadi platform yang mampu mengelola berbagai jenis event. Current MVP tetap **CAI Operational**.

Contoh penggunaan:

- CAI
- Festival
- Seminar
- Pelatihan
- Kejuaraan
- Lomba
- Event Organisasi

---

# Vision (Updated)

KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi.

**KJA Event Manager adalah Event Operating System.**

Filosofi: **Build Engine, Not Module** — jangan membuat modul khusus per jenis event. Bangun Competition Engine + Scoring Engine + Blueprint Event yang bersifat generic.

Lihat `VISION_V2.md` untuk detail.

---

# Vision (Original)

Membangun platform Event Management yang dapat digunakan oleh berbagai organisasi tanpa harus menginput ulang data peserta setiap kali mengadakan kegiatan.

Setiap orang hanya memiliki **satu identitas** dan dapat mengikuti banyak event sepanjang waktu.

---

# Core Concept

Person

↓

Event

↓

Participation

↓

Attendance

↓

Scoring

↓

Report

---

# Main Features

## Current

- Master Data
- Registration
- Re-Registration
- QR Attendance
- Dashboard
- Attendance Session
- Excel Import
- Excel Export

---

## Planned (V1 — Now Completed)

- Universal Person Database ✅
- Attendance Code ✅
- Internal QR Generator ✅
- Multi Event ✅
- Multi Venue ✅
- Multi Category ✅
- Dashboard Division ✅

## V2 — Event Operating System (Planned)

- Blueprint Event
- Competition Engine
- Scoring Engine
- Venue Management (Reusable)
- Live Schedule Engine
- Public Dashboard
- Announcement Engine
- Certificate Engine
- Mobile App
- Public API

---

# Technology

Backend

Laravel 12

Frontend

Livewire

Flux UI

Tailwind CSS

Database

SQLite (Current)

MariaDB (Future)

Storage

Nextcloud

TrueNAS

Deployment

Rocky Linux

Proxmox

Cloudflare Tunnel

---

# Documentation

Read documentation in this order:

1. INDEX.md
2. VISION_V2.md — V2 product vision
3. AGENTS.md
4. ROADMAP.md
5. FEATURE.md
6. DATABASE.md
7. RULES.md
8. MODULES.md

---

# Development Workflow

Idea

↓

Discussion

↓

Documentation

↓

Database Design

↓

Todo

↓

Sprint

↓

Development

↓

Testing

↓

Release

---

# Development Rule

No Code Before Design.

Every feature must be documented before implementation.

---

# Version

Current

v1.5 (CAI Operational — V1 Foundation COMPLETE)

Target

Roadmap V1: ✅ 100% COMPLETE

Roadmap V2: 📋 Planned — Event Operating System

Next

v2.0 (Event Operating System)

---

# Long Term Goal

Menjadi platform Event Management yang:

- Modular
- Multi Event
- Multi Organization
- Commercial Ready
- AI Friendly
- Easy to Extend

---

# Repository Structure

app/

database/

resources/

routes/

storage/

tests/

docs/

---

# License

Private Project

Copyright © KJA Event Manager