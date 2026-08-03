# VISION V2 — Event Operating System

> Filosofi, Arsitektur, dan Roadmap V2 KJA Event Manager

---

# Filosofi: Event Operating System

KJA Event Manager tidak lagi diposisikan sebagai aplikasi absensi.

KJA Event Manager adalah **Event Operating System**.

Sebuah platform yang dapat mengelola **semua jenis event** tanpa perlu membuat modul khusus untuk setiap cabang event.

---

# Build Engine, Not Module

## Jangan pernah membuat:

- Modul Silat
- Modul Voli
- Modul MTQ
- Modul PAUD
- Modul Seminar
- Modul Turnamen

## Sebagai gantinya, bangun:

```
Competition Engine
        +
   Scoring Engine
        +
  Blueprint Event
```

Semua jenis event dikonfigurasi melalui **engine**, bukan melalui **modul khusus**.

Engine bersifat generic dan dapat digunakan ulang untuk berbagai jenis event tanpa perubahan kode.

---

# Blueprint Event

Blueprint memberikan **konfigurasi awal** untuk sebuah event.

## Contoh Blueprint:

| Blueprint | Deskripsi |
|-----------|-----------|
| Pengajian | Event keagamaan dengan absensi dan laporan desa |
| Kejuaraan Silat | Event kompetisi dengan scoring versus |
| Turnamen Olahraga | Event olahraga dengan bracket dan league |
| Festival PAUD | Event non-kompetisi dengan penilaian pass/fail |
| Seminar | Event satu arah dengan registrasi dan absensi |
| Custom | Event tanpa blueprint — konfigurasi manual |

## Karakteristik Blueprint:

- Hanya memberikan **konfigurasi awal**
- **Dapat diubah** oleh panitia setelah event dibuat
- Tidak mengunci jenis event secara permanen
- Tidak membutuhkan coding untuk membuat blueprint baru

---

# Competition Engine

Competition Engine menangani **semua format kompetisi** secara generic.

## Konfigurasi Competition:

| Parameter | Nilai |
|-----------|-------|
| Nama | Nama kompetisi |
| Format | Bracket, League, Individual, Team |
| Sistem | Round Robin, Double Elimination, Single Elimination, Swiss |
| Peserta | Individual atau Tim |
| Scoring Type | Versus, Score, Time, Distance, Ranking, Pass/Fail |

## Tidak ada hardcode jenis lomba.

Competition Engine hanya memiliki **konfigurasi**.

Tidak ada class `SilatMatch`, `VoliMatch`, atau `MTQMatch`.

Semua perilaku ditentukan oleh konfigurasi.

---

# Scoring Engine

Scoring Engine bersifat **generic** dan dapat menangani berbagai jenis penilaian.

## Jenis Penilaian:

| Jenis | Contoh Penggunaan |
|-------|------------------|
| Versus | Silat, Tanding, Debat |
| Score | Basket, Voli, Sepak Bola |
| Time | Lari, Renang, Balap |
| Distance | Lompat Jauh, Lempar |
| Ranking | Penilaian subjektif (MTQ, Fashion Show) |
| Pass/Fail | Sertifikasi, Festival PAUD |

## Template Penilaian:

- Dapat dibuat tanpa coding
- Dapat diedit oleh panitia
- Dapat digunakan ulang lintas event
- Dapat memiliki multiple komponen penilaian dengan bobot berbeda

---

# Venue Management

Venue bersifat **reusable** dan **hierarkis**.

```
Master Venue
     ↓
 Event Venue
     ↓
 Arena / Room
```

## Konsep:

| Level | Deskripsi | Scope |
|-------|-----------|-------|
| Master Venue | Data venue global (nama, alamat, kapasitas) | Global |
| Event Venue | Venue yang digunakan pada event tertentu | Per Event |
| Arena / Room | Sub-lokasi dalam venue | Per Event Venue |

## Karakteristik:

- Satu Master Venue dapat digunakan oleh **banyak event**
- Event Venue merupakan **penggunaan** dari Master Venue pada event tertentu
- Arena/Room merupakan pembagian area dalam Event Venue
- Data venue tidak hilang setelah event selesai

---

# Live Schedule Engine

Jadwal tidak bersifat **statis**.

## Karakteristik:

- Jadwal mengikuti **kondisi pertandingan** secara realtime
- Public melihat **estimasi terbaru**, bukan jadwal cetak
- Jadwal otomatis menyesuaikan ketika pertandingan molor atau lebih cepat
- Notifikasi perubahan jadwal dikirim ke peserta dan panitia terkait

## Konsep:

```
Jadwal Rencana
     ↓
 Jadwal Aktual
     ↓
 Estimasi Real-time
```

---

# Public Dashboard

Portal publik yang dapat diakses **tanpa login**.

## Tampilan:

| Bagian | Deskripsi |
|--------|-----------|
| Sedang Berlangsung | Pertandingan/aktivitas yang aktif saat ini |
| Selanjutnya | Jadwal yang akan datang |
| Jadwal Lengkap | Seluruh jadwal event |
| Bracket | Visualisasi bracket kompetisi |
| Hasil | Hasil pertandingan yang sudah selesai |
| Pengumuman | Pengumuman resmi panitia |
| Pencarian | Cari peserta/jadwal/hasil |

---

# Panitia Dashboard

Dashboard internal untuk panitia.

## Menu:

