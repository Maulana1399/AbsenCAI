# SPRINT — HEAT MANAGER + HEAT FORMAT BUILDER (PINDAH MENU, 2026-08-26)

> Sprint operator-facing untuk **Heat** (Individual Heat + Team Heat): format builder (peserta per
> heat + jumlah lolos per heat per babak), auto-generate heat per babak, auto-generate babak
> berikutnya dari kualifikasi, navigasi menu Heat sendiri. Dibangun di atas arsitektur multi-round
> heat (R4H) yang sudah ada — **tanpa** mengubah Bracket, CompetitionSchedule, identitas
> (registration/team), ranking, atau result_type.

- **Tanggal:** 2026-08-26
- **Hash sprint:** Sprint R4H berakhir tidak menyimpan `top_n` (parameter method) dan tidak
  menyediakan UI pembuatan heat/pembagian heat. Heat Manager menutup gap itu dengan **1 migration
  yang diizinkan** (`competition_heat_formats`) + service orchestrator baru di atas service existing.

---

## 1. Kontrak Heat Manager (daripada R4H §4 items 1–3)

1. **Auto-split peserta → multi-heat per round.** `generateRound()` membagi peserta kelas ke
   beberapa heat berkapasitas `participants_per_heat`, membuat `CompetitionSchedule` per heat
   (`sort_order = round*100 + heatIndex`, `required_participants = participants_per_heat`,
   status `Scheduled`, `canAutoReady()`), lalu mengisi semua `CompetitionScheduleEntry`
   (idempoten — tidak menimpa entry yang sudah ada).
2. **UI pembuatan schedule multi-round.** Menu **Heat** (`competition.heat.index`) memungkinkan
   operator menebak format per babak dan menekan "Generate Heat" → membuat heat babak tsb dari UI,
   tanpa perlu mengisi form Jadwal manual dengan `sort_order`.
3. **Penyimpanan parameter kualifikasi (`top_n`) + peserta per heat.** Kolom baru
   `competition_heat_formats` (per `competition_class_id` + `round`): `participants_per_heat` +
   `qualifiers_per_heat`. Advance/Ranking tetap memakai parameter eksplisit pada pemanggilan
   service (kontrak lama tidak berubah), tetapi UI Heat memakai format tersimpan sebagai default.

## 2. Kontrak tambahan (persyaratan sprint)

- **A. Semua kelas yang masuk Heat Manager** adalah `individual_heat` / `team_heat`
  (`CompetitionHeatManagerService::SUPPORTED_FORMATS`). Kelas lain ditolak.
- **B. Auto-split:** 28 peserta, format 7 peserta/heat → 4 heat (101–104) terisi 7-7-7-7.
  Sisa (30 peserta → 7/heat) → heat terakhir berisi sisa (7-7-7-7-2). Tidak pernah ada heat kosong.
- **C+D. Next-round auto-generate:** babak 1 selesai (semua heat lengkap + `advanceRound` top 3)
  → 12 lolos (4 heat × 3) → format babak 2 (6/heat, 3 lolos/heat) → 2 heat (201–202) berisi 6-6.
- **E. Identitas tidak pernah tertukar:** hasil per `competition_registration_id` (individu) /
  `competition_team_id` (team). Ranking mengikuti `CompetitionResultType::sortDirection()`.
- **F. Input hasil read-back:** operator mengisi hasil via `OutcomeManager` (Livewire
  `heatResults[]`), nilai waktu tetap menempel ke peserta yang diketik (uji baca-balik db).
- **G. Round belum bisa lanjut bila ada kompetitor belum terisi hasil** → `not_all_finished`,
  tidak dibuat schedule/entry baru.
- **H. Single-round:** advance terakhir → `no_next_format` (babak berikutnya tidak punya format),
  TIDAK pernah memfabrikasi babak/schedule/entry baru.
- **Backward compat:** Jadwal manual (`Schedule/Index`), `OutcomeManager`, Match Center, Bracket,
  `CompetitionSchedule` (status lifecycle + `canAutoReady`), hasil `competition_heat_results`,
  ranking & `result_type` tetap berfungsi tanpa perubahan perilaku.

## 3. Status Implementasi

