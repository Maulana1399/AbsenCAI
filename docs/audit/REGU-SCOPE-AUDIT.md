# REGU SCOPE & EVENT BOUNDARY AUDIT

> Audit khusus fitur **Regu** untuk memverifikasi temuan H1 dari `AUTH-EVENT-FOUNDATION-AUDIT.md`
> ("`/regu` + `Database/Regu/*` CRUD tanpa gate sama sekali").
> Fase ini **AUDIT ONLY** — tidak ada perubahan kode, migration, route, authorization, atau UI.
> Source of truth: actual code + actual migrations + actual tests.

- **Tanggal:** 2026-08-12
- **Status:** REGU SCOPE AUDIT COMPLETE
- **Output:** `docs/audit/REGU-SCOPE-AUDIT.md`

---

## 1. Executive Summary

Audit ini **membantah hipotesis awal** bahwa domain Regu berbentuk `Event CAI → Kelompok → Regu`.

**Actual code menunjukkan `regus` adalah entitas master global datar:**

```
regus (id, regu, jenis_kelamin, timestamps)   ← TIDAK ada FK event/kelompok/desa
   ↑ regu_id (nullable, nullOnDelete)
participations (event_id → events) → person
```

- Regu **tidak punya link apa pun ke Event atau Kelompok** di schema (`regus` hanya `regu` + `jenis_kelamin` + unique `regu`).
- Satu-satunya jalur dari Regu ke Event adalah **transitif via `participations`** (`regu → participations.event_id`).
- Kelompok tidak menghubungkan Regu; Peserta legacy (`pesertas.regu_id`) sudah dihapus (`2026_08_11_000001`).
- Tidak ada enforcement bahwa Regu hanya milik CAI: tidak ada cek `isCai()` di route `/regu` maupun komponen Regu CRUD. Yang mengecek `isCai()` hanya `GantiPeserta` dan `CaiParticipantReplacementService` (flow penggantian peserta).
- **H1 diverifikasi sebagai CONFIRMED SECURITY BUG untuk jalur mutasi (create/edit/delete)** — setiap user `auth+verified` dapat membuat/mengubah/menghapus Regu global. `ImportRegu` (satunya mutator yang punya gate) memakai `manage-participants`; `TambahRegu/EditRegu/HapusRegu` **tanpa gate sama sekali**. Halaman GET `/regu` justru **sengaja terbuka** (diuji di test suite, backward-compat sesuai docs).

Karena Regu tidak punya relasi Event/Kelompok, opsi perbaikan "gunakan relationship existing: User → Active Event → Regu → Kelompok → Event" **tidak tersedia** — tidak ada jalur tersebut di code. Rekomendasi perbaikan harus memakai jalur authorization yang sudah ada (`Gate::authorize('manage-participants')`, sama seperti `ImportRegu` dan komponen mutasi peserta CAI), dan menunda event-scoping penuh ke refactor "event-scoped configuration" yang memang sudah direncanakan di `docs/TERMINOLOGY.md`.

---

## 2. Actual Regu Architecture

### 2.1 Inventory Regu (file → fungsi → domain → event-aware → authorization)

