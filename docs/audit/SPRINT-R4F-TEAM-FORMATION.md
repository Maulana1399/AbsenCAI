# SPRINT R4F — COMPETITION TEAM FORMATION + PJ TEAM MANAGEMENT

> Implementasi terbatas R4F. Sebagian besar fitur (formation, group isolation, player/substitute, event/class scoping, UI PJ) **sudah ada dari R4A** — sprint ini **audit → reuse → fill gap**. Gap yang diisi: **manual protection (jangan timpa diam-diam)** + **ringkasan UI** + test R4F.
> R4E bracket **tidak disentuh**.

- **Tanggal:** 2026-08-14
- **Baseline suite:** 2349 passed / 6155 assertions · **Final suite:** 2353 passed / 6189 assertions / 0 failed / 0 skipped

---

## 1. Existing Team Architecture (reuse R4A)

```
CompetitionClass (format team_vs_team / team_mass)
   → CompetitionTeam (event_id, class_id, name, kelompok_id, is_active; unique (class,name) & (class,kelompok))
   → CompetitionTeamMember (team_id, registration_id, is_substitute, sort_order; unique (team,registration))
```
- `CompetitionTeamFormationService::formForClass(eventId, classId, ?teamSize, force)` — grup by `person.kelompok_id`, team size = kelompok terkecil, players/substitutes, transactional, tanpa Regu.
- `CompetitionTeamService` — add/remove/setSubstitute/shuffle/listForClass + validasi (kelas sama, kelompok sama, satu-team-per-lomba).
- UI `Competition/Team/Index` — pilih class, formasi, daftar team (players/substitutes), tambah/hapus/pindah/shuffle.
- Group source: `CompetitionRegistration → Participation → Person.kelompok_id` (actual relationship; tidak mengarang).

## 2. Team Formation Logic (R4F)

`formForClass` sudah memenuhi kontrak: event/class scoped, 1 kelompok = 1 team, team size = kelompok terkecil, players = teamSize, sisanya substitutes, deterministik (order id), transactional, tanpa Regu/PlacementService, tidak mencampur kelompok, tidak duplicate.

## 3. Gap yang Diisi Sprint Ini

### a. Manual Protection (R4F J) — `CompetitionTeamFormationService`
- Tambah param `bool $force = false`.
- Guard baru: bila sudah ada team berisi member (`whereHas('members')`) dan `!force` → `ValidationException` ("Tim sudah dibentuk… gunakan rebuild eksplisit").
- Guard existing (team dipakai jadwal/hasil) tetap.
- Rebuild hanya lewat `force: true` (aksi eksplisit) — **tidak ada timpaan diam-diam**.

### b. UI Rebuild + Ringkasan — `Competition/Team/Index` + blade
- `autoFormation()` → formasi awal (ditolak bila sudah dibentuk).
- `rebuildFormation()` → `force: true` + `wire:confirm` warning ("Membentuk ulang akan mengganti pembagian").
- `isFormed` + `summary` (Total Team, Team Size) ditampilkan.
- Button "Bentuk Tim Otomatis" / "Bentuk Ulang Tim" (dengan warning).

## 4. Eligibility / Status

- `competition_registrations` tidak punya status eligible/suspended/cancelled; kontrak existing: semua registration = eligible. **Tidak mengarang status baru.** (Participant `status_registrasi` di Participation adalah flow registrasi, bukan eligibility team.)

## 5. Event / Class Scoping

- Event: `formForClass` → `CompetitionClass::where('event_id', $eventId)->findOrFail`; komponen scopes `CompetitionTeam::where('event_id', activeEvent)`; route + gate event. (tested)
- Class: formation per `classId`; team unique per (class, kelompok); test F (Mahasiswa KM 7 ≠ Umum KM 7) ditambahkan.

## 6. Database Changes

- **TIDAK ADA migration / schema change.** Reuse `competition_teams` + `competition_team_members` (R4A). Unique `(class,kelompok)` & `(team,registration)` sudah ada.

## 7. UI Changes

- `resources/views/livewire/competition/team/index.blade.php` — ringkasan (Total Team / Team Size) + tombol rebuild dengan warning.
- `app/Livewire/Competition/Team/Index.php` — `rebuildFormation()`, `isFormed`, `summary`.

## 8. Tests

### Baru `CompetitionTeamFormationR4FTest` (4):
- **J** — re-form tanpa force ditolak; manual protection; rebuild eksplisit berhasil.
- **F** — kelompok sama di dua class → team terpisah per class (no cross-class mixing).
- **G** — satu Person di dua team-competition → valid; Person tetap satu.
- **A** — regression team size = kelompok terkecil (KM7=10,KM10=8,KM12=15,KM15=12 → 8; 8+2 / 8+0 / 8+7 / 8+4).

### Update `CompetitionTeamFoundationTest`
- `auto formation is transactional on re-run` — re-run tanpa force kini ditolak (manual protection); rebuild via `force: true`.

### Sudah tercakup R4A (tidak di-duplicate)
- B one group one team · C no duplicate (re-run) · D group isolation (add member kelompok lain DENY) · E event isolation · H duplicate team in same competition DENY · I player↔substitute move · K event authorization (teams page event lain 403) · PJ names/move/replace/shuffle.

## 9. Files Changed

- `app/Services/Competition/CompetitionTeamFormationService.php` (+ force + manual protection)
- `app/Livewire/Competition/Team/Index.php` (+ rebuildFormation, isFormed, summary)
- `resources/views/livewire/competition/team/index.blade.php` (ringkasan + rebuild button)
- `tests/Feature/Competition/CompetitionTeamFormationR4FTest.php` (baru, 4 test)
- `tests/Feature/Competition/CompetitionTeamFoundationTest.php` (update re-run test → force)

## 10. Result

- **Competition dir:** 185 passed (501 assertions).
- **Full suite:** 2353 passed / 6189 assertions / 0 failed / 0 skipped.
- **Pint:** 4 files, 3 style fixes (non-logik); test ulang hijau.
- Cache cleared.

## 11. Check

- Migration = 0 · Schema = 0 · Seeder = 0 · Production/UAT data = 0 · Design C = 0 · Regu = 0 · `CompetitionTeam` contract tetap · **R4E bracket = 0 (tidak disentuh)** · R4D official flow tetap.

## 12. Remaining Gaps (dokumentasi)

- `competition_registrations` belum punya status eligible/suspended/cancelled → eligibility = semua terdaftar (ikuti kontrak existing).
- Multi-team per kelompok (2 team dari 1 kelompok) tidak didukung — by design (1 grup = 1 team) kecuali requirement khusus masa depan.
- UI "replace player" memakai add/remove member (available = peserta kelas tersisa) — sudah ada; placeholder dropdown lanjutan opsional.
