# 01 — WORKSPACE BLUEPRINT

> Halaman pertama setelah login. Pusat operasional harian.
> BUKAN dashboard statistik.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| Linear | Greeting + recent activity + issue list | Greeting + Aktivitas Terbaru |
| Notion | Workspace switcher + page tree | Workspace sebagai root concept |
| Stripe | Ringkasan balance + recent transactions + quick actions | Quick actions + ringkasan |
| GitHub | Feed repository + recent activity | Event cards grid |
| Vercel | Project grid + deployment status | Event cards with status |
| Clerk | Centered, clean, minimal | Greeting personalization |

---

## 2. PAGE PURPOSE

Workspace adalah jawaban atas pertanyaan:

> "Apa yang perlu saya lakukan hari ini?"

Bukan:

> "Bagaimana statistik event saya?"

Workspace memberikan:
1. **Orientasi** — event apa yang sedang aktif?
2. **Aksi** — apa yang bisa saya lakukan sekarang?
3. **Konteks** — apa agenda hari ini?
4. **Kesadaran** — apa yang terjadi terakhir?

---

## 3. WIREFRAME — DESKTOP (1440×900)

```
+------------------------------------------------------------------+
|  SIDEBAR                    |  CONTENT AREA                       |
|  ┌─────────────────────┐    |  ┌────────────────────────────────┐ |
|  │                     │    |  │  HEADER                        │ |
|  │  ● KJA              │    |  │  ┌──────────────────────────┐  │ |
|  │  Event Manager      │    |  │  │ Selamat datang, Rina     │  │ |
|  │                     │    |  │  │ Rabu, 29 Juli 2026       │  │ |
|  │  ────────────────   │    |  │  │ 3 Event Aktif            │  │ |
|  │                     │    |  │  └──────────────────────────┘  │ |
|  │  ☰ Workspace        │    |  │                               │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │  ▷ Events           │    |  │                               │ |
|  │    └ Pengajian      │    |  │  QUICK ACTIONS                │ |
|  │    └ CAI            │    |  │  ┌────────┬────────┬────────┐ │ |
|  │    └ PAUD           │    |  │  │  ├──   │  ├──   │  ├──   │ │ |
|  │                     │    |  │  │ Absen  │ Daftar│ Lapor  │ │ │ |
|  │  ▷ People           │    |  │  │ Cepat  │ Peserta│ Cepat │ │ |
|  │                     │    |  │  └────────┴────────┴────────┘ │ |
|  │  ▷ Analytics        │    |  │                               │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │  ▷ Administration   │    |  │                               │ |
|  │                     │    |  │  EVENT AKTIF                   │ |
|  │  ────────────────   │    |  │  ┌──────────┬──────────┬────┐ │ |
|  │                     │    |  │  │          │          │    │ │ |
|  │  👤 Rina            │    |  │  │ PENGAIJAN│   CAI    │PAUD│ │ |
|  │     Admin           │    |  │  │ ● Aktif  │ ● Aktif  │ ○  │ │ |
|  │                     │    |  │  │ 120 org  │ 450 org  │Draf│ │ |
|  │                     │    |  │  │ 75% hr   │ 60% hr   │t   │ │ |
|  │                     │    |  │  │ [Buka]   │ [Buka]   │    │ │ |
|  │                     │    |  │  └──────────┴──────────┴────┘ │ |
|  │                     │    |  │                               │ │
|  │                     │    |  │  ──────────────────────────    │ |
|  │                     │    |  │                               │ │
|  │                     │    |  │  AGENDA HARI INI              │ |
|  │                     │    |  │  ┌────────────────────────┐   │ |
|  │                     │    |  │  │ 🕐 08:00-10:00         │   │ |
|  │                     │    |  │  │ Sesi 1 — Pengajian     │   │ |
|  │                     │    |  │  │ 📍 Aula Utama          │   │ |
|  │                     │    |  │  ├────────────────────────┤   │ |
|  │                     │    |  │  │ 🕐 10:00-12:00         │   │ |
|  │                     │    |  │  │ Sesi 2 — CAI           │   │ |
|  │                     │    |  │  │ 📍 Gedung Serbaguna    │   │ |
|  │                     │    |  │  └────────────────────────┘   │ |
|  │                     │    |  │                               │ |
|  │                     │    |  │  ──────────────────────────    │ |
|  │                     │    |  │                               │ |
|  │                     │    |  │  AKTIVITAS TERBARU            │ |
|  │                     │    |  │  ┌────────────────────────┐   │ |
|  │                     │    |  │  │ • 5 menit — Budi absen │   │ |
|  │                     │    |  │  │ • 12 menit — Siti      │   │ |
|  │                     │    |  │  │   registrasi           │   │ |
|  │                     │    |  │  │ • 30 menit — Dasbor    │   │ |
|  │                     │    |  │  │   diekspor             │   │ |
|  │                     │    |  │  └────────────────────────┘   │ |
|  │                     │    |  │                               │ |
|  └─────────────────────┘    |  └────────────────────────────────┘ |
+------------------------------------------------------------------+
```

---

## 4. WIREFRAME — TABLET (768×1024)