| File | Fungsi | Domain | Event-aware? | Authorization? |
|---|---|---|---|---|
| `app/Models/regu.php` | Model `regu` (fillable: `regu`, `jenis_kelamin`); relasi `participations()` hasMany | Master global | ❌ tidak ada FK event | — |
| `database/migrations/2025_06_16_071211_create_regu_table.php` | Buat `regus (id, regu, timestamps)` — **tanpa FK** | Master global | ❌ | — |
| `database/migrations/2026_07_01_000003_add_jenis_kelamin_to_regus_table.php` | Tambah `jenis_kelamin` enum nullable | Master global | ❌ | — |
| `database/migrations/2026_07_02_000001_add_unique_constraints_to_tables.php` | Unique `regu` | Master global | ❌ | — |
| `database/migrations/2026_08_10_000001_add_regu_id_to_participations_table.php` | Tambah `participations.regu_id` nullable → regus (nullOnDelete) + index | Event (via participation) | ⚠️ transitif | — |
| `database/migrations/2026_08_11_000001_drop_regu_id_from_pesertas_table.php` | Hapus `pesertas.regu_id` (rebuild table) | Legacy | ❌ | — |
| `database/migrations/2026_07_21_095318_create_cai_participant_replacements_table.php` | `cai_participant_replacements.regu_id` nullable → regus | CAI | ⚠️ (nama tabel "cai") | — |
| `app/Livewire/Database/Regu/DataRegu.php` | `regu::all()` list + dispatch edit/delete | Global | ❌ | ❌ (no Gate) |
| `app/Livewire/Database/Regu/TambahRegu.php` | `regu::create()` | Global | ❌ | ❌ (no Gate) |
| `app/Livewire/Database/Regu/EditRegu.php` | `regu::where(id)->update()` | Global | ❌ | ❌ (no Gate) |
| `app/Livewire/Database/Regu/HapusRegu.php` | `regu->delete()` | Global | ❌ | ❌ (no Gate) |
| `app/Livewire/Database/Regu/ImportRegu.php` | Wizard import (extends `ImportWizardBase`) | Global | ⚠️ gate session-based | ✅ `Gate::authorize('manage-participants')` (`ImportWizardBase.php:112,162`) |
| `app/Services/Import/Adapters/Regu/ReguImport*` | Parser/Validator/Normalizer/DuplicateDetector/Committer | Global | ❌ (docblock: "Regu is a global entity (no event/desa/kelompok FK)") | via wizard gate |
| `app/Services/Placement/PlacementService.php` | `leastFilledRegu()` / `autoPlacement()` per event+gender | Event (CAI flow) | ⚠️ eventId hanya untuk hitung count | — |
| `app/Services/Import/Adapters/Participation/ParticipationImportNormalizer.php:67` | Resolve `regu_id` by name saat import participation | Global lookup | ❌ | via import gate |
| `app/Livewire/QRLabel/Index.php:58` | `daftarRegu = regu::orderBy('regu')->get()` | Global dropdown | ❌ (list global) | page gated `manage-qr-labels` |
| `app/Livewire/Rekap/Peserta/RekapPeserta.php:38` | `daftarRegu = regu::all()` | Global dropdown | ❌ (list global) | page gated `view-reports` |
| `app/Livewire/Rekap/Absensi/RekapAbsensi.php:28` | `daftarRegu = regu::orderBy('regu')->get()` | Global dropdown | ❌ (list global) | page gated `view-reports` |
| `app/Livewire/Database/Peserta/Database.php:33` | `daftarRegu = regu::all()` | Global dropdown | ❌ (list global) | page gated `manage-participants` |
| `app/Livewire/Database/Peserta/EditPeserta.php:52` | `daftarRegu = regu::all()` | Global dropdown | ❌ (list global) | mutation gated `manage-participants` |
| `app/Livewire/Registrasi/Ulang.php:159` | `daftarRegu = regu::all()` | Global dropdown | ❌ (list global) | page gated `manage-registration` |
| `app/Livewire/Database/Peserta/TambahPeserta.php` | auto-placement regu saat tambah peserta | Event | ⚠️ via PlacementService | mutation gated `manage-participants` |
| `app/Livewire/Registrasi/SelfRegister.php` | auto-placement regu (regu_id nullable) | Event | ⚠️ via PlacementService | gated |
| `app/Livewire/Database/Peserta/GantiPeserta.php` | bawa `regu_id` ke replacement; **cek `$event->isCai()`** | CAI | ✅ | gated `manage-participants` |
| `app/Services/Cai/CaiParticipantReplacementService.php:121` | cek `$event->isCai()` sebelum replacement | CAI | ✅ | via caller gate |
| `app/Exports/PesertaExport.php:50-51` | filter `where('regu_id', ...)` pada query event-scoped | Event | ✅ (query scoped) | via caller gate |
| `app/Http/Controllers/PublicEventController.php:547-548` | filter `where('regu_id', ...)` pada query event-scoped | Event | ✅ | controller checks ownership |
| `app/Http/Controllers/ImportDataController.php:147,187` | `import/regu` + template | Global | ⚠️ gate session-based | route `can:manage-participants` |
| `routes/web.php:62-64` | `GET /regu` view | Global | ❌ | `auth`, `verified` only — **no can** |
| `routes/web.php:159-161` | `GET import/regu/template` | Global | ⚠️ | `can:manage-participants` |
| `routes/web.php:175-177` | `POST import/regu` | Global | ⚠️ | `can:manage-participants` |
| `resources/views/database/regu.blade.php` | Halaman Regu (DataRegu + TambahRegu + ImportRegu + EditRegu + HapusRegu) | Global | ❌ | mengikuti route |
| `resources/views/components/layouts/app/sidebar.blade.php:142-148` | Menu "Regu" di group "Peserta CAI" `@can('manage-participants')` (cabang CAI/default) | CAI UI | ⚠️ menu CAI-scoped, route global | `@can('manage-participants')` |
| Blade lain (`qr-label/index`, `rekap-absensi`, `rekap-peserta`, `database`, `edit-peserta`, `tambah-peserta`, `ulang`, `ganti-peserta`, `self-register`) | dropdown/filter Regu di halaman event-scoped | Event UI | ⚠️ dropdown global | page/mutation gated |

