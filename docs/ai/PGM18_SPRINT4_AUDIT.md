# PGM.18 SPRINT 4 — REGU EVENT-SCOPING AUDIT

**Tanggal:** 23 Juli 2026
**Mode:** READ ONLY — Tidak ada perubahan kode
**Baseline (post-Sprint 3):** 1508 passed / 3624 assertions / 0 failures
**Design C:** problem_total = 0
**Commit Sprint 3:** `097f22d refactor: finalize legacy peserta mapping sprint 3`

---

## 1. Ringkasan Eksekutif

Audit membuktikan bahwa **regu_id harus dipindahkan ke Participation** untuk mendukung multi-event correctness. Arsitektur saat ini menyimpan `regu_id` secara global di tabel `pesertas` — artinya satu Person hanya bisa memiliki satu regu untuk semua event. Ini adalah **architectural blocker** untuk multi-event.

**Temuan Kunci:**

| Temuan | Detail |
|--------|--------|
| `regu_id` lokasi saat ini | **Hanya di `pesertas`** (legacy), **tidak ada di `participations`** |
| Tabel `regus` | **Tidak punya `event_id`** — global, satu regu bisa dipakai lintas event |
| `PlacementService::leastFilledRegu()` | **Hitung dari `pesertas`** via `regu::withCount('peserta')` — tidak event-scoped |
| Production WRITE ke `pesertas.regu_id` | **4 paths**: RegistrationService (2x), Ulang (1x), Impor (1x via RegistrationService) |
| Production READ/FILTER dari `pesertas.regu_id` | **~20+ paths**: dashboard, rekap, export, QR, attendance read, views |
| Participation punya `regu_id`? | **TIDAK** — kolom belum ada |
| Multi-event correctness | **GAGAL** — Person di Event A dan B punya regu yang SAMA |
| EditPeserta regu_id | **BUG** — reads regu_id but never writes back |

---

## 2. Current Architecture

### Lokasi regu_id Saat Ini

```
pesertas (table)
  ├── regu_id (FK → regus.id)  ← satu regu untuk semua event
  │
  ├── peserta A → Person A → Participation Event A (regu_id = X)
  └── peserta A → Person A → Participation Event B (regu_id = X, SAMA!)
```

### Arsitektur Target

```
participations (table)
  ├── regu_id (FK → regus.id)  ← event-scoped
  │
  ├── Participation Event A (person A) → regu_id = X
  └── Participation Event B (person A) → regu_id = Y (BERBEDA!)
```

### Regu Model Architecture Saat Ini

| Aspek | Status | Problem |
|-------|--------|---------|
| `regus` tabel punya `event_id`? | **TIDAK** | Regu bersifat global — tidak bisa di-scope per event |
| `regus` tabel | `id, regu, jenis_kelamin` | Minimal — tidak ada relasi ke event |
| `regu::peserta()` | `hasMany(peserta::class)` | Menghitung dari `pesertas` — bukan dari `participations` |
| `regu` model punya `participations()`? | **TIDAK** | Belum ada relationship |
| Satu regu milik satu Event? | **TIDAK** | Regu bisa dipakai lintas event (saat ini hanya ada 1 event CAI) |

---

## 3. Inventory regu_id — Lengkap

### A. Production WRITE ke `pesertas.regu_id`

| # | Caller | File:Line | Cara | Event-aware? |
|---|--------|-----------|------|-------------|
| 1 | `RegistrationService::createParticipant()` | `app/Services/Registration/RegistrationService.php:91` | `'regu_id' => $data['regu_id']` via peserta::create() | TIDAK — regu_id dari input, tidak diverifikasi event |
| 2 | `RegistrationService::updateParticipant()` | `RegistrationService.php:181` | `'regu_id' => $data['regu_id']` via peserta->update() | TIDAK |
| 3 | `Ulang::updatePeserta()` | `app/Livewire/Registrasi/Ulang.php:125` | Direct `peserta::find()->update(['regu_id'])` | TIDAK — langsung update legacy |
| 4 | `PesertaImport` (via RegistrationService) | `app/Imports/PesertaImport.php:50` | Auto-placement, via RegistrationService | TIDAK — regu dari PlacementService yang global |

**Semua write path menulis ke `pesertas.regu_id` — tidak ada yang menulis ke `participations.regu_id` karena kolom belum ada.**

### B. Production READ langsung `pesertas.regu_id`

