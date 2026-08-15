# AUDIT — CompetitionResultType & CompetitionClass untuk Team Mass

> AUDIT ONLY — tidak ada perubahan kode/database/migration/seeder/UI.

- **Tanggal:** 2026-08-14
- **Status:** TIDAK ADA PERUBAHAN DIPERLUKAN — konfigurasi saat ini sudah benar.

---

## 1. Default result_type untuk team_mass

- `CompetitionFormat::defaultResultType('team_mass')` → **`ranking`** (`app/Support/CompetitionFormat.php`).
- Backfill migrasi `2026_08_22_000001` juga menetapkan `team_mass → 'ranking'`.
- `CompetitionResultType::sortDirection('ranking')` → **`asc`** (`app/Support/CompetitionResultType.php`).
- **UAT Team Mass** (dev `database.sqlite`, class #5 `UAT Team Mass`, format `team_mass`) → `result_type = 'ranking'` → **sesuai kebutuhan** (ranking / finish order).

## 2. Apakah team_mass harus ranking ascending atau score descending?

**Keduanya sah, tergantung cara lomba di-scoring; default sudah tepat:**

| Skenario Team Mass | Result type | Arah | Keterangan |
|---|---|---|---|
| Finish order / waktu (kanonik) | `ranking` (default) | **asc** (angka kecil = lebih baik) | ✅ benar untuk "semua team race → urutan finish → Juara 1–3" |
| Skor/poin (nilai lebih besar menang) | `score` (override per-class) | **desc** (angka besar = lebih baik) | didukung via kolom `competition_classes.result_type` |

`CompetitionClass::resultType()` (`app/Models/CompetitionClass.php:59-66`):
```php
if (CompetitionResultType::isValid($this->result_type)) {
    return $this->result_type;      // override eksplisit per-class
}
return CompetitionFormat::defaultResultType($this->format); // fallback
```

## 3. Perbedaan tipe result (source of truth `CompetitionResultType`)

| Type | sortDirection | Makna |
|---|---|---|
| `ranking` | `asc` | posisi/urutan finish — **kecil lebih baik** (1,2,3) |
| `score` | `desc` | skor/poin — **besar lebih baik** |
| `time` | `asc` | waktu — **kecil lebih baik** |
| `win_loss` | `asc` | vs-format — tidak di-ranking otomatis (via winner) |

## 4. Konsistensi engine

- `CompetitionResultService::rankTeams` (Team Mass), `rankSchedule` (Individual Mass), `aggregateHeatResults` (Individual Heat) **semua** memakai `$class->resultType()` + `CompetitionResultType::sortDirection()` (`CompetitionResultService.php:55-67, 158-170, 261-273`).
- Artinya override `result_type='score'` pada class team_mass otomatis mengubah arah ranking menjadi desc tanpa ubah kode.

## 5. Rekomendasi

1. **Pertahankan default `team_mass → ranking (asc)`** — benar untuk lomba mass race berbasis finish order/waktu.
2. Jika ada lomba Team Mass berbasis **poin**, set `competition_classes.result_type = 'score'` pada class tsb (tanpa mengubah kode/format) — engine + UI (Rank Otomatis, podium) mengikuti.
3. **Tidak diperlukan perubahan kode, migration, database, atau seeder.**

## 6. Dampak ke format lain (tidak berubah)

- `individual_heat → time (asc)` ✅ · `individual_mass → ranking (asc)` ✅ · `individual_vs_individual → score (desc)` ✅ · `team_vs_team → win_loss` ✅ · `team_mass → ranking (asc)` ✅.
- Semua memakai jalur engine yang sama (`resultType` + `sortDirection`) → override per-class konsisten dan tidak merusak format lain.