### 2.2 Ringkasan

- **Model:** `app/Models/regu.php` — tanpa relasi event/kelompok.
- **Migration:** 4 menyentuh `regus` (create, jenis_kelamin, unique, drop di pesertas) + 2 menambah FK ke regus dari participation & replacement.
- **Route:** hanya `GET /regu` (view) + `import/regu` + template. Tidak ada route `/regu/{id}`.
- **Livewire:** 5 komponen CRUD di `Database/Regu/*`.
- **Service:** `PlacementService` (auto-place), import adapter `Regu/*`.
- **Test:** `ReguImportFrameworkTest`, `MasterDataProtectionTest` (akses halaman + import), `RegistrationLivewireBindingTest` (CRUD happy-path admin), `MasterDataNavigationTest` (backward-compat), dsb. — lihat §10.

---

## 3. Database Relationship

### 3.1 Schema aktual

```
regus
 ├── id PK
 ├── regu  string, UNIQUE (regu)
 ├── jenis_kelamin enum('Laki - Laki','Perempuan') NULL
 └── timestamps

TIDAK ADA kolom: event_id, kelompok_id, desa_id, participation_id
```

FK **menuju** `regus` (incoming):

| Table | Column | Nullable | onDelete | Catatan |
|---|---|---|---|---|
| `participations` | `regu_id` | ✅ nullable | `nullOnDelete` | **Satu-satunya relasi live** |
| `cai_participant_replacements` | `regu_id` | ✅ nullable | `nullOnDelete` | snapshot replacement |
| `pesertas` (legacy) | `regu_id` | — | — | **DROPPED** `2026_08_11_000001_drop_regu_id_from_pesertas_table.php` |

### 3.2 Hubungan Kelompok — terbantahkan

- `kelompoks` = `kelompok_asal` + `desa_id` (`2025_06_16_071801_create_kelompok_table.php`). **Tidak ada `regu_id`.**
- `regus` tidak punya `kelompok_id`. **Tidak ada link Regu ↔ Kelompok.**
- Model `kelompok.php` hanya relasi `peserta()` dan `desa()`. Model `regu.php` hanya `participations()`.
- Peserta legacy (`pesertas`) punya `kelompok_id` dan `desa_id` (independen), dan `regu_id`-nya sudah dihapus.

**Kesimpulan:** diagram `Regu → Kelompok → Event` **salah**. Diagram aktual:

```
Regu (global master, tanpa FK ke mana pun)
   ↑  regu_id (nullable, nullOnDelete)
Participation (event_id NOT NULL → events; person_id NOT NULL → people)
   ↑
Person
```

Event dapat diturunkan dari Regu **hanya** secara transitif: `Regu → participations.event_id → Event`. Tidak ada FK langsung, sehingga authorization "Regu → Kelompok → Event" yang diusulkan pada prompt **tidak dapat digunakan** tanpa relasi baru.

---

## 4. Event Relationship

| Pertanyaan | Jawaban (dari code) |
|---|---|
| Apakah Regu punya FK ke Event? | **TIDAK.** `regus` tidak punya `event_id`. |
| Apakah Event dapat diturunkan dari Regu via Kelompok? | **TIDAK.** Tidak ada link Regu ↔ Kelompok. |
| Bagaimana Regu terhubung ke Event? | **Transitif** via `participations.regu_id → participations.event_id`. Sebuah Regu bisa dipakai oleh **banyak event** sekaligus (karena banyak participations dengan event berbeda). |
| Bisakah ada Regu yang "tidak dapat ditelusuri ke Event"? | **YA.** Regu bisa ada di tabel `regus` tanpa satupun participation (regu yang belum dipakai / sisa master). Regu ini sama sekali tidak punya jejak event. |
| Apakah menghapus Regu berdampak ke data event? | **YA** — `participations.regu_id` `nullOnDelete` → menghapus Regu **diam-diam men-set NULL** `regu_id` semua partisipan lintas event yang memakainya (data loss/integrity). |

---

## 5. Event Type Relationship

