# SPRINT 2 — DASHBOARD CONSOLIDATION (AUDIT)

> Status: **AUDIT ONLY — belum ada implementasi.**
> Produced: 2026-08-03
> Sumber kebenaran: kode aktual (routes/web.php, app/Livewire, resources/views, tests)

---

## 1. RINGKASAN TEMUAN

Terdapat **6 komponen bertipe dashboard** + 1 halaman scan. Dari semuanya:

- `Event\Dashboard` (**`/events/{event}/dashboard`**) adalah dashboard tunggal yang paling banyak dipakai, tetapi **mencampur CAI + Competition** dalam satu file & satu blade, dan **menampilkan Quick Action Competition pada semua event type** (termasuk CAI).
- `Competition\Dashboard` (**`/events/{event}/competition-dashboard`**) adalah **subset** dari rendering Competition yang sudah ada di `Event\Dashboard` → **redundan**.
- `Pengajian\RegionalReport` (**`/pengajian/report`**) berfungsi sebagai dashboard Pengajian, tetapi **tidak berada di bawah `/events/{event}`** dan tidak lewat `resolve.active-event`.
- `Pengajian\DesaDashboard` adalah dashboard **operator desa berbasis token** dengan layout terpisah — audience berbeda, bukan bagian dari konsolidasi `/events/{event}/dashboard`.
- `Competition\OperatorDashboard` adalah **halaman kontrol match live**, bukan dashboard statistik — tetap terpisah.
- `Dashboard\PlatformDashboard` (`/dashboard`) adalah **landing platform** (launcher event), bukan dashboard event.

**Bug utama yang dikonfirmasi:**
1. Quick Action (menu Competition: Pendaftaran, Peserta, Jadwal, Match Center, Bracket, Konfigurasi) dirender **tanpa guard `isCompetition()`** → tampil di dashboard CAI. (event/dashboard.blade.php:220-253)
2. Untuk event `competition`, `Event\Dashboard::render()` tetap mengeksekusi seluruh query absensi CAI (`SesiAbsensi`, `AttendanceReadService`) walau hasilnya tidak dirender → **pemborosan & kebocoran logika CAI** ke Competition.
3. `mount()` menghitung `totalDesa`, `totalKelompok`, `totalRegu` dari tabel **global** (tidak event-scoped) — tidak pernah dipakai di blade (dead/hidden logic).
4. `$this->totalPeserta` ditimpa `$totalPesertaFiltered` (count sesi-aktif) hanya saat ada sesi aktif; tanpa sesi aktif kartu "Total Peserta" menampilkan 0 (`$totalPesertaFiltered = 0` default) sementara `$this->totalPeserta` punya nilai benar → **inkonsistensi sumber data**.

---

## 2. INVENTARIS DASHBOARD

| # | Komponen | Route / URL | Layout | Audience | Peran |
|---|----------|-------------|--------|----------|-------|
| D1 | `Dashboard\PlatformDashboard` | `dashboard` `/dashboard` | platform | Semua user | Landing platform / launcher event |
| D2 | `Event\Dashboard` | `events.dashboard` `/events/{event}/dashboard` | app (sidebar) | Admin event | **Dashboard event utama (CAI+Competition campur)** |
| D3 | `Competition\Dashboard` | `competition.dashboard` `/events/{event}/competition-dashboard` | app (sidebar) | Admin competition | Dashboard Competition (subset D2) |
| D4 | `Competition\OperatorDashboard` | `competition.operator-dashboard` `/competition/operator-dashboard` | app (sidebar) | Panitia (manage-events) | Kontrol match live (bukan statistik) |
| D5 | `Pengajian\RegionalReport` | `pengajian.report` `/pengajian/report` | app (sidebar) | Admin pengajian | Dashboard/landing Pengajian |
| D6 | `Pengajian\DesaDashboard` | `pengajian.desa` `/pengajian/desa` | pengajian (token) | Operator desa | Dashboard operasional desa |
| — | `Dashboard\Scan` | `absensi` `/absensi` | app | Petugas absensi | Scan kehadiran CAI (bukan dashboard) |

---

## 3. MATRIKS REDUNDANSI & KEPEMILIKAN WIDGET

### 3a. Widget per dashboard (saat ini)