| # | Caller | File:Line | Cara |
|---|--------|-----------|------|
| 1 | `PlacementService::leastFilledRegu()` | `PlacementService.php:60` | `regu::withCount('peserta')->orderBy('peserta_count')` — hitung dari peserta |
| 2 | `PlacementService::autoPlacement()` | `PlacementService.php:83` | Return `['regu_id' => $regu?->id]` |
| 3 | `EditPeserta::mount()` | `EditPeserta.php:74` | `$this->regu_id = $legacyPeserta?->regu_id` (BUG: never written back) |
| 4 | `Ulang::editPeserta()` | `Ulang.php:91` | `$this->editRegu = $legacyPeserta?->regu_id` |
| 5 | `CaiParticipantReplacementService::replace()` | `CaiParticipantReplacementService.php:250` | `'regu_id' => $peserta->regu_id` (snapshot ke replacement record) |
| 6 | `RekapPeserta` filter | `RekapPeserta.php:47` | `whereHas('person.legacyPesertaMapping.peserta', fn => where('regu_id', ...))` |
| 7 | `PesertaExport` filter | `PesertaExport.php:47-48` | Sama seperti RekapPeserta |
| 8 | `routes/web.php` QR label filter | `web.php:150-154,342-343` | `$q->where('regu_id', request('regu'))` via legacyParticipationMapping.peserta |
| 9 | `QRLabel/Index.php` filter | `QRLabel/Index.php:249` | `$legacyQuery->where('regu_id', $this->filterRegu)` |
| 10 | `AttendanceReadService::getSessionAttendance()` | `AttendanceReadService.php:25` | `$q->where('regu_id', $reguId)` via legacy chain |
| 11 | `Dashboard/Dashboard.php` | `Dashboard.php:83` | Logging regu filter |
| 12 | `Database.php` display | `Database.php:61` | `'regu' => $legacyPeserta?->regu` |
| 13 | `TambahPeserta.php` search results | `TambahPeserta.php:171,183,190,199` | Display regu name in search |
| 14 | `ResetEventData` integrity check | `ResetEventData.php:164,198,281,293,300,325-331` | Multiple FK integrity checks |

### C. FILTER/Query regu_id (via legacy chain)

| # | Livewire/View | Property | Cara Filter |
|---|--------------|----------|-------------|
| 1 | `Dashboard` | `$regu_id` | Passed to view for optional display filter |
| 2 | `RekapPeserta` | `$regu_id` | `whereHas('person.legacyPesertaMapping.peserta', where regu_id)` |
| 3 | `RekapAbsensi` | `$regu_id` | Passed to AttendanceReadService |
| 4 | `QRLabel/Index` | `$filterRegu` | `$legacyQuery->where('regu_id', ...)` |
| 5 | `PesertaExport` | `$regu_id` | Same as RekapPeserta filter |

### D. Relationship

| Model | Relationship | Target Table | Ada? |
|-------|-------------|-------------|------|
| `peserta` | `regu()` belongsTo | `regus` via `pesertas.regu_id` | ✅ |
| `regu` | `peserta()` hasMany | `pesertas` via `regu_id` | ✅ |
| `regu` | `participations()` hasMany | `participations` via `regu_id` | ❌ **TIDAK ADA** |
| `Participation` | `regu()` belongsTo | `regus` via `participations.regu_id` | ❌ **TIDAK ADA** (kolom belum ada) |
| `CaiParticipantReplacement` | `regu()` belongsTo | `regus` via `cai_participant_replacements.regu_id` | ✅ |

### E. Views Display

Semua view membaca regu dari legacy peserta:

| View | Display Pattern | Source |
|------|----------------|--------|
| `rekap-absensi.blade.php` | `$lp->regu->regu`, `$peserta->regu->regu` | Legacy peserta |
| `rekap-peserta.blade.php` | `$p->regu->regu` | Legacy peserta |
| `dashboard.blade.php` | `$lp->regu->regu`, `$legacy->regu->regu` | Legacy peserta |
| `database.blade.php` | `$peserta->regu->regu` | Legacy peserta |
| `edit-peserta.blade.php` | Select dropdown for `regu_id` | Legacy peserta |
| `ulang.blade.php` | `$peserta->regu->regu`, select for `editRegu` | Legacy peserta |
| `self-register.blade.php` | `$regu_nama` (computed) | Auto-placement |
| `tambah-peserta.blade.php` | `$regu_nama`, `$result['regu']`, `$selectedPerson['regu']` | Auto-placement + search |
| `ganti-peserta.blade.php` | `$regu` (property string) | Legacy peserta |
| `data-regu.blade.php` | `$regu->regu` (regu CRUD) | Regu model langsung |

---

## 4. Multi Event Correctness Analysis

### Skenario: Person A di 2 Event

```
Person A
  → peserta (legacy) — regu_id = 3 (Regu Melati)
      ↓
  → Participation Event A (CAI 27)
      → regu_id = 3 (Regu Melati)    ← HARUSNYA Regu Melati
  → Participation Event B (CAI 28)
      → regu_id = 3 (Regu Melati)    ← JUGA Regu Melati, SALAH!
```