| Pertanyaan | Jawaban |
|---|---|
| 1. Apakah Regu hanya digunakan oleh CAI? | **Dalam praktik YA** (flow CAI: placement, database peserta, rekap, QR label, registrasi ulang, self-register), **tapi TIDAK di-enforce**. Tidak ada cek event_type di route `/regu` maupun komponen `Database/Regu/*`. |
| 2. Apakah Pengajian memiliki Regu? | **TIDAK** dalam praktik — `PengajianImportParityTest.php:144` menegaskan `Participation::first()->regu_id` adalah `null` untuk flow Pengajian; UI Pengajian tidak memunculkan Regu. Namun tidak ada guard skema yang mencegahnya. |
| 3. Apakah ada event type lain yang menggunakan Regu? | Competition **tidak** (memakai `competition_registrations`/`regus` hanya sebagai sisa legacy global; tidak ada guard). |
| 4. Apakah database mengikat Regu ke CAI secara implisit via Kelompok? | **TIDAK.** Regu tidak terikat ke Kelompok maupun Event. Satu-satunya petunjuk "CAI" adalah nama tabel `cai_participant_replacements` dan cek `isCai()` di `GantiPeserta`/`CaiParticipantReplacementService`. |
| 5. Apakah ada data Regu yang tidak dapat ditelusuri ke Event? | **YA** — regu tanpa participation (lihat §4). |

Penggunaan `isCai()` di seluruh code (`grep isCai`): hanya `Event.php` (definisi), `ActiveEventContext::isCurrentCai()`, `GantiPeserta.php:53,117`, `CaiParticipantReplacementService.php:121`. **Tidak ada satupun** di modul Regu CRUD atau route `/regu`.

---

## 6. Route & UI Audit

### 6.1 Route

| Route | Middleware | Gate | Event context | Catatan |
|---|---|---|---|---|
| `GET /regu` (`routes/web.php:62-64`) | `auth`, `verified` | ❌ **tanpa `can:`** | ❌ tanpa `resolve.active-event` | View `database.regu` → memuat semua komponen CRUD |
| `GET /import/regu/template` (`web.php:159-161`) | `auth`, `verified` | ✅ `can:manage-participants` | ⚠️ tanpa resolve → gate session-based | — |
| `POST /import/regu` (`web.php:175-177`) | `auth`, `verified` | ✅ `can:manage-participants` | ⚠️ tanpa resolve → gate session-based | menulis global `regus` |

Tidak ada `Route::model`/binding untuk `{regu}`; tidak ada route `/regu/{id}`. Semua CRUD via Livewire internal (component method, bukan HTTP endpoint).

### 6.2 Pola berbahaya `Regu::find($id)` / `regu::find($id)`

- `EditRegu.php:22` → `regu::find($id)` (dispatch `editRegu`)
- `EditRegu.php:46` → `regu::where('id', $this->regu_id)->update($data)`
- `HapusRegu.php:19,27` → `regu::find($id)` / `regu::find($this->regu_id)` lalu `delete()`
- `DataRegu.php:15,39` → `regu::all()`

Semua tanpa validasi event — **tapi ini bukan IDOR cross-event** karena Regu memang global (tidak ada konsep "Regu milik event X"). Cacatnya bukan *event-scoping* melainkan *ketiadaan otorisasi mutasi*.

### 6.3 UI / navigasi

- Sidebar: menu "Regu" hanya muncul pada cabang **CAI/default** (`sidebar.blade.php:105-150`) di dalam group "Peserta CAI" `@can('manage-participants')`. Jadi UI menu tampil untuk CAI dan user yang punya `manage-participants`; route tetap global.
- Dropdown "Regu" di halaman event-scoped (`RekapAbsensi`, `RekapPeserta`, `QRLabel`, `Database`, `EditPeserta`, `Ulang`) memakai **daftar global** (`regu::all()` / `regu::orderBy('regu')`), tidak difilter event → semua regu muncul di semua event. Minor scoping smell, bukan celah keamanan (filter yang diterapkan ke query participation tetap event-scoped).

---

## 7. Authorization Audit

### 7.1 Yang dipakai

- Route `/regu`: hanya `auth` + `verified`.
- `ImportRegu`: `Gate::authorize('manage-participants')` via `ImportWizardBase` → `EventPermissionService::allows()` → butuh active event (session) + person assignment + EventRole aktif berisi `manage-participants`.
- `TambahRegu::simpan()`, `EditRegu::update()`, `HapusRegu::destroy()`: **tidak ada `Gate::authorize`, tidak ada `can()`, tidak ada policy.** `regu::create/update/delete` langsung dieksekusi.
- Tidak ada Policy class untuk Regu. Tidak ada middleware khusus.

### 7.2 Apakah akses dibatasi "secara tidak langsung" via parent relation?

**TIDAK.** Regu tidak punya parent relation ke event/kelompok. Satu-satunya "indirect" adalah bahwa halaman `/regu` hanya mudah ditemukan dari sidebar CAI untuk user `manage-participants`. Tapi:
- route `/regu` bisa diakses langsung (`GET`) oleh siapa pun yang login+verified (diuji sengaja terbuka);
- komponen Livewire `TambahRegu/EditRegu/HapusRegu` bisa dipanggil langsung (crafted Livewire request) tanpa lewat UI.