| Widget | D2 CAI | D2 Competition | D3 Competition | D5 Pengajian |
|--------|:------:|:--------------:|:--------------:|:------------:|
| Total Peserta / Statistik Peserta | ✓ | ✓ (overview) | ✓ (Pendaftaran) | ✓ (Total Warga) |
| Statistik Absensi (Hadir/Izin/Belum) | ✓ | ✗ | ✗ | ✓ |
| Sesi Aktif + Ganti Sesi | ✓ | ✗ | ✗ | ✗ |
| Peserta Belum Absen (list) | ✓ | ✗ | ✗ | ✗ |
| Ringkasan Event / Informasi Event | ✓ | ✓ | ✗ | — |
| Aktivitas (Registrasi/Hasil terbaru) | ✗ | ✓ | ✗ | ✗ |
| Ringkasan Competition (cards) | ✗ | ✓ | ✓ (parsial) | ✗ |
| Live Pertandingan / Match Hari Ini | ✗ | ✓ | ✗ | ✗ |
| Jadwal Hari Ini (Competition) | ✗ | ✓ | ✗ | ✗ |
| Regional / per-Desa breakdown | ✗ | ✗ | ✗ | ✓ |
| Daftar Kehadiran (list + filter) | ✗ | ✗ | ✗ | ✓ |
| **Quick Action menu Competition** | **✓ (SALAH)** | **✓ (duplikat menu)** | ✗ | ✗ |

### 3b. Identik / duplikat logika

| Bagian | Lokasi | Keterangan |
|--------|--------|------------|
| `ResolvesEventDashboard` trait | D2 & D3 sama-sama `use` | `Event\Dashboard` + `Competition\Dashboard` identik di mount (validasi event aktif, EventAccessService, set ActiveEventContext, set eventName) |
| Kartu statistik (`rounded-xl border ... p-4 ... text-2xl font-bold`) | D2, D3, D5 | Pola markup berulang 3x (kandidat Blade component/partial) |
| Breadcrumb (Dashboard / eventName) | D2, D3 | Markup hampir identik |
| Quick Action | D2 vs `sidebar.blade.php` | **Duplikasi menu sidebar** (Pendaftaran=Registrasi, Peserta, Jadwal, Match Center, Bracket, Konfigurasi) |
| Overview cards Competition | D2 vs D3 | D2 menampilkan Peserta/Kelas/Venue/Hari Ini/Berlangsung/Selesai; D3 menampilkan Pendaftaran/Kategori/Kelas/Venue — **tumpang tindih** |

### 3c. Kepemilikan eksklusif

| Milik CAI saja | Milik Competition saja | Milik Pengajian saja |
|----------------|------------------------|----------------------|
| Statistik Absensi (Hadir/Izin/Belum) | Live Pertandingan / Match Hari Ini | Regional / per-Desa breakdown |
| Sesi Aktif + Ganti Sesi | Jadwal Competition | Daftar Kehadiran (list+filter) |
| Peserta Belum Absen | Arena (venue) | (Kehadiran self/operator) |
| Scan Kehadiran (`/absensi`) | Official (panel terpisah) | |
| QR Label / Surat Izin (halaman terpisah) | Bracket / Konfigurasi (halaman terpisah) | |

---

## 4. DIAGRAM DEPENDENCY — SEBELUM

```
/users (auth+verified)
   │
   ├─ /dashboard ─────────────► PlatformDashboard ──► list event + openEvent
   │
   ├─ /events/{event}/dashboard ─────► Event\Dashboard  (D2, file 191 baris + blade 254)
   │      ├─ SesiAbsensi (event-scoped) ─ CAI
   │      ├─ AttendanceReadService ───── CAI (dieksekusi juga utk competition)
   │      ├─ Participation ───────────── CAI
   │      ├─ desa/kelompok/regu::count() GLOBAL ─ CAI (dead)
   │      ├─ CompetitionClass/Venue/CompetitionSchedule/CompetitionRegistration ─ Competition (hanya isCompetition)
   │      └─ Quick Action → competition.{registration,participants,schedule,match-center,bracket,category} ─ ALWAYS
   │
   ├─ /events/{event}/competition-dashboard ► Competition\Dashboard (D3, subset, trait sama)
   │
   ├─ /competition/operator-dashboard ───► OperatorDashboard (D4 — kontrol match)
   ├─ /pengajian/report ────────────────► RegionalReport (D5 — landing pengajian, TANPA event di URL)
   ├─ /pengajian/desa ──────────────────► DesaDashboard (D6 — operator desa, token, layout sendiri)
   └─ /absensi ────────────────────────► Scan (halaman scan CAI)

Rute dashboard yang dipakai redirect source:
   Event::dashboardRoute() → pengajian: pengajian.report | competition: competition.dashboard | lain: events.dashboard
   (dipakai oleh EventSwitcher, PlatformDashboard::openEvent, Event\Index::create)
```