### Root Cause

`regu_id` hanya ada di `pesertas`. Satu `peserta` hanya memiliki satu `regu_id` untuk semua event. Ketika Person A bergabung ke Event B, regu yang dipakai adalah regu yang sama dengan Event A.

### Blocker Detail

| Komponen | Problem |
|----------|---------|
| `pesertas.regu_id` | Global — tidak bisa dibedakan per event |
| `PlacementService::leastFilledRegu()` | Hitung dari `regu::withCount('peserta')` — global, tidak event-filtered |
| `regus` tabel | Tidak punya `event_id` — regu tidak bisa di-scope |
| `Participation` | Tidak punya `regu_id` — kolom belum ada |
| `RegistrationService::createParticipant()` | Set regu_id di peserta, bukan di Participation |
| Semua filter/display | Baca regu dari `pesertas.regu_id` via legacy chain |

### Apakah Arsitektur Saat Ini Mendukung Multi-Event?

**TIDAK.** Semua skenario multi-event akan gagal karena:
1. Person di Event A dan Event B memiliki regu yang SAMA (dari `pesertas.regu_id`)
2. Auto-placement tidak event-scoped — menghitung dari semua peserta, bukan per event
3. Filter regu di rekap/dashboard/QR membaca dari legacy peserta — tidak mungkin bedakan regu per event

---

## 5. PlacementService Audit

### Cara Kerja Saat Ini

```php
// PlacementService.php
public static function leastFilledRegu(?string $jenisKelamin = null): ?regu
{
    $jenisKelaminFix = static::normalizeGender($jenisKelamin);
    
    return regu::where('jenis_kelamin', $jenisKelaminFix)
        ->withCount('peserta')           // ← HITUNG DARI PESERTAS
        ->orderBy('peserta_count')
        ->orderBy('id')
        ->first();
}

public static function autoPlacement(?string $jenisKelamin = null): array
{
    $regu = static::leastFilledRegu($jenisKelamin);
    
    return [
        'regu_id' => $regu?->id,        // ← RETURN regu_id
        'nip' => ...,
        'regu_nama' => $regu?->regu ?? '',
    ];
}
```

### Masalah

1. **`withCount('peserta')` menghitung dari `pesertas`** — global, tidak event-filtered
2. **Tidak ada parameter event** — method `leastFilledRegu()` tidak menerima `eventId`
3. **Regu yang paling sedikit peserta-nya dihitung dari SEMUA event**
4. **NIP generation juga dari `pesertas`** — `legacyNextNip()` membaca `peserta::max('nip')`
5. **`autoPlacement()` return array** — perlu diubah return type jika Participation punya regu_id

### Yang Harus Berubah

```php
// Target: event-scoped
public static function leastFilledRegu(?string $jenisKelamin = null, ?int $eventId = null): ?regu
{
    $query = regu::where('jenis_kelamin', $jenisKelaminFix);
    
    if ($eventId) {
        // Partisipasi di event ini saja
        $query->withCount(['participations' => fn($q) => $q->where('event_id', $eventId)]);
    } else {
        $query->withCount('peserta'); // fallback legacy
    }
    
    return $query->orderBy('participations_count')->orderBy('id')->first();
}
```

---

## 6. Registration Flow Audit

### Flow 1: TambahPeserta (Person Baru)

```
TambahPeserta Livewire
  → autoPlacement() → return ['regu_id' => X, 'regu_nama' => 'Regu A']
  → $this->regu_id = X
  → RegistrationService::createParticipant(['regu_id' => X, ...])
      → peserta::create(['regu_id' => X])  ← WRITE ke pesertas
      → Person::create(...)
      → Participation::create(...)          ← TIDAK ADA regu_id
      → LegacyPesertaMapping::create(...)
```

**Problem:** `regu_id` ditulis ke `pesertas`, bukan ke `participations`.

### Flow 2: SelfRegister

```
SelfRegister Livewire
  → autoPlacement() → regu_id = X
  → RegistrationService::createParticipant(['regu_id' => X])
  → Sama seperti Flow 1
```

### Flow 3: Ulang (Re-registration)

```
Ulang Livewire
  → editPeserta() → $this->editRegu = $peserta->regu_id (read)
  → updatePeserta() → peserta::update(['regu_id' => $this->editRegu]) ← WRITE langsung
```

**Problem:** Direct write ke `pesertas.regu_id` — tidak melewati service layer.

### Flow 4: Import Peserta

```
PesertaImport
  → autoPlacement() → regu_id = X
  → RegistrationService::createParticipant(['regu_id' => X])
  → Sama seperti Flow 1
```

### Flow 5: EditPeserta