Jadi **menyembunyikan menu ≠ authorization**, dan di sini bahkan menu pun tidak menyembunyikan apa-apa untuk mutasi (button selalu dirender pada `/regu`).

### 7.3 Gap persis

```
Current:
User (auth + verified) ──→ /regu (200) ──→ TambahRegu::simpan()        → regu::create()  ✅ (tanpa cek)
                                              EditRegu::update()        → regu::update()  ✅ (tanpa cek)
                                              HapusRegu::destroy()      → regu::delete()  ✅ (tanpa cek)
                                              ImportRegu::preview/commit → Gate::authorize('manage-participants') ✅
Desa/Kelompok (pembanding):
User ──→ /desa → Gate('view-master-data') ; mutasi → Gate('manage-master-data') ✅
```

**Tidak ada jalur authorization sama sekali untuk create/edit/delete Regu.** Semua user auth+verified (termasuk user `role=null` hasil `/register-user`, atau akun ber-role viewer/operator_scan) dapat memanggil method Livewire tersebut.

---

## 8. Cross-Event Access Test Analysis

### Skenario A — user akses CAI Event A mencoba Regu

- URL `/regu/{regu_event_B}` **tidak ada** — tidak ada route dengan parameter regu. Konsep "Regu milik Event B" tidak ada di data model (Regu global).
- Yang bisa dilakukan: `GET /regu` (200 untuk semua), lalu via Livewire `TambahRegu/EditRegu/HapusRegu` mengubah **Regu mana pun** termasuk yang dipakai partisipan Event B.
- Karena Regu tidak menyimpan data event, ini **bukan kebocoran data lintas-event**, melainkan **mutasi tidak sah atas data bersama** yang berdampak lintas-event (contoh: hapus Regu → `participations.regu_id` semua event menjadi NULL).

### Skenario B — user akses Pengajian Event A mencoba `/regu`

- `GET /regu` → **200** (route global, tanpa gate). Halaman menampilkan daftar global Regu.
- Mutasi → saat ini **berhasil tanpa gate** (bug). Setelah rekomendasi §12 dipasang, user Pengajian (yang umumnya tidak punya `manage-participants`) akan mendapat **403** pada mutasi.

### Skenario C — user akses CAI A, "Regu dari CAI B"

- Secara data, tidak ada konsep "Regu milik CAI B" — Regu adalah tabel global yang bisa dipakai banyak event.
- Saat ini user dapat membaca dan mengubah Regu apa pun (termasuk yang dipakai event lain) — tapi lagi-lagi ini karena Regu global, bukan karena bocor data event.
- Setelah gate `manage-participants` dipasang pada mutasi, user dengan akses CAI A dan memiliki assignment `manage-participants` pada event aktif (session) tetap bisa mengubah Regu global — karena Regu memang global, ability `manage-participants` (event-scoped) menjadi "pintu masuk" ke data bersama. Ini adalah trade-off yang perlu keputusan (lihat §12).

**Kesimpulan cross-event:** Regu tidak menyebabkan **cross-event data confidentiality breach** (tidak ada data event di dalamnya), tetapi menyebabkan **cross-event data integrity impact** bila mutasi tidak sah dijalankan (hapus/rename Regu memengaruhi semua event). Maka: *cross-event vulnerability* = **YES (integrity) / NO (confidentiality)**.

---

## 9. Regu vs Competition Team

| Aspek | Regu (existing) | Team (Competition) |
|---|---|---|
| Tabel | `regus` (id, regu, jenis_kelamin) | Belum ada; `competition_registrations.registration_type` string default `'individual'`; "team" saat ini via `participations.regu_id` + `regus` (sisa legacy) |
| Event link | **Tidak ada** (global) | Harusnya event-scoped (`event_id`) |
| Penggunaan | Pembagian peserta CAI (placement otomatis per gender, absensi, rekap, QR label, registrasi) | Pembagian tim lomba per kelas (players + substitutes) |
| Anggota | `participations.regu_id` (nullable, nullOnDelete) | Perlu entitas `Team` + `TeamMember` |
| Gender | Kolom `jenis_kelamin` pada regu untuk placement | Kelas competition sudah punya `gender` |
| Event boundary | Tidak ada | Wajib ada |

**Kesimpulan:** Regu dan Competition Team adalah **dua domain berbeda**. Memakai ulang `regus` sebagai Team akan (a) memaksa Regu menjadi event-scoped (perubahan besar), (b) mengotori tabel global dengan data lomba, (c) melanggar Design C dan kesepakatan bahwa Regu adalah struktur operasional CAI. **Jangan satukan.** Saat Competition dibangun, buat entitas Team/TeamMember event-scoped yang terpisah (lihat `AUTH-EVENT-FOUNDATION-AUDIT.md` §10).

---

