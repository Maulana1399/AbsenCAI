# AUDIT — HEAT MANAGER + HEAT FORMAT BUILDER (2026-08-26)

> Audit pasca-implementasi untuk fitur **Heat Manager**: format builder per babak, auto-generate
> heat, auto-generate babak berikutnya, dan menu Heat operator — dibangun di atas
> `CompetitionMultiRoundHeatService` (R4H). Dilakukan setelah implementasi, sebagai dasar penguncian.

- **Tanggal audit:** 2026-08-26
- **Hasil:** implementasi sesuai kontrak. **1 migration baru** (`competition_heat_formats`),
  **1 service orchestrator** baru, **1 Livewire component + blade** baru, **1 helper additive** pada
  service R4H, route + menu. Tidak ada perubahan pada `rankHeat`/`advanceRound`/`OutcomeManager`
  logic, Bracket, Mass, VS, atau `result_type`.

---

## 1. Root Cause & Keputusan Schema

### 1.1 Kenapa butuh tabel baru
- `competition_schedules.required_participants` = kapasitas **satu** heat yang sudah dibuat —
  tidak menyimpan "peserta per heat" pada tingkat round.
- `sort_order = round*100 + heatIndex` hanya menyandikan babak/urutan, bukan kapasitas/kuota lolos.
- R4H sengaja menyimpan `top_n` sebagai **parameter method** (bukan persisten) — keputusan audit
  lalu. Heat Manager butuh menyimpan format per round agar bisa auto-generate heat dan
  auto-generate babak berikutnya **tanpa hardcode** (sprint mensyaratkan "jangan hardcode 7 dan 3").

### 1.2 Bentuk tabel `competition_heat_formats`
- `competition_class_id` FK → `competition_classes` `cascadeOnDelete`.
- `round` unsigned (default 1); `participants_per_heat` unsigned (default 1);
  `qualifiers_per_heat` unsigned (default 1); timestamps.
- `unique(['competition_class_id','round'])` `uniq_heat_format_class_round` → satu format per
  (kelas, babak). Upsert idempoten via `updateOrCreate`.
- `down()` = `dropIfExists`. Tidak ada seeder; data diisi operator via UI Heat.

### 1.3 Alternatif yang ditolak
- Menambah kolom di `competition_classes` (hanya satu format per kelas — tidak mendukung multi-round,
  sedangkan setiap babak bisa beda kapasitas/kuota).
- Mengubah `competition_schedules` (konsep per-babak, bukan per-heat).
- Store dalam array/JSON di kelas — tidak fungsional untuk relasi/konsistensi.

## 2. Service Orchestrator (`CompetitionHeatManagerService`)

Semua method event-scoped (kelas di luar event → `ModelNotFoundException`).

| Method | Perilaku |
|--------|----------|
| `isSupportedClass` / `isSupportedFormat` | hanya `individual_heat` / `team_heat` (`SUPPORTED_FORMATS`) |
| `validateFormat` | reject participants<1, qualifiers<1, qualifiers>participants |
| `upsertFormat` / `deleteFormat` | `updateOrCreate` per (class_id, round); delete satu format |
| `formatForRound` / `formats` | baca format tersimpan |
| `competitorCount` | individu: registrasi aktif kelas (exclude status terdaftar tdk valid); team: teams kelas |
| `computeHeatCount` | ceil(count / participants_per_heat) |
| `generateRound` | idempoten: reject `round_exists` (heat round sudah ada), `no_format`, `no_competitors`; buat N heat `Scheduled` (`sort_order=round*100+i`, `required_participants=participants`, `canAutoReady()`), isi `CompetitionScheduleEntry` merata tanpa menimpa entry lama |
| `generateNextRound` | reject `no_format` (babak ini tak ada format), `no_next_format`, `not_all_finished` (via `isRoundCompleteForAdvancement`), `next_round_exists`, `no_qualifiers`; panggil `advancedRound = advanceRound(event, class, round, qualifiers)` → kembali `{ advanced, reason, assigned, next_round, heats }` |
| `removeRoundSchedules` | reject `round_started` bila ada heat non-`Scheduled`; hapus schedule round (entries + heat_results + outcomes ikut cascade/hook `CompetitionSchedule`), hapus format round |

`generateRound`/`generateNextRound` **tidak menyentuh** hasil/`competition_heat_results` dan
`competition_outcomes` — hasil tetap dikelola masukan `OutcomeManager` + `saveHeatResults`,
rank via `rankHeat`, agregasi via `aggregateRoundResults`.

## 3. Helper Additive pada R4H

`CompetitionMultiRoundHeatService::isRoundCompleteForAdvancement(int $classId, int $round, bool $isTeam): bool`
— semua schedule round memenuhi `isHeatCompleteForAdvancement` (lifecycle `Finished` ATAU semua
`competition_heat_results.status` terisi). Dipakai guard `generateNextRound`. Tidak mengubah
`advanceRound`, `rankHeat`, `roundSchedules`, `isFinalRound`, `roundOf`.

## 4. UI Heat (`App\Livewire\Competition\Heat\Index` + blade)

- Kelas filter: hanya `individual_heat`/`team_heat` **dalam event**.
- Action: `selectClass`, `toggleFormatForm`, `createFormat`, `deleteFormat`, `validateFormat`,
  `generateRound`, `generateNextRound`, `removeRound`. RELOAD penuh setelah mutasi (konsisten dengan
  codebase, menghindari state basi). Gate `manage-events`.
