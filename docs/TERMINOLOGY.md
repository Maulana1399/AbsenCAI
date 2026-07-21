# TERMINOLOGY

## Master Data

Data referensi global yang reusable lintas event.
Tidak memiliki `event_id` — independen dari event context.

Global Master Data:

Person
Desa
Kelompok

Legacy CAI Operational (bukan Master Data):

Regu

Regu adalah struktur operasional CAI yang masih digunakan oleh modul legacy.
Akan dipindahkan ke event-scoped configuration pada refactor terpisah.
Route `/regu` tetap tersedia untuk backward compatibility.

---

## Event-Scoped Data

Data yang terikat pada event tertentu.
Memiliki `event_id` — tidak reusable lintas event.

Contoh:

Venue
CategoryDefinition
SesiAbsensi
Participation

---

## Person

Master human data. Source of truth untuk identitas global (nama, jenis_kelamin, desa, kelompok).

- Person (L/P) ↔ peserta legacy (Laki - Laki/Perempuan) — dikonversi via `PlacementService::normalizePersonGender()`
- Jika Person memiliki `LegacyPesertaMapping`, edit Person akan sync identity fields ke peserta legacy via `PersonLegacySyncService`
- Field yang disinkronkan: nama, jenis_kelamin, desa_id, kelompok_id
- Field yang TIDAK disinkronkan: nip, regu_id, participant_number, attendance_code

---

## Participation

Relationship between Person and Event.

---

## Event

Activity.

Example:

CAI

Festival

Seminar

Competition

---

## Category

Age Category.

---

## Competition

Cabang lomba.

---

## Group

General term.

Can become:

Regu

Tim

Kontingen

---

## Session

Attendance Session.

---

## Attendance

Presence Record.

---

## Attendance Code

Unique random code used in QR.

---

## Universal ID

Permanent Person ID.

Never changes.