**Masalah struktural:** Satu event competition punya 2 dashboard (D2 + D3) dengan logika & render terpisah; CAI dashboard (D2) mengenal menu Competition lewat Quick Action; Pengajian tidak punya dashboard di bawah `/events/{event}/dashboard`.

---

## 5. ARSITEKTUR TARGET — SESUDAH (RENCANA IMPLEMENTASI, BELUM DIEKSEKUSI)

Prinsip: **satu entry point** `/events/{event}/dashboard`, dispatch berdasarkan `event_type`, tanpa God Component.

```
/events/{event}/dashboard            (rute TIDAK berubah)
   │
   ▼
Event\Dashboard (fasade tipis: resolve mount via trait, dispatch strategy)
   │   event_type = cai | competition | pengajian
   │
   ▼
DashboardStrategy / Presenter (per-type, DI service)
   ├─ CaiDashboardPresenter
   │     → data: statistik peserta, absensi (hadir/izin/belum), sesi aktif, belum absen
   │     → view: livewire.event.partials.cai.*
   ├─ CompetitionDashboardPresenter
   │     → data: ringkasan, match hari ini, jadwal, arena, official, statistik
   │     → view: livewire.event.partials.competition.*
   └─ PengajianDashboardPresenter
         → data: ringkasan, regional/desa, kehadiran, statistik
         → view: livewire.event.partials.pengajian.*

Event\Dashboard shell (shared):
   - Breadcrumb
   - Ringkasan/Informasi Event (shared component)
   - Aktivitas (shared component, CAI=registrasi, Competition=registrasi/hasil)
   - QUICK ACTION DIHAPUS (duplikat sidebar)
```

**Cara menghindari God Component:**
1. `Event\Dashboard` = resolver tipis (< ~80 baris): mount validasi, pilih presenter, render shell.
2. Setiap presenter = class service mandiri (query per-type), masing-masing < ~100 baris.
3. Setiap view = partial blade terpisah per modul (bukan satu blade ribuan baris).
4. Jika sebuah bagian mulai tumbuh > batas wajar → dipindah ke presenter/partial baru (aturan "STOP, ubah ke Strategy/Presenter/Partial").

---

## 6. KEPUTUSAN / KENDALA ROUTING (PENTING)

| Kendala | Temuan |
|---------|--------|
| Rute `competition.dashboard` | Harus tetap 200 OK — test lama `RoutingConsolidationTest` line 96-103 `assertOk()`. → **Pertahankan sebagai alias tipis** (isinya sudah bersih: hanya statistik competition, tanpa widget CAI, tanpa Quick Action). Tidak dijadikan redirect agar test lama hijau. |
| `Event::dashboardRoute()` | Test lama mengharuskan competition → `competition.dashboard`, pengajian → `pengajian.report`. → **TIDAK diubah** (kendala "semua test lama hijau"). |
| `pengajian.report` | Bukan di bawah `/events/{event}`. Route Migration Pengajian = tech debt Sprint 3. |
| Sidebar Competition | Link "Dashboard" saat ini → `competition.dashboard`. Opsional: arahkan ke `events.dashboard` (bukan perubahan routing). |

> Konflik terdeteksi antara target user ("setiap event membuka `/events/{event}/dashboard`", "CompetitionDashboard lama redirect") vs aturan "jangan ubah routing" + "semua test lama hijau". Rekomendasi: pertahankan `competition.dashboard` sebagai alias 200 OK yang bersih; migrasi penuh (ubah `dashboardRoute()` + redirect + hapus rute) ditunda ke **Sprint 3**.

---

## 7. TEST PLAN (REGRESSION MINIMAL — IMPLEMENTASI)

File baru: `tests/Feature/Event/DashboardConsolidationTest.php` (+7 test):

