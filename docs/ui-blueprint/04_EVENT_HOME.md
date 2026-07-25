# 04 — EVENT HOME BLUEPRINT

> Halaman setelah user memilih event dari Workspace.
> Di sinilah event accent BOLEH digunakan.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| GitHub | Repo header + tab navigation | Event header + module tabs |
| Linear | Project header + team + issue list | Module organization |
| Notion | Page title + icon + content blocks | Event identity + modules |
| Vercel | Project overview + deployments | Event overview + activities |

### Pola Universal:

1. **Identity Header** — Nama event + type badge + accent color
2. **Summary Bar** — Ringkasan cepat (peserta, hadir, sesi)
3. **Module Grid** — Akses ke fitur-fitur event
4. **Activity Feed** — Aktivitas terbaru dalam event ini

---

## 2. WIREFRAME — EVENT HOME (CONTOH: PENGAJIAN)

```
+------------------------------------------------------------------+
|  SIDEBAR                    |  CONTENT AREA                       |
|  ┌─────────────────────┐    |  ┌────────────────────────────────┐ |
|  │                     │    |  │                                │ |
|  │  ● KJA              │    |  │  HERO BANNER (Emerald Accent)  │ |
|  │                     │    |  │  ┌──────────────────────────┐  │ |
|  │  ────────────────   │    |  │  │                          │  │ |
|  │                     │    |  │  │  ☰ Pengajian Ramadhan   │  │ |
|  │  ☰ Workspace        │    |  │  │  2026                    │  │ |
|  │                     │    |  │  │                          │  │ |
|  │  ▸ Events           │    |  │  │  ● Aktif  │  📍 Masjid  │  │ |
|  │    └ ● Pengajian ◀  │    |  │  │            Al-Falah     │  │ |
|  │    └ CAI             │    |  │  │                          │  │ |
|  │    └ PAUD            │    |  │  │  🟢 120 peserta  │  🟢  │  │ |
|  │                     │    |  │  │  75% hadir       │  8   │  │ |
|  │  ▸ People           │    |  │  │                   │ sesi │  │ |
|  │                     │    |  │  └──────────────────────────┘  │ |
|  │  ▸ Analytics        │    |  │                                │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │  ▸ Administration   │    |  │                                │ |
|  │                     │    |  │  QUICK STATS                   │ |
|  │  ────────────────   │    |  │  ┌────────┬────────┬────────┐ │ |
|  │                     │    |  │  │ 120    │ 90     │ 8      │ │ |
|  │  👤 Rina            │    |  │  │ Peserta│ Hadir  │ Sesi   │ │ |
|  │                     │    |  │  └────────┴────────┴────────┘ │ |
|  │                     │    |  │                                │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │                     │    |  │                                │ |
|  │                     │    |  │  MODUL EVENT                  │ |
|  │                     │    |  │  ┌──────────┬──────────┬────┐ │ |
|  │                     │    |  │  │          │          │    │ │ |
|  │                     │    |  │  │ Absensi  │ Peserta  │    │ │ |
|  │                     │    |  │  │ QR Scan  │ Daftar   │ QR │ │ |
|  │                     │    |  │  │          │ Peserta  │ &  │ │ |
|  │                     │    |  │  │          │          │Label│ │ |
|  │                     │    |  │  ├──────────┼──────────┼────┤ │ |
|  │                     │    |  │  │          │          │    │ │ |
|  │                     │    |  │  │ Laporan  │ Akses    │    │ │ |
|  │                     │    |  │  │ Regional │ Desa     │    │ │ |
|  │                     │    |  │  │          │ Token    │    │ │ |
|  │                     │    |  │  └──────────┴──────────┴────┘ │ |
|  │                     │    |  │                                │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │                     │    |  │                                │ |
|  │                     │    |  │  AKTIVITAS TERBARU            │ |
|  │                     │    |  │  ┌────────────────────────┐   │ |
|  │                     │    |  │  │ • Budi — hadir (08:15) │   │ |
|  │                     │    |  │  │ • Siti — daftar (07:30)│   │ |
|  │                     │    |  │  │ • Token Desa A dibuat  │   │ |
|  │                     │    |  │  └────────────────────────┘   │ |
|  └─────────────────────┘    |  └────────────────────────────────┘ |
+------------------------------------------------------------------+
```

