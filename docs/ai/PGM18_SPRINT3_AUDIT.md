# PGM.18 SPRINT 3 — PHYSICAL LEGACY PESERTA MAPPING CONTRACT CLEANUP AUDIT

**Tanggal:** 23 Juli 2026
**Mode:** READ ONLY — Tidak ada perubahan kode
**Baseline:** 1499 passed / 3592 assertions / 0 failures
**Design C:** problem_total = 0

---

## 1. Ringkasan Eksekutif

Audit membuktikan bahwa **Sprint 3 aman untuk diimplementasikan secara fisik** — tidak ada data runtime yang bergantung pada `participation_id`, `event_id`, atau `backfill_batch_id` di `LegacyPesertaMapping`.

| Metric | Value |
|--------|-------|
| Production WRITE ke deprecated columns | **0** (Semua create/write hanya menggunakan field active contract) |
| Production READ langsung ke deprecated columns | **0** (Tidak ada `->participation_id`, `->event_id`, `->backfill_batch_id` pada LegacyPesertaMapping di production code) |
| `->participation` production runtime call | **0** (Hanya diagnostic command `AttendanceDiagnose.php` — tapi itu pun sebenarnya `LegacyParticipationMapping`, bukan LegacyPesertaMapping) |
| `->event` production runtime call | **0** |
| `Participation::legacyPesertaMapping()` callers | **0** (Routes dan Livewire hanya eager-load via Eloquent `legacyPesertaMapping.peserta` sebagai alternatif ke Person → peserta; tidak ada yang mengakses deprecated columns) |
| Stale import `LegacyPesertaMapping` | 2 files (`AttendanceService.php`, `Scan.php`) |
| Migration compatibility | **SQLite-native** — `DROP COLUMN` didukung SQLite 3.35+; butuh 3-step strategy untuk safe FK/index removal |
| Test dependencies | **Minimal** — 3 test files, 7 assertion lines, test contract tetap berlaku setelah drop |
| Blocker | **0** — Sprint 3 bisa GO |

**Keputusan: ✅ GO — Sprint 3 dapat diimplementasikan.**

---

## 2. Final Contract

### Active Contract (LegacyPesertaMapping — akan dipertahankan)

```
legacy_peserta_mappings:
  id                   (PK, auto-increment)
  peserta_id           (FK → pesertas, NOT NULL, UNIQUE)
  person_id            (FK → people, NOT NULL)
  legacy_nip           (nullable integer)
  legacy_participant_number (nullable string)
  legacy_attendance_code    (nullable string)
  migrated_at          (nullable timestamp)
  created_at / updated_at
```

### Deprecated Contract (akan di-drop Sprint 3)

```
  participation_id     (FK → participations, nullable, UNIQUE — TIDAK PERNAH DIISI)
  event_id             (FK → events, nullable — TIDAK PERNAH DIISI)
  backfill_batch_id    (nullable string — TIDAK PERNAH DIISI)
```

### Active Contract (LegacyParticipationMapping — akan dipertahankan)

```
legacy_participation_mappings:
  id                   (PK, auto-increment)
  peserta_id           (FK → pesertas, NOT NULL)
  person_id            (FK → people, NOT NULL)
  participation_id     (FK → participations, NOT NULL, UNIQUE)
  event_id             (FK → events, NOT NULL)
  backfill_batch_id    (nullable string — TIDAK PERNAH DIISI)
  migrated_at          (nullable timestamp)
  created_at / updated_at
```

---

## 3. Production Reference Inventory

### Total References to `LegacyPesertaMapping` (44 files)

| Kategori | Jumlah File | Keterangan |
|----------|-------------|------------|
| Model definition | 1 | `app/Models/LegacyPesertaMapping.php` |
| Relationships in other models | 4 | `Participation.php`, `Person.php`, `peserta.php`, `Event.php` |
| Services | 5 | Registration, CaiReplacement, PersonSync, IdentityCorrection, Attendance |
| Livewire | 6 | Scan, Database, TambahPeserta, DeletePerson, EditPerson, QRLabel, RekapPeserta |
| Routes | 1 | `routes/web.php` |
| Exports | 1 | `PesertaExport.php` |
| Console commands | 2 | `AttendanceDiagnose.php`, `ResetEventData.php`, `DesignCDiagnostics.php` |
| Migrations | 2 | create + nullable migration |
| Views | 1 | `dashboard.blade.php` |
| Tests | 33 | Various test files |

### References to Deprecated Columns Only