1. Dashboard CAI tidak menampilkan shortcut/menu Competition (Quick Action dihapus; `assertDontSee('Match Center')`).
2. Dashboard Competition tidak menampilkan widget CAI (`assertDontSee('Belum Absen')`, `assertDontSee('Ganti Sesi')`).
3. Dashboard Pengajian tidak menampilkan widget Competition (`assertDontSee('Match Center')`).
4. Semua event type (cai/competition/pengajian) membuka `/events/{event}/dashboard` → 200.
5. Strategy memilih view yang benar (cai → partial cai; competition → partial competition; pengajian → partial pengajian).
6. `competition.dashboard` (jika dipertahankan) tetap 200 OK / alias (ter-cover test lama).
7. Semua test lama tetap hijau.

---

## 8. PERKIRAAN TEST COUNT

- **Sebelum:** ±1.905 deklarasi test statis (1881 `test(`, 24 `it(`) di 125 file. Catatan: docs `CURRENT_STATE.md` tercatat 1508–1756 (baseline tua); **suite tidak dapat dieksekusi di lingkungan ini (PHP tidak terpasang)** — angka eksak harus diverifikasi saat suite berjalan.
- **Sesudah:** ±1.905 + 7 test baru ≈ **1.912** (belum termasuk test yang di-refactor karena file dashboard dipindah).

---

## 9. LIST FILE (RENCANA PERUBAHAN IMPLEMENTASI)

**Ubah:**
- `app/Livewire/Event/Dashboard.php` — dipersempit jadi fasade tipis + dispatch strategy.
- `resources/views/livewire/event/dashboard.blade.php` — jadi shell (breadcrumb + event info + dispatch partial).
- `resources/views/components/layouts/app/sidebar.blade.php` — (opsional) arahkan link Dashboard Competition → `events.dashboard`.

**Baru:**
- `app/Services/Dashboard/DashboardPresenterContract.php` (interface).
- `app/Services/Dashboard/CaiDashboardPresenter.php`
- `app/Services/Dashboard/CompetitionDashboardPresenter.php`
- `app/Services/Dashboard/PengajianDashboardPresenter.php`
- `resources/views/livewire/event/partials/cai/*.blade.php`
- `resources/views/livewire/event/partials/competition/*.blade.php`
- `resources/views/livewire/event/partials/pengajian/*.blade.php`
- `tests/Feature/Event/DashboardConsolidationTest.php`

**Dipertahankan (tidak diubah):**
- `app/Livewire/Competition/Dashboard.php` + blade (alias tipis, sudah bersih).
- `app/Livewire/Pengajian/DesaDashboard.php`, `OperatorDashboard`, `PlatformDashboard`, `Scan`.
- `routes/web.php`, `app/Models/Event.php` (`dashboardRoute()`), `ResolvesEventDashboard`, middleware `resolve.active-event`, permission/gate/service authorization, DB/migration/seeder.

**Dihapus:**
- Panel **Aksi Cepat** di `event/dashboard.blade.php` (duplikat sidebar). Tidak ada fungsi non-menu di dalamnya → sesuai aturan "jika hanya duplikasi menu sidebar, HAPUS".
- Logika CAI yang bocor ke rendering competition (attendance query & `activateSesi` tidak dipanggil untuk event competition) — dihapus via presenter per-type.

**Dipindah:**
- Widget CAI (statistik peserta, absensi, sesi aktif, belum absen) → `CaiDashboardPresenter` + partial `cai/*`.
- Widget Competition (ringkasan, live, jadwal, arena, statistik) → `CompetitionDashboardPresenter` + partial `competition/*`.
- Widget Pengajian (ringkasan, regional, kehadiran) → `PengajianDashboardPresenter` + partial `pengajian/*`.
- Kartu statistik & breadcrumb → Blade component bersama (shared).

---

## 10. TECHNICAL DEBT — SPRINT 3 (ROUTE MIGRATION COMPETITION & PENGAJIAN)

1. **Route Migration Competition:** seluruh `/competition/*` (registration, schedules, match-center, official-panel, bracket, reports, operator-dashboard) belum di bawah `/events/{event}` → tidak resolvable dari URL, bergantung `ActiveEventContext` (session). Perlu pindah ke prefix `/events/{event}/competition/*`.
2. **Route Migration Pengajian:** `/pengajian/report` (landing pengajian) tidak di bawah `/events/{event}` dan tidak lewat `resolve.active-event`; `PengajianRegionalReportService` bergantung session context.
3. **`dashboardRoute()` + `competition.dashboard`:** pengkonsolidasian penuh (semua type → `events.dashboard`, hapus alias/redirect) tertahan oleh test lama — dipindah ke Sprint 3.
4. **Query CAI pada event competition** (di `Event\Dashboard::render`) — dibersihkan di Sprint 2 via presenter; sisa duplikasi query dihapus bertahap.
5. **Count global** `desa/kelompok/regu` di mount dashboard — perlu keputusan: dihapus (dead) atau event-scoped.
6. **`Pengajian\DesaDashboard`** tetap surface terpisah (alur token) — apakah di-unify ke `/events/{event}/dashboard` masih open.
7. **`OperatorDashboard`** tetap halaman operasional terpisah — tidak termasuk dashboard statistik.

