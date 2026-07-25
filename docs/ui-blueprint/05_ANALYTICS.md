# 05 — ANALYTICS BLUEPRINT

> Dashboard statistik dipindahkan ke sini.
> Workspace TIDAK boleh memiliki tabel, chart, atau grafik.
> Benchmark: Stripe Dashboard, Supabase Dashboard, Google Analytics.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| Stripe | KPI cards + chart + recent transactions | KPI + chart + table |
| Supabase | Project stats + usage graphs | Time-series charts |
| Google Analytics | Date range + segments + trend | Date filter + trend indicators |
| GitHub Insights | Contribution graph + breakdown | Per-event breakdown |

### Pola Universal Analytics:

1. **Date Range Filter** — Hari ini, 7 hari, 30 hari, custom
2. **KPI Cards** — 4-6 metrik utama
3. **Chart** — Trend visual (line/bar)
4. **Table** — Data detail
5. **Export** — Excel/PDF
6. **Event Selector** — Pilih event atau semua event

---

## 2. WIREFRAME — ANALYTICS OVERVIEW (DESKTOP)

```
+------------------------------------------------------------------+
|  SIDEBAR           |  CONTENT AREA                               |
|  ┌────────────┐    |  ┌────────────────────────────────────────┐ |
|  │            │    |  │  HEADER                                │ |
|  │ Workspace  │    |  │  ┌──────────────────────────────────┐  │ |
|  │ Events     │    |  │  │ Analitik                    [▼ Export]│ |
|  │ People     │    |  │  │ Ringkasan seluruh event       │  │ |
|  │            │    |  │  └──────────────────────────────────┘  │ |
|  │ ● Analitik │    |  │                                        | |
|  │            │    |  │  FILTER BAR                            | |
|  │ Admin      │    |  │  ┌──────────────────────────────────┐  │ |
|  └────────────┘    |  │  │ [Hari Ini ▾] [Semua Event ▾]    │  │ |
|                    |  │  └──────────────────────────────────┘  │ |
|                    |  │                                        | |
|                    |  │  KPI CARDS                             | |
|                    |  │  ┌────────┬────────┬────────┬────────┐│ |
|                    |  │  │ Total  │ Total  │ Rata   │ Event  ││ |
|                    |  │  │ Peserta│ Hadir  │ Hadir  │ Aktif  ││ |
|                    |  │  │ 5.230  │ 3.892  │ 74%    │ 3      ││ |
|                    |  │  │ ▲ 12%  │ ▲ 8%   │ ▼ 2%   │ —      ││ |
|                    |  │  └────────┴────────┴────────┴────────┘│ |
|                    |  │                                        | |
|                    |  │  TREND CHART                           | |
|                    |  │  ┌──────────────────────────────────┐  │ |
|                    |  │  │  Kehadiran per Hari              │  │ |
|                    |  │  │                                  │  │ |
|                    |  │  │  ██                              │  │ |
|                    |  │  │  ██ ██                           │  │ |
|                    |  │  │  ██ ██ ██    ██                  │  │ |
|                    |  │  │  ██ ██ ██ ██ ██ ██ ██           │  │ |
|                    |  │  │  ██ ██ ██ ██ ██ ██ ██ ██ ██     │  │ |
|                    |  │  │  ──────────────────────────      │  │ |
|                    |  │  │  Sen Sel Rab Kam Jum Sab Min    │  │ |
|                    |  │  └──────────────────────────────────┘  │ |
|                    |  │                                        | |
|                    |  │  BREAKDOWN PER EVENT                  | |
|                    |  │  ┌──────────────────────────────────┐  │ |
|                    |  │  │ Event         │ Hadir  │ %     │  │ |
|                    |  │  │───────────────┼────────┼───────│  │ |
|                    |  │  │ Pengajian     │ 90/120 │ 75%  │  │ |
|                    |  │  │ CAI           │ 270/450│ 60%  │  │ |
|                    |  │  │ PAUD          │ 0/0    │ —    │  │ |
|                    |  │  └──────────────────────────────────┘  │ |
|                    |  │                                        | |
|                    |  │  RECENT ACTIVITY LOG                   | |
|                    |  │  ┌──────────────────────────────────┐  │ |
|                    |  │  │ Waktu    │ Aksi     │ User      │  │ |
|                    |  │  │─────────┼──────────┼───────────│  │ |
|                    |  │  │ 08:15   │ Absen    │ Budi      │  │ |
|                    |  │  │ 07:30   │ Regist   │ Operator  │  │ |
|                    |  │  │ 07:00   │ Login    │ Rina      │  │ |
|                    |  │  └──────────────────────────────────┘  │ |
|                    |  └────────────────────────────────────────┘ |
+------------------------------------------------------------------+
```

---

## 3. WIREFRAME — EMPTY STATE (BELUM ADA DATA)

```
                    ANALITIK
                    Ringkasan seluruh event

                    [Hari Ini ▾] [Semua Event ▾]

    ┌────────────────────────────────────────┐
    │                                        │
    │              📊                        │
    │                                        │
    │        Belum Ada Data Analitik         │
    │                                        │
    │   Data akan muncul setelah event       │
    │   pertama dimulai.                     │
    │                                        │
    │       [Buat Event Pertama]             │
    │                                        │
    └────────────────────────────────────────┘
```