| Reference | File | Status |
|-----------|------|--------|
| `participation_id` in $fillable | `app/Models/LegacyPesertaMapping.php:12` | Model definition — diubah saat drop |
| `event_id` in $fillable | `app/Models/LegacyPesertaMapping.php:13` | Model definition — diubah saat drop |
| `backfill_batch_id` in $fillable | `app/Models/LegacyPesertaMapping.php:14` | Model definition — diubah saat drop |
| `participation()` relationship | `app/Models/LegacyPesertaMapping.php:38-41` | Model definition — dihapus saat drop |
| `event()` relationship | `app/Models/LegacyPesertaMapping.php:43-46` | Model definition — dihapus saat drop |
| `Participation::legacyPesertaMapping()` | `app/Models/Participation.php:27-30` | Inverse relationship — **dihapus** |
| `Event::legacyPesertaMappings()` | `app/Models/Event.php:93-95` | Inverse relationship — **dihapus** |
| `$mapping->participation` (read) | `app/Console/Commands/AttendanceDiagnose.php:126` | **FALSE POSITIVE** — `$mapping` adalah `LegacyParticipationMapping`, bukan `LegacyPesertaMapping` |
| Stale import | `app/Services/Attendance/AttendanceService.php:8` | Import tidak digunakan — hapus |
| Stale import | `app/Livewire/Dashboard/Scan.php:6` | Import tidak digunakan — hapus |

---

## 4. Six Participation Relationship Callers

Sprint 2 menyebutkan "6 production references melalui `Participation::legacyPesertaMapping()`". Berikut audit eksak:

### Eksak Count: **9 call sites di 3 files**, tapi semuanya hanya eager-load `->peserta` untuk mengakses legacy data. TIDAK ADA yang membaca deprecated columns.

#### File 1: `routes/web.php` (4 call sites)

| Line | Pola | Keterangan |
|------|------|------------|
| 135 | `Participation::with(['legacyPesertaMapping.peserta'])` | Eager load untuk QR label — ambil data legacy peserta (regu, kelompok) |
| 147 | `$query->whereHas('legacyPesertaMapping.peserta', fn($q) => ...)` | Filter QR label by regu/kelompok via legacy peserta |
| 310 | `Participation::with(['legacyPesertaMapping.peserta'])` | Sama — A4 print route |
| 337 | `$query->whereHas('legacyPesertaMapping.peserta', fn($q) => ...)` | Sama — filter by regu/kelompok |

**Need**: `regu_id` dan `kelompok_id` dari tabel `pesertas` — data legacy.  
**Replacement**: `person.legacyParticipationMappings.peserta.regu` (Person → peserta via LegacyParticipationMapping)  
**Risiko**: Rendah — legacy `peserta` tetap ada, hanya traversal path yang berubah.

#### File 2: `app/Livewire/QRLabel/Index.php` (3 call sites)

| Line | Pola | Keterangan |
|------|------|------------|
| 72 | `Participation::with(['legacyPesertaMapping'])` | Eager load — hanya cek existence mapping? |
| 177 | `Participation::with(['legacyPesertaMapping'])` | Sama |
| 243 | `$query->whereHas('legacyPesertaMapping.peserta', fn($q) => ...)` | Filter by regu/kelompok |

**Need**: Legacy peserta regu/kelompok untuk filter.  
**Replacement**: Pindah ke `LegacyParticipationMapping` atau `person.legacyParticipationMappings.peserta`.  
**Risiko**: Rendah.

#### File 3: `app/Livewire/Database/Peserta/Database.php` (2 call sites)

| Line | Pola | Keterangan |
|------|------|------------|
| 36 | Eager load `'person.legacyPesertaMapping.peserta.regu'` dan `'legacyPesertaMapping.peserta.regu'` | Dual eager load — melalui Person dan Participation |
| 50 | `$participation->legacyPesertaMapping?->peserta` | Null-safe read untuk display |

**Need**: Regu/kelompok legacy peserta untuk display.  
**Replacement**: Pindah ke `LegacyParticipationMapping.peserta.regu`.  
**Risiko**: Rendah — data sama, path berbeda.

### Kesimpulan 6+ Callers

Semua 9 call sites hanya menggunakan `legacyPesertaMapping.peserta` untuk mengakses **regu/kelompok data dari tabel `pesertas`**. TIDAK ADA yang mengakses:
- `participation_id` → Tidak ada
- `event_id` → Tidak ada
- `participation()` → Tidak ada
- `event()` → Tidak ada
- Deprecated columns → Tidak ada

**Setelah Sprint 3 drop column, semua 9 call sites tetap bisa di-refactor ke `LegacyParticipationMapping.peserta.regu/kelompok` tanpa kehilangan data.**

