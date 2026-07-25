# 07 — MOBILE BLUEPRINT

> Mobile-first operational platform.
> Benchmark: Linear mobile, GitHub mobile, Notion mobile.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| Linear Mobile | Bottom tab + clean list | Bottom tab navigation |
| GitHub Mobile | Bottom tabs + slide-in filter | Contextual bottom sheet |
| Notion Mobile | Sidebar drawer + page content | Clean content focus |
| Stripe Mobile | Card-based + swipeable | Card-first layout |
| WhatsApp | Bottom tabs + FAB | Quick action FAB pattern |

### Pola Universal Mobile App:

1. **Bottom Tab Bar** — 5 item maksimal, selalu visible
2. **Thumb-friendly targets** — Minimal 44×44px
3. **Swipe actions** — Untuk aksi cepat pada list items
4. **Pull to refresh** — Untuk data real-time
5. **FAB (Floating Action Button)** — Untuk aksi utama

---

## 2. MOBILE NAVIGATION ARCHITECTURE

```
+----------------------------------+
|                                  |
|          CONTENT                 |
|                                  |
|                                  |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
| Home│Event│People│Analyt│Admin  |
+----------------------------------+
```

| Tab | Icon | Label | Halaman |
|-----|------|-------|---------|
| Home | ☐ | Beranda | Workspace |
| Event | ▸ | Event | Event List (drawer) |
| People | 👥 | Orang | People List |
| Analytics | 📊 | Analitik | Analytics |
| Admin | ⚙ | Atur | Administration |

---

## 3. MOBILE WORKSPACE (375×812)

```
+----------------------------------+
|  ☰ KJA EM              👤      |
+----------------------------------+
|                                  |
|  Selamat datang, Rina            |
|  Rabu, 29 Jul 2026               |
|  3 Event Aktif                   |
|                                  |
|  ┌──────────────┐                |
|  │ [+ Absen Cepat]│              |  ← FAB besar
|  └──────────────┘                |
|                                  |
|  ──────────────────               |
|                                  |
|  EVENT AKTIF                     |
|  ┌──────────────────────────┐   |
|  │ ▸ Pengajian Ramadhan    │   |
|  │   ● Aktif  •  120 org   │   |
|  ├──────────────────────────┤   |
|  │ ▸ CAI 2026              │   |
|  │   ● Aktif  •  450 org   │   |
|  ├──────────────────────────┤   |
|  │ ▸ PAUD ...              │   |
|  │   ○ Draft  •  0 org     │   |
|  └──────────────────────────┘   |
|                                  |
|  ──────────────────               |
|                                  |
|  AGENDA                          |
|  🕐 08:00  Sesi 1 Pengajian     |
|  🕐 10:00  Sesi 2 CAI           |
|                                  |
|  ──────────────────               |
|                                  |
|  AKTIVITAS TERBARU               |
|  • Budi — absen 5 menit lalu    |
|  • Siti — registrasi 12 menit   |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
+----------------------------------+
```

---

## 4. MOBILE EVENT HOME (375×812)

```
+----------------------------------+
|  ← Kembali         Pengajian    |
+----------------------------------+
|                                  |
|  ┌──────────────────────────┐   |
|  │  ☰ Pengajian Ramadhan    │   |
|  │  2026                    │   |
|  │                          │   |
|  │  ● Aktif  │ Masjid       │   |
|  │            │ Al-Falah    │   |
|  │                          │   |
|  │  120 peserta • 75% hadir  │   |
|  └──────────────────────────┘   |
|                                  |
|  ┌──────┬──────┬──────┐        |
|  │ 120  │ 90   │ 8    │        |
|  │Peserta│Hadir │Sesi  │        |
|  └──────┴──────┴──────┘        |
|                                  |
|  ──────────────────               |
|                                  |
|  MODUL EVENT                    |
|  ┌──────────┬──────────┐       |
|  │ 📱      │ 👥       │       |
|  │ Absensi │ Peserta  │       |
|  │ QR Scan │ Daftar   │       |
|  ├──────────┼──────────┤       |
|  │ 📍      │ 📊       │       |
|  │ Akses   │ Laporan  │       |
|  │ Desa    │ Regional │       |
|  ├──────────┼──────────┤       |
|  │ 🖨️      │          │       |
|  │ QR &    │          │       |
|  │ Label   │          │       |
|  └──────────┴──────────┘       |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
+----------------------------------+
```

---

## 5. MOBILE PEOPLE SEARCH (375×812)

