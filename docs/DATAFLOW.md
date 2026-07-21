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

Reverse direction (peserta → Person) via RegistrationService::updateParticipant():
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