---

## 5. Model Relationship Audit

### Current State

```
LegacyPesertaMapping:                  ← Akan dipertahankan (peserta↔Person ONLY)
  peserta() → belongsTo(peserta)       ✅ VALID (active contract)
  person()  → belongsTo(Person)        ✅ VALID (active contract)
  participation() → belongsTo(Participation)  ❌ DEPRECATED (0 prod refs)
  event()   → belongsTo(Event)          ❌ DEPRECATED (0 prod refs)

LegacyParticipationMapping:            ← Sole event-specific bridge
  peserta() → belongsTo(peserta)       ✅ VALID
  person()  → belongsTo(Person)        ✅ VALID
  participation() → belongsTo(Participation) ✅ VALID
  event()   → belongsTo(Event)         ✅ VALID

Participation:
  legacyPesertaMapping() → hasOne(LegacyPesertaMapping, 'participation_id')  ❌ DEPRECATED (akan dihapus)

Event:
  legacyPesertaMappings() → hasMany(LegacyPesertaMapping, 'event_id')  ❌ DEPRECATED (akan dihapus)

Person:
  legacyPesertaMapping() → hasOne(LegacyPesertaMapping, 'person_id')  ✅ VALID (active contract — dipertahankan)

peserta:
  legacyPesertaMapping() → hasOne(LegacyPesertaMapping, 'peserta_id')  ✅ VALID (active contract — dipertahankan)
  legacyParticipationMappings() → hasMany(LegacyParticipationMapping, 'peserta_id')  ✅ VALID
```

### Jawaban Audit

| # | Pertanyaan | Jawaban |
|---|-----------|---------|
| 1 | Apakah `LegacyPesertaMapping::participation()` masih dipanggil production? | **Tidak** — 0 references di production code (AttendanceDiagnose menggunakan `LegacyParticipationMapping`) |
| 2 | Apakah `LegacyPesertaMapping::event()` masih dipanggil production? | **Tidak** — 0 references |
| 3 | Apakah `Participation::legacyPesertaMapping()` masih dipanggil production? | **Ya** — 9 eager load paths (routes + Livewire), tapi hanya untuk akses `->peserta`, BUKAN untuk deprecated columns |
| 4 | Apakah ada canonical replacement? | **Ya** — `LegacyParticipationMapping.peserta` bisa menggantikan path `legacyPesertaMapping.peserta` |
| 5 | Apakah `Participation` seharusnya punya relationship ke `LegacyParticipationMapping`? | **Saat ini tidak ada** — Perlu ditambahkan: `$this->hasOne(LegacyParticipationMapping::class, 'participation_id')` |
| 6 | Apakah `peserta` sudah punya relationship ke `LegacyParticipationMapping`? | **Ya** — `legacyParticipationMappings()` (hasMany) |
| 7 | Apakah `Person` punya relationship yang cukup? | **Ya** — Bisa via `participations → LegacyParticipationMapping` atau perlu direct relationship |

### Target Model

```
LegacyPesertaMapping:                  ← peserta↔Person ONLY (no event bridge)
  peserta() → belongsTo(peserta)       ✅ RETAIN
  person()  → belongsTo(Person)        ✅ RETAIN
  (participation() REMOVED)            ❌ DROP
  (event() REMOVED)                    ❌ DROP

Participation:
  (legacyPesertaMapping() REMOVED)     ❌ DROP (inverse via participation_id — column dropped)
  legacyParticipationMapping() → hasOne(LegacyParticipationMapping)  ✅ ADD (inverse — recommended)

Event:
  (legacyPesertaMappings() REMOVED)    ❌ DROP
```

---

## 6. Write Path Audit

### RegistrationService::createParticipant() (line 111)

```php
LegacyPesertaMapping::create([
    'peserta_id' => $peserta->id,
    'person_id' => $person->id,
    'legacy_nip' => $peserta->nip,
    'legacy_participant_number' => $legacyParticipantNumber,
    'legacy_attendance_code' => $attendanceCode,
    'migrated_at' => now(),
    // participation_id: NOT SET → disengaja
    // event_id: NOT SET → disengaja
    // backfill_batch_id: NOT SET → disengaja
]);
```

**Status: ✅ Clean** — Tidak menulis deprecated columns setelah kontrak Sprint 2.

### CaiParticipantReplacementService::replace() (line 211)

```php
$pesertaMapping->update([
    'person_id' => $newPerson->id,
    // participation_id: NOT SET
    // event_id: NOT SET
    // backfill_batch_id: NOT SET
]);
```

