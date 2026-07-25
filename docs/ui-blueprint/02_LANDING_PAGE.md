# 02 — LANDING PAGE BLUEPRINT

> SaaS-style landing page. Bukan company profile.
> Target benchmark: Linear, Stripe, Vercel, Notion, Clerk.

---

## 1. UX BENCHMARK REFERENCE

| Aplikasi | Pola UX | Yang Diadopsi |
|----------|---------|---------------|
| Linear | Clean hero + feature grid + code snippet | Hero + feature cards |
| Stripe | Single CTA + dashboard preview | Dashboard preview image |
| Vercel | Dark hero + deployment preview | Typography-heavy hero |
| Notion | Illustration + feature tabs | Benefit-oriented copy |
| Clerk | Centered hero + auth component preview | Clean, developer-friendly |

### Pola Universal SaaS Landing Page:

1. **Hero** — Satu kalimat value proposition + CTA + preview
2. **Social Proof** — Logos atau testimonial (future)
3. **Features** — 3-6 feature cards dengan icon
4. **Use Cases** — Siapa yang menggunakan (target user)
5. **Supported Events** — Jenis event yang didukung
6. **CTA Final** — Call to action terakhir
7. **Footer** — Links + copyright

---

## 2. WIREFRAME — FULL PAGE

```
+------------------------------------------------------------------+
|  NAVBAR                                                          |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │  ● KJA Event Manager     Features  Events  Login  [Coba Gratis] │
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  HERO SECTION                                                    |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │                                                              │|
|  │  Satu Platform untuk                                       │|
|  │  Semua Event Organisasi                                      │|
|  │                                                              │|
|  │  Kelola peserta, absensi, kompetisi, dan laporan            │|
|  │  dalam satu sistem. Dari pengajian hingga kejuaraan.         │|
|  │                                                              │|
|  │  [Mulai Gratis]  [Lihat Demo]  ────────────────              │|
|  │                                                              │|
|  │  ✦ Digunakan oleh 10+ organisasi                             │|
|  │                                                              │|
|  │  ┌───────────────────────────────────────────────────────┐   │|
|  │  │                                                       │   │|
|  │  │        PREVIEW DASHBOARD / WORKSPACE MOCKUP          │   │|
|  │  │        (ilustrasi screenshot aplikasi)               │   │|
|  │  │                                                       │   │|
|  │  └───────────────────────────────────────────────────────┘   │|
|  │                                                              │|
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  FEATURES SECTION                                               |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │                                                              │|
|  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │|
|  │  │ 📋       │  │ 📱       │  │ 📊       │  │ 🔄       │   │|
|  │  │ Registrasi│  │ Absensi  │  │ Laporan  │  │ Multi    │   │|
|  │  │ Digital   │  │ QR Scan  │  │ & Ekspor │  │ Event    │   │|
|  │  │ Peserta   │  │          │  │          │  │          │   │|
|  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │|
|  │                                                              │|
|  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │|
|  │  │ 👥       │  │ 🏆       │  │ 📄       │  │ 🔐       │   │|
|  │  │ Database  │  │ Kompetisi│  │ Sertifikat│  │ RBAC     │   │|
|  │  │ Person    │  │ & Skor   │  │ Otomatis │  │ & Hak    │   │|
|  │  │ │  │  │          │  │          │  │ Akses   │   │|
|  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │|
|  │                                                              │|
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  SUPPORTED EVENTS                                               |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │                                                              │|
|  │  Untuk Berbagai Jenis Kegiatan                               │|
|  │                                                              │|
|  │  ┌──────┬──────┬──────┬──────┬──────┬──────┐               │|
|  │  │CAI   │PENG  │PAUD  │SILAT │LOMBA │SEMIN │               │|
|  │  │      │AJIAN │      │      │      │AR    │               │|
|  │  └──────┴──────┴──────┴──────┴──────┴──────┘               │|
|  │                                                              │|
|  │  ┌──────┬──────┬──────┬──────┬──────┬──────┐               │|
|  │  │OLAH  │FESTI │WORKS │MUDA  │Dll   │      │               │|
|  │  │RAGA  │VAL   │HOP   │BAQAH │      │      │               │|
|  │  └──────┴──────┴──────┴──────┴──────┴──────┘               │|
|  │                                                              │|
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  STATS / SOCIAL PROOF                                           |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │                                                              │|
|  │  ┌────────────┐  ┌────────────┐  ┌────────────┐           │|
|  │  │ 10+        │  │ 5.000+     │  │ 50+        │           │|
|  │  │ Organisasi │  │ Peserta    │  │ Event      │           │|
|  │  │            │  │ Terdaftar  │  │ Terselenggara          │|
|  │  └────────────┘  └────────────┘  └────────────┘           │|
|  │                                                              │|
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  CTA SECTION                                                    |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │                                                              │|
|  │  Siap Mengelola Event Lebih Efisien?                         │|
|  │  Mulai gratis. Tidak perlu kartu kredit.                     │|
|  │                                                              │|
|  │  [Mulai Gratis]                                              │|
|  │                                                              │|
|  └──────────────────────────────────────────────────────────────┘|
|                                                                  |
|  ──────────────────────────────────────────────────────────────── |
|                                                                  |
|  FOOTER                                                         |
|  ┌──────────────────────────────────────────────────────────────┐|
|  │  ● KJA Event Manager                                        │|
|  │  © 2026 KJA Techno. All rights reserved.                    │|
|  │  Terms  Privacy  Contact                                    │|
|  └──────────────────────────────────────────────────────────────┘|
+------------------------------------------------------------------+
```

