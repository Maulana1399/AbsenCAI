# COMPETITION MIGRATION — PRODUCTION READINESS AUDIT

> Audit migration Competition sebelum dijalankan di MariaDB production.
> **Tidak menjalankan `php artisan migrate`.** Tidak mengubah DB production. Tidak mengubah code.
> Source of truth: migration file + migration sebelumnya + model + test + compiled SQL (MariaDB grammar).

- **Tanggal audit:** 2026-08-14 (per task). Wall-clock sandbox: 2026-08-13.
- **Migration:** `database/migrations/2026_08_21_000001_add_competition_teams_and_format.php`
- **Kesimpulan:** **PASS — aman dijalankan** · **TIMESTAMP: ACCEPTED**

---

## 1. Executive Summary

Migration Competition (`2026_08_21_000001_add_competition_teams_and_format.php`) **layak dijalankan di production**:

- **Additive** — hanya menambah 2 kolom (`competition_classes.format`, `.status`) dan 2 tabel baru (`competition_teams`, `competition_team_members`). Tidak ada DROP COLUMN / DROP TABLE / hapus data pada `up()`.
- **Timestamp future-dated** — **ACCEPTED**. Konsisten dengan konvensi repository: sudah ada banyak migration ber-tanggal ≥ 2026-08-14 (08-14/15/16/17/18/19/20) sebelum migration ini, dan `2026_08_21` mengikuti pola sprint/release-dating yang sama. Migration belum pernah dijalankan di DB persisten mana pun; urutan eksekusi (terakhir) memenuhi seluruh dependency; tidak ada referensi code/test ke nama file.
- **MariaDB compatible** — SQL yang dikompilasi grammar MariaDB diverifikasi langsung (tidak ada duplicate index/FK/unique; key length aman; utf8mb4; tidak ada SQLite-only behavior).
- **Model ↔ migration sinkron** — tabel, kolom, casts, relasi, unique sesuai service.
- **Test** — test suite (RefreshDatabase, SQLite) mengeksekusi migration ini dan seluruh contract lulus.

---

## 2. Migration Filename

```
database/migrations/2026_08_21_000001_add_competition_teams_and_format.php
```

- Prefix timestamp: `2026_08_21_000001`.
- Slug: `add_competition_teams_and_format`.
- Saat ini migration **terakhir** dalam urutan (timestamp terbesar).

---

## 3. Timestamp Analysis

| Pertanyaan | Evidence | Jawaban |
|---|---|---|
| 1. Future-dated disengaja? | Repo sudah memuat migration ber-tanggal ≥ 2026-08-14 (wall-clock 08-13, task 08-14): `2026_08_14/15/16/17(×5)/18/19/20`. Migration 08-18/19/20 ini pun future. `2026_08_21` melanjutkan konvensi yang sama. | **Ya — disengaja / konsisten** dengan pola sprint/release-dating repository. |
| 2. Dependency urutan? | Migration bergantung pada: `events`, `competition_classes`, `kelompoks`, `competition_registrations` — semuanya dibuat jauh sebelumnya. Tidak ada migration lain yang bergantung pada tabel team. Migration berjalan paling akhir. | **Tidak ada masalah urutan.** |
| 3. Ubah ke tanggal implementasi aman? | Aman secara urutan (dependency lebih awal). Namun **tidak perlu** — lihat #4–#6. Rename hanya menimbulkan churn kecil pada 3 baris dokumen audit. | **Aman, tetapi tidak disarankan (tanpa manfaat).** |
| 4. Pernah dijalankan di environment? | Migrations table pada repo `database/database.sqlite` tidak memuat `2026_08_20/21`. MariaDB production tidak dijangkau / tidak pernah di-migrate. Migration hanya pernah jalan pada SQLite ephemeral `/tmp` (test) dan `:memory:` (suite). | **Tidak — belum dijalankan di DB persisten mana pun.** |
| 5. Sudah masuk commit/repository? | `git` binary tidak tersedia di sandbox sehingga status commit tidak dapat diverifikasi langsung. File baru di working tree; tidak ada referensi nama file di code/test; hanya 3 referensi di `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md`. | **Tidak terverifikasi via git; tidak ada bukti dipush/diaplikasikan.** Karena belum diaplikasikan ke DB mana pun, rename (bila kelak dikehendaki) aman. |
| 6. Referensi langsung nama file? | `grep -rn "2026_08_21\|add_competition_teams"` → tidak ada referensi di code/test. Hanya dokumen audit (non-runtime). | **Tidak ada referensi runtime.** |