## 10. Existing Tests

| Test | Isi terkait Regu |
|---|---|
| `tests/Feature/Security/MasterDataProtectionTest.php:400-427` | `GET /regu` **200 untuk semua role** (ketua_event, pj_divisi, operator_registrasi, operator_scan, juri, viewer) dan `null role`; `ImportRegu` → `assertForbidden` untuk viewer + `assertDatabaseMissing` |
| `tests/Feature/Security/RbacFoundationTest.php:247,398` | `GET /regu` 200 untuk `null role` (dan `role=null` tidak boleh akses route event lain) |
| `tests/Feature/MasterData/MasterDataNavigationTest.php:46,70,148-153,208-220` | route name `regu` = `/regu`; guest → redirect login; halaman terbuka tanpa active event; backward-compat terbuka |
| `tests/Feature/Event/EventFoundationTest.php:472` | `GET /regu` 200 |
| `tests/Feature/Registrasi/RegistrationLivewireBindingTest.php:1084-1137` | CRUD happy-path **sebagai admin** (create/edit/delete, `assertRedirect('/regu')`); `delete regu nullifies participation regu_id (nullOnDelete)` |
| `tests/Feature/Database/ReguImportFrameworkTest.php` | ImportRegu: preview/commit/duplikat/skip — as admin via Livewire |
| `tests/Unit/Services/Import/ReguImportTest.php` | Unit import regu |
| `tests/Feature/Database/PesertaImportParityTest.php:34,53` | Import peserta menulis `participation.regu_id` |
| `tests/Unit/Services/PlacementServiceTest.php:62-87` | Auto-placement per gender ke regu |
| `tests/Feature/Attendance/AttendanceStatusSummaryTest.php:25` | Regu dipakai di summary absensi |
| `tests/Feature/Pengajian/PengajianImportParityTest.php:144` | Participation Pengajian `regu_id === null` |

**Cakupan authorization saat ini:**
- ✅ authorized admin → CRUD (happy path)
- ✅ unauthorized (viewer/null) → `GET /regu` tetap 200 (intentional)
- ✅ unauthorized (viewer) → `ImportRegu` forbidden
- ❌ **TIDAK ADA** test: unauthorized → `TambahRegu::simpan` / `EditRegu::update` / `HapusRegu::destroy`
- ❌ **TIDAK ADA** test: authorized user event A → mutasi regu (sekarang tidak ada konsep "regu event A")
- ❌ **TIDAK ADA** test: non-CAI event → regu (sekarang tidak ada enforcement)

**Test yang perlu ditambahkan saat implementasi (bukan sekarang):**
1. `TambahRegu::simpan` → Forbidden untuk user tanpa `manage-participants`.
2. `EditRegu::update` → Forbidden untuk user tanpa `manage-participants`.
3. `HapusRegu::destroy` → Forbidden untuk user tanpa `manage-participants`.
4. (Opsional) Hapus Regu yang masih direferensi participations → ditolak / warning (integrity guard).
5. (Opsional) `GET /regu` tetap 200 untuk backward-compat setelah gate dipasang pada mutasi.

---

## 11. Documentation Drift

| Docs | Klaim | Code aktual | Verdict |
|---|---|---|---|
| `docs/TERMINOLOGY.md:18-20` | "Regu adalah struktur operasional CAI ... akan dipindahkan ke event-scoped configuration ... route `/regu` tetap tersedia untuk backward compatibility" | `regus` global tanpa FK; route `/regu` terbuka; **tidak ada enforcement CAI**; belum dipindah ke event-scoped | ⚠️ **DRIFT sebagian** — niat/arah sesuai code ("struktur operasional CAI" tercermin di pemakaian CAI saja), tapi code belum meng-enforce CAI dan belum event-scoped |
| `docs/DATABASE.md:122` | "Regu ... CRUD ✅ (legacy CAI, **not global master data**)" | Regu **secara fisik adalah data global** (tanpa event_id), hanya bukan bagian dari halaman Master Data | ⚠️ **DRIFT** — kontradiksi: dokumen menempatkan Regu di luar "Master Data (Global — no event_id)" padahal fisiknya global |
| `docs/FEATURE.md:33` | "Regu dikeluarkan dari Master Data (Legacy CAI Operational — route `/regu` tetap ada)" | Route `/regu` tetap ada dan terbuka; bukan bagian master data | ✅ Sesuai |
| `docs/ARCHITECTURE.md:108` | `Participation ├── regu (CAI only, nullable)` | `participations.regu_id` nullable, dipakai flow CAI; "CAI only" adalah praktik, bukan enforcement | ⚠️ DRIFT sebagian |
| `docs/MODULES.md:127` | Import Regu (IF-05) `POST /import/regu` + template | Sesuai; gate `manage-participants` | ✅ Sesuai |
| `docs/PERMISSION.md` / `ROLE_MATRIX.md:99` | `/regu` "(no gate)" | Benar; tapi tidak mencatat bahwa **mutasi CRUD juga tanpa gate** | ⚠️ Kurang lengkap (gap terdokumentasi sebagian) |