- ✅ Migration `2026_08_26_000001_create_competition_heat_formats_table.php`.
- ✅ `CompetitionHeatFormat` model + relasi `CompetitionClass::heatFormats()`.
- ✅ `CompetitionHeatManagerService` (orchestrator):
  - `isSupportedClass/isSupportedFormat`, `competitorCount` (individu = registrasi non-excluded;
    team = teams).
  - `validateFormat` (`participants<1`, `qualifiers<1`, `qualifiers>participants`).
  - `upsertFormat` / `deleteFormat`; `formatForRound` / `formats`.
  - `computeHeatCount`, `generateRound` (idempoten) — mereject alasan `round_exists` /
    `no_format` / `no_competitors`.
  - `generateNextRound` — memakai `advanceRound` eksisting; reject `no_format` /
    `no_next_format` / `not_all_finished` / `next_round_exists` / `no_qualifiers`.
  - `removeRoundSchedules` — reject `round_started` (heat non-Scheduled).
  - `classInEvent` — semua operasi event-scoped, throw `ModelNotFoundException` bila kelas di
    luar event.
- ✅ Helper additive `CompetitionMultiRoundHeatService::isRoundCompleteForAdvancement()` (round
  dikatakan lengkap bila SEMUA heat round memenuhi `isHeatCompleteForAdvancement`); dipakai
  `generateNextRound` tanpa mengubah service lama.
- ✅ UI `App\Livewire\Competition\Heat\Index` + blade heat cards (badge status, daftar peserta,
  tombol Peserta / Match Center / Input Hasil). Default format baru: participants=7, qualifiers=3.
- ✅ Route `events/{event}/competition/heat` (`can:manage-events`) + menu **Heat** di sidebar
  (grup Operasional, sebelum Jadwal).
- ✅ Test `tests/Feature/Competition/HeatManagerTest.php` — 27 test/121 assertions (A–H + schema + validasi +
  team + remove-round + regression UAT Case A–D, rebuild legacy 2-participant, started-round &
  existing-results protection, Livewire rebuild action F3).

## 4. Verification

- `tests/Feature/Competition`: **268 passed / 855 assertions / 0 failed** (HeatManagerTest = 27 test / 121 assertions).
- Full suite: **2436 passed / 6543 assertions / 0 failed / 0 skipped**
  (`/tmp/opencode/bin/php -d memory_limit=1G vendor/bin/pest`).
- Pint: 8 file diperiksa, 5 style issue otomatis diperbaiki → PASS (formatting only).

## 4b. Regression Fix UAT 2026-08-26 — Format Source of Truth + Rebuild Round

Temuan UAT: UI Heat menampilkan "5 peserta/heat · Top 2 lolos" tetapi heat yang ada "2 / 2 peserta".
Trace end-to-end: generator (`generateRound`) selalu menulis `required_participants` dari
`participants_per_heat` dan `chunk($perHeat)` — hanya heat yang **dibuat di luar Heat Manager**
(Jadwal manual / legacy R4H, `required_participants = 2`) yang bisa berkapasitas 2. Fix:

- `generateRoundInternal()` — format = **satu-satunya source of truth** kapasitas (`participants_per_heat`
  → `required_participants` + pembagian entries). Dipakai `generateRound` & `rebuildRound`.
- `rebuildRound()` (baru) — hapus heat round yang belum dimulai + generate ulang dari format.
  Guard data: `round_started` (ada heat `Playing`/`Waiting Result`/`Finished`) dan `has_results`
  (sudah ada `competition_heat_results`). **Tidak otomatis** — aksi operator eksplisit.
- `needs_rebuild` — UI menandai round bila ada heat yang `required_participants` ≠ format →
  banner amber + tombol **Generate Ulang Babak Ini**.
- Behavior 5/2: 5→1 heat isi 5; 9→2 heat 5+4; 10→2 heat 5+5; 4→1 heat isi 4 (bukan 2+2);
  top-N per heat tetap `qualifiers_per_heat` (5→2; 9→2+2=4).
- Regression: +8 test (`UAT Case A/B/C/D`, legacy rebuild, started-round protection, existing-results
  protection, Livewire rebuild F3) → HeatManagerTest 27 test / 121 assertions.
- Tidak diubah: ranking, `result_type`, `OutcomeManager`, `CompetitionMultiRoundHeatService`, Bracket,
  Match Center, team competition, kontrak advancement R4H.

## 5. Batasan & Konsistensi Kontrak
- Bracket R4E, Mass/Team Mass, VS, R4D/R4F: tidak diubah.
- Service R4H (`rankHeat`, `advanceRound`, `roundSchedules`, `isFinalRound`) dipakai apa adanya;
  `advanceRound` tetap menerima `topN` eksplisit dari format tersimpan.
- `generateRound`/`generateNextRound` menghasilkan `CompetitionSchedule` baru — ini adalah
  perubahan kontrak dari R4H §4 item 1–2 ("service NEVER membuat schedule") yang **disengaja**:
  Heat Manager adalah UI operator yang membuat heat dari format, bukan jalur advancement lama.
  Advancement lama (manual) tetap valid.
- Memory: full suite butuh `-d memory_limit=1G` (constraint env PHP CLI 128M).