# LEGACY RETIREMENT PLAN

> Rencana pensiun (retirement) layer legacy menuju **UAT** dan penghapusan komponen lama secara bertahap.
>
> Sprint 3.3 — **Legacy Retirement Preparation & UAT Readiness**.
>
> Dokumen ini **tidak** mengubah business logic, Permission Engine, EventRole, Route, Dashboard flow, database, migration, seeder, bridge model, maupun `ATTENDANCE_LEGACY_WRITE`. Semua rencana bersifat **usulan untuk Sprint 4+**.

---

## 1. Current Legacy Components

Komponen legacy dikelompokkan berdasarkan peran saat ini di sistem.

### 1.1 Bridge Models (WAJIB PERTAHANKAN — bukan kandidat retire)

| Component | Model | Status | Catatan |
|-----------|-------|--------|---------|
| `legacy_peserta_mappings` | `LegacyPesertaMapping` | Aktif read/write | Bridge peserta↔Person. Kontrak PGM.18: peserta↔Person only |
| `legacy_participation_mappings` | `LegacyParticipationMapping` | Aktif read/write | Bridge peserta↔Participation per-event |

Kedua bridge adalah **satu-satunya jembatan identitas** antara data legacy CAI dan arsitektur canonical (Person→Participation). Seluruh resolver, registrasi, attendance, surat izin, QR, rekap, dan tooling migrasi bergantung padanya. **Tidak boleh dihapus.**

### 1.2 Legacy Data Tables

| Component | Model | Klasifikasi | Status |
|-----------|-------|-------------|--------|
| `absensis` | `Absensi` | **Migration + Test Only** | Tidak ditulis di production (PGM.20 retired). Hanya dibaca tooling rekonsiliasi |
| `izin_absensis` | `IzinAbsensi` | **Compatibility + Production guard** | Masih ditulis fallback untuk peserta unmappable; dibaca sebagai guard delete/replacement |
| `sesi_absensis` | `SesiAbsensi` | **Production** (bukan legacy) | Session CRUD + scan + rekap aktif — **bukan kandidat retire** |
| `surat_izins` | `SuratIzin` | **Production** (bukan legacy) | Workflow live aktif — **bukan kandidat retire** (kolom `peserta_id` adalah coupling legacy) |
| `pesertas` | `peserta` | **Compatibility** (dual-maintain) | Masih dibentuk/ditulis oleh RegistrationService sebagai compat |
| `desas` | `desa` | **Production** (master data) | Tabel lookup shared — efektif canonical dimension |
| `kelompoks` | `kelompok` | **Production** (master data) | Tabel lookup shared — efektif canonical dimension |
| `regus` | `regu` | **Production** (master data) | Tabel lookup shared (`Participations.regu_id`); `pesertas.regu_id` sudah retired PGM.19 |

### 1.3 Legacy Services & Tooling

| Component | Klasifikasi | Status |
|-----------|-------------|--------|
| `LegacyParticipationResolver` | **Compatibility** | Resolver identity via bridge — dipakai attendance, QR, registrasi |
| `PersonLegacySyncService` | **Compatibility** | Sync Person→peserta (nama, gender, desa, kelompok) |
| `AttendanceParityService` | **Migration** | Rekonsiliasi legacy vs canonical |
| `AttendanceBackfillService` | **Migration** | Backfill canonical dari legacy |
| `SuratIzinBackfillService` | **Migration** | Backfill participation_id |
| `AttendanceStatus` (command) | **Migration** | Status write-mode + hitungan tabel |
| `AttendanceDiagnose` (command) | **Migration** | Diagnosa legacy unmappable |
| `AttendanceParity` (command) | **Migration** | Parity CLI |
| `AuditLegacyData` (command) | **Migration** | Kualitas data legacy |
| `DesignCDiagnostics` (command) | **Migration** | Bridge integrity diagnostic |

### 1.4 Feature Flag

| Config | Nilai saat ini | Dipakai oleh |
|--------|----------------|--------------|
| `ATTENDANCE_LEGACY_WRITE` | `.env=false`, default `true` | `AttendanceStatus` command (status only) + tests. **Tidak ada gate kode live pada flag ini** — perilaku fallback mengikuti resolusi `Participation` |

