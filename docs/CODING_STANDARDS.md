# CODING STANDARDS

Version: 1.0

Project: KJA Event Manager

Last Updated: 2026-07-14

---

# Purpose

Dokumen ini menjadi standar pengembangan KJA Event Manager.

Semua developer dan AI wajib mengikuti aturan ini.

Jika ada konflik antara implementasi dan dokumen ini, ikuti dokumen ini atau buat ADR (Architectural Decision Record) sebelum mengubah standar.

---

# General Principles

- Readability over cleverness.
- Simplicity over complexity.
- Reuse before rewrite.
- Document before implement.
- Test before merge.

---

# Development Workflow

Idea

↓

Discussion

↓

Documentation

↓

Architecture Review

↓

Implementation

↓

Testing

↓

Documentation Update

↓

Merge

---

# Folder Responsibility

## Model

Hanya menyimpan:

- Relationship
- Scope
- Cast
- Accessor
- Mutator

Model TIDAK BOLEH berisi business logic yang kompleks.

---

## Service

Seluruh business logic berada di Service.

Contoh:

AttendanceService

RegistrationService

QRService

PlacementService

ExportService

DashboardService

---

## Livewire

Livewire hanya bertugas:

- menerima input
- validasi
- memanggil Service
- menampilkan hasil

Livewire tidak boleh berisi business logic panjang.

---

## Action

Action digunakan untuk proses tunggal.

Contoh:

GenerateQRCodeAction

ExportAttendanceAction

RegisterPersonAction

---

## Helper

Helper hanya untuk fungsi utilitas.

Contoh:

Format tanggal

Generate slug

Formatter

Bukan business logic.

---

# Naming Convention

## Class

PascalCase

Example

AttendanceService

---

## Method

camelCase

Example

generateAttendanceCode()

---

## Variable

camelCase

Example

attendanceStatus

---

## Table

Plural

snake_case

Example

attendance_logs

---

## Column

snake_case

Example

attendance_code

---

## Enum

PascalCase

Example

AttendanceStatus

---

## Constant

UPPER_SNAKE_CASE

Example

MAX_QR_RETRY

---

# Database Rules

Never:

- duplicate data
- hardcode IDs
- raw SQL unless necessary

Always:

- foreign key
- eager loading
- transaction for critical operations

---

# Service Rules

One Service

One Responsibility

Good

AttendanceService

Bad

SystemService

---

# Validation

Always validate.

Use:

Livewire Validation

or

Form Request

Never trust user input.

---

# Error Handling

Never return raw exception to user.

Log exception.

Return friendly message.

---

# Query Rules

Avoid

Model::all()

when unnecessary.

Always select only required columns.

Use eager loading.

Prevent N+1 query.

---

# Configuration

Never hardcode:

- role
- permission
- QR size
- attendance status
- feature toggle

Use config().

---

# Enum

Use Enum whenever possible.

Never compare string directly.

Bad

if ($status == "Hadir")

Good

if ($status === AttendanceStatus::PRESENT)

---

# UI

Dark Mode compatible.

Responsive.

Flux UI component first.

Avoid duplicated Blade.

---

# Performance

Prefer:

Cache

Chunk

Lazy Collection

Queue

Avoid:

Nested Loop

Repeated Query

Repeated Component

---

# Security

Never:

Store plain password

Expose Attendance Code

Disable validation

Bypass authorization

---

# Logging

Important action must be logged.

Examples:

Import

Export

Delete

Attendance

QR Generation

Permission

---

# Documentation

Every feature must update:

FEATURE.md

CHANGELOG.md

TODO.md

If architecture changes:

DATABASE.md

ARCHITECTURE.md

DECISION.md

---

# Git

One feature

One commit.

Commit message follows Conventional Commits.

Examples

feat:

fix:

refactor:

docs:

test:

chore:

---

# Testing

Feature is NOT complete until:

- Business Logic done
- Validation done
- Responsive checked
- Dark Mode checked
- Documentation updated
- Test passed

---

# AI Rules

AI must:

Read CURRENT_STATE.md first.

Never modify unrelated files.

Never refactor whole project without approval.

Keep changes minimal.

Update documentation when required.

Think before coding.

Preserve backward compatibility whenever possible.