---

## 3. WIREFRAME — HERO ONLY (MOBILE)

```
+----------------------------------+
|  ☰ KJA EM           [Coba]     |
+----------------------------------+
|                                  |
|  Satu Platform untuk            |
|  Semua Event Organisasi          |
|                                  |
|  Kelola peserta, absensi,        |
|  kompetisi, dan laporan          |
|  dalam satu sistem.              |
|                                  |
|  [Mulai Gratis]                  |
|  [Lihat Demo]                    |
|                                  |
|  ✦ 10+ organisasi percaya       |
|                                  |
|  ┌──────────────────────────┐   |
|  │                          │   |
|  │  Preview Mockup          │   |
|  │                          │   |
|  └──────────────────────────┘   |
+----------------------------------+
```

---

## 4. CONTENT STRATEGY

### 4.1 Headline Options

| Option | Copy | Fokus |
|--------|------|-------|
| A | Satu Platform untuk Semua Event Organisasi | Universal |
| B | Operasional Event. Satu Sistem. | Efisiensi |
| C | Kelola Seluruh Event dalam Satu Platform | Produktivitas |

**Rekomendasi: Option A** — paling universal, tidak bias ke jenis event tertentu.

### 4.2 Subheadline

> Kelola peserta, absensi, kompetisi, dan laporan dalam satu sistem. Dari pengajian hingga kejuaraan.

Mengapa: Menyebutkan dua ujung spektrum (pengajian = religious, kejuaraan = kompetitif) untuk menunjukkan keluasan cakupan.

### 4.3 Feature Copy

| Feature | Headline | Description |
|---------|----------|-------------|
| Registrasi | Registrasi Digital | Input data peserta sekali, gunakan untuk semua event |
| Absensi | Absensi QR Scan | Scan cepat, real-time, tanpa antre |
| Laporan | Laporan & Ekspor | Rekap otomatis, export Excel satu klik |
| Multi Event | Multi Event | Satu database untuk semua kegiatan organisasi |
| Database Person | Database Person | Satu identitas per orang, reusable lintas event |
| Kompetisi | Kompetisi & Skor | Kelola penilaian, ranking, dan pemenang |
| Sertifikat | Sertifikat Otomatis | Generate sertifikat peserta secara massal |
| RBAC | Hak Akses | Atur siapa bisa apa, per event dan peran |

---

## 5. VISUAL GUIDELINES

| Element | Specification |
|---------|---------------|
| Navbar | Transparent → solid on scroll. Logo kiri, nav tengah, CTA kanan |
| Hero background | Blue gradient subtle (brand blue 50 → 100) atau dark (brand blue 900) |
| Typography hero | Display 1 (36px), Bold, max-width 720px centered |
| Preview image | Browser frame mockup berisi Workspace |
| Feature cards | 3×2 grid, icon atas, headline medium, description small |
| Supported events | Badge-style chips dengan warna accent masing-masing |
| CTA buttons | Solid blue (`variant="primary"`), border-radius standar |
| Footer | Simple, 3 kolom: brand, legal, social |

### 5.1 Color Usage on Landing

| Section | Light Mode | Dark Mode |
|---------|------------|-----------|
| Background | `white` / `zinc-50` | `zinc-950` / `zinc-900` |
| Hero CTA | `blue-600` | `blue-500` |
| Feature icons | `blue-500` | `blue-400` |
| Event badges | Per event type accent | Per event type accent |
| Text | `zinc-900` / `zinc-600` | `white` / `zinc-300` |

---

## 6. INTERACTION NOTES

| Element | Interaksi |
|---------|-----------|
| Navbar CTA | Scroll ke form registrasi atau buka halaman auth |
| "Lihat Demo" | Scroll ke preview section atau buka modal video |
| Feature card | Hover: subtle lift + shadow |
| Supported events | Klik: filter use case atau scroll ke detail |
| "Mulai Gratis" | Navigasi ke `/register` |

---

## 7. RULES

| Rule | Detail |
|------|--------|
| Jangan gunakan ilustrasi masjid, orang berdoa, kaligrafi | Larangan branding agama |
| Jangan gunakan foto stok | Gunakan ilustrasi abstract geometric atau screenshot aplikasi |
| Jangan sebut "absensi" sebagai primary feature | Platform operational, bukan absensi |
| Jangan sebut "CAI" di hero | CAI adalah contoh event type, bukan identitas platform |
| Feature maksimal 8 | Pilih yang paling diferensiasi |
| Supported events minimal 6 | Tunjukkan variasi tanpa membuat kewalahan |
| Loading landing: static first, lazy load images | Jangan hambat First Contentful Paint |
