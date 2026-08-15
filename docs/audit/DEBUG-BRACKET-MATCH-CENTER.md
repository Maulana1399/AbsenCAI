# DEBUG — BRACKET MATCH CENTER

> Audit-only. **Tidak ada perubahan kode / database / data UAT / seeder / migration.**
> Scope: kenapa Match Center menampilkan `PLAYING 0 · WAITING 0 · READY 0 · FINISHED 0` padahal bracket + `competition_schedule_entries` untuk "UAT Individual vs Individual Bracket (4)" sudah ada.

- **Tanggal:** 2026-08-14
- **Status:** ROOT CAUSE FOUND (D + B) — bukan masalah Team Formation / PJ / PASS.

---

## Ringkasan

Bracket match **punya schedule yang benar**, **schedule punya entries yang benar**, tapi status schedule-nya tetap **`Scheduled`** setelah auto-seed. `MatchCenter::render()` (dan seluruh counter-nya) **hanya mengenali `Ready`, `Playing`, `Waiting Result`** untuk daftar, dan **`Playing/Waiting Result/Ready/Finished`** untuk angka. Status **`Scheduled` tidak pernah dilist dan tidak pernah dihitung** → seluruh UI Match Center kosong (0/0/0/0).

---

## ROOT CAUSE

**D + B — bracket schedule tidak pernah menjadi match yang "dikenali" Match Center, karena status-nya macet di `Scheduled`.**

1. `BracketManager::generate()` membuat setiap schedule bracket dengan `status = 'Scheduled'`.
2. `CompetitionBracketSeederService::seedInitialRound()` mengisi `competition_schedule_entries` **tanpa** memanggil `checkAutoReady()` / `prepareMatch()` → status schedule **tetap `Scheduled`**.
3. `MatchCenter`:
   - daftar utama difilter `whereIn('status', ['Ready','Playing','Waiting Result'])` → `Scheduled` terbuang;
   - 4 counter hanya `Playing`, `Waiting Result`, `Ready`, `Finished` → `Scheduled` tidak dihitung.

Hasil: schedule bracket ber-status `Scheduled` sama sekali tak terlihat → semua kartu kosong + semua counter 0.

---

## Exact File + Method + Line

| Lokasi | Fungsi | Baris | Peran |
|---|---|---|---|
| `app/Livewire/Competition/BracketManager.php` | `generate()` | 100–105 | `CompetitionSchedule::create(['status' => 'Scheduled', ...])` — semua schedule bracket lahir `Scheduled` |
| `app/Services/Competition/CompetitionBracketSeederService.php` | `seedInitialRound()` | 68–94 | Membuat `CompetitionScheduleEntry` langsung; **tidak pernah** ubah `schedule.status` (tidak panggil `checkAutoReady`) |
| `app/Livewire/Competition/MatchCenter.php` | `render()` | 150 | `->whereIn('status', ['Ready','Playing','Waiting Result'])` — `Scheduled` TIDAK dilist |
| `app/Livewire/Competition/MatchCenter.php` | `render()` | 161–168 | Counter hanya `Playing`, `Waiting Result`, `Ready`, `Finished` — `Scheduled` TIDAK dihitung |
| `app/Livewire/Competition/Schedule/EntryManager.php` | `assign()` | 138–144 | Pembanding: jalur non-bracket memanggil `workflow()->checkAutoReady()` setelah entry dibuat → `Scheduled→Ready` |
| `app/Services/Competition/CompetitionWorkflowService.php` | `checkAutoReady()` | 472–486 | Logika promosi `Scheduled→Ready` yang (tidak) dipanggil oleh seeder bracket |
| `app/Services/Competition/CompetitionWorkflowService.php` | `advanceWinner()` | 214–216 | Hanya mempromosikan ke `Ready` untuk **match berikutnya** setelah finish — tidak menyentuh round awal hasil auto-seed |

`Scheduled` **sengaja** tidak dieksklusi lewat nama/class/event; ia tersaring lewat **status whitelist** di query. Tidak ada pengecualian bracket eksplisit di Match Center — bracket hilang karena status, bukan karena di-skip eksplisit.

---

## Bukti Database (UAT "UAT Individual vs Individual Bracket (4)")