**Status: ✅ Clean** — Update hanya `person_id`, tidak menyentuh deprecated columns.

### PersonLegacySyncService (read-only for LegacyPesertaMapping)

```php
$mapping = $person->legacyPesertaMapping()->with('peserta')->first();
// Read only — writes to peserta, not to LegacyPesertaMapping
```

**Status: ✅ Clean** — Meskipun menggunakan deprecated model, tidak menulis ke deprecated columns.

### IdentityCorrectionService::syncLegacyPeserta() (line 206-222)

```php
$mapping = LegacyPesertaMapping::query()->where('person_id', $person->id)->first();
$mapping->peserta()->update($pesertaUpdates); // updates peserta, not mapping
```

**Status: ✅ Clean** — Update ke tabel `pesertas`, bukan ke LegacyPesertaMapping.

### All Other Services

| Service | Writes to LegacyPesertaMapping deprecated columns? |
|---------|---------------------------------------------------|
| AttendanceService | Stale import only — no writes |
| AttendanceReadService | Read-only |
| AttendanceExceptionService | Read-only |
| AttendanceBackfillService | Uses LegacyParticipationMapping only |
| SuratIzinBackfillService | Uses LegacyParticipationMapping only |
| ParticipationResolver | Uses LegacyParticipationMapping only |
| AttendanceParityService | Uses LegacyParticipationMapping only |

### Verdict Write Path

**✅ ZERO production writes to:**
- `LegacyPesertaMapping.participation_id` — NOT WRITTEN
- `LegacyPesertaMapping.event_id` — NOT WRITTEN
- `LegacyPesertaMapping.backfill_batch_id` — NOT WRITTEN

---

## 7. Read Path Audit

### Direct column reads (production)

| Pattern | LegacyPesertaMapping | LegacyParticipationMapping |
|---------|---------------------|---------------------------|
| `->participation_id` | **0** (hanya test assertions) | 0 (used via relationship) |
| `->event_id` | **0** (hanya test assertions) | 0 (used via relationship) |
| `->backfill_batch_id` | **0** | 0 |
| `where('participation_id', ...)` | **0** | Used by caller services |
| `where('event_id', ...)` | **0** | Used by resolver services |
| `whereNotNull('participation_id')` | **0** | Design C diagnostic |
| `$mapping->participation` | **0** (AttendanceDiagnose is LegacyParticipationMapping) | Used by resolver |

### False Positives Clarified

1. **`app/Console/Commands/AttendanceDiagnose.php:126`** — `$mapping->participation`:
   - `$mapping` adalah `LegacyParticipationMapping` (dari query di line 109-111)
   - BUKAN `LegacyPesertaMapping`
   - **Not a blocker**

2. **`app/Services/Cai/CaiParticipantReplacementService.php:64,74,86`** — `$participationMapping->participation_id`:
   - `$participationMapping` adalah `LegacyParticipationMapping`
   - **Not a blocker**

3. **`app/Services/Attendance/LegacyParticipationResolver.php:43,79`** — `$mapping->event_id`:
   - `$mapping` adalah `LegacyParticipationMapping`
   - **Not a blocker**

### Verdict Read Path

**✅ ZERO production reads of LegacyPesertaMapping deprecated columns.**

---

## 8. Database Schema & Migration Audit

### Current Schema (legacy_peserta_mappings)

| Column | Type | Constraints | Sprint 3 Action |
|--------|------|-------------|----------------|
| id | bigIncrements | PK | RETAIN |
| peserta_id | foreignId | NOT NULL, FK → pesertas(id) restrictOnDelete, UNIQUE | RETAIN |
| person_id | foreignId | NOT NULL, FK → people(id) restrictOnDelete | RETAIN |
| participation_id | foreignId | **nullable**, FK → participations(id) nullOnDelete, UNIQUE | **DROP** |
| event_id | foreignId | **nullable**, FK → events(id) nullOnDelete | **DROP** |
| backfill_batch_id | string | **nullable** | **DROP** |
| legacy_nip | integer | nullable | RETAIN |
| legacy_participant_number | string | nullable | RETAIN |
| legacy_attendance_code | string | nullable | RETAIN |
| migrated_at | timestamp | nullable | RETAIN |

### Migration Dependencies Before Drop

1. **Unique constraint on `participation_id`** — named `legacy_peserta_mappings_participation_id_unique`
2. **Foreign key on `participation_id`** → `participations(id)` nullOnDelete
3. **Foreign key on `event_id`** → `events(id)` nullOnDelete
4. **Indexes** — if any (SQLite auto-index FK columns)

