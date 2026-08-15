# UAT COMPETITION DUMMY — DATA REPORT

> Event dummy khusus UAT fitur Competition, dibuat di database aktif yang dapat dijangkau dari sandbox.
> **Database yang dipakai:** `database/database.sqlite` (dev DB; koneksi MariaDB di `.env` tidak dapat dijangkau dari sandbox — `Connection refused`). DB dev dibawa ke schema terbaru via **migrasi inkremental additive** (bukan migrate:fresh / db:wipe / reset), lalu di-seed.

- **Tanggal:** 2026-08-14 (wall-clock sandbox 2026-08-13)
- **Safety:** tidak ada event existing (sebelumnya kosong), tidak ada Person/Participation existing yang diubah, `regus` tidak tersentuh (count = 0). Backup DB dibuat: `database/database.sqlite.backup.pre-UAT-dummy-20260813_231604`.
- **Idempoten:** seeder aman dijalankan ulang (re-run = 0 duplikat baru; teams di-regenerate oleh auto formation).

---

## Event

| Field | Value |
|---|---|
| Event ID | **1** |
| Name | UAT Competition Dummy |
| Slug | `uat-competition-dummy` |
| Event type | `competition` |
| Status | `active` |
| is_default | `false` — **catatan:** tabel `events` tidak memiliki kolom `is_default` pada schema saat ini, sehingga tidak dapat diset (tidak ada kolom untuk itu). |

## Categories

| ID | Name | Code |
|---|---|---|
| 1 | UAT Lomba Individu | `uat-individu` |
| 2 | UAT Lomba Beregu | `uat-beregu` |

## Competition Classes (5 format)

| Class ID | Name | Format | Status | is_active | Category |
|---|---|---|---|---|---|
| 1 | UAT Individual Heat | `individual_heat` | registration_open | 1 | 1 |
| 2 | UAT Individual Mass | `individual_mass` | registration_open | 1 | 1 |
| 3 | UAT Individual vs Individual | `individual_vs_individual` | registration_open | 1 | 1 |
| 4 | UAT Team vs Team | `team_vs_team` | registration_open | 1 | 2 |
| 5 | UAT Team Mass | `team_mass` | registration_open | 1 | 2 |

Semua class terhubung ke event #1 (`event_id = 1`), gender `M` (campuran).

## Persons (dummy, baru dibuat — `people` sebelumnya kosong)

| Person ID | Nama | Kelompok (id) |
|---|---|---|
| 1–10 | UAT Competition 01–10 | KM 7 (#1) |
| 11–18 | UAT Competition 11–18 | KM 10 (#2) |
| 19–24 | UAT Competition 19–24 | Kariangau (#4) |

Total **24** Person (`UAT Competition 01`–`24`), jenis kelamin bergantian L/P, desa_id = 1, tanggal_lahir valid.

## Participation (event-scoped)

- 24 baris `participations`, ID **#1..#24**, seluruhnya `event_id = 1` (tidak ada Participation lintas event).

## Registration (satu Person boleh ikut banyak lomba)

| Class | Registrations | Registration IDs |
|---|---|---|
| 1 UAT Individual Heat | 5 | 1–5 |
| 2 UAT Individual Mass | 5 | 6–10 |
| 3 UAT Individual vs Individual | 4 | 11–14 |
| 4 UAT Team vs Team | 18 | 15–32 |
| 5 UAT Team Mass | 24 | 33–56 |

Total **56** registrations.

**Overlap (bukti "satu Person banyak lomba", tanpa duplicate Person):**
- UAT Competition 01, 02, 03, 04 → masing-masing ikut **4 lomba** (Individual Heat + Individual vs Individual + Team vs Team + Team Mass).
- UAT Competition 10 → 3 lomba. Dst.

## Teams (hasil auto team formation)

Team dibentuk per Kelompok (bukan Regu; `regus` tetap 0).

| Team ID | Name | Class / Format | Players | Substitutes |
|---|---|---|---|---|
| 6 | KM 7 | UAT Team vs Team / team_vs_team | 8 | 2 |
| 7 | KM 10 | UAT Team vs Team / team_vs_team | 8 | 0 |
| 8 | KM 7 | UAT Team Mass / team_mass | 6 | 4 |
| 9 | KM 10 | UAT Team Mass / team_mass | 6 | 2 |
| 10 | Kariangau | UAT Team Mass / team_mass | 6 | 0 |

- **Team vs Team:** kelompok terkecil = KM 10 (8 peserta) → team size **8**. KM 7 (10 peserta) → 8 pemain + 2 cadangan. ✅
- **Team Mass:** kelompok terkecil = Kariangau (6 peserta) → team size **6**. KM 7 → 6+4, KM 10 → 6+2, Kariangau → 6+0. ✅
- Total team members: **42** (18 + 24).

**Auto team formation: BERHASIL** (2 team team_vs_team + 3 team team_mass).

## Expected UAT — status

| Item | Status |
|---|---|
| Event Competition dapat dibuka | ✅ Event #1 aktif, tipe competition → `/events/1/competition` |
| Competition class tampil | ✅ 5 class (is_active) |
| 5 format tampil dengan benar | ✅ format di `competition_classes.format` + label `CompetitionFormat` |
| Registrasi peserta | ✅ 56 registrations |
| Satu Person ikut beberapa lomba | ✅ (UAT Competition 01–04 → 4 lomba) |
| Team formation | ✅ 5 team |
| Player | ✅ (8 / 8 / 6 / 6 / 6) |
| Substitute | ✅ (2 / 0 / 4 / 2 / 0) |
| Shuffle | ⚙️ siap diuji di halaman Teams (`competition.teams`) |
| Pindah player ↔ substitute | ⚙️ siap diuji |
| Hapus member | ⚙️ siap diuji |
| Event isolation | ✅ seluruh data `event_id = 1` |
| Permission / event membership | ⚙️ akses via Super Admin / Admin / Event Membership |

## Akses

Login sebagai Super Admin / Admin, buka Platform Dashboard → pilih event **UAT Competition Dummy** → menu Competition (Dashboard, Registrasi, Teams, Match Center, dll).

## Files / Safety

- Seeder: `database/seeders/UatCompetitionSeeder.php` (idempotent; bisa dipakai ulang, termasuk di MariaDB via `php artisan db:seed --class=UatCompetitionSeeder`).
- Migrasi yang dijalankan pada dev DB: 6 migration additive terbaru (08-18 s/d 08-21) — tidak ada reset/wipe/fresh.
- Backup: `database/database.sqlite.backup.pre-UAT-dummy-20260813_231604`.
- Tidak menyentuh: event lain (tidak ada), Person/Participation non-dummy (tidak ada), Regu (count 0), Competition non-dummy.
