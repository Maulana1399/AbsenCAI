# DATABASE V2

Version: 2.0 (Planning)

Project

KJA Event Manager

Status

Architecture Design

---

# Purpose

Database V2 dirancang untuk mendukung:

- Multi Event
- Multi Competition
- Multi Venue
- Multi Organization
- Long-term Person Database

Database ini TIDAK akan langsung diimplementasikan.

Database V1 tetap digunakan sampai proses refactor selesai.

---

# Design Principles

- Person First
- Event Driven
- Modular
- Reusable
- Scalable
- Backward Compatible

---

# High Level Architecture

Organization

↓

Event

↓

Participation

↓

Attendance

↓

Competition

↓

Score

↓

Certificate

---

# Core Entities

## Organization

Represents an organization.

Examples

- KJA
- School
- Community
- Foundation

One organization has many Events.

---

## Person

Master data.

One person only exists once.

Example

Person

↓

Can become:

- Participant
- Committee
- Judge
- Volunteer
- Official

Person never belongs directly to Event.

---

## Event

Represents one activity.

Examples

CAI 2026

Festival

Seminar

Training

Competition

Each Event has:

- Sessions
- Venues
- Categories
- Competitions

---

## Participation

Bridge between Person and Event.

Contains:

- Role
- Status
- Registration Date
- Attendance Code

This replaces direct relation between Person and Attendance.

---

# Attendance

Attendance belongs to Participation.

Attendance contains:

- Session
- Time
- Status

Status

Present

Permission

Absent

Late

---

# Session

Each Event has many Sessions.

Example

Day 1 Morning

Day 1 Afternoon

Closing

---

# Venue

Examples

Main Hall

Mosque

School

Field

One Event can have multiple Venues.

---

# Category

Examples

SD

SMP

SMA

Adult

Open

---

# Competition

Examples

Silat

Pidato

Adzan

Football

One Event has many Competitions.

---

# Judge

Judge belongs to Competition.

Future module.

---

# Score

Stores scoring result.

Future module.

---

# Certificate

Stores generated certificate.

Future module.

---

# Violation

Stores participant penalty.

Future module.

---

# Audit Log

Stores every important activity.

Examples

Registration

Attendance

Delete

Export

Import

Login

---

# Notification

Future module.

Email

WhatsApp

Telegram

Push Notification

---

# File Storage

Database stores metadata only.

Actual files stored in:

Nextcloud

TrueNAS

---

# Current Database (V1)

Desa

↓

Kelompok

↓

Regu

↓

Peserta

↓

Absensi

---

# Target Database (V2)

Organization

↓

Person

↓

Participation

↓

Attendance

↓

Report

Future

↓

Competition

↓

Score

↓

Certificate

↓

Violation

---

# Identity Strategy

Every Person has:

Universal ID

Never changes.

Every Participation has:

Attendance Code

Can be regenerated.

Every Event has:

Participant Number

Optional.

---

# Relationships

Organization

1:N

Event

---

Event

1:N

Participation

---

Person

1:N

Participation

---

Participation

1:N

Attendance

---

Event

1:N

Session

---

Event

1:N

Venue

---

Event

1:N

Competition

---

Competition

1:N

Judge

---

Competition

1:N

Score

---

Participation

1:N

Violation

---

Participation

1:N

Certificate

---

# Migration Strategy

Phase 1

Keep current database.

Phase 2

Create new tables.

Phase 3

Data migration.

Phase 4

Compatibility layer.

Phase 5

Remove deprecated tables.

No breaking migration allowed.

---

# Compatibility

Current CAI features must continue to work during migration.

Refactor gradually.

---

# Future Scalability

Target Capacity

Organizations

Unlimited

Events

Unlimited

Persons

100,000+

Participation

1,000,000+

Attendance

10,000,000+

---

# AI Notes

Never generate migration directly from this document.

This document is architecture only.

Migration requires approval.