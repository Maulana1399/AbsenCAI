# DEBUG — TEAM VS TEAM TIDAK MUNCUL DI OFFICIAL PANEL

> **AUDIT ONLY** — tidak ada perubahan kode/database/migration/seeder/data UAT.
> Skenario: match Team vs Team (UAT) sudah **Playing**, tetapi Official Panel menampilkan **"Menunggu Hasil (0)"**.

- **Tanggal:** 2026-08-14

---

## Runtime Reproduction

- Event UAT · Class `UAT Team vs Team` (format `team_vs_team`) · Team KM7 vs KM10.
- Schedule 2/2 (2 entry team). Match masuk Match Center, Start Match → status **`Playing`**.
- Official Panel → "Menunggu Hasil (0)" / "Tidak ada pertandingan yang menunggu hasil."

## Expected Behavior

- Setelah match team **Playing**, mengalir ke **Waiting Result**, lalu muncul di Official Panel untuk official yang ditugaskan, dan official bisa `submitTeamResult` (pilih team pemenang) → advance bracket → Juara 1/2/3.

## Actual Behavior

- Match tetap **Playing**; Official Panel kosong; jalur `submitTeamResult` tidak dapat dijangkau.

---

## Root Cause — Rantai (kombinasi)

### R1. Official Panel HANYA menampilkan `status = 'Waiting Result'`
`app/Livewire/Competition/OfficialPanel.php:183` → `->where('status', 'Waiting Result')`.
- Match yang masih **`Playing`** memang **tidak** akan muncul — ini **bukan** bug query; operator harus menekan **"Move to Waiting Result"** dulu di Match Center (`MatchCenter::moveToWaitingResult()` :71 → `workflow()->completeMatch()`).

### R2. `requiresOfficial()` hanya true untuk BRACKET match → non-bracket vs tidak pernah Waiting Result (GAP)
`app/Services/Competition/CompetitionWorkflowService.php:32-35`:
```php
public function requiresOfficial(CompetitionSchedule $schedule): bool
{
    return $this->isBracketMatch($schedule);   // hanya ada bracketMatch
}
```
`completeMatch()` (`:67-87`): jika `requiresOfficial` → **Playing → Waiting Result**; jika TIDAK → **Playing → Finished langsung**.
- UAT memakai **satu match manual** (KM7 vs KM10, 2/2). `BracketManager::generate` hanya membuat bracket 4/8/16/32 — schedule manual **tidak punya `competition_bracket_matches`**.
- Akibat: `completeMatch` menyelesaikan match **langsung ke Finished** → **tidak pernah Waiting Result** → Official Panel (`status=Waiting Result`) dan `submitTeamResult` (butuh Waiting Result) **tidak terjangkau**.
- Ini berlaku sama untuk Individual vs Individual non-bracket — Individual vs Individual UAT "berfungsi" karena memakai **bracket** (R3/R4B). Team vs Team yang di-bracket juga sudah bekerja (test R4B).

### R3. Official Panel juga memfilter "official yang ditugaskan"
`OfficialPanel.php:170-171` → `$assignedScheduleIds = CompetitionMatchOfficial::where('user_id', auth()->id())->pluck('competition_schedule_id')`; `:182` → `whereIn('id', $assignedScheduleIds)`.
- Dev DB: `competition_match_officials` = **0 baris**. User UAT kemungkinan **belum di-assign sebagai official** match tsb → walau match sudah Waiting Result, panel tetap kosong untuk user tsb.

---

## Evidence

