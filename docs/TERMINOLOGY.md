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

## User / Account

Akun login (email/username + password) pada tabel `users`.
**User ≠ Person.** Person bersifat **opsional** bagi User (`users.person_id` nullable).

- User TANPA Person = valid (contoh: Guest/PJ).
- User tidak wajib memiliki Person untuk login/change password/confirm password/manage account.
- Fitur yang memang membutuhkan Person (partisipasi, absensi) harus explicit-guard, bukan asumsi `$user->person` selalu ada.

---

## Event Membership

Hubungan User/Person ke Event + Role + Permission.

Sebelumnya berbasis Person saja (`event_committee_assignments.person_id`). Sejak implementasi Event Membership, `event_committee_assignments` mendukung **dua jalur**:

```
User / Account
 ├── Person (optional) ──► event_committee_assignments.person_id
 │
 └── Event Membership ──► event_committee_assignments.user_id
       ├── Event
       ├── Role       (EventRole.code + permissions)
       └── Permission (EventRole.permissions JSON)
```

Prinsip: **User membership OR Person membership**. Event access di-resolve dari `assignment.user_id === user.id` ATAU `assignment.person_id === user.person_id`. Role **global** (`users.role`) TIDAK menjadi satu-satunya sumber event access.

---

## Global Role vs Event-Scoped Access

- **Global (platform) role** — `users.role`: `super_admin` (bypass semua gate), `admin` (bypass ability event). Ditambah role akun event-scoped: `event_chair`, `guest` (tanpa akses global; akses hanya dari membership).
- **Event-scoped access** — Event Membership (`event_committee_assignments`) + `EventRole.permissions`.

Contoh: Ahmad role = `event_chair`, membership hanya Event CAI 2026 → CAI 2026 ALLOW; Pengajian 2026 & event lain DENY.

---

## Guest

Akun login tanpa Person dan tanpa akses global. Contoh:

```
Guest
email       = pj@example.com
password    = ...
role        = guest
person_id   = NULL
Event Membership:
  event_id  = CAI 2026
  role      = guest (EventRole)
```

Guest hanya dapat mengakses event yang diberikan lewat membership.

---

## Event Chair

Role event-scoped (contoh: `users.role = event_chair`, EventRole code `event_chair`). Hanya dapat mengakses event yang ditugaskan lewat membership.

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

Cabang lomba. (V2 — akan ditangani oleh Competition Engine)

---

## Group

General term.

Can become:

Regu

Tim

Kontingen

---

## Competition (lomba)

`CompetitionClass` = lomba (contoh: "Tarik Tambang Putra", "Pingpong Mahasiswa"). Event-scoped.
`CompetitionCategory` = pengelompokan lomba.

### Competition Class / Category

Satu lomba dapat memiliki kategori (Mahasiswa/Pekerja/Umum) sebagai konteks. Aturan usia/kategori lomba berada pada Competition/Class, **bukan** pada Person. Person tetap hanya memiliki `tanggal_lahir`.

### Competition Format (5 format)

`competition_classes.format`:

- `individual_heat` — peserta individu bertanding bergantian → ranking (waktu).
- `individual_mass` — banyak peserta bersamaan → satu heat → ranking → juara.
- `team_vs_team` — team melawan team (bisa bracket).
- `team_mass` — semua team bersamaan → satu heat → ranking.
- `individual_vs_individual` — satu lawan satu (bisa bracket).

Jumlah juara adalah konfigurasi lomba (bukan format).

### Competition Team vs Regu

- **Competition Team** = domain baru: `competition_teams` (event-scoped), satu kelompok = satu team per lomba, anggota = players + substitutes (`competition_team_members` → `competition_registration_id`).
- **Regu** = master data operasional CAI lama (global, tidak dipakai untuk Competition). Competition Team **tidak memakai** `regus` / `regu_id` / `PlacementService::leastFilledRegu()`.

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