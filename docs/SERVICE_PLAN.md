# SERVICE PLAN

Version: 1.0

Project

KJA Event Manager

Status

Planning

---

# Purpose

Seluruh business logic KJA Event Manager harus berada di dalam Service Layer.

Service menjadi pusat seluruh proses bisnis.

Model hanya menyimpan:

- Relationship
- Scope
- Accessor
- Mutator
- Cast

Livewire hanya bertugas:

- Input
- Validation
- Call Service
- Return Response

---

# Architecture

User

↓

Livewire

↓

Action (optional)

↓

Service

↓

Model

↓

Database

---

# Service Rules

Every Service has ONE responsibility.

Services may call another Service.

Services must never render UI.

Services should never know Blade or Livewire.

Services return data only.

---

# Core Services

## AttendanceService

Status

🟢 COMPLETE — `app/Services/Attendance/AttendanceService.php`

Purpose

Manage attendance lifecycle.

Responsibilities

- Scan attendance
- Validate attendance
- Prevent duplicate attendance
- Store attendance
- Attendance history
- Attendance summary

Methods

scan()

manualAttendance()

validateAttendance()

checkDuplicate()

getHistory()

getSummary()

Dependencies

Attendance

Session

Person

Future

Attendance Code

QR Validation

Offline Sync

---

## RegistrationService

Status

🟢 COMPLETE — `app/Services/Registration/RegistrationService.php`

Purpose

Manage participant registration.

Responsibilities

- Register person
- Re-registration
- Search person
- Validate registration
- Update participant status

Methods

register()

reRegister()

cancel()

search()

validate()

Dependencies

Person

Participation

---

## PlacementService

Status

🟢 COMPLETE — `app/Services/Placement/PlacementService.php`

Purpose

Automatic participant placement.

Responsibilities

- Auto NIP
- Auto Group
- Auto Team
- Capacity check

Methods

generateNIP()

assignGroup()

assignTeam()

leastFilledGroup()

Dependencies

Group

Person

Settings

---

## QRService

Status

🟢 COMPLETE — `app/Services/QR/QRService.php`

Purpose

Manage QR Code generation.

Responsibilities

Generate QR

Regenerate QR

Validate QR

Methods

generate()

regenerate()

validate()

Future

Attendance Code

Encrypted QR

---

## DashboardService

Status

Medium Priority

Purpose

Dashboard calculation.

Responsibilities

Statistics

Summary

Chart

Progress

Methods

overview()

todayAttendance()

registrationProgress()

groupStatistic()

Future

Realtime Dashboard

---

## ImportService

Status

Medium Priority

Responsibilities

Excel Import

Validation

Duplicate Detection

Error Report

Methods

importPerson()

importGroup()

validateFile()

generateReport()

---

## ExportService

Status

Medium Priority

Responsibilities

Excel Export

PDF Export

QR Export

Attendance Export

Methods

attendanceExcel()

attendancePdf()

qrPdf()

registrationExcel()

---

## PermissionService

Status

Medium Priority

Responsibilities

Leave Permission

Approve

Reject

Print Letter

Methods

create()

approve()

reject()

history()

Future

Workflow Approval

---

## ReportService

Status

Medium Priority

Responsibilities

Attendance Report

Registration Report

Violation Report

Statistics

Methods

attendance()

registration()

summary()

daily()

---

## AuditService (ActivityLogService)

Status

🟢 COMPLETE — `app/Services/Audit/ActivityLogService.php`

Responsibilities

Record important activities.

Methods

log()

login()

attendance()

export()

delete()

---

# Future Services

CompetitionService

ScoringService

CertificateService

VenueService

NotificationService

MediaService

VolunteerService

FinanceService

InventoryService

TransportationService

AccommodationService

OrganizationService

---

# Service Dependency

Registration

↓

Placement

↓

QR

↓

Attendance

↓

Dashboard

↓

Report

---

# Forbidden

Service MUST NOT

- render Blade
- return View
- use session directly
- contain HTML
- contain CSS
- access Request globally

---

# Allowed

Service MAY

- call another Service
- use Transaction
- use Cache
- dispatch Queue
- throw Exception

---

# Future Refactor Order

Phase 1

PlacementService

RegistrationService

AttendanceService

---

Phase 2

DashboardService

ImportService

ExportService

---

Phase 3

PermissionService

AuditService

ReportService

---

Phase 4

CompetitionService

ScoringService

CertificateService

---

# Definition of Complete

A Service is complete when:

- Single Responsibility
- Tested
- Reusable
- Documented
- No UI dependency
- No duplicated logic

---

# Notes

Current project still contains business logic inside Livewire.

During future development, every modified feature should gradually move its business logic into the corresponding Service.

Do NOT refactor everything at once.

Use incremental refactoring.