| Fakta | Bukti |
|---|---|
| Panel filter status | `OfficialPanel.php:183` `where('status','Waiting Result')` |
| Panel filter assignment | `OfficialPanel.php:170-171,182` `$assignedScheduleIds` |
| requiresOfficial = bracket only | `CompetitionWorkflowService.php:32-35` |
| completeMatch non-bracket → Finished | `CompetitionWorkflowService.php:67-87` (else branch `update(['status'=>'Finished'])`) |
| Move to Waiting Result = completeMatch | `MatchCenter.php:71,77` |
| submitTeamResult butuh Waiting Result | `CompetitionWorkflowService::submitTeamResult` → `canTransitionTo('Finished')` (Waiting Result→Finished legal; Playing→Finished tidak) |
| Bracket hanya 4/8/16/32 | `BracketManager::generate` `if (! in_array($count,[4,8,16,32]))` |
| Official count dev = 0 | `select count(*) from competition_match_officials` → 0 |
| Tidak ada schedule team_vs_team di dev | query dev → kosong (match UAT ada di production) |

## Exact File + Line

- `app/Livewire/Competition/OfficialPanel.php:170-171,182-183` — dua filter (assignment + status).
- `app/Services/Competition/CompetitionWorkflowService.php:32-35` (`requiresOfficial`) dan `:67-87` (`completeMatch`).
- `app/Livewire/Competition/MatchCenter.php:71-86` (`moveToWaitingResult`).

## Klasifikasi

**E. Kombinasi**:
- **C (workflow)** — akar utama: `requiresOfficial` hanya bracket-based → match vs non-bracket (manual) tidak bisa masuk Waiting Result.
- **B (assignment official)** — panel juga butuh `competition_match_officials` untuk user tsb (0 di dev).
- Status belum di-advance ke Waiting Result (masih Playing).
- **D (data/setup UAT)** — match team dibuat sebagai **schedule manual**, bukan via BracketManager (bracket 4/8/16/32), sehingga tidak memiliki bracketMatch.

Bukan bug query `competition_team_id`/`winner_team_id`/eager loading — panel & workflow sudah team-aware (R4B).

---

## Minimal Recommended Fix (BELUM diimplementasikan — keputusan produk)

Pilih salah satu model yang koheren:

**Opsi A — Official flow untuk SEMUA vs match (perubahan kecil di workflow):**
`requiresOfficial()` mengembalikan true untuk format vs (`team_vs_team` / `individual_vs_individual`) TANPA mensyaratkan bracketMatch:
```php
public function requiresOfficial(CompetitionSchedule $schedule): bool
{
    if ($schedule->bracketMatch()->exists()) return true;
    return in_array($schedule->competitionClass?->format, ['team_vs_team','individual_vs_individual'], true);
}
```
→ `completeMatch` mengirim match vs non-bracket ke **Waiting Result**; official `submitTeamResult/submitResult` bisa submit; `advance*` no-op bila tidak ada bracket. Perlu verifikasi `finishMatch/resetMatch/completeMatch` tetap benar.

**Opsi B — vs wajib via bracket (tanpa ubah kode):**
Buat Team vs Team UAT melalui **BracketManager** (4/8/16/32); match bracket sudah melewati Waiting Result + official (terbukti R4B).

**Langkah operasional wajib (dalam dua opsi):**
- Di Match Center: setelah Playing, tekan **"Move to Waiting Result"**.
- **Assign official** (match tsb) untuk user yang membuka Official Panel (`competition_match_officials`).

## Risiko / Regression

- Opsi A mengubah perilaku `completeMatch`/`finishMatch` untuk **semua** match vs non-bracket (individual & team). Perlu regression: R3 (Individual vs Individual bracket), R4B (Team vs Team bracket), dan flow non-bracket mass/heat (tidak terpengaruh karena bukan vs).
- Opsi B tanpa perubahan kode — nol risiko code; murni setup UAT.
- Tidak ada migration/schema baru di kedua opsi.

## Test yang Harus Ditambahkan

1. **Non-bracket vs match → Waiting Result**: schedule `team_vs_team` TANPA bracketMatch, `completeMatch` → `'Waiting Result'` (Opsi A), lalu `submitTeamResult` memilih pemenang team.
2. **Official Panel visibility**: Waiting Result match tampil di panel HANYA untuk user yang di-assign `CompetitionMatchOfficial`; tidak tampil untuk user tanpa assignment.
3. **Regression**: bracket individual (R3) & bracket team (R4B) tetap Passing → Waiting Result → submit → podium.