**Catatan khusus:** `docs/TERMINOLOGY.md` memang **menyatakan rencana** menjadikan Regu event-scoped configuration pada refactor terpisah. Audit ini menyimpulkan bahwa refactor tersebut **belum dilakukan** — code saat ini tetap global. Jadi klaim "regu = CAI-specific" adalah **aspirasi dokumentasi**, bukan fakta runtime.

---

## 12. H1 Classification

### Klasifikasi: CONFIRMED SECURITY BUG (untuk jalur mutasi create/edit/delete)

**Alasan berdasarkan code:**

1. **Mutasi tanpa otorisasi**: `TambahRegu::simpan()` (`regu::create`), `EditRegu::update()` (`regu::update`), `HapusRegu::destroy()` (`regu->delete`) — tidak ada `Gate::authorize`, `can()`, maupun policy. Dapat dipanggil oleh **siapa pun yang auth + verified**, termasuk user `role=null` dan role low-privilege.
2. **Dampak lintas-event (data integrity)**: Regu adalah data bersama global. Menghapus Regu yang direferensi `participations.regu_id` (nullOnDelete) **menghapus assignment Regu dari partisipan semua event secara diam-diam**. Rename Regu mengubah label di rekap/QR/absensi semua event. Ini merusak integritas data tenant lain.
3. **Melanggar kebijakan keamanan proyek**: `docs/PERMISSION.md` ("Setiap fitur baru wajib menambahkan permission", "Semua endpoint harus memvalidasi permission", deny-by-default, least privilege). Endpoint mutasi ini tidak memvalidasi permission.
4. **Inkonsisten dengan komponen sejenis**: `ImportRegu` memakai `Gate::authorize('manage-participants')`; mutasi peserta CAI (`Database/Peserta/*`) memakai `manage-participants`; master data desa/kelompok memakai `manage-master-data`. Regu CRUD **satu-satunya yang kosong**.
5. **Bukan IDOR cross-event confidentiality**: Regu tidak menyimpan data event; tidak ada kebocoran data sensitif lintas event. Klasifikasi ini mempertimbangkan akses write yang tidak sah atas data bersama (privilege escalation/access-control), bukan confidentiality.

**Nuansa — bagian GET dari H1:** `GET /regu` **sengaja terbuka** (diuji di `MasterDataProtectionTest`, `MasterDataNavigationTest`, `EventFoundationTest`, `RbacFoundationTest`; didukung `docs/FEATURE.md:33` & `docs/TERMINOLOGY.md:20`). Maka pernyataan H1 "route `/regu` tanpa gate sama sekali" adalah **sebagian benar** — page read memang tanpa gate (intentional backward-compat), sedangkan **mutasi create/edit/delete tanpa gate adalah bug yang confirmed**. `ImportRegu` sudah aman.

**Cross-event vulnerability:**
- Confidentiality: **NO** (Regu global, tanpa data event).
- Integrity/authorization: **YES** (mutasi tidak sah atas data global berdampak semua event).

---

## 13. Recommended Fix (tanpa implementasi)

**Batasan:** tidak menambahkan `event_id` redundan ke `regus` sekarang; tidak mengubah Regu menjadi Competition Team; tidak menjadikan Regu fitur generic; tetap konsisten dengan `EventAccessService`/`ActiveEventContext`/event type CAI.

Karena **tidak ada relasi existing** antara Regu ↔ Event/Kelompok, opsi "authorization via relationship existing (User → Active Event → Regu → Kelompok → Event)" **tidak tersedia**. Perbaikan minimal yang konsisten dengan architecture:

### Rekomendasi (interim — tanpa migration)

1. **Gate tiga mutator Regu dengan `manage-participants`** (sama dengan `ImportRegu`):
   - `TambahRegu::simpan()` → `Gate::authorize('manage-participants')`
   - `EditRegu::update()` → `Gate::authorize('manage-participants')`
   - `HapusRegu::destroy()` → `Gate::authorize('manage-participants')`
   - Alasan: ability yang sama sudah dipakai `ImportRegu` (konsisten), dan di-resolve via `EventPermissionService` → `EventAccessService` → `ActiveEventContext` (sama seperti route `import/regu`). `/regu` tanpa `resolve.active-event` → gate memakai session active event, sama persis dengan perilaku `import/regu` yang sudah ada (trade-off yang perlu dicatat, bukan perbaikan baru yang merusak).