- Per heat card: badge status, daftar peserta (registrasi/team + hasil + posisi bila ada), tombol
  Peserta (`EntryManager`), Match Center, Input Hasil (`OutcomeManager`).
- Default form: participants=7, qualifiers=3 (bukan hardcode—dapat diubah operator per round).
- Route: `events/{event}/competition/heat` → `competition.heat.index`, middleware `can:manage-events`.
- Sidebar: menu **Heat** grup Operasional, sebelum Jadwal, `can('manage-events')` (operator/juri
  tidak melihat menu ini di kompetisi — override eksisting, drastic yang dihargai).

## 5. Migration & Dampak

| File | Jenis |
|------|-------|
| `database/migrations/2026_08_26_000001_create_competition_heat_formats_table.php` | baru (1 migration) |
| `app/Models/CompetitionHeatFormat.php` | baru |
| `app/Services/Competition/CompetitionHeatManagerService.php` | baru |
| `app/Livewire/Competition/Heat/Index.php` + `resources/views/livewire/competition/heat/index.blade.php` | baru |
| `tests/Feature/Competition/HeatManagerTest.php` | baru |
| `app/Services/Competition/CompetitionMultiRoundHeatService.php` | +1 helper additive |
| `app/Models/CompetitionClass.php` | +`heatFormats()` relasi |
| `routes/web.php` | +route heat index |
| `resources/views/components/layouts/app/sidebar.blade.php` | +menu Heat |

**Tidak diubah:** `CompetitionWorkflowService`, Bracket, `MatchCenter`, `OutcomeManager` logic,
`CompetitionResultService`, migrations heat results/team.

## 6. Verifikasi

- **Test baru** `tests/Feature/Competition/HeatManagerTest.php` (**27 test, 121 assertions** — 19 awal + 8 regression 2026-08-26):
  schema+unique+model, validasi format (0/0, qualifiers>participants, format unsupported, upsert
  idempoten, event-scope), B (28→4 heat 101-104, 7/heat), idempotensi generate, partial (30→7/heat
  → 7-7-7-7-2), team generation (entry team-only), C+D (4×3→12→2 heat 201/202 berisi 6-6),
  A (top 3 per 7), E (identitas tidak tertukar di advance), F (OutcomeManager read-back waktu per
  registrasi), F2 (halaman Heat: createFormat+generateRound), G (kompetitor belum terisi →
  `not_all_finished`), H (single-round → `no_next_format`, tidak ada round/schedule/entry fabrikasi),
  removeRound (reject `round_started`, sukses reset), team heat advancement, **UAT Case A/B/C/D**
  (5→1 heat req 5 entries 5; 9→2 heat req [5,5] entries [5,4]; 10→2 heat req [5,5] entries [5,5];
  4→1 heat entries 4 — bukan 2+2), **rebuild legacy 2-participant heat**, **started-round protection**,
  **existing-results protection**, **Livewire rebuild action (F3)**.
- **Competition dir:** 268 passed / 855 assertions / 0 failed.
- **Full suite:** 2436 passed / 6543 assertions / 0 failed / 0 skipped.
- **Pint:** 8 file, 5 style issue diperbaiki otomatis (formatting only) → PASS.

## 6b. Regression Fix — Format Source of Truth (UAT 2026-08-26)

Temuan: round berformat 5/2 menampilkan heat 2/2. Root cause (dari trace code end-to-end):
generator selalu memakai `participants_per_heat`; heat berkapasitas 2 hanya bisa berasal dari
luar Heat Manager (Jadwal manual / legacy). Fix:

1. `generateRoundInternal()` (refactor) — `participants_per_heat` → `required_participants` + entries
   per heat. Kapasitas lama tidak pernah dibaca.
2. `rebuildRound(eventId, classId, round)` — perbaiki existing round dari format; hapus heat
   belum-dimulai lalu generate ulang. Guard `round_started` & `has_results` — **tanpa mutation otomatis**.
3. `needs_rebuild` (render) — deteksi mismatch `required_participants` vs format → banner amber +
   tombol "Generate Ulang Babak Ini".
4. Behavior 5/2: 5→1 heat(5); 9→5+4; 10→5+5; 4→4 (bukan 2+2); top-N per heat = `qualifiers_per_heat`.

Scope tidak berubah: ranking, `result_type`, `OutcomeManager`, `CompetitionMultiRoundHeatService`,
Bracket, Match Center, team competition, kontrak R4H advancement tetap utuh.

## 7. Risiko & Batasan
- **Advancement lama tetap valid**: `advanceRound` sendiri tetap menolak `no_next_round` bila round
  berikutnya belum dibuat manual — Heat Manager menawarkan jalur `generateNextRound` yang membuat
  schedule-nya dari format. Kontrak R4H §4 item 1–2 ("service tidak pernah membuat schedule")
  tetap berlaku untuk `advanceRound`; pembuatan schedule baru hanya lewat
  `generateRound`/`generateNextRound`/`removeRoundSchedules` yang memang milik Heat Manager.
- **Deleting format tanpa menghapus heat** tidak diatur (format bisa hilang padahal heat masih ada);
  UI menyediakan `removeRound` untuk membersihkan keduanya sekaligus.
- **Idempotensi** `generateRound` didasarkan pada keberadaan schedule round — kelas yang punya
  schedule round parsial/manual akan ditolak `round_exists` (operator pakai removeRound dulu).
- **Memory:** full suite butuh `-d memory_limit=1G` (constraint env, bukan bug).