```
EditPeserta Livewire
  → mount() → $this->regu_id = $legacyPeserta?->regu_id (READ)
  → update() → validasi regu_id required, tapi...
     → $participation->person->update(...)  ← TIDAK update regu_id
     → Tidak ada syncToPeserta untuk regu_id
```

**BUG:** `EditPeserta` membaca `regu_id` tetapi **tidak pernah menulisnya kembali**. Perubahan regu di form edit peserta akan hilang.

### Flow 6: Existing Person Join Event B

```
TambahPeserta (Person existing, Case B)
  → Cari Person → ditemukan
  → Cari peserta legacy → ditemukan (punya regu_id dari Event A)
  → RegistrationService::createParticipant(['regu_id' => $regu->id])
      → Ini WRITE regu_id KE peserta YANG SAMA
      → Artinya: regu_id peserta Event A BERUBAH karena Event B!
```

**BUG MULTI-EVENT:** Ketika Person yang sudah punya peserta di Event A bergabung ke Event B, `regu_id` di `pesertas` akan ter-update dengan regu dari Event B — merusak data regu Event A.

---

## 7. Attendance Impact

### AttendanceReadService

| Komponen | Cara Baca regu | Impact |
|----------|---------------|--------|
| `getSessionAttendance()` | `person.legacyPesertaMapping.peserta.regu` via eager load | Akan baca regu dari legacy — tidak event-scoped |
| Filter regu | `$q->where('regu_id', $reguId)` via `whereHas('person.legacyPesertaMapping.peserta')` | Filter regu tidak bisa bedakan event |

### Dashboard

| Komponen | Cara Baca regu | Impact |
|----------|---------------|--------|
| Daftar sudah scan | `$lp->regu->regu` | Dari legacy peserta — global |
| Daftar belum scan | `$legacy->regu->regu` | Dari legacy peserta — global |

### Verdict

**Attendance tidak terpengaruh langsung** karena attendance read saat ini sudah event-scoped via `participation_id` dan `event_id`. Tapi display regu tetap dari legacy — tidak akan mencerminkan regu yang benar jika multi-event aktif.

---

## 8. Dashboard/Reporting Impact

### RekapPeserta

| Komponen | Cara Baca regu | Impact Multi-Event |
|----------|---------------|-------------------|
| Filter | `whereHas('person.legacyPesertaMapping.peserta', where regu_id)` | Satu regu untuk semua event — SALAH |
| Display | `$peserta->regu->regu` | Sama — dari legacy |

### RekapAbsensi

| Komponen | Cara Baca regu | Impact |
|----------|---------------|--------|
| Filter | `regu_id` passed to `AttendanceReadService` | Sama — filter via legacy |
| Display | `$lp->regu->regu`, `$peserta->regu->regu` | Sama — dari legacy |

### PesertaExport

Filter regu via `whereHas('person.legacyPesertaMapping.peserta', where regu_id)` — sama problemnya.

### Verdict

**Semua reporting membaca regu dari legacy peserta — tidak event-scoped.**

---

## 9. QR/Print/Export Impact

### QRLabel/Index

| Komponen | Cara | Impact |
|----------|------|--------|
| Filter regu | `$legacyQuery->where('regu_id', $this->filterRegu)` via `legacyParticipationMapping.peserta` | Tidak event-scoped |

### QR Label Routes (web.php)

| Route | Cara Filter | Impact |
|-------|------------|--------|
| Print filtered | `$q->where('regu_id', request('regu'))` via `legacyParticipationMapping.peserta` | Tidak event-scoped |
| Print A4 | Same | Same |

### Verdict

**QR/Print/Export tidak event-scoped untuk regu.**

---

## 10. Database Schema Audit

### Current Schema (relevant)

**`pesertas` table:**
| Column | Type | Constraints |
|--------|------|-------------|
| `regu_id` | FK | `→ regus(id) cascadeOnDelete, nullable` |

**`participations` table:**
| Column | Type | Constraints |
|--------|------|-------------|
| (no `regu_id`) | — | ❌ Kolom belum ada |

**`regus` table:**
| Column | Type | Constraints |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `regu` | string | Nama regu |
| `jenis_kelamin` | string | `Laki - Laki` / `Perempuan` |
| (no `event_id`) | — | ❌ Tidak ada FK ke events |

### Schema yang Dibutuhkan

**Migration additive (Sprint 4):**
```php
// 1. Tambah participations.regu_id
Schema::table('participations', function (Blueprint $table) {
    $table->foreignId('regu_id')
        ->nullable()
        ->constrained('regus')
        ->nullOnDelete();  // atau cascadeOnDelete — perlu keputusan
    // NO unique — satu regu bisa punya banyak participation
    // Index untuk performa filter
    $table->index('regu_id');
});

// 2. Opsional: tambah regus.event_id untuk event-scoped regu
Schema::table('regus', function (Blueprint $table) {
    $table->foreignId('event_id')
        ->nullable()
        ->constrained('events')
        ->cascadeOnDelete();
    $table->index('event_id');
});
```