> **Temuan kunci audit (S3.3):** di kode `app/`, hanya `AttendanceStatus.php` yang membaca flag. Write behavior aktual di `AttendanceService` / `AttendanceExceptionService` / `SuratIzinService` **tidak** di-guard oleh flag — fallback write legacy terjadi untuk peserta yang **unmappable** (tidak ada Participation), terlepas dari nilai flag. Flag saat ini bersifat **informasional/status + toggle test**.

---

## 2. Dependency

### 2.1 Dependency Graph (simplified)

```
                        ┌───────────────────────────┐
                        │      Person (canonical)    │
                        └─────────────┬─────────────┘
                                      │ person_id
                                      ▼
                        ┌───────────────────────────┐
                        │     Participation         │
                        │  (attendance_code, regu)  │
                        └─────────────┬─────────────┘
                                      │ participation_id / event_id
            ┌─────────────────────────┼─────────────────────────┐
            │                         │                         │
            ▼                         ▼                         ▼
 ┌───────────────────┐   ┌────────────────────────┐   ┌───────────────────┐
 │ LegacyParticipation│   │  EventAttendance       │   │   Event           │
 │ Mapping (BRIDGE)   │   │  (canonical fact)      │   │                   │
 └─────────┬─────────┘   └────────────────────────┘   └─────────┬─────────┘
           │ peserta_id                                       │ event_id
           ▼                                                    ▼
 ┌───────────────────┐                              ┌───────────────────┐
 │ LegacyPesertaMapping │                              │  SesiAbsensi      │
 │ (BRIDGE)          │                              └───────────────────┘
 └─────────┬─────────┘
           │ peserta_id
           ▼
 ┌───────────────────┐      ┌───────────────────┐
 │   peserta (legacy)│◄────►│ RegistrationService│  dual-maintain
 └─────────┬─────────┘      └───────────────────┘
           │ peserta_id
           ▼
 ┌───────────────────┐      ┌───────────────────┐      ┌───────────────────┐
 │   Absensi         │      │  IzinAbsensi      │      │  SuratIzin        │
 │   (historical)    │      │  (fallback write) │      │  (live workflow)  │
 └───────────────────┘      └───────────────────┘      └───────────────────┘

 Lookup dimensions (bukan legacy):
   desa ──────────── Person.desa_id / peserta.desa_id
   kelompok ──────── Person.kelompok_id / peserta.kelompok_id
   regu ──────────── Participation.regu_id
```

### 2.2 Production Dependencies (live flows)

| Legacy component | Prod dependents |
|------------------|-----------------|
| `peserta` | RegistrationService (dual-write), Dashboard/Scan (manual fallback), Database/Peserta list, EditPeserta/HapusPeserta, SuratIzin/Create, AttendanceService (identity fallback), AttendanceExceptionService, CaiParticipantReplacementService, PesertaImport |
| `IzinAbsensi` | AttendanceService (izin check), AttendanceExceptionService (fallback write), SuratIzinService (fallback), CaiParticipantReplacementService (guard), HapusPeserta (delete guard) |
| `LegacyPesertaMapping` | AttendanceReadService, PesertaExport, PersonLegacySyncService, EditPerson, IdentityCorrectionService, LegacyParticipationResolver |
| `LegacyParticipationResolver` | AttendanceService, QRIdentityResolver |
| `PersonLegacySyncService` | EditPeserta, EditPerson |

### 2.3 Test-Only Dependencies

Lebih dari **40 file test** mereferensikan tabel/model legacy (`Absensi`, `IzinAbsensi`, `peserta`, bridge). Menghapus komponen legacy **harus** disertai refactor test. Ini bukan blocker — dokumentasi untuk Sprint 4.

---

## 3. Removal Order

Urutan pensiun dirancang agar **canonical selalu menjadi satu-satunya source of truth** sebelum tabel legacy dihapus.

### Phase 0 — Gatekeeping (TIDAK dalam sprint ini)
- Pastikan **seluruh flow baru** menulis canonical (EventAttendance / Person→Participation).
- `ATTENDANCE_LEGACY_WRITE` dibiarkan sesuai keputusan opsional (default `true`). Flip default bukan keputusan sprint ini.