2. **Integrity guard pada delete (opsional tapi disarankan)**: di `HapusRegu::destroy()`, tolak penghapusan bila `regu->participations()->exists()` (atau tampilkan pesan), alih-alih membiarkan `nullOnDelete` menghapus assignment partisipan lintas event secara diam-diam. Ini mengikuti pola `Event\Index::delete()` yang menolak hapus bila `hasRuntimeDependencies()`.
3. **Biarkan `GET /regu` tetap terbuka** (backward-compat, diuji). Jika kelak ingin konsisten dengan desa/kelompok, pindahkan halaman di bawah `can:view-master-data` — keputusan produk, bukan perbaikan keamanan wajib.
4. **Tidak menambahkan `event_id` ke `regus` pada tahap ini.** Event-scoping penuh hanya layak saat refactor "event-scoped configuration" yang direncanakan `docs/TERMINOLOGY.md` — saat itu: route `events/{event}/regu` + `resolve.active-event` + `can:manage-participants` + list `regu::whereHas('participations', fn($q)=>$q->where('event_id', activeEvent))` + redirect shim dari `/regu` untuk backward-compat.

### Architecture yang disarankan (setelah refactor event-scoped)

```
Event CAI
   ↓ (participations.event_id)
Participation ──regu_id──▶ Regu   (event-scoped setelah refactor; sekarang global)

Authorization (interim, tanpa migration):
Current User
   ↓
ActiveEventContext (session) + EventAccessService/EventPermissionService
   ↓  Gate::authorize('manage-participants') pada create/edit/delete
Regu (global) → dianggap bagian dari event aktif yang sedang dikelola user
```

---

## REGU SCOPE AUDIT COMPLETE

**Actual Scope:**
Regu adalah **master data global datar** (`regus`: `regu` + `jenis_kelamin` + unique `regu`), **tanpa FK ke event/kelompok/desa**. Satu-satunya relasi live: `participations.regu_id` (nullable, nullOnDelete). `pesertas.regu_id` sudah dihapus. Bukan bagian Master Data UI, bukan event-scoped.

**Event Relationship:**
Tidak ada FK Regu→Event. Event dapat diturunkan **hanya transitif** via `Regu → participations.event_id`. Regu yang belum dipakai sama sekali tidak dapat ditelusuri ke event. Diagram `Regu → Kelompok → Event` dari prompt **tidak sesuai code** (tidak ada link Kelompok↔Regu).

**CAI-specific:** NO (tidak di-enforce). Dalam praktik dipakai flow CAI; Pengajian tidak memakainya (`regu_id` null, diuji); Competition tidak memakainya. Tidak ada cek `isCai()` di route `/regu` maupun modul Regu CRUD (cek `isCai()` hanya di `GantiPeserta`/`CaiParticipantReplacementService`).

**Regu vs Competition Team:**
Dua domain berbeda. Regu = pembagian peserta CAI (placement gender, absensi, rekap, QR, registrasi). Competition Team = entitas event-scoped (players + substitutes) yang belum ada dan **tidak boleh memakai ulang `regus`**. Jangan satukan.

**H1 Classification:**
**CONFIRMED SECURITY BUG** — untuk jalur **mutasi** `TambahRegu::simpan()`, `EditRegu::update()`, `HapusRegu::destroy()` yang tanpa gate sama sekali (bisa dipanggil user auth+verified mana pun; berdampak data integrity lintas-event via `nullOnDelete`). Bagian **GET `/regu`** dari H1 adalah **intentional/not a bug** (terbuka, diuji, backward-compat). `ImportRegu` sudah gated (`manage-participants`).

**Cross-event vulnerability:**
- Confidentiality: **NO** (Regu global, tanpa data event).
- Integrity/authorization: **YES** (mutasi tidak sah atas data global berdampak semua event).

**Recommended Fix:**
(Interim, tanpa migration) Pasang `Gate::authorize('manage-participants')` pada `TambahRegu::simpan()`, `EditRegu::update()`, `HapusRegu::destroy()` — konsisten dengan `ImportRegu` dan mutasi peserta CAI, via `EventPermissionService`/`EventAccessService`/`ActiveEventContext`; tambahkan integrity guard pada delete (tolak bila `participations` masih merujuk); biarkan GET `/regu` terbuka untuk backward-compat; jangan tambah `event_id` ke `regus` sekarang — serahkan ke refactor event-scoped configuration (`docs/TERMINOLOGY.md`).

**Files Changed:**
Hanya `docs/audit/REGU-SCOPE-AUDIT.md` yang dibuat. Tidak ada kode, migration, database, route, authorization, atau UI yang diubah.