---

## 11. Existing Data Strategy

### Database Snapshot (dari dokumentasi)

| Entity | Jumlah | Keterangan |
|--------|--------|------------|
| Legacy peserta | 143 | Mayoritas belum punya Participation (NULL participation_id) |
| People | 144 | Termasuk Person dari backfill |
| Participations | 2 (Sprint 1) → 144+ (post-backfill) | Semua di Event CAI yang sama |
| Events | 1 | Hanya CAI operational |

### Strategi Data

**Untuk 143 peserta legacy + 144 participations (semua di Event yang sama):**

1. **Peserta yang sudah punya Participation:** `participations.regu_id = pesertas.regu_id` — aman karena saat ini hanya ada 1 event
2. **Peserta legacy tanpa Participation:** Tidak perlu migrasi — mereka tidak punya event context
3. **Person dengan multiple Participation:** Saat ini TIDAK ADA (semua di event yang sama)
4. **Participation baru setelah Sprint 4:** Langsung tulis ke `participations.regu_id`

**Kesimpulan:** Migrasi data aman dilakukan dengan satu query:
```sql
UPDATE participations 
SET regu_id = (
    SELECT pesertas.regu_id 
    FROM legacy_peserta_mappings 
    JOIN pesertas ON pesertas.id = legacy_peserta_mappings.peserta_id 
    WHERE legacy_peserta_mappings.participation_id = participations.id
)
WHERE EXISTS (
    SELECT 1 
    FROM legacy_peserta_mappings 
    WHERE legacy_peserta_mappings.participation_id = participations.id 
    AND legacy_peserta_mappings.peserta_id IS NOT NULL
);
```

**Tapi** ini hanya aman KARENA saat ini hanya ada 1 event. Jika sudah ada multi-event, migrasi tidak bisa semudah ini.

---

## 12. status_registrasi Comparison

### Option A: Sprint 4 Hanya regu_id

| Aspek | Nilai |
|-------|-------|
| Complexity | **RENDAH** — hanya 1 kolom, 1 migration |
| Risk | **RENDAH** — additive migration, dual-write aman |
| Caller impact | ~25 files (services, Livewire, views, routes, exports) |
| Migration impact | 1 migration (add participations.regu_id) |
| Test impact | ~24 test files perlu fixture update, ~5 perlu assertion update |
| Multi-event correctness | regu_id solved ✅, status_registrasi masih broken ❌ |
| Dual-write needed | **YA** — sementara tulis ke peserta.regu_id + participations.regu_id |

### Option B: Sprint 4 regu_id + status_registrasi

| Aspek | Nilai |
|-------|-------|
| Complexity | **SEDANG** — 2 kolom, caller overlap |
| Risk | **SEDANG** — lebih banyak path yang perlu diubah |
| Caller impact | ~28 files (regu_id ~25 + status_registrasi ~8 unik) |
| Migration impact | 1 migration (2 kolom) |
| Test impact | ~30 test files |
| Multi-event correctness | ✅ regu_id + status_registrasi solved |
| Dual-write needed | **YA** — sementara tulis ke legacy + participations |

### Option C: Sprint 4 Hanya Additive Schema (Preparation)

| Aspek | Nilai |
|-------|-------|
| Complexity | **SANGAT RENDAH** — hanya tambah kolom, zero behavioral change |
| Risk | **HAMPER NOL** — additive, tidak mengubah runtime |
| Caller impact | **0** — belum ada caller refactor |
| Migration impact | 1 migration |
| Test impact | **0** — belum ada assertion change |
| Multi-event correctness | ❌ Belum solved — hanya persiapan schema |
| Dual-write needed | **TIDAK** — belum ada write path |

### Rekomendasi

**Option A — Sprint 4 hanya regu_id.**

Alasan:
- `status_registrasi` memiliki caller yang lebih sedikit dan lebih sederhana → bisa Sprint 5
- `regu_id` memiliki dampak multi-event yang lebih kritis
- Memisahkan scope mengurangi risiko regression
- Dual-write untuk regu_id lebih mudah diverifikasi

---

## 13. Legacy Attendance Dependency

### Apakah regu_id Migration Bergantung pada Legacy Attendance?

**TIDAK.** Absensi/IzinAbsensi legacy tidak membaca `regu_id`. Dependency chain:

```
Attendance read → Ambil Participation → Cari regu dari legacy peserta (pesertas.regu_id)
```

