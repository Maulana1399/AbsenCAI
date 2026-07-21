# DATA FLOW

## Identity Sync (Person → Legacy Peserta)

Person CRUD (Master Data)
  │
  ├── Person (no mapping) → update only Person table
  │
  └── Person (has LegacyPesertaMapping)
        │
        ├── nama          → sync to peserta.nama
        ├── jenis_kelamin → sync to peserta.jenis_kelamin (L/P → Laki - Laki/Perempuan)
        ├── desa_id       → sync to peserta.desa_id
        ├── kelompok_id   → sync to peserta.kelompok_id
        │
        └── NOT synced:
            ├── nip               → immutable for mapped Person
            ├── regu_id           → not in Person schema
            ├── participant_number → event-scoped
            └── attendance_code    → event-scoped

## Authorization (RBAC Chain)

```
User (authenticated account)
  │
  ├── role (App\Enums\Role)
  │     └── determines WHAT the account may do (SuperAdmin, Admin, KetuaEvent, etc.)
  │
  ├── person_id → Person (optional)
  │     └── links account to canonical Person identity
  │
  ├── Person → EventCommitteeAssignment
  │     ├── event_id → Event (assigned event)
  │     └── event_role_id → EventRole (operational position, NOT RBAC)
  │     └── determines WHICH Event the User may access (KetuaEvent only)
  │
  └── Gate abilities (15 defined)
        ├── SuperAdmin → bypass via Gate::before()
        ├── Admin/Sekretariat/other roles → global access
        └── KetuaEvent → event-scoped (role + person + assignment = access)
```

## Activity Log

All user mutations are logged via `ActivityLogService`:
- User created/updated/deleted → `module: user`
- Role changed → `action: role_changed`
- Person link changed → `action: person_link_changed`
- Committee assignment created → via EventCommitteeService

## Identity Sync (Person → Legacy Peserta)
  ├── nama          → sync to Person.nama
  ├── jenis_kelamin → sync to Person.jenis_kelamin (konversi)
  ├── desa_id       → sync to Person.desa_id
  ├── kelompok_id   → sync to Person.kelompok_id ✅
  └── NOT synced: regu_id, participant_number, attendance_code

## Registration

Person

↓

Registration

↓

Participation

↓

Ready

---

## Attendance

Scan QR

↓

Attendance Code

↓

Validate

↓

Session

↓

Attendance Record

↓

Dashboard

---

## Import

Excel

↓

Validation

↓

Person

↓

Group

↓

Registration

---

## Report

Attendance

↓

Aggregation

↓

Export

↓

Excel / PDF

---

## Future Competition

Registration

↓

Competition

↓

Judge

↓

Score

↓

Ranking