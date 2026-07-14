# AGENTS

> AI Development Guide for KJA Event Manager

---

# Project

## Name

KJA Event Manager

Former Project:

AbsenCAI

---

# Product Vision

KJA Event Manager adalah platform Event Management berbasis web yang dikembangkan secara modular dan dapat digunakan oleh berbagai organisasi.

Current MVP:

CAI Operational.

Long Term Goal:

Commercial Event Management Platform.

---

# Tech Stack

## Backend

* Laravel 12

## Frontend

* Livewire
* Flux UI
* Tailwind CSS

## Database

Current

* SQLite

Future

* MariaDB

Possible Future

* PostgreSQL

## Storage

* Nextcloud
* TrueNAS

## Deployment

* Rocky Linux
* Proxmox

Future:

* VPS
* Dedicated Server

---

# Development Principles

AI harus mengikuti prinsip berikut.

* Documentation First
* No Code Before Design
* Reuse Existing Components
* Keep Modular
* Keep Scalable
* Keep Maintainable

---

# Documentation Read Order

Sebelum melakukan perubahan kode WAJIB membaca:

1. INDEX.md
2. CURRENT_STATE.md
3. ROADMAP.md
4. FEATURE.md
5. DATABASE.md
6. DATAFLOW.md
7. DECISION.md
8. SECURITY.md
9. PERMISSION.md
10. TODO.md

---

# Development Workflow

Semua fitur wajib mengikuti alur berikut.

Idea

↓

Discussion

↓

Documentation

↓

Architecture

↓

Database

↓

Todo

↓

Development

↓

Testing

↓

Release

↓

Changelog

Tidak diperbolehkan langsung membuat fitur tanpa mengikuti workflow tersebut.

---

# Coding Rules

Selalu:

* Menggunakan Livewire Convention
* Menggunakan Laravel Best Practice
* Menggunakan Validation
* Menggunakan Service apabila business logic mulai besar
* Menggunakan Eloquent
* Menjaga kode tetap sederhana

Hindari:

* Duplicate Code
* Business Logic di Blade
* Raw SQL jika tidak diperlukan
* Hardcode Role
* Hardcode Permission

---

# Database Rules

Jangan:

* Rename tabel tanpa DATABASE.md
* Rename Model tanpa dokumentasi
* Menghapus kolom tanpa ADR
* Mengubah struktur utama tanpa Discussion

Selalu:

* Gunakan Migration
* Gunakan Foreign Key
* Gunakan Validation
* Gunakan Soft Delete jika diperlukan

---

# UI Rules

Semua UI wajib:

* Responsive
* Mobile Friendly
* Dark Mode Compatible
* Menggunakan Flux Component apabila tersedia
* Konsisten dengan desain sistem

---

# Naming Convention

## Code

English

Contoh:

Attendance

Registration

Competition

Certificate

---

## UI

Bahasa Indonesia

Contoh:

Registrasi

Absensi

Penilaian

Dashboard

---

## Documentation

English

---

# Core Concepts

## Main Entity

Person

Bukan Participant.

Satu orang hanya dibuat satu kali.

Orang tersebut dapat menjadi:

* Participant
* Committee
* Judge
* Official
* Volunteer

melalui Participation.

---

# Current Architecture Direction

Current

Person

↓

Attendance

Future

Person

↓

Participation

↓

Attendance

↓

Competition

↓

Certificate

---

# AI Responsibilities

AI harus membantu:

* Mendesain arsitektur
* Menjaga konsistensi database
* Mengurangi technical debt
* Menjaga dokumentasi tetap sinkron
* Menghindari duplicate logic
* Mengikuti roadmap proyek

AI bukan hanya membantu coding, tetapi juga menjaga kualitas software.

---

# Long Term Goal

Mengembangkan KJA Event Manager menjadi platform Event Management komersial yang:

* Modular
* Multi Event
* Multi Organization
* Multi Venue
* Scalable
* Maintainable
* Easy to Extend