Setelah Sprint 4:
```
Attendance read → Ambil Participation → Baca participations.regu_id LANGSUNG
```

Tabel `Absensi` dan `IzinAbsensi` tidak memiliki kolom `regu_id` — mereka tidak perlu diubah.

### Verdict

**Legacy attendance TIDAK perlu disentuh di Sprint 4.**

---

## 14. Safe/Unsafe Matrix

| Perubahan | Klasifikasi | Alasan |
|-----------|-------------|--------|
| **Tambah `participations.regu_id`** (additive) | **SAFE TO DO NOW** ✅ | Hanya tambah kolom nullable — tidak mengubah runtime |
| **Tambah `regus.event_id`** (additive) | **NEEDS DISCUSSION** ⚠️ | Mengubah model regu — perlu keputusan arsitektural |
| **Dual-write: tulis ke peserta + participations** | **NEEDS DUAL-WRITE** | Sementara, sampai peserta.regu_id dihapus |
| **Refactor PlacementService ke event-scoped** | **NEEDS CALLER REFACTOR** | Ubah signature method, update semua caller |
| **Refactor RegistrationService ke participations.regu_id** | **NEEDS CALLER REFACTOR** | Stop tulis ke peserta.regu_id |
| **Refactor filter/display ke participations.regu_id** | **NEEDS CALLER REFACTOR** | ~20 file |
| **Hapus `pesertas.regu_id`** | **UNSAFE NOW** ❌ | Semua caller masih baca dari sini — perlu cutover penuh |
| **Migrasi data: peserta.regu_id → participations.regu_id** | **SAFE (dengan syarat)** | Hanya aman karena saat ini 1 event |
| **Tambah `Participation::regu()` relationship** | **SAFE TO DO NOW** ✅ | Model-only, zero behavioral change |
| **Tambah `regu::participations()` relationship** | **SAFE TO DO NOW** ✅ | Model-only |
| **Refactor views ke `$participation->regu->regu`** | **NEEDS DUAL-WRITE FIRST** | Data harus tersedia dulu di participations |

---

## 15. Recommended Sprint 4 Scope

### Scope: Paling Kecil yang Memberikan Kemajuan Arsitektural Nyata

**Option A — Sprint 4: Tambah participations.regu_id + Dual-Write + Refactor Write Paths**

#### Deliverables

**Phase 1 — Additive Schema (SAFE, zero behavioral change):**
1. Migration: tambah `participations.regu_id` (nullable FK → regus, nullOnDelete)
2. Migration: tambah index `participations.regu_id`
3. Model: tambah `Participation::regu()` belongsTo
4. Model: tambah `regu::participations()` hasMany (dengan event scope helper)
5. Test: schema test untuk kolom baru

**Phase 2 — Write Path Refactor:**
6. `RegistrationService::createParticipant()` — dual-write: peserta.regu_id + participations.regu_id
7. `RegistrationService::updateParticipant()` — dual-write
8. `Ulang::updatePeserta()` — dual-write
9. `EditPeserta::update()` — fix BUG (write regu_id back) + dual-write

**Phase 3 — PlacementService Event-Scoped:**
10. `PlacementService::leastFilledRegu()` — tambah parameter eventId
11. `PlacementService::leastFilledRegu()` — hitung dari participations jika eventId diberikan
12. Update semua caller PlacementService untuk passing event ID

**Phase 4 — Data Migration:**
13. Migration data: `UPDATE participations SET regu_id = peserta.regu_id WHERE ...`

### What NOT to Do in Sprint 4

- ❌ JANGAN refactor display/filter/views ke participations.regu_id (terlalu banyak — Sprint 5)
- ❌ JANGAN hapus `pesertas.regu_id` (masih dibaca oleh semua filter/display)
- ❌ JANGAN refactor PlacementService NIP generation (tidak terkait regu)
- ❌ JANGAN tambah `regus.event_id` (butuh diskusi arsitektural)
- ❌ JANGAN refactor status_registrasi (Sprint 5)
- ❌ JANGAN sentuh legacy attendance

---

## 16. Implementation Order

```
Phase 1: Additive Schema (SAFE)
  1.1 Migration: add participations.regu_id
  1.2 Model: add Participation::regu(), regu::participations()
  1.3 Schema test

Phase 2: Data Migration
  2.1 Migration: copy peserta.regu_id → participations.regu_id
  2.2 Verify data integrity (Design C update?) — tergantung apakah diagnostic perlu diperbarui

Phase 3: Write Path Dual-Write
  3.1 RegistrationService — dual-write
  3.2 Ulang — dual-write
  3.3 EditPeserta — fix BUG + dual-write
  3.4 Test: dual-write regression

Phase 4: PlacementService Event-Scoped
  4.1 leastFilledRegu() — eventId parameter
  4.2 withCount dari participations jika eventId diberikan
  4.3 Update callers (RegistrationService, SelfRegister, TambahPeserta)
  4.4 Test: event-scoped placement
```