### Phase 1 — Pensiun fallback write (Sprint 4 Candidate)
1. **Attendance scan** — hapus fallback `peserta::whereRaw('attendance_code')` di `AttendanceService::resolveIdentity()` (PGM.20: NIP sudah retire; attendance_code fallback berikutnya).
2. **Izin fallback** — di `AttendanceExceptionService::recordIzin()` dan `SuratIzinService`, hapus branch `IzinAbsensi::create()` untuk unmappable (kebijakan baru: izin wajib punya Participation).
3. **Manual izin guard** — pindahkan guard `IzinAbsensi` di `CaiParticipantReplacementService` & `HapusPeserta` ke `EventAttendance` (canonical).
4. Setelah 1–3: **tabel `izin_absensis` tidak lagi ditulis** → retire kolom/relasi.

### Phase 2 — Pensiun dual-write peserta (Sprint 4 Candidate)
1. **RegistrationService** — hapus dual-write `peserta::create/update`. Person→Participation menjadi satu-satunya path.
2. **PersonLegacySyncService** — freeze sync ke peserta (no-op) bila mapping masih ada; lalu hapus pemanggilnya.
3. **Scan manual fallback** (`Dashboard/Scan`), **SuratIzin/Create** — gunakan Participation/Person, bukan `peserta`.
4. **CaiParticipantReplacementService** — pertahankan bridge (peserta row dipakai historis), tetap via mapping.

### Phase 3 — Pensiun read path legacy
1. **AttendanceReadService** — hapus eager-load `person.legacyPesertaMapping.peserta.kelompok`; gunakan `person.desa/kelompok` + `participation.regu`.
2. **PesertaExport** — hapus `whereHas('person.legacyPesertaMapping.peserta')`; filter via Participation.
3. **QRIdentityResolver** — pastikan resolusi hanya via Participation/Person (bridge optional).

### Phase 4 — Physical retirement (Sprint 4+)
1. Hapus migration/kolom legacy: `absensis`, `izin_absensis` (setelah Phase 1–2), `pesertas` (setelah semua flow pindah).
2. **Bridge model tetap dipertahankan** — `legacy_peserta_mappings` / `legacy_participation_mappings` adalah artefak migrasi yang **disimpan** untuk histori identity. Hanya pemakaian runtime yang boleh diputus bertahap.
3. Tooling migration (`AttendanceBackfill`, `AttendanceParity`, `AttendanceStatus`, `AttendanceDiagnose`, `AuditLegacyData`) dipensiun setelah rekonsiliasi final.

---

## 4. Risk

| Risk | Level | Mitigasi |
|------|-------|----------|
| Menghapus fallback attendance_code membuat peserta lama tidak bisa absen | **Tinggi** | Pastikan semua peserta legacy sudah di-backfill ke Participation (S3.5). Verifikasi parity = 100% sebelum cutover |
| Menghapus fallback izin memutus workflow Surat Izin untuk peserta unmappable | **Tinggi** | Wajib kebijakan: surat izin hanya untuk Participation. Validasi sebelum deploy |
| Menghapus dual-write peserta menghapus data historis rekap | **Tinggi** | Rekap sudah canonical via Participation. Backfill penuh + parity check |
| `ATTENDANCE_LEGACY_WRITE` flag tidak konsisten dengan perilaku aktual | **Sedang** | Dokumentasikan flag sebagai status; jangan jadikan kontrol kode |
| 40+ test file mereferensikan legacy — refactor besar | **Sedang** | Refactor test bertahap per Phase, jalankan suite tiap Phase |
| Bridge model kehilangan referensi bila `pesertas` dihapus | **Sedang** | Bridge dipetakan ke Person/Participation dulu; `pesertas` dihapus terakhir |
| QueryException 23000 pada write legacy saat race | Rendah | Sudah di-handle exception path; hilang setelah fallback dihapus |

---

## 5. Rollback Plan

Setiap Phase dipisahkan ke **commit terpisah** agar dapat di-revert independen:

