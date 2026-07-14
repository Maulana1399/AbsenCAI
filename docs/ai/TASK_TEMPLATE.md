# TASK TEMPLATE

Version: 1.0

Project

KJA Event Manager

---

# Purpose

Semua task yang diberikan kepada AI wajib mengikuti template ini.

Tujuan:

- Konsisten
- Mengurangi token
- Meminimalisir perubahan tidak relevan
- Memudahkan review

---

# Template

## Task

Nama task.

Example

Implement Attendance Code

---

## Goal

Apa tujuan task.

Contoh

Implement Attendance Code tanpa mengubah business flow yang sudah ada.

---

## Background

Mengapa task ini diperlukan.

---

## Scope

Yang boleh diubah.

Example

- Attendance
- QR
- Service

---

## Out of Scope

Yang tidak boleh diubah.

Example

- Registration
- Dashboard
- Database V2

---

## Read Documentation

AI wajib membaca

CURRENT_STATE.md

CONTEXT.md

SERVICE_PLAN.md

ENUM_PLAN.md

RULES.md

FEATURE.md

---

## Files Allowed

Daftar file yang boleh diubah.

---

## Files Forbidden

Daftar file yang tidak boleh diubah.

---

## Constraints

Contoh

- Do not rename Model
- Do not modify migration
- Do not change routes
- Keep backward compatibility

---

## Acceptance Criteria

Checklist keberhasilan.

Example

☐ Attendance Code dibuat

☐ Validation selesai

☐ Dark Mode tetap berjalan

☐ Tidak merusak fitur lama

---

## Testing

Manual Test

Feature Test

Unit Test

---

## Documentation Update

Update:

CHANGELOG.md

TODO.md

FEATURE.md

Jika diperlukan.

---

## Expected Output

Summary

Changed Files

Testing Result

Known Limitation

Recommendation

---

# AI Reminder

One Task

↓

One Goal

↓

One Commit

Never combine unrelated features.