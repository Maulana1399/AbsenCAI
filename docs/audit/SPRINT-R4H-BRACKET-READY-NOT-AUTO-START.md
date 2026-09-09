# SPRINT R4H — BRACKET READY, BUKAN AUTO-START

> Ubah behavior setelah winner advancement: next match menjadi **`Ready`**, bukan otomatis `Playing`. Auto-seed (R4E) dan auto-ready (R4G) dipertahankan. `Playing` hanya via aksi eksplisit operator `Start Match`.

- **Tanggal:** 2026-08-14
- **Baseline suite (R4G):** 2359 passed / 6210 assertions · **Final suite:** 2368 passed / 6252 assertions / 0 failed / 0 skipped (+9 test, +42 assertions)

---

## 1. Root Cause Auto-Start

Kecacatan: setelah winner di-advance ke next match dan competitor lengkap, next match **langsung `Playing`** tanpa aksi operator.

Penyebabnya adalah `promoteReadyMatch()` di `app/Services/Competition/CompetitionWorkflowService.php` (baris lama 488-508). Method ini mencari schedule `Ready` berikutnya di venue yang sama lalu `update(['status' => 'Playing'])` — **auto-start**.

## 2. Call Chain Penyebabnya

```
OfficialPanel::submitResult()
  → CompetitionWorkflowService::submitResult()          // official individu
      → schedule.status = Finished
      → advanceWinner()                                 // isi entry next round
          → nextSchedule.status = Ready  (canAutoReady true)   ← R4G auto-ready OK
      → promoteReadyMatch(venueId)                      // ❌ AUTO-START
          → nextReady.update(['status' => 'Playing'])   // next match jadi Playing

OfficialPanel::submitResult()
  → CompetitionWorkflowService::submitTeamResult()      // official team
      → schedule.status = Finished
      → advanceWinnerTeam()                             // isi entry team next round
          → nextSchedule.status = Ready
      → promoteReadyMatch(venueId)                      // ❌ AUTO-START
          → nextReady.update(['status' => 'Playing'])
```

Pemicu tambahan: `completeMatch()` dan `finishMatch()` (non-official / mass-heat) juga memanggil `promoteReadyMatch()` — behavior yang sama.

## 3. File/Method yang Diubah

`app/Services/Competition/CompetitionWorkflowService.php`:
- **Dihapus** semua pemanggilan `$this->promoteReadyMatch(...)`:
  - di `completeMatch()` (path non-official → Finished)
  - di `finishMatch()`
  - di `submitResult()` (official individu) — ini jalur utama bracket auto-start
  - di `submitTeamResult()` (official team)
- **Dihapus** method `promoteReadyMatch()` (sudah dead-code).
- **Dihapus** import yang tidak terpakai: `ActiveEventContext`, `CompetitionClass`.
- `advanceWinner()` / `advanceWinnerTeam()` **tidak diubah** — tetap menjalankan auto-ready logic (`Scheduled` → `Ready` saat entry lengkap). Ini adalah jalur yang benar.

Tidak ada perubahan: schema/migration, `BracketManager`, `MatchCenter`, `EntryManager`, `checkAutoReady()`, R4D official flow, contract `CompetitionTeam`, Mass/Heat result engine.

## 4. Behavior Sebelum

```
M1 finish → winner di-advance → M2 competitor lengkap → M2 Ready (R4G)
  → promoteReadyMatch() → M2 otomatis Playing  ❌
```

`Scheduled → Ready → otomatis Playing` terjadi tanpa operator.

## 5. Behavior Sesudah

```
Generate → auto-seed (R4E) → checkAutoReady (R4G) → M1/M2 Ready
M1 finish → winner di-advance → M2 competitor lengkap → M2 Ready  ✅
  → operator klik Start Match → M2 Playing
```

`Scheduled → Ready → operator Start → Playing`. **Tidak ada** jalur `Ready → Playing` otomatis.

## 6. Test Ditambahkan/Diubah

`tests/Feature/Competition/CompetitionBracketReadyNotAutoStartTest.php` (baru, 9 test / 42 assertions):
- **A** auto-seed initial → `Ready`, bukan `Playing`.
- **B** winner advancement → entry next round masuk; next round `Ready` setelah lengkap, bukan `Playing`.
- **C** no auto-start → setelah winner advancement next match tetap `Ready`.
- **D** explicit start → next match `Ready`, panggil `MatchCenter::startMatch()` → `Playing`.
- **E** final → kedua semifinal selesai, final mendapat 2 winner → `Ready`, bukan `Playing`.
- **F** team bracket → winner team di-advance → next match `Ready`, bukan auto-start.
- **G** regression R4D → non-bracket `team_vs_team`: Playing → Waiting Result → Official → Finished PASS.
- **H** regression R4E/R4G → auto-seed + auto-ready + team display (bukan TBD) PASS.
- **I** regression Mass/Heat → `completeMatch`/`finishMatch` finish langsung (non-official) PASS.

Diubah `tests/Feature/Competition/CompetitionWorkflowTest.php`:
- test 46 `official submits result and auto-promotes next Ready` → **`...does NOT auto-start next Ready (R4H)`**: setelah official submit, schedule `Ready` di venue yang sama **tetap `Ready`**.
- test 47 `auto-promote only promotes from same venue` → **`official submit does not auto-start Ready matches anywhere (R4H)`**.

## 7. Competition Test Result

- **200 passed / 564 assertions** (sebelumnya 191 / 522; +9 test R4H).
- Semua suite Competition hijau termasuk: WorkflowDecision, WorkflowEnforcement, NonBracketTeamVsTeam, BracketPodium, TeamBracketPodium, AutoSeed, HeatAggregation, Report.

## 8. Full Suite Result

- **2368 passed / 6252 assertions / 0 failed / 0 skipped** (2× run hijau).
- Catatan: ada 1 run interim yang gagal masif (98 failed) karena **disk 100% penuh** (tmpfile/tempnam gagal) — murni lingkungan, bukan kode. Setelah ruang pulih, full suite hijau 2× berturut.

## 9. Pint

`vendor/bin/pint` pada 3 file berubah → **PASS** (0 style issue). Test ulang setelah Pint: Competition dir 200 hijau, full suite 2368 hijau.

## 10. Regression Result

- R3 Individual bracket ✅ · R4B Team bracket ✅ · R4D non-bracket official ✅ · R4E auto-seed ✅ · R4G auto-ready ✅ · R4H (baru) ✅ · Mass/Heat ✅ · Podium Juara 1/2/3 ✅ · WorkflowDecision/Enforcement ✅.

## 11. Safety Checks

- Migration = 0 · Schema = 0 · Seeder = 0 · Production/UAT data = 0
- Query Match Center = 0 perubahan · UI = 0 perubahan (tidak menyembunyikan masalah)
- `BracketManager::generate()` = 0 perubahan · `checkAutoReady()` = 0 perubahan
- R4D official flow = tetap · `CompetitionTeam` contract = tetap · Mass/Heat result engine = tetap
- Tidak ada status/workflow baru; hanya memutus jalur auto-start (`promoteReadyMatch`).

## 12. Final Verdict

**PASS** — winner advancement kini berhenti di `Ready`. Satu-satunya jalur `Ready → Playing` adalah aksi eksplisit operator `Start Match` (`MatchCenter::startMatch()` / `OperatorDashboard::advanceStatus`). Auto-seed, auto-ready, winner advancement, dan official flow tidak berubah.
