# ENUM PLAN

Version: 1.0

Project

KJA Event Manager

Status

Planning

---

# Purpose

Dokumen ini mendefinisikan seluruh Enum yang digunakan di dalam sistem.

Seluruh nilai tetap (fixed value) wajib menggunakan Enum.

Tujuan:

- Menghilangkan hardcoded string
- Mengurangi typo
- Mempermudah refactor
- Mempermudah AI memahami project
- Meningkatkan type safety

---

# Rules

Always use Enum when:

- Status
- Type
- Category
- Role
- Permission
- Action
- Priority

Never compare string directly.

Bad

if ($status == "Hadir")

Good

if ($status === AttendanceStatus::PRESENT)

---

# AttendanceStatus

Status

High Priority

Purpose

Attendance state.

Cases

PRESENT

PERMISSION

ABSENT

LATE

Future

SICK

EXCUSED

REMOTE

---

# RegistrationStatus

Status

High Priority

Cases

REGISTERED

RE_REGISTERED

CANCELLED

WAITING

VERIFIED

---

# RegistrationMethod

Status

Medium

Cases

ADMIN

SELF_REGISTER

IMPORT

API

---

# Gender

Status

High Priority

Cases

MALE

FEMALE

Future

OTHER

UNKNOWN

---

# ParticipantRole

Status

High Priority

Cases

PARTICIPANT

COMMITTEE

JUDGE

OFFICIAL

VOLUNTEER

SPEAKER

GUEST

---

# UserRole

Status

High Priority

Cases

SUPER_ADMIN

ADMIN

SECRETARIAT

EVENT_LEADER

DIVISION_LEADER

SCAN_OPERATOR

REGISTRATION_OPERATOR

JUDGE

VIEWER

PUBLIC

---

# EventStatus

Status

Medium

Cases

DRAFT

OPEN

RUNNING

FINISHED

ARCHIVED

CANCELLED

---

# EventType

Status

Medium

Cases

TRAINING

SEMINAR

COMPETITION

FESTIVAL

MEETING

CAI

OTHER

---

# SessionStatus

Status

Medium

Cases

OPEN

CLOSED

LOCKED

---

# PermissionStatus

Status

Medium

Cases

PENDING

APPROVED

REJECTED

RETURNED

---

# CertificateStatus

Future

Cases

DRAFT

GENERATED

SIGNED

PRINTED

SENT

---

# CompetitionStatus

Future

Cases

REGISTRATION

DRAWING

RUNNING

FINISHED

CANCELLED

---

# ScoreStatus

Future

Cases

DRAFT

SUBMITTED

VERIFIED

LOCKED

---

# QRStatus

Status

High Priority

Cases

ACTIVE

REVOKED

EXPIRED

REGENERATED

---

# ImportStatus

Status

Medium

Cases

SUCCESS

FAILED

PARTIAL

VALIDATING

---

# ExportStatus

Status

Low

Cases

QUEUED

PROCESSING

SUCCESS

FAILED

---

# AuditAction

Status

Medium

Cases

LOGIN

LOGOUT

REGISTER

UPDATE

DELETE

SCAN

IMPORT

EXPORT

GENERATE_QR

PRINT

---

# NotificationChannel

Future

Cases

EMAIL

WHATSAPP

TELEGRAM

PUSH

SMS

---

# Priority

General Enum

LOW

MEDIUM

HIGH

CRITICAL

---

# FeatureFlag

Future

Cases

MULTI_EVENT

COMPETITION

CERTIFICATE

API

OFFLINE_MODE

AI_ASSISTANT

---

# Enum Location

app/

Enums/

Attendance/

Registration/

System/

Event/

Competition/

Notification/

---

# Migration Strategy

Phase 1

Create Enum.

Phase 2

Replace hardcoded string.

Phase 3

Update validation.

Phase 4

Update export.

Phase 5

Remove legacy string comparison.

---

# AI Rules

Always use existing Enum.

Never create duplicated Enum.

Never compare string directly.

Never hardcode status.

Prefer Enum over constant.

---

# Notes

Current project still uses strings in several modules.

Refactor should be incremental.

Do not replace all strings in one commit.

One Enum per feature is recommended.