---

## 11. CATATAN LINGKUNGAN

- `git` dan `php` **tidak tersedia** di environment ini → tidak bisa commit dan tidak bisa menjalankan test suite. Baseline test eksak tidak dapat diverifikasi secara runtime.
- Audit murni statis: routes, controller, Livewire, blade, tests, docs.

---

## 12. ADDENDUM — MEMORY LEAK AUDIT (2026-08-03)

> Metodologi: instrumentation PHPUnit extension (events `TestSuite\Started/Finished`, `Test\PreparationStarted/Finished`)
> di `tests/Support/` + `phpunit.xml` (dihapus setelah selesai). Mengukur `memory_get_usage(true/false)` + GC per class/test.

### 12.1 Hasil instrumentation (full suite, single process, `php -d memory_limit=-1`)

- Suite dimulai di ~47MB real / 50MB alloc.
- Berakhir di ~259MB real / 277MB alloc (124 test class files, 1938 test).
- **Carryover antar test class: rata-rata −1.376 bytes, maks +496 bytes** → memori TIDAK di-retain antar class (bukan leak objek).
- Pertumbuhan per class ~1.7MB real = **akumulasi class definitions** (tiap file test memuat ratusan class baru yang PHP simpan permanen dalam satu proses). Ini normal.
- Class pertama dengan lonjakan terbesar = `ActivityLogServiceTest` (+29.6MB) — unit test pertama yang boot penuh framework + RefreshDatabase (migrasi semua tabel, memuat ~931 class). **Ini biaya boot sekali, bukan leak.**
- Tidak ada static property, Cache::remember, singleton, View::share, Blade::directive, Livewire::component, Event::listen di app/tests/bootstrap yang menumpuk. Presenter baru tanpa static cache. Blade dashboard tidak ada include rekursif.

### 12.2 Akar masalah

`php artisan test` (Pest/PHPUnit single process) memuat **seluruh 124 file test + ribuan class** dalam satu proses PHP.
Class definitions **tidak pernah di-unload** oleh PHP. Total kebutuhan memori suite ≈ 270MB real / 290MB alloc > `memory_limit=128M` (default).
Crash di `ExtendedCompilerEngine.php` (Livewire) hanyalah **lokasi alokasi terakhir yang gagal**, bukan penyebab.

**Bukan leak.** Tidak ada satu test class pun yang "menyebabkan lonjakan yang tidak kembali turun".

### 12.3 Solusi yang diterapkan (tanpa melanggar batasan)

- Tidak menaikkan `memory_limit`.
- Tidak skip/hapus/disable test.
- Tidak mengubah vendor/Livewire.
- Solusi: **isolasi proses** — `pest --parallel` (atau `php artisan test --parallel` bila pest-plugin-parallel terpasang).
  Setiap worker = proses PHP terpisah dengan memori sendiri → akumulasi class definitions terbagi, tidak pernah melewati limit.

### 12.4 Hasil akhir

- **`pest --parallel`: 1938 passed / 4599 assertions / 0 failures** (4 processes, 16.86s).
- Semua test hijau termasuk DashboardConsolidationTest (21), RoutingConsolidationTest (13), PermissionEngineTest, PlatformDashboardRoleDisplayTest, EventRoleCodePolicyTest, CommitteeAutoCreateUserTest.
- 21 failure yang muncul saat PHP setup lokal tanpa GD extension adalah artifact lingkungan (`GDLibRenderer` butuh `php-gd`), bukan regresi — hilang setelah `php83-gd` dipasang.

### 12.5 Catatan untuk environment user

Jika `php artisan test` di environment asli crash di ~128M:
1. Pastikan PHP `memory_limit` cukup untuk satu proses (suite butuh ~290MB) — ATAU
2. Gunakan `--parallel` (proses terpisah) — sesuai batasan "jangan menaikkan memory_limit".