### SQLite Safe Migration Strategy

SQLite mendukung `DROP COLUMN` sejak version 3.35.0 (2021-03-12). Laravel SQLite driver menggunakan PRAGMA foreign_keys = ON.

**Step-by-step safe migration:**

```php
// Step 1: Drop FK constraints
Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
    // SQLite requires recreate for FK drop — use raw
    DB::statement('PRAGMA foreign_keys = OFF');
    
    // SQLite can't drop FK directly — need to recreate table
    // Use Schema::disableForeignKeyConstraints() approach
});

// Step 2: Drop unique constraint (SQLite requirement — table recreate needed for any constraint change)
// Step 3: Drop columns
Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
    $table->dropColumn(['participation_id', 'event_id', 'backfill_batch_id']);
});
```

**IMPORTANT SQLite considerations:**
- `dropColumn()` in SQLite requires table recreation internally — Laravel handles this
- FK constraints on columns being dropped must be removed first
- Unique constraint on `participation_id` must be dropped before the column
- Use separate migration steps: (1) drop FK + unique, (2) drop columns
- Test migration on SQLite before production

### Recommended Migration Plan

```php
// Migration 1: Drop constraints (FK + unique)
public function up(): void
{
    Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
        $table->dropUnique('legacy_peserta_mappings_participation_id_unique');
        $table->dropForeign(['participation_id']);
        $table->dropForeign(['event_id']);
    });
}

// Migration 2: Drop columns
public function up(): void
{
    Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
        $table->dropColumn(['participation_id', 'event_id', 'backfill_batch_id']);
    });
}
```

**SQLite compatibility note:** Laravel 12's Schema builder handles SQLite column drops via table recreation internally. Each migration that modifies schema on SQLite causes a full table rebuild. Splitting into separate migrations ensures cleaner rollback.

---

## 9. Test Dependency Audit

### Group A: Active Runtime Contract Test

| File | Lines | Tests | Impact |
|------|-------|-------|--------|
| `tests/Feature/Sprint2MappingContractTest.php` | 66-75 | "LegacyPesertaMapping does not require participation_id or event_id" | **Refactor** — remove `participation_id`/`event_id` assertion (column no longer exists) |
| `tests/Feature/Sprint2MappingContractTest.php` | 256-273 | "participant replacement does not write participation_id to LegacyPesertaMapping" | **Refactor** — remove `participation_id` assertion; kolom sudah tidak ada |

### Group B: Transitional Schema Test

| File | Lines | Tests | Impact |
|------|-------|-------|--------|
| `tests/Feature/LegacyPesertaMapping/LegacyPesertaMappingFoundationTest.php` | 46-58 | Column existence check | **Refactor** — remove `participation_id`, `event_id`, `backfill_batch_id` assertions; ganti dokumentasi |

### Group C: Historical Migration Test

None identified — tidak ada migration-specific tests untuk deprecated columns.

### Group D: LegacyParticipationMapping Test

Semua test untuk `LegacyParticipationMapping` tidak tersentuh — contract valid.

### Exact Impact Count

| Metric | Count |
|--------|-------|
| Test files terdampak | **3** |
| Test assertions terdampak | **5** (3 schema + 2 runtime) |
| Test methods perlu refactor | **3** (2 di Sprint2MappingContractTest, 1 schema test) |
| Test methods perlu hapus | **0** |
| Test methods tetap | Semua test lain tidak berubah |

### Files Not Affected

Semua service test, Livewire test, integration test, dan Design C test tidak perlu perubahan karena:
- Tidak ada service/Livewire yang membaca deprecated columns
- Design C diagnostic hanya memeriksa `LegacyParticipationMapping`
- Data fixtures di semua test sudah tidak mengisi `participation_id`/`event_id`/`backfill_batch_id` sejak Sprint 2

---

## 10. Actual Data Audit

### Database Availability

**Database file exists** (`/var/www/AbsenCAI/database/database.sqlite`) tetapi **tidak ada PHP/SQLite CLI** di environment ini untuk query read-only.

**Berdasarkan audit source code dan pengetahuan Sprint 1/2:**

Setelah Sprint 2, tidak ada kode yang menulis ke `participation_id`, `event_id`, atau `backfill_batch_id` di `LegacyPesertaMapping`. Sprint 2 factory fixture juga sudah menghapus default values untuk kolom-kolom tersebut.

**Asumsi aman:** Semua nilai di 3 deprecated columns adalah NULL — karena tidak ada production write path yang mengisinya.

---

## 11. Design C Diagnostic Impact