| Phase | Rollback |
|-------|----------|
| Phase 1 (fallback write izin/scan) | Revert commit; fallback tetap menulis `IzinAbsensi`. Tidak ada data loss (canonical tetap ditulis) |
| Phase 2 (dual-write peserta) | Revert commit; RegistrationService kembali dual-write. Data Person/Participation tetap utuh |
| Phase 3 (read path) | Revert commit; eager-load dikembalikan |
| Phase 4 (physical drop) | **Pertahankan migration drop terakhir**; buat migration rollback (`down()`) yang restore tabel + data dari backup |

**Prinsip:** canonical selalu dapat dijadikan source of truth. Sebelum drop fisik, lakukan:
1. Backup penuh DB.
2. `php artisan attendance:parity` → target 100% match.
3. `php artisan diagnose:design-c` → `problem_total = 0`.
4. Setelah drop, pertahankan bridge mapping sebagai artefak.

---

## 6. Acceptance Criteria

Retirement dianggap selesai bila:

1. **Tidak ada write path legacy yang aktif** di production:
   - `absensis` = 0 write (sudah tercapai PGM.20).
   - `izin_absensis` = 0 write dari flow baru.
   - `pesertas` = 0 write dari RegistrationService (dual-write dihapus).
2. **Tidak ada read path legacy** di production:
   - `AttendanceReadService`, `PesertaExport`, `QRIdentityResolver`, `Rekap*` tidak lagi eager-load/query `pesertas` / `absensis` / `izin_absensis`.
3. **Bridge model dipertahankan** sebagai artefak migrasi:
   - `legacy_peserta_mappings` / `legacy_participation_mappings` tidak dihapus.
   - Semua runtime resolver memakai `Participation`/`Person` langsung.
4. **Tooling migration** (`AttendanceBackfill`, `AttendanceParity`, `AttendanceStatus`, `AttendanceDiagnose`, `AuditLegacyData`) dipensiun atau ditandai read-only historical.
5. **Test suite tetap hijau** setelah setiap Phase:
   - `php artisan test --parallel` → target **1944+ passed / 0 failures** per Phase.
   - Refactor test legacy selesai tanpa mengurangi coverage canonical.
6. **Parity & integrity**:
   - `attendance:parity` = 100%.
   - `diagnose:design-c` = `problem_total = 0`.
7. **Dokumentasi sinkron**:
   - `DATABASE.md`, `ARCHITECTURE.md`, `DATAFLOW.md`, `HANDOFF.md`, `PROJECT_STRUCTURE.md` tidak lagi menyebut tabel legacy sebagai bagian runtime.

---

## Sprint 4 Candidate (dari audit S3.3)

Perubahan yang **tidak dilakukan** di sprint ini karena berpotensi mengubah behavior / melanggar aturan (Route & Permission Engine):

| # | Candidate | Alasan ditunda |
|---|-----------|----------------|
| C1 | Pensiun fallback attendance_code di `AttendanceService` | Mengubah behavior scan peserta lama |
| C2 | Hapus branch fallback `IzinAbsensi::create()` | Mengubah behavior workflow izin unmappable |
| C3 | Hapus dual-write `peserta` di RegistrationService | Mengubah data yang ditulis saat registrasi |
| C4 | Ganti guard `IzinAbsensi` → `EventAttendance` di replacement/delete | Mengubah guard behavior |
| C5 | Flip `ATTENDANCE_LEGACY_WRITE` default → `false` | Mengubah default config |
| C6 | Wire `manage-import` ability ke route `/import/regu` & `/import/peserta` | Mengubah permission mapping route (diluar aturan sprint) |
| C7 | Tambah `can:view-master-data` / ability pada route `/regu` | Mengubah Route (diluar aturan sprint) |
| C8 | Refactor 40+ test file legacy | Perlu sprint khusus test refactor |
| C9 | Drop migration fisik `absensis` / `izin_absensis` / `pesertas` | Melanggar aturan "jangan ubah migration/database" |

---

## Related Docs

- `UAT_CHECKLIST.md` — UAT checklist (area Import/Permission terkait)
- `DATABASE.md` — Skema tabel legacy vs canonical
- `DATAFLOW.md` — Alur data canonical
- `HANDOFF.md` — Ringkasan legacy components
- `DEAD_CODE.md` — Dead code report
- `CHANGELOG.md` — Riwayat perubahan