---

## 17. Test Plan

### Schema Tests (Phase 1)
| Test | Verifikasi |
|------|-----------|
| `participations.regu_id` column exists | Schema assertion |
| `participations.regu_id` nullable | Create participation without regu |
| `Participation::regu()` belongsTo | Method exists, returns correct regu |
| `regu::participations()` hasMany | Method exists |
| FK `nullOnDelete` works | Delete regu, participation.regu_id becomes null |

### Data Migration Tests (Phase 2)
| Test | Verifikasi |
|------|-----------|
| Existing peserta regu_id copied to participation | Data integrity after migration |
| Peserta without participation not affected | Zero side effects |
| Idempotent migration | Run twice, same result |
| Rollback restores previous state | down() works |

### Dual-Write Tests (Phase 3)
| Test | Verifikasi |
|------|-----------|
| New registration writes to both peserta.regu_id AND participations.regu_id | Dual-write consistency |
| Self-register dual-write | Same |
| Import dual-write | Same |
| Ulang dual-write | Same |
| EditPeserta regu_id write (BUG FIX) | Changed regu_id persisted |
| Person yang sama di dua event punya regu berbeda | Multi-event correctness |
| EditPeserta tidak mengubah regu_id Person di event lain | Event isolation |

### Event-Scoped Placement Tests (Phase 4)
| Test | Verifikasi |
|------|-----------|
| Auto placement event isolation | Dua event, regu dihitung per event |
| Person A di Event A → Regu 1 | Correct |
| Person A daftar Event B → Regu 5 (regu paling sedikit di Event B) | Correct |
| Least-filled regu di Event A tidak terpengaruh peserta Event B | Isolation |
| Gender filter masih berfungsi | Only same-gender regu |
| Legacy fallback (tanpa eventId) masih bekerja | Backward compat |

### Regression Tests
| Test | Verifikasi |
|------|-----------|
| Sprint 2 mapping contract | All 14 tests still pass |
| Sprint 3 final contract | All 9 tests still pass |
| Full suite | 1508+ passed / 0 failures |
| Design C | problem_total = 0 |

---

## 18. Risks

| Risk | Severity | Probability | Mitigasi |
|------|----------|-------------|----------|
| Dual-write inconsistency (satu write sukses, satu gagal) | **HIGH** | Low | Gunakan DB transaction untuk atomic dual-write |
| Caller PlacementService tidak update semua | **HIGH** | Medium | Grep semua caller `autoPlacement()` dan `leastFilledRegu()` |
| BUG EditPeserta (regu_id tidak pernah di-write) | **MEDIUM** | **HIGH (existing)** | Fix di Sprint 4 — dual-write menyelesaikan bug ini |
| Regu filter/display masih baca dari legacy | **MEDIUM** | **HIGH (existing)** | Diterima sebagai technical debt — akan di-refactor di Sprint 5 |
| Data migration salah jika ada multiple event | **LOW** | Low (saat ini 1 event) | Validasi pra-migrasi: count events |
| regus.event_id belum ada — regu masih global | **MEDIUM** | High | Diterima — regu global cukup untuk CAI yang hanya 1 event per tahun |
| Participation.regu_id null untuk legacy data | **LOW** | Medium | Default null — display fallback ke legacy chain |

---

## 19. Blockers

| Blocker | Status |
|---------|--------|
| `participations.regu_id` column belum ada | ✅ Bisa dibuat — additive migration |
| `Participation::regu()` relationship belum ada | ✅ Bisa ditambah — model-only |
| `regu::participations()` relationship belum ada | ✅ Bisa ditambah |
| `PlacementService::leastFilledRegu()` belum event-scoped | ✅ Bisa di-refactor — tambah parameter |
| 10+ callers `leastFilledRegu()` dan `autoPlacement()` | ✅ Teridentifikasi — semua di app/ |
| RegistrationService write ke peserta.regu_id | ✅ Dual-write — tambah ke participations |
| 20+ filter/display masih baca dari legacy | ❌ **DITOLAK UNTUK SPRINT 4** — terlalu banyak, pindah ke Sprint 5 |
| `regus.event_id` belum ada | ⚠️ **BUTUH KEPUTUSAN** — apakah regu harus event-scoped? |
| 143 peserta legacy + 144 participations | ✅ Migrasi data aman (1 event) |

---

## 20. Documentation Gap