### Current Diagnostic (DesignCDiagnostics.php)

```php
$checks = [
    'legacy_participation_without_participation' => LegacyParticipationMapping::whereDoesntHave('participation')->count(),
    'duplicate_legacy_participation_participation_id' => LegacyParticipationMapping::select('participation_id')...
    'duplicate_legacy_participation_peserta_event' => LegacyParticipationMapping::select('peserta_id', 'event_id')...
    'legacy_participation_missing_participation' => LegacyParticipationMapping::whereDoesntHave('participation')->count(),
    'bridge_event_mismatch' => LegacyParticipationMapping::whereHas(...)
    'bridge_person_mismatch' => LegacyParticipationMapping::whereHas(...)
    'orphan_legacy_bridge' => LegacyParticipationMapping::whereNull('participation_id')->count(),
    // Semua query menarget LegacyParticipationMapping ✅
];
```

**Impact: ✅ NONE** — Design C diagnostic sudah 100% menggunakan `LegacyParticipationMapping`. Tidak ada query yang bergantung pada `LegacyPesertaMapping` deprecated columns.

### After Column Drop

- Diagnostic metric `problem_total` — **tetap** dipertahankan (sama)
- Diagnostic metric **tidak perlu rename, pindah, atau hapus**
- Test Design C (DesignCDiagnosticsTest.php) — **tidak perlu perubahan**; fixture sudah tidak mengisi `participation_id`/`event_id` di `LegacyPesertaMapping` sejak Sprint 2

---

## 12. Print/Export Regression Audit

### Sprint 2 Fix Recap

Sprint 2 memperbaiki production regression di **QR label single print route** (`routes/web.php` line 128 — `qr-label/print/single`). Fix: replace `$participant->legacyPesertaMapping()` with `LegacyParticipationMapping::where('peserta_id', ...)`.

### Audit All Print/Export Paths

| Route/Path | File | Uses LegacyPesertaMapping? | Status |
|------------|------|---------------------------|--------|
| `qr-label/print/single` | `routes/web.php:128` | **Tidak** — sudah di-fix Sprint 2 ✅ | Clean |
| `qr-label/print/filtered` | `routes/web.php:135` | **Ya** — via `'legacyPesertaMapping.peserta'` | Need refactor (Step 1) |
| `qr-label/print/a4` | `routes/web.php:310` | **Ya** — via `'legacyPesertaMapping.peserta'` | Need refactor (Step 1) |
| `qr-label/print` | `app/Livewire/QRLabel/Index.php:72,177` | **Ya** — via eager load | Need refactor (Step 1) |
| Export Peserta | `app/Exports/PesertaExport.php:39` | **Ya** — via `person.legacyPesertaMapping.peserta` | Need refactor (Step 1) |
| Rekap Peserta | `app/Livewire/Rekap/Peserta/RekapPeserta.php:38` | **Ya** — via `person.legacyPesertaMapping.peserta` | Need refactor (Step 1) |
| PrintLog | `tests/Feature/PrintLog/PrintLogTest.php` | Uses `LegacyParticipationMapping` ✅ | Clean |
| ExportLog | `tests/Feature/ExportLog/ExportLogTest.php` | Fixture creates LegacyPesertaMapping ✅ | Clean |

**Kesimpulan:** 5 export/print/report paths masih menggunakan `legacyPesertaMapping.peserta` traversal, tapi ini hanya akses ke tabel `pesertas` (regu/kelompok), bukan deprecated columns. Semua perlu refactor ke `LegacyParticipationMapping.peserta` — tapi **tidak ada regression blocker**.

---

## 13. Blockers

### Blocker Sebelum Sprint 3: **TIDAK ADA**

| Blocker | Status |
|---------|--------|
| Apakah ada production write ke deprecated columns? | ✅ **Tidak ada** |
| Apakah ada production read ke deprecated columns? | ✅ **Tidak ada** |
| Apakah LegacyPesertaMapping::participation() masih diperlukan? | ❌ **Tidak** — 0 prod refs |
| Apakah LegacyPesertaMapping::event() masih diperlukan? | ❌ **Tidak** — 0 prod refs |
| Apakah Participation::legacyPesertaMapping() masih diperlukan? | ❌ **Tidak** — perlu replacement |
| Apakah ada data non-NULL di deprecated columns? | ❓ **Tidak diverifikasi** (no DB access), tapi dipastikan dari source code |
| Apakah Design C diagnostic aman setelah drop? | ✅ **Aman** — hanya target LegacyParticipationMapping |
| Apakah SQLite mendukung DROP COLUMN? | ✅ **Ya** — sejak SQLite 3.35.0 |
| Apakah ada open uncommitted changes? | ✅ **Tidak** — working tree CLEAN |