Sumber: mirror lokal UAT (`database/database.sqlite`) — struktur/relasi identik dengan MariaDB UAT (event UAT live `id=4`; mirror memakai `event_id=1`, data peserta sama: `UAT Competition 01..04`).

**`competition_brackets`**
| id | competition_class_id | name | participant_count | status |
|---|---|---|---|---|
| 1 | 3 | UAT Individual vs Individual Bracket | 4 | active |

**`competition_bracket_matches`**
| id | bracket_id | schedule_id | round | position | sumber |
|---|---|---|---|---|---|
| 1 | 1 | 4 | 2 | 1 | — (SEMI M1) |
| 2 | 1 | 5 | 2 | 2 | — (SEMI M2) |
| 3 | 1 | 6 | 1 | 1 | srcA=1, srcB=2 (Final) |

**`competition_schedules`**
| id | class_id | status | required | entries |
|---|---|---|---|---|
| 4 | 3 | **Scheduled** *(fresh) / Finished (snapshot play-through)* | 2 | 2 |
| 5 | 3 | **Scheduled** *(fresh) / Finished (snapshot)* | 2 | 2 |
| 6 | 3 | **Scheduled** *(fresh) / Finished (snapshot)* | 2 | 2 |

> Snapshot DB yang tersedia sudah dalam kondisi **Finished** karena pernah di-play-through saat UAT R4E. Keadaan **fresh** (baru auto-seed, sesuai keluhan) adalah **`Scheduled`** — status awal yang dibuat `BracketManager::generate()`. Semua jalur (fresh maupun played) membuktikan: Match Center hanya menampilkan Ready/Playing/Waiting Result + counter Finished, jadi `Scheduled` → 0.

**`competition_schedule_entries` (konkret — sudah benar)**
| schedule | order | competition_registration_id | peserta (person) |
|---|---|---|---|
| 4 | 1 | 11 | UAT Competition 01 |
| 4 | 2 | 12 | UAT Competition 02 |
| 5 | 1 | 13 | UAT Competition 03 |
| 5 | 2 | 14 | UAT Competition 04 |
| 6 | 1 | 11 | winner M1 → Final |
| 6 | 2 | 13 | winner M2 → Final |

→ Entry berisi `competition_registration_id` dan `participant` yang **benar persis** seperti keluhan (M1 = 01 vs 02; M2 = 03 vs 04). Jadi **data bukan masalahnya**; status yang menutupi.

---

## Actual Data Flow (sekarang)

```
BracketManager::generate()
  └─ CompetitionSchedule.create(status='Scheduled') ×3      (BracketManager.php:102)
  └─ CompetitionBracketMatch.create(...)                    (meng-connect schedule)
CompetitionBracketSeederService::seedInitialRound()
  └─ CompetitionScheduleEntry.create(...) ×4  ← hanya entri, status TIDAK diubah
Status schedule = 'Scheduled' (macet)
MatchCenter::render()
  └─ whereIn(status, [Ready, Playing, Waiting Result])  → 0 match
  └─ count Playing=0, Waiting=0, Ready=0, Finished=0     → kartu + angka = 0/0/0/0
```

## Expected Data Flow (seharusnya)

```
BracketManager::generate() → schedules (Scheduled) + bracket_matches
seedInitialRound()
  └─ entry dibuat
  └─ checkAutoReady(schedule)  → 2/2 entry ⇒ status 'Ready'   (sama seperti EntryManager.assign)
MatchCenter::render()
  └─ Ready queue berisi M1 (01 vs 02), M2 (03 vs 04)  → READY ≥ 2
  └─ operator startMatch → Playing → Waiting Result → official submit → Finished → winner advancement
```

---

## Perbandingan: Non-bracket yang BERHASIL tampil