---

## 3. WIREFRAME — EVENT HOME (CONTOH: CAI) — ACCENT BIRU

```
+------------------------------------------------------------------+
|  SIDEBAR           |  CONTENT AREA                               |
|  ┌────────────┐    |  ┌────────────────────────────────────────┐ |
|  │            │    |  │  HERO BANNER (Blue Accent)              │ |
|  │ ...        │    |  │  ┌──────────────────────────────────┐  │ |
|  │            │    |  │  │                                  │  │ |
|  │ ● Pengajian│    |  │  │  ☰ CAI 2026                     │  │ |
|  │ ○ CAI ◀    │    |  │  │                                  │  │ |
|  │ ○ PAUD     │    |  │  │  ● Aktif  │  📍 Bumi Perkemahan │  │ |
|  │            │    |  │  │            Cibubur              │  │ |
|  │ ...        │    |  │  │                                  │  │ |
|  │            │    |  │  │  🟢 450 peserta  │  🟢 12 sesi  │  │ |
|  └────────────┘    |  │  └──────────────────────────────────┘  │ |
|                    |  │                                        | |
|                    |  │  QUICK STATS                           | |
|                    |  │  ┌──────┬──────┬──────┬──────┐        | |
|                    |  │  │ 450  │ 270  │ 12   │ 80%  │        | |
|                    |  │  │Peserta│Hadir │Sesi  │Hadir │        | |
|                    |  │  └──────┴──────┴──────┴──────┘        | |
|                    |  │                                        | |
|                    |  │  MODUL EVENT (CAI)                     | |
|                    |  │  ┌──────┬──────┬──────┬──────┐        | |
|                    |  │  │Absen │Regis │Data  │QR &  │        | |
|                    |  │  │si    │trasi │base  │Label │        | |
|                    |  │  │      │      │      │      │        | |
|                    |  │  ├──────┼──────┼──────┼──────┤        | |
|                    |  │  │Surat │Lap   │Kompe │Serti │        | |
|                    |  │  │Izin  │oran  │tisi  │fikat │        | |
|                    |  │  └──────┴──────┴──────┴──────┘        | |
|                    |  │                                        | |
|                    |  │  AKTIVITAS                             | |
|                    |  │  • Andi izin — 09:00                   | |
|                    |  │  • Regu A lengkap — 08:45              | |
|                    |  └────────────────────────────────────────┘ |
+------------------------------------------------------------------+
```

---

## 4. WIREFRAME — MOBILE (375×812)

```
+----------------------------------+
|  ← Kembali ke Workspace          |
+----------------------------------+
|                                  |
|  ┌──────────────────────────┐   |
|  │                          │   |
|  │  ☰ Pengajian Ramadhan    │   |
|  │  2026                    │   |
|  │                          │   |
|  │  ● Aktif  │ Masjid       │   |
|  │            │ Al-Falah    │   |
|  │                          │   |
|  │  120 peserta • 75% hadir  │   |
|  │  8 sesi                   │   |
|  └──────────────────────────┘   |
|                                  |
|  ──────────────────               |
|                                  |
|  MODUL                           |
|  ┌──────────┬──────────┐       |
|  │ Absensi  │ Peserta  │       |
|  │ QR Scan  │ Daftar   │       |
|  ├──────────┼──────────┤       |
|  │ QR Label │ Laporan  │       |
|  │          │ Regional │       |
|  ├──────────┼──────────┤       |
|  │ Akses    │          │       |
|  │ Desa     │          │       |
|  └──────────┴──────────┘       |
|                                  |
|  ──────────────────               |
|                                  |
|  AKTIVITAS                       |
|  • Budi hadir — 08:15           |
|  • Siti daftar — 07:30          |
|  • Token desa dibuat — 07:00    |
+----------------------------------+
```

---

## 5. EVENT ACCENT IMPLEMENTATION

### 5.1 Accent Usage Map