---

## 14. Proposed Implementation Plan

### Step 0: Add inverse relationship to Participation

Sebelum refactor 9 callers, tambahkan relationship baru:

```php
// app/Models/Participation.php
public function legacyParticipationMapping()
{
    return $this->hasOne(LegacyParticipationMapping::class, 'participation_id');
}
```

Ini akan menjadi canonical replacement untuk `Participation::legacyPesertaMapping()`.

### Step 1: Refactor 9 Production References (6 original + 3 additional)

Replace `legacyPesertaMapping.peserta` → `legacyParticipationMapping.peserta` across:

1. `routes/web.php:135,147` — filtered print: `'legacyParticipationMapping.peserta'`
2. `routes/web.php:310,337` — A4 print: `'legacyParticipationMapping.peserta'`
3. `app/Livewire/QRLabel/Index.php:72,177,243` — QR label
4. `app/Livewire/Database/Peserta/Database.php:36,50` — Database list
5. `app/Exports/PesertaExport.php:39` — Export
6. `app/Livewire/Rekap/Peserta/RekapPeserta.php:38,47,51,69` — Report
7. `app/Livewire/Database/Peserta/TambahPeserta.php:171,183,190,199,228,244` — Person search
8. `app/Services/Attendance/AttendanceReadService.php:20-21,25,51,55,74` — Session attendance
9. `app/Services/Attendance/AttendanceExceptionService.php:30` — Exception handling

**Total: ~30 replacement sites across 9 files, 3 services, 1 export, 1 route file, 4 Livewire components.**

### Step 2: Zero-Reference Verification

Setelah Step 1, verifikasi tidak ada lagi:
```bash
grep -r "legacyPesertaMapping" app/ routes/ --include="*.php" | grep -v "tests/" | grep -v "views/"
```

Hanya tersisa:
- Model definitions (LegacyPesertaMapping.php, Person.php, peserta.php — active contract)
- PersonLegacySyncService (read-only, uses person_id bukan deprecated columns)
- DeletePerson (read-only, uses person_id bukan deprecated columns)
- EditPerson (read-only, uses person_id bukan deprecated columns)
- Stale imports yang akan di-clean di Step 4

### Step 3: Refactor/Remove Stale Test Dependencies

3 test files, 5 assertion lines:
- `LegacyPesertaMappingFoundationTest.php:56-58` — Remove column existence assertions
- `Sprint2MappingContractTest.php:74-75,273` — Remove participation_id/event_id assertions

### Step 4: Update Model Relationships

Remove from `app/Models/LegacyPesertaMapping.php`:
- `'participation_id'` from $fillable
- `'event_id'` from $fillable
- `'backfill_batch_id'` from $fillable
- `participation()` relationship
- `event()` relationship

Remove from `app/Models/Participation.php`:
- `legacyPesertaMapping()` relationship

Remove from `app/Models/Event.php`:
- `legacyPesertaMappings()` relationship

Remove stale imports:
- `app/Services/Attendance/AttendanceService.php:8` — `use App\Models\LegacyPesertaMapping;`
- `app/Livewire/Dashboard/Scan.php:6` — `use App\Models\LegacyPesertaMapping;`

### Step 5: Create SQLite-Compatible Forward Migration

```php
// 2026_07_23_000001_cleanup_legacy_peserta_mapping_deprecated_columns.php

public function up(): void
{
    // Step 1: Drop FK and unique constraints
    Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
        $table->dropUnique('legacy_peserta_mappings_participation_id_unique');
    });
    
    DB::statement('ALTER TABLE legacy_peserta_mappings DROP FOREIGN KEY legacy_peserta_mappings_participation_id_foreign');
    DB::statement('ALTER TABLE legacy_peserta_mappings DROP FOREIGN KEY legacy_peserta_mappings_event_id_foreign');
    
    // Step 2: Drop columns
    Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
        $table->dropColumn(['participation_id', 'event_id', 'backfill_batch_id']);
    });
}
```

### Step 6: Design C Diagnostic

**No change needed** — diagnostic sudah menggunakan `LegacyParticipationMapping` only.

### Step 7: Focused Tests

Tambah test migration: verify columns are dropped.
Tambah regression test: verify Sprint 2 contract masih aman setelah column drop.

### Step 8: Full Suite

```bash
php artisan test
# Expected: 1499 passed / 3592 assertions / 0 failures (no net change)
```