| Area | Deskripsi |
|------|-----------|
| Peserta | Manajemen data peserta |
| Absensi | Rekap dan monitoring absensi |
| Registrasi | Pendaftaran dan import peserta |
| Jadwal | Manajemen jadwal dan rundown |
| Penilaian | Input dan monitoring penilaian |
| Sekretariat | Surat izin, dokumen, arsip |
| Laporan | Export dan rekap data |

---

# Roadmap V2

## Status

🟡 **Sebagian terimplementasi.** Competition V1 (Sprint 7–10), Public Portal (Sprint 9.0), dan Event Dashboard (Sprint 10.0) sudah COMPLETE. Fondasi Competition announcements, Venue CRUD, jadwal + status match sudah ada. Item generic engine (Blueprint Event, Competition Engine generic, Scoring Engine, hierarki Venue V2, Live Schedule realtime, Certificate, Mobile, Public API) masih Planned.

## Urutan Pengembangan

| # | Item | Status |
|---|------|--------|
| 1 | Blueprint Event | 📋 Planned |
| 2 | Competition Engine | 🟡 Partial — Competition V1 (module) COMPLETE |
| 3 | Scoring Engine | 📋 Planned |
| 4 | Venue Management | 🟡 Partial — Venue CRUD V1 ada; hierarki V2 Planned |
| 5 | Live Schedule Engine | 🟡 Partial — jadwal + status match ada |
| 6 | Public Dashboard | ✅ COMPLETE — Sprint 9.0 Public Portal |
| 7 | Announcement Engine | 🟡 Partial — Competition announcements live |
| 8 | Certificate Engine | 📋 Planned |
| 9 | Mobile | 📋 Planned |
| 10 | Public API | 📋 Planned |

---

# Diagram Arsitektur V2

```
┌─────────────────────────────────────────────┐
│              Blueprint Event                 │
│  (Konfigurasi awal event, dapat diubah)     │
└────────────────────┬────────────────────────┘
                     │
     ┌───────────────┼───────────────┐
     ▼               ▼               ▼
┌──────────┐  ┌──────────┐  ┌──────────────┐
│Competition│  │  Scoring │  │    Venue     │
│  Engine   │  │  Engine  │  │  Management  │
│           │  │          │  │              │
│• Bracket  │  │• Versus  │  │• Master Venue│
│• League   │  │• Score   │  │• Event Venue │
│• Round    │  │• Time    │  │• Arena/Room  │
│  Robin    │  │• Ranking │  │              │
│• Double   │  │• Pass/   │  │              │
│  Elim     │  │  Fail    │  │              │
└──────┬────┘  └─────┬────┘  └──────┬───────┘
       │             │              │
       └──────┬──────┘              │
              ▼                     ▼
      ┌──────────────┐    ┌──────────────┐
      │Live Schedule │    │  Panitia     │
      │   Engine     │    │  Dashboard   │
      └──────┬───────┘    └──────┬───────┘
             │                   │
             └───────┬───────────┘
                     ▼
            ┌──────────────────┐
            │ Public Dashboard │
            │  (No Login)      │
            └──────────────────┘
```

---

# Dampak pada Arsitektur Existing

## Tidak Ada Perubahan

Roadmap V2 tidak mengubah:

- Arsitektur Person → Participation → EventAttendance
- RBAC S1–S7
- Event Type (cai / pengajian)
- Multi Event Foundation
- Seluruh fitur CAI Operational
- Seluruh fitur Pengajian Desa MVP

## Perluasan

Roadmap V2 menambahkan:

- Competition Engine sebagai layer baru di atas Participation
- Scoring Engine sebagai layer terpisah untuk penilaian
- Blueprint Event sebagai konfigurasi awal event
- Venue Management dengan hierarki reusable
- Live Schedule Engine dengan estimasi realtime
- Public Dashboard tanpa login

---

# Future Expansion

Setelah V2, area ekspansi potensial:

| Area | Deskripsi |
|------|-----------|
| Multi Organization | Satu platform digunakan banyak organisasi |
| White Label | Branding khusus per organisasi |
| SaaS | Platform sebagai layanan |
| Plugin System | Ekstensi pihak ketiga |
| AI Integration | Auto-scoring, rekomendasi jadwal |
| Offline Mode | Operasional tanpa internet |
| Advanced Analytics | Business intelligence untuk event |

---

# Hubungan dengan Roadmap V1

```
Roadmap V1 (COMPLETED)
├── Foundation Platform
├── Multi Event Architecture
├── RBAC
├── CAI Operational
├── Pengajian Desa MVP
└── Documentation

Roadmap V2 (PARTIAL)
├── ✅ Competition V1 (module) — COMPLETE
├── ✅ Public Dashboard / Public Portal — COMPLETE
├── ✅ Event Dashboard — COMPLETE
├── 🟡 Competition Engine (generic) — Planned
├── 🟡 Venue Management — Venue CRUD V1 ada
├── 🟡 Live Schedule Engine — jadwal + status ada
├── 🟡 Announcement Engine — fondasi ada
├── 📋 Blueprint Event — Planned
├── 📋 Scoring Engine — Planned
├── 📋 Certificate Engine — Planned
├── 📋 Mobile — Planned
└── 📋 Public API — Planned
```

V1 adalah fondasi. V2 adalah transformasi menjadi Event Operating System.

---

*Dokumen ini mencerminkan visi produk. Implementasi aktual mengikuti kode.*