**Rekomendasi:** pertahankan timestamp `2026_08_21_000001`. **TIMESTAMP: ACCEPTED** — tidak perlu diubah. (Catatan: bila proyek kelak mengadopsi kebijakan ketat "tanggal migration = tanggal implementasi", rename sekarang masih aman karena belum diaplikasikan; perbarui 3 baris di `docs/audit/COMPETITION-IMPLEMENTATION-AUDIT.md` bila itu dilakukan.)

---

## 4. Schema Safety Audit

### `up()` — additive

| Objek | Aksi | Safety |
|---|---|---|
| `competition_classes.format` | ADD varchar(40) NULL default `individual_heat` | ✅ additive |
| `competition_classes.status` | ADD varchar(30) NULL default `registration_open` | ✅ additive |
| index `(event_id, status)` | ADD — nama `competition_classes_event_id_status_index` (baru, unik) | ✅ |
| `competition_teams` | CREATE (baru) | ✅ |
| `competition_team_members` | CREATE (baru) | ✅ |

Tidak ada: DROP TABLE, DROP COLUMN, UPDATE/DELETE data, truncate. Data existing `competition_classes` hanya mendapat default kolom baru.

### Foreign keys

| FK | onDelete | Valid |
|---|---|---|
| `competition_teams.event_id → events` | RESTRICT | ✅ |
| `competition_teams.competition_class_id → competition_classes` | RESTRICT | ✅ |
| `competition_teams.kelompok_id → kelompoks` | SET NULL (nullable) | ✅ |
| `competition_team_members.competition_team_id → competition_teams` | CASCADE | ✅ |
| `competition_team_members.competition_registration_id → competition_registrations` | RESTRICT | ✅ |

Semua tabel induk (`events`, `competition_classes`, `kelompoks`, `competition_registrations`) ada & dibuat lebih dulu. `kelompoks` global (tanpa event) — FK nullOnDelete aman.

### Unique constraints

- `uniq_class_team_name (competition_class_id, name)` — satu nama team per lomba.
- `uniq_class_team_kelompok (competition_class_id, kelompok_id)` — satu kelompok = satu team per lomba (NULL kelompok diizinkan banyak, konsisten MySQL & SQLite).
- `uniq_team_member (competition_team_id, competition_registration_id)` — anggota unik per team.

Asumsi service (`CompetitionTeamFormationService`, `CompetitionTeamService`) sesuai constraint di atas.

---

## 5. MariaDB Compatibility

Diverifikasi dengan **mengompilasi blueprint migration memakai `MariaDbGrammar`** (tanpa eksekusi):

- `competition_teams` → CREATE TABLE utf8mb4/utf8mb4_unicode_ci; FKs (restrict / set null); unique ×2; **index `competition_teams_event_id_index` muncul 1×** (Laravel menduplikasi FK-fluent-index dengan `->index()` eksplisit → grammar dedup → tidak ada "Duplicate key name"); key length aman (< 3072 bytes).
- `competition_team_members` → CREATE TABLE + FK cascade/restrict + unique `uniq_team_member` + index `competition_registration_id` — bersih.
- ALTER `competition_classes` → `add format ... after gender`, `add status ... after format`, `add index (event_id, status)` — bersih (kolom `gender` ada dari migration 08-08/10/11).
- `down()` → drop index + drop columns + drop tables (hanya untuk rollback; tidak dijalankan saat `migrate`).

Tidak ada: `PRAGMA`, raw SQLite, SQLite-only tipe. `after()` didukung MariaDB. Boolean → tinyint(1). Timestamp nullable default. Semua nama objek < 64 char.

**Catatan:** migration **belum pernah dieksekusi di MariaDB nyata**. Disarankan dry-run di staging: `php artisan migrate --pretend` (pratinjau SQL) atau jalankan di staging MariaDB sebelum production.

---

## 6. Model ↔ Migration Audit

| Model | Table | Kolom | Casts | Sesuai? |
|---|---|---|---|---|
| `CompetitionTeam` | `competition_teams` (default plural) | event_id, competition_class_id, name, kelompok_id, is_active | is_active → bool | ✅ |
| `CompetitionTeamMember` | `competition_team_members` (default plural) | competition_team_id, competition_registration_id, is_substitute, sort_order | is_substitute → bool; sort_order → int | ✅ |
| `CompetitionClass` | `competition_classes` (+ format, status) | fillable menambah format/status | is_active → bool | ✅ |