---

## 4. WIREFRAME — DETAIL ANALYTICS PER EVENT

```
                    ANALITIK  >  PENGAJIAN RAMADHAN 2026

    [Hari Ini ▾]                [Export ▾]

    ┌────────┬────────┬────────┬────────┐
    │ 120    │ 90     │ 75%    │ 8      │
    │ Peserta│ Hadir  │ Hadir  │ Sesi   │
    │ ▲ 5%   │ ▲ 12%  │ ▲ 3%   │ —      │
    └────────┴────────┴────────┴────────┘

    ┌────────────────────────────────────────┐
    │  Kehadiran per Sesi                    │
    │                                        │
    │  ████████  Sesi 1  (08:00) — 95       │
    │  ██████    Sesi 2  (10:00) — 80       │
    │  ███████   Sesi 3  (13:00) — 85       │
    │  ████      Sesi 4  (15:00) — 50       │
    │  ██████    Sesi 5  (17:00) — 70       │
    │  ███       Sesi 6  (19:00) — 40       │
    │  █████     Sesi 7  (20:00) — 65       │
    │  ██        Sesi 8  (21:00) — 30       │
    └────────────────────────────────────────┘

    ┌────────────────────────────────────────┐
    │  Breakdown Metode Kehadiran            │
    │                                        │
    │  ● Scan QR       60  (67%)            │
    │  ● Manual        25  (28%)            │
    │  ● Surat Izin     5  (5%)             │
    └────────────────────────────────────────┘

    ┌────────────────────────────────────────┐
    │  Rincian Kehadiran                     │
    │  ┌───────┬──────┬──────┬──────┬────┐  │
    │  │ Nama  │ Sesi1│ Sesi2│ ...  │ %  │  │
    │  ├───────┼──────┼──────┼──────┼────┤  │
    │  │ Budi  │  ✓   │  ✓   │  ✓   │100%│  │
    │  │ Siti  │  ✓   │  ✗   │  ✓   │ 75%│  │
    │  │ Adi   │  ✗   │  ✓   │  ✓   │ 75%│  │
    │  └───────┴──────┴──────┴──────┴────┘  │
    └────────────────────────────────────────┘
```

---

## 5. MOBILE (375×812)

```
+----------------------------------+
|  ← Analitik                      |
+----------------------------------+
|                                  |
|  Ringkasan Event                 |
|                                  |
|  [7 Hari ▾] [Semua ▾]           |
|                                  |
|  ┌────────┬────────┐            |
|  │ 5.230  │ 3.892  │            |
|  │ Total  │ Hadir  │            |
|  │ ▲ 12%  │ ▲ 8%   │            |
|  ├────────┼────────┤            |
|  │ 74%    │ 3      │            |
|  │ Rata   │ Event  │            |
|  │ ▼ 2%   │ Aktif  │            |
|  └────────┴────────┘            |
|                                  |
|  ──────────────────               |
|                                  |
|  Kehadiran (7 hari)              |
|  ██ ██ ███ █ ████ ██ ██        |
|  Sen Sel Rab Kam Jum Sab Min    |
|                                  |
|  ──────────────────               |
|                                  |
|  Per Event                       |
|  ┌──────────────────────────┐   |
|  │ Pengajian    75%  ██████ │   |
|  │ CAI          60%  █████  │   |
|  │ PAUD          —    ○     │   |
|  └──────────────────────────┘   |
+----------------------------------+
```

---

## 6. COMPONENTS

| Component | Usage |
|-----------|-------|
| AnalyticsHeader | Title + export button |
| FilterBar | Date range + event selector |
| KPICard | Single metric with trend indicator |
| KPIGrid | 2×2 or 4×1 grid of KPI cards |
| TrendChart | ASCII bar chart (implemented as canvas/SVG) |
| BreakdownTable | Per-event or per-session breakdown |
| DataTable | Detailed rincian dengan pagination |
| ExportButton | Dropdown: Excel, PDF, CSV |
| EmptyStateAnalytics | Belum ada data illustration |

---

## 7. FILTER BEHAVIOR

| Filter | Options | Behavior |
|--------|---------|----------|
| Date Range | Hari Ini, 7 Hari, 30 Hari, Custom | Refresh seluruh KPI, chart, dan table |
| Event | Semua Event, [Event 1], [Event 2] | Filter data per event |
| Module | Kehadiran, Registrasi, Kompetisi | Switch antara modul analitik |

Default: "7 Hari" + "Semua Event" + "Kehadiran"

---

## 8. RULES

| Rule | Detail |
|------|--------|
| Analytics adalah satu-satunya tempat chart dan grafik | Workspace, Event Home tidak boleh punya |
| KPI cards harus memiliki trend indicator (▲/▼) | Bandingkan dengan periode sebelumnya |
| Chart harus responsif | Stack horizontal di mobile |
| Export harus mencakup seluruh data yang terfilter | Bukan hanya halaman pertama |
| Empty state harus informatif | Bukan hanya "No data" |
| Analytics global (semua event) vs per event | Filter menentukan scope |
| Data harus di-cache | Refresh setiap 5 menit atau manual |
| Role-based visibility | Viewer hanya bisa melihat, bukan export |