| Element | Pengajian | CAI | PAUD | Silat |
|---------|-----------|-----|------|-------|
| Hero Banner BG | Emerald 50 | Blue 50 | Orange 50 | Red 50 |
| Hero Banner Border | Emerald 200 | Blue 200 | Orange 200 | Red 200 |
| Event Badge | Emerald | Blue | Orange | Red |
| Stat Icon | Emerald 500 | Blue 500 | Orange 500 | Red 500 |
| Module Card Icon | Emerald 500 | Blue 500 | Orange 500 | Red 500 |

### 5.2 Hero Banner Specification

```blade
{{-- Hero Banner menggunakan event accent --}}
<div class="rounded-xl border border-{event-accent}-200 bg-{event-accent}-50 p-6 
            dark:border-{event-accent}-800 dark:bg-{event-accent}-950/20">
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <flux:badge color="{event-accent}" size="sm">
                    {{ $event->type_label }}
                </flux:badge>
                <flux:badge color="emerald" size="sm">
                    {{ __('Aktif') }}
                </flux:badge>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $event->name }}
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                📍 {{ $event->location }}
            </p>
        </div>
        <flux:icon class="w-8 h-8 text-{event-accent}-500" />
    </div>
</div>
```

### 5.3 Critical Rule

> **Event accent hanya digunakan di dalam Event Home.**
> Sidebar, navbar, tombol utama, form, tabel tetap menggunakan brand blue.

---

## 6. MODULE GRID

### 6.1 Module Card Pattern

```
┌──────────────────┐
│                  │
│  [Icon]          │
│  Nama Modul      │
│  Deskripsi       │
│                  │
│  [Buka]          │
└──────────────────┘
```

### 6.2 Module Grid Layout

| Grid Size | Desktop | Tablet | Mobile |
|-----------|---------|--------|--------|
| Columns | 4 | 2 | 2 |
| Card padding | p-6 | p-4 | p-4 |
| Icon size | w-8 h-8 | w-6 h-6 | w-6 h-6 |

### 6.3 Module Visibility by Event Type

Setiap event type memiliki modul yang berbeda. Tidak semua modul tersedia untuk semua event.

| Modul | CAI | Pengajian | PAUD | Silat | Lomba |
|-------|-----|-----------|------|-------|-------|
| Absensi | ✅ | ✅ | ✅ | ✅ | ✅ |
| Registrasi | ✅ | ✅ | ✅ | ✅ | ✅ |
| Database Peserta | ✅ | ✅ | ✅ | ✅ | ✅ |
| QR & Label | ✅ | ✅ | ○ | ○ | ○ |
| Surat Izin | ✅ | ○ | ○ | ○ | ○ |
| Laporan | ✅ | ✅ | ✅ | ✅ | ✅ |
| Kompetisi | ○ | ○ | ○ | ✅ | ✅ |
| Sertifikat | ○ | ○ | ✅ | ✅ | ✅ |
| Akses Desa | ○ | ✅ | ○ | ○ | ○ |

✅ = Available  |  ○ = Not available

---

## 7. INTERACTION NOTES

| Element | Interaksi |
|---------|-----------|
| Sidebar event list | Klik → ganti event context, redirect ke Event Home |
| Hero Banner | Static display — tidak ada interaksi |
| Stat cards | Klik → filter terkait (misal klik "Hadir" → lihat daftar hadir) |
| Module card | Klik → buka modul (dalam event context) |
| Back to Workspace | Breadcrumb atau tombol back |
| Edit event | Icon gear di pojok hero banner |

---

## 8. RULES

| Rule | Detail |
|------|--------|
| Event accent hanya di Event Home | Jangan bocor ke sidebar, navbar, atau halaman lain |
| Hero banner wajib untuk setiap event | Memberikan identity pada event |
| Module grid harus dinamis | Sesuai event type dan role user |
| Jangan tampilkan modul yang tidak tersedia | Atau tampilkan sebagai "coming soon" dengan lock icon |
| Event Home adalah default setelah pilih event | Bukan langsung ke absensi |
| Quick stats harus real-time atau near real-time | Cache max 60 detik |