- Unique `(class, kelompok)` — dibutuhkan `CompetitionTeamFormationService` (1 team per kelompok). ✅
- Unique `(team, registration)` — dibutuhkan `CompetitionTeamService::addMember` (no-duplicate). ✅
- `event_id` pada `competition_teams` — event-scoped; `formForClass(eventId, classId)` memvalidasi `CompetitionClass::where('event_id', $eventId)`; team dibuat dengan `event_id` dari event tervalidasi. ✅ (diuji: "team belongs to the event of its class", "auto formation cannot run for class of another event").

---

## 7. Existing Schema Conflict Check

| Check | Hasil |
|---|---|
| Duplicate column (`format`/`status` di `competition_classes`) | ✅ Tidak ada — hanya migration ini yang menambahkannya |
| Duplicate index | ✅ `competition_classes_event_id_status_index` unik; `competition_teams_*` tabel baru |
| Duplicate constraint | ✅ `uniq_class_team_name`, `uniq_class_team_kelompok`, `uniq_team_member` baru |
| Table name conflict (`competition_teams`/`competition_team_members`) | ✅ Tidak ada migration lain yang membuat tabel ini |
| Foreign key conflict | ✅ FK baru menunjuk tabel induk existing; tidak menabrak FK lain |
| Enum/type incompatible MariaDB | ✅ varchar/tinyint(1)/timestamp/bigint unsigned — semua didukung MariaDB |

---

## 8. Test Contract

- Test Competition team (`tests/Feature/Competition/CompetitionTeamFoundationTest.php`, 18 test) memakai `RefreshDatabase` (SQLite `:memory:`) → **mengeksekusi migration `2026_08_21` secara nyata** dan memvalidasi schema yang dihasilkan (tabel, kolom, unique) melalui model/service.
- Test **tidak diubah** agar lulus — suite hijau sebelum & sesudah task (baseline 2265/5881 → final 2283/5932, 0 failed).
- Contract yang diuji terhadap schema migration: 5 format + status; auto formation 1 team/kelompok; players/substitutes; unique per kelas/kelompok/team; event-scoping.
- Catatan: test memakai SQLite; kompatibilitas MariaDB diverifikasi terpisah via compiled SQL (bagian 5) karena MariaDB production tidak dapat dijangkau dari sandbox.

---

## 9. Required Changes

**Tidak ada perubahan wajib.** Migration aman apa adanya.

Catatan opsional / non-blocking (untuk keputusan, bukan prasyarat):
1. **Dry-run MariaDB** — jalankan `php artisan migrate --pretend` (atau staging MariaDB) sebelum production, karena belum pernah dieksekusi di MariaDB nyata.
2. **Edge-case nama team** — `uniq_class_team_name` per (class, name). Jika dua kelompok berbeda desa memiliki `kelompok_asal` sama dan ikut class yang sama, auto formation akan kena unique violation pada nama team (kelompok_asal unik hanya per (nama, desa)). Bukan bug migration; catatan domain untuk `CompetitionTeamFormationService` (mis. suffix nama team dengan desa) bila diinginkan.
3. **Rollback** — `down()` bersifat destruktif (drop kolom/tabel) — normal untuk rollback; tidak dijalankan saat `migrate`.

---

## 10. Migration Readiness

Checklist:

[✅] Migration timestamp aman — **TIMESTAMP: ACCEPTED** (`2026_08_21` konsisten dengan konvensi future-dated repository; tidak diaplikasikan; urutan aman; tanpa referensi runtime).
[✅] Migration schema aman — additive; tidak drop table/column/data pada `up()`.
[✅] MariaDB compatible — compiled SQL via `MariaDbGrammar` diverifikasi bersih.
[✅] Foreign key aman — restrict/set null/cascade benar; tabel induk ada.
[✅] Index/unique aman — tanpa duplikat; key length OK.
[✅] Model compatible — tabel/kolom/casts/relasi sinkron.
[✅] Existing Competition tidak rusak — hanya kolom baru + tabel baru; engine match/schedule/bracket existing tidak disentuh (115 test Competition tetap hijau).
[✅] Design C tidak berubah — `people`/`participations`/`event_attendances` tidak tersentuh.
[✅] Regu tidak berubah — `regus`/`regu_id`/`PlacementService` tidak tersentuh.

---

## KESIMPULAN

# PASS — aman dijalankan

`TIMESTAMP: ACCEPTED`

Migration `2026_08_21_000001_add_competition_teams_and_format.php` dapat dijalankan di MariaDB production tanpa perubahan. Timestamp future-dated dipertahankan (sesuai konvensi repository dan aman dari segi urutan/status aplikasi). Satu-satunya prasyarat non-teknis: lakukan dry-run/staging di MariaDB terlebih dahulu karena migration belum pernah dieksekusi pada engine production nyata.