```
+----------------------------------------------------+
|  ☰ KJA Event Manager                    👤 Rina   |
+----------------------------------------------------+
|                                                    |
|  Selamat datang, Rina                              |
|  Rabu, 29 Juli 2026  •  3 Event Aktif             |
|                                                    |
|  ──────────────────────────────────────             |
|                                                    |
|  QUICK ACTIONS                                     |
|  ┌────────────┬────────────┬────────────┐         |
|  │  Absen     │  Daftar    │  Lapor     │         |
|  │  Cepat     │  Peserta   │  Cepat     │         |
|  └────────────┴────────────┴────────────┘         |
|                                                    |
|  ──────────────────────────────────────             |
|                                                    |
|  EVENT AKTIF                                       |
|  ┌──────────────────┬──────────────────┐          |
|  │ PENGAIJAN        │ CAI              │          |
|  │ ● Aktif          │ ● Aktif          │          |
|  │ 120 org • 75%    │ 450 org • 60%    │          |
|  │ [Buka]           │ [Buka]           │          |
|  ├──────────────────┼──────────────────┤          |
|  │ PAUD             │                  │          |
|  │ ○ Draft          │                  │          |
|  │ 0 org            │                  │          |
|  │ [Lanjutkan]      │                  │          |
|  └──────────────────┴──────────────────┘          |
|                                                    |
|  ──────────────────────────────────────             |
|                                                    |
|  AGENDA HARI INI                                   |
|  ┌────────────────────────────────────┐           |
|  │ 🕐 08:00 — Sesi 1 Pengajian       │           |
|  │ 🕐 10:00 — Sesi 2 CAI             │           |
|  └────────────────────────────────────┘           |
|                                                    |
|  AKTIVITAS TERBARU                                 |
|  ┌────────────────────────────────────┐           |
|  │ • Budi absen (5 menit)            │           |
|  │ • Siti registrasi (12 menit)      │           |
|  │ • Dasbor diekspor (30 menit)      │           |
|  └────────────────────────────────────┘           |
+----------------------------------------------------+
```

---

## 5. WIREFRAME — MOBILE (375×812)

```
+----------------------------------+
|  ☰ KJA EM              👤      |
+----------------------------------+
|                                  |
|  Selamat datang, Rina            |
|  Rabu, 29 Jul 2026               |
|  3 Event Aktif                   |
|                                  |
|  ──────────────────               |
|                                  |
|  QUICK ACTION                    |
|  ┌────────┬────────┬────────┐   |
|  │ Absen  │ Daftar │ Lapor  │   |
|  │ Cepat  │ Peserta│ Cepat  │   |
|  └────────┴────────┴────────┘   |
|                                  |
|  ──────────────────               |
|                                  |
|  EVENT AKTIF                     |
|  ┌──────────────────────────┐   |
|  │ PENGAIJAN    ● Aktif     │   |
|  │ 120 org • 75% hadir      │   |
|  │ [Buka Event]             │   |
|  ├──────────────────────────┤   |
|  │ CAI          ● Aktif     │   |
|  │ 450 org • 60% hadir      │   |
|  │ [Buka Event]             │   |
|  ├──────────────────────────┤   |
|  │ PAUD         ○ Draft     │   |
|  │ 0 org                    │   |
|  │ [Lanjutkan]              │   |
|  └──────────────────────────┘   |
|                                  |
|  ──────────────────               |
|                                  |
|  AGENDA                          |
|  🕐 08:00 Sesi 1 Pengajian      |
|  🕐 10:00 Sesi 2 CAI            |
|                                  |
|  AKTIVITAS TERBARU               |
|  • Budi absen — 5 menit          |
|  • Siti registrasi — 12 menit    |
+----------------------------------+
```

---

## 6. VISUAL HIERARCHY

```
Level 1: GREETING
    → Nama user, tanggal, jumlah event aktif
    → Font: Heading 1 (24px), Bold
    → Tujuan: Orientasi personal

Level 2: QUICK ACTIONS
    → 3-4 tombol aksi cepat
    → Font: Body + icon
    → Tujuan: Aksi instan tanpa navigasi

Level 3: EVENT AKTIF
    → Card grid (2-4 kolom desktop, 1 kolom mobile)
    → Setiap card: nama event, status, ringkasan, tombol aksi
    → Card border-left: warna accent event
    → Tujuan: Seleksi event untuk working context

Level 4: AGENDA
    → Timeline vertical
    → Waktu + nama kegiatan + lokasi
    → Tujuan: Kesadaran jadwal hari ini

Level 5: AKTIVITAS TERBARU
    → Feed vertikal, timestamp relatif
    → Tujuan: Situational awareness
```

---

## 7. INTERACTION NOTES

| Element | Interaksi |
|---------|-----------|
| Greeting | Static, personalisasi berdasarkan nama user |
| Quick Action "Absen Cepat" | Buka modal scanner QR langsung |
| Quick Action "Daftar Peserta" | Navigasi ke halaman registrasi event terakhir |
| Quick Action "Lapor Cepat" | Buka modal export cepat |
| Event Card | Klik "Buka" → navigasi ke Event Home |
| Event Card (Draft) | Klik "Lanjutkan" → navigasi ke Event Setup |
| Agenda Item | Klik → buka detail jadwal |
| Activity Item | Klik → buka halaman terkait aktivitas |

---

## 8. RULES

| Rule | Detail |
|------|--------|
| Workspace adalah satu-satunya halaman tanpa event accent | Brand blue murni — tidak ada warna event |
| Event accent hanya muncul di Event Card (border-left, badge) | Bukan di background atau header |
| Quick Action maksimal 4 tombol | Jika lebih, prioritaskan berdasarkan role |
| Activity feed maksimal 10 item | Urutkan descending by timestamp |
| Agenda hanya menampilkan hari ini | Lihat semua → navigasi ke Event Home |
| Loading state: skeleton cards untuk Event grid | Bukan spinner |
| Empty state (no active events): tombol "Buat Event Pertama" | Jangan tampilkan workspace kosong |
| Error state: retry button | Jika gagal load data |