Dokumentasi yang perlu diperbarui NANTI (setelah Sprint 4 implementasi):
- `docs/DATABASE.md` — tambah `participations.regu_id` ke schema
- `docs/ai/CURRENT_STATE.md` — update PGM.18 status
- `docs/CHANGELOG.md` — Sprint 4 entry
- `docs/ROADMAP.md` — Sprint 4 status
- `docs/ai/LAPORAN_AUDIT_LEGACY_PGM18.md` — regu_id migrated dari peserta
- `docs/DATAFLOW.md` — update data flow untuk regu

JANGAN update dokumentasi sebagai COMPLETE sebelum implementasi dan test berhasil.

---

## 21. GO/NO-GO

### Jawaban atas 10 Pertanyaan Kunci

| # | Pertanyaan | Jawaban |
|---|-----------|---------|
| 1 | Apakah regu_id harus pindah ke Participation? | **YA.** Multi-event correctness membutuhkan regu_id event-scoped. Saat ini Person di 2 event punya regu SAMA — arsitektur broken untuk multi-event. |
| 2 | Apakah tabel regus juga harus event-scoped? | **TIDAK SEKARANG.** Regu global cukup untuk CAI yang hanya 1 event per tahun. `regus.event_id` bisa ditunda ke Sprint terpisah setelah ada kebutuhan multi-event dengan regu berbeda. |
| 3 | Apakah Sprint 4 aman dilakukan sekarang? | **YA — dengan syarat additive-first.** Tambah kolom dulu (safe), lalu dual-write, lalu refactor PlacementService. Jangan destructive migration. |
| 4 | Apakah peserta.regu_id harus langsung dihapus? | **TIDAK.** `pesertas.regu_id` masih dibaca oleh ~20 filter/display paths. Hapus nanti di Sprint 5 setelah semua caller pindah ke `participations.regu_id`. |
| 5 | Apakah perlu dual-write sementara? | **YA.** Selama transisi, tulis ke `pesertas.regu_id` (legacy) DAN `participations.regu_id` (canonical). Hentikan legacy write di Sprint 6+. |
| 6 | Bagaimana menangani 143 peserta legacy yang belum punya Participation? | **Tidak perlu migrasi.** Mereka tidak punya event context. Hanya peserta yang sudah punya Participation yang perlu migrasi regu_id. |
| 7 | Apakah status_registrasi sebaiknya ikut Sprint 4? | **TIDAK.** Pisahkan scope. `status_registrasi` memiliki caller lebih sedikit dan lebih sederhana → Sprint 5 yang lebih aman. |
| 8 | Apakah legacy attendance perlu disentuh? | **TIDAK.** Absensi/IzinAbsensi tidak membaca `regu_id`. Tidak ada dependency. |
| 9 | Scope Sprint 4 paling kecil dan aman apa? | **Option A: Tambah participations.regu_id + dual-write + refactor write paths.** Scope: 4 phase (additive schema → data migration → dual-write → PlacementService event-scoped). Tidak termasuk refactor display/filter/views atau hapus peserta.regu_id. |
| 10 | Apa blocker terbesar? | **Tidak ada blocker teknis.** Blocker terbesar adalah scope creep — jangan tambah `regus.event_id`, jangan refactor display/views, jangan sentuh status_registrasi di Sprint 4. |

### Kesimpulan

**✅ GO — Sprint 4 dapat diimplementasikan dengan scope terbatas.**

| Aspek | Status |
|-------|--------|
| Apakah partisipasi.regu_id harus pindah? | ✅ **YA** — architectural requirement |
| Apakah aman? | ✅ **YA** — additive-first, dual-write, no destructive |
| Apakah ada blocker? | ✅ **TIDAK** — semua sudah teridentifikasi |
| Scope terlalu besar? | ⚠️ **JAGA SCOPE** — Phase 1-4 saja, jangan refactor display |
| Test plan siap? | ✅ **YA** — schema, data migration, dual-write, event isolation |
| Risiko terbesar? | Dual-write inconsistency — mitigasi dengan DB transaction |

### Urutan Implementasi yang Direkomendasikan

```
Sprint 4.1: Additive Schema + Data Migration (1-2 hari)
Sprint 4.2: Dual-Write + PlacementService Refactor (2-3 hari)
Sprint 4.3: Testing + Bug Fixes (1-2 hari)
Sprint 4.4: Dokumentasi + Closure (1 hari)
Total: ~5-8 hari
```

### Yang TIDAK BOLEH Dilakukan di Sprint 4

❌ Hapus `pesertas.regu_id`
❌ Refactor display/filter/views ke `participations.regu_id`
❌ Tambah `regus.event_id`
❌ Refactor `status_registrasi`
❌ Sentuh legacy attendance
❌ Refactor NIP generation
❌ Ubah Design C diagnostic (belum perlu)