```
+----------------------------------+
|  ← Orang                        |
+----------------------------------+
|                                  |
|  🔍 Cari nama person...         |
|                                  |
|  ┌──────────────────────────┐   |
|  │ Filter ▼                  │   |
|  └──────────────────────────┘   |
|                                  |
|  ┌──────────────────────────┐   |
|  │ 👤 Budi Santoso          │   |
|  │    📍 Desa Mekar Sari    │   |
|  │    🟢 3 event            │   |
|  ├──────────────────────────┤   |
|  │ 👤 Siti Nurhaliza        │   |
|  │    📍 Desa Sukamaju      │   |
|  │    🟢 2 event            │   |
|  ├──────────────────────────┤   |
|  │ 👤 Adi Pratama           │   |
|  │    📍 Desa Cinta Alam    │   |
|  │    🟢 1 event            │   |
|  └──────────────────────────┘   |
|                                  |
|  [Lihat Semua]                   |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
+----------------------------------+
```

---

## 6. MOBILE ANALYTICS (375×812)

```
+----------------------------------+
|  ← Analitik                     |
+----------------------------------+
|                                  |
|  [7 Hari ▾]  [Semua Event ▾]   |
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
|  Kehadiran per Event             |
|  Pengajian    ████████  75%     |
|  CAI          ██████    60%     |
|  PAUD         ○          —     |
|                                  |
|  ──────────────────               |
|                                  |
|  Recent                          |
|  • Budi absen — 08:15           |
|  • Siti daftar — 07:30          |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
+----------------------------------+
```

---

## 7. MOBILE QR SCAN (375×812)

```
+----------------------------------+
|  ← Kembali       [Manual]       |
+----------------------------------+
|                                  |
|    ┌──────────────────────┐     |
|    │                      │     |
|    │                      │     |
|    │    QR SCANNER        │     |
|    │    VIEWFINDER        │     |
|    │                      │     |
|    │                      │     |
|    └──────────────────────┘     |
|                                  |
|    Arahkan kamera ke QR code    |
|    peserta                    |
|                                  |
|    [Cari Manual]                |
|                                  |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
+----------------------------------+
```

---

## 8. GESTURE & INTERACTION

| Gesture | Aksi |
|---------|------|
| Tap bottom tab | Navigasi ke halaman |
| Swipe right | Kembali (back) |
| Swipe left on list item | Reveal actions (edit, delete) |
| Pull down | Refresh data |
| Long press on event | Show event quick actions |
| Tap FAB | Quick scan (modal scanner) |
| Tap search bar | Focus + keyboard appear |

---

## 9. TOUCH TARGET SIZES

| Element | Minimum Size | Notes |
|---------|-------------|-------|
| Bottom tab items | 48×48px | Label + icon |
| List items | 48px height | Entire row tappable |
| Buttons | 44×44px atau full width | Full width preferred |
| FAB | 56×56px | Circular, above tabs |
| Badge / chip | 32px height | Read-only |
| Icon only | 44×44px (with padding) | Must have tooltip |
| Modal close (×) | 44×44px | Pojok kanan atas |
| Search bar | 44px height | Full width |

---

## 10. RESPONSIVE BREAKPOINTS (MOBILE-FOCUSED)

| Breakpoint | Width | Layout |
|------------|-------|--------|
| Small phone | 320-374px | Single column, compact padding |
| Standard phone | 375-428px | Single column, standard padding |
| Large phone | 414-480px | Single column, comfortable |
| Foldable | 600-720px | Dual column possible |
| Tablet | 768-1024px | Sidebar visible, multi-column |

---

## 11. RULES

| Rule | Detail |
|------|--------|
| Bottom tab adalah navigasi utama mobile | Sidebar drawer hanya untuk menu tambahan |
| Event list di tab "Event" berupa bottom sheet / drawer | Bukan halaman penuh |
| FAB hanya untuk aksi utama (absensi cepat) | Jangan lebih dari 1 FAB |
| Back gesture (swipe right) harus didukung | Browser default atau custom handler |
| Pull to refresh harus ada di semua halaman data | Kecuali form |
| Keyboard must NOT cover form fields | Scroll into view |
| Camera access untuk QR scan harus one-tap | Jangan minta permission berulang |
| Loading lebih agresif di mobile | Skeleton, bukan spinner |
| Touch targets minimal 44×44px | WCAG requirement |
| Jangan gunakan hover di mobile | Gunakan tap state |