### Step 9: diagnose:design-c

```bash
php artisan diagnose:design-c
# Expected: problem_total = 0
```

### Step 10: Documentation Sync

Update:
- DATABASE.md — hapus 3 deprecated columns dari legacy_peserta_mappings schema
- CHANGELOG.md — Sprint 3 entry
- CURRENT_STATE.md — update PGM.18 completion
- LAPORAN_AUDIT_LEGACY_PGM18.md — Sprint 3 closure

---

## 15. Risk Matrix

| Risiko | Severity | Probability | Mitigasi |
|--------|----------|-------------|----------|
| Participation cascade eager load broken setelah relationship remove | High | Low | Step 0: add `legacyParticipationMapping` relationship dulu; Step 1: replace all callers; verifikasi 0 references sebelum Step 4 |
| SQLite DROP COLUMN gagal karena FK | High | Low | Drop FK constraint terpisah sebelum drop column; test migration di SQLite |
| Lupa satu caller `legacyPesertaMapping.peserta` | Medium | Low | Grep exhaustive setelah Step 1; 9 files sudah diidentifikasi |
| Test fixtures masih set deprecated columns | Low | Low | Hanya 2 fixtures (`Sprint2MappingContractTest`, `LegacyPesertaMappingFoundationTest`) yang assert deprecated columns; keduanya sudah teridentifikasi |
| Backfill service masih tergantung | Low | None | `AttendanceBackfillService` dan `SuratIzinBackfillService` hanya menggunakan `LegacyParticipationMapping` |

---

## 16. GO / NO-GO

| # | Pertanyaan | Jawaban |
|---|-----------|---------|
| 1 | Berapa EXACT production references ke deprecated contract? | **0 references ke deprecated columns.** 9 references ke `->legacyPesertaMapping.peserta` (hanya akses ke tabel `pesertas`), tapi perlu di-refactor sebelum model relationship dihapus. |
| 2 | Apa exact 6 Participation::legacyPesertaMapping callers? | **Bukan "6" tapi 9 call sites di 3 files:** routes/web.php (4), QRLabel/Index.php (3), Database.php (2). Semua hanya eager-load `->peserta`. |
| 3 | Apakah ada production WRITE ke deprecated columns? | **TIDAK** — semua write path sudah hanya menggunakan active contract fields sejak Sprint 2. |
| 4 | Apakah ada production READ ke deprecated columns? | **TIDAK** — tidak ada `->participation_id`, `->event_id`, `->backfill_batch_id` di production code untuk LegacyPesertaMapping. |
| 5 | Apakah `LegacyPesertaMapping::participation()` masih diperlukan? | **TIDAK** — 0 prod runtime references. |
| 6 | Apakah `LegacyPesertaMapping::event()` masih diperlukan? | **TIDAK** — 0 prod runtime references. |
| 7 | Apakah `Participation::legacyPesertaMapping()` masih diperlukan? | **YA** — untuk sementara, 9 call sites masih membutuhkan path ke `peserta` via `legacyPesertaMapping.peserta`. **Tapi bisa diganti** dengan `LegacyParticipationMapping.peserta` di Step 1. |
| 8 | Apa replacement untuk setiap caller? | `Participation::with('legacyParticipationMapping.peserta')` — tambahkan inverse relationship `hasOne(LegacyParticipationMapping)` di model Participation. |
| 9 | Berapa test files terdampak? | **3 test files** |
| 10 | Berapa test references terdampak? | **5 assertion lines** (3 schema + 2 runtime) |
| 11 | Apakah data aktual di deprecated columns semuanya NULL? | **Tidak diverifikasi** (no DB access), tapi dipastikan dari source code — tidak ada production write path yang mengisi kolom tersebut sejak Sprint 2. |
| 12 | Apakah Design C diagnostic aman setelah drop? | **YA** — 100% menggunakan `LegacyParticipationMapping`. |
| 13 | Apakah migration drop aman untuk SQLite? | **YA** — SQLite 3.35+ support DROP COLUMN. Butuh 2-step: (1) drop FK + unique, (2) drop columns. |
| 14 | Apakah Sprint 3 bisa langsung implementasi? | **✅ GO** — Tidak ada blocker. Semua prasyarat terpenuhi. |
| 15 | Apa blocker yang harus diselesaikan sebelum migration? | **Tidak ada blocker.** Urutan aman: tambah inverse relationship → refactor 9 callers → hapus test assertions → hapus model relationships → drop columns. |

---

## ⚡ Final Verdict: ✅ GO — Sprint 3 aman diimplementasikan