| Alur | Jalur entry | Promosi status | Tampil di Match Center? |
|---|---|---|---|
| **Bracket (R3/R4E/R4B)** | seeder langsung `CompetitionScheduleEntry::create` | **TIDAK ada** → tetap `Scheduled` | ❌ tidak pernah |
| **Individual Mass / non-bracket** | `EntryManager::assign()` → `checkAutoReady()` | `Scheduled→Ready` (WorkflowService:472–486) | ✅ tampil sebagai Ready |
| **Team vs Team non-bracket (R4D)** | `EntryManager` (team) → `checkAutoReady()` | `Scheduled→Ready` | ✅ tampil |
| **Bracket Team (R4B)** | `CompetitionBracketSeederService` (team column) | **TIDAK ada** → tetap `Scheduled` | ❌ kasus yang sama persis |

Perbedaan kunci: jalur **non-bracket** selalu melewati `checkAutoReady()` setelah entry di-assign; jalur **bracket auto-seed R4E** tidak pernah memanggilnya → bracket tidak pernah naik ke `Ready`.

---

## Minimal Fix

> Audit-only — berikut deskripsi, **tidak diterapkan**.

**Opsi terbaik (konsisten dengan EntryManager):** di `CompetitionBracketSeederService::seedInitialRound()`, setelah membuat entry pada sebuah initial-round match, panggil:

```php
app(\App\Services\Competition\CompetitionWorkflowService::class)
    ->checkAutoReady($match->schedule);
```

Efek: schedule round awal yang sudah penuh 2/2 peserta naik ke `Ready` → muncul di Ready queue Match Center. Final (round 1) tetap `Scheduled` sampai winner advancement (perilaku `advanceWinner` tidak berubah).

**Alternatif (lebih invasif, TIDAK direkomendasikan):** tambahkan status `Scheduled` ke daftar/ counter Match Center — mengubah kontrak UI dan menutupi akar masalah tanpa memperbaiki alur kerja operator.

---

## Regression Risk

- **Rendah** untuk opsi `checkAutoReady`:
  - Idempotency seeder tetap (skip match berisi; `checkAutoReady` no-op bila sudah Ready).
  - Non-bracket / R4D / mass / heat **tidak tersentuh** (jalur terpisah).
  - `advanceWinner`/`advanceWinnerTeam` tetap bekerja (promosi match berikutnya).
  - Operator Dashboard: daftar `Scheduled` akan berkurang (round awal bracket pindah ke Ready) — ekspektasi baru yang wajar.
- **Perlu dicek:** test yang mengasumsikan initial-round bracket tetap `Scheduled` setelah auto-seed (bila ada) akan gagal dan harus diperbarui ekspektasinya menjadi `Ready`.

---

## Tests yang Harus Ditambahkan

1. **Auto-seed mempromosikan round awal ke `Ready`**
   - `seedInitialRound()` → schedule M1 & M2 `status === 'Ready'`, Final tetap `Scheduled`.
2. **Match Center menampilkan bracket auto-seed**
   - Render `MatchCenter` untuk class bracket berisi → `READY >= 2`, dan `assertSee('UAT Competition 01')` / `02 / 03 / 04`.
3. **Counter Match Center**
   - `countReady >= 2`, `countPlaying/Waiting/Finished` tidak bertambah.
4. **Idempotency + ready** — re-seed tidak menduplikasi dan tidak me-reset status `Ready` ke `Scheduled`.
5. **Regression non-bracket** — Individual Mass & Team vs Team non-bracket tetap tampil (Ready) tanpa perubahan.
6. **Regression winner advancement** — M1/M2 selesai → Final terisi & naik `Ready` (lanjut `advanceWinner`).

---

## Final Verdict

**PASS (audit selesai) — akar masalah: status bracket schedule macet di `Scheduled` karena auto-seed R4E tidak memanggil `checkAutoReady()`, sementara Match Center hanya mengenali `Ready/Playing/Waiting Result` (+ counter `Finished`).**

- Data bracket/schedule/entries: **benar** (bukti di atas).
- Event/class scoping: **benar** (query Match Center sudah `whereIn competition_class_id` kelas event aktif).
- Tidak ada pengecualian bracket eksplisit; bracket hilang **karena status whitelist**.
- Team Formation / PJ Management / PASS / R4D / R4E: **tidak diubah**, tidak perlu disentuh.
- Migration = 0 · DB/UAT data = 0 · Seeder = 0 · Truncate/reset = 0 · **Belum ada perbaikan diterapkan** (menunggu persetujuan minimal fix).
