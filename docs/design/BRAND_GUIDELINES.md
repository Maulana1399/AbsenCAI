# BRAND GUIDELINES

> Master brand reference for KJA Event Manager.

---

## 1. Brand Identity

### 1.1 Brand Name

| Element | Value |
|---------|-------|
| Full Name | KJA Event Manager |
| Short Form | KJA EM |
| Former Name | AbsenCAI (retired) |
| Tagline | One Platform for Every Event. |
| Tagline (Alternate) | Operational Platform for Every Organization. |

### 1.2 Brand Pillars

| Pillar | Description |
|--------|-------------|
| **Universal** | Tidak terikat pada jenis event, agama, atau organisasi tertentu. |
| **Modular** | Setiap fitur adalah modul yang dapat diaktifkan sesuai kebutuhan. |
| **Operational** | Fokus pada efisiensi operasional event, bukan sekadar absensi. |
| **Scalable** | Dari event desa hingga organisasi besar, platform yang sama. |
| **Professional** | Tampilan SaaS-grade, enterprise-ready. |

### 1.3 Brand Voice

| Attribute | Guidelines |
|-----------|------------|
| Tone | Professional, clear, authoritative |
| Language | Bahasa Indonesia untuk UI, English untuk code/docs |
| Formality | Formal untuk sistem, friendly untuk feedback |
| Persona | Platform operator — tepercaya, efisien, modern |

### 1.4 What KJA Event Manager IS

- Platform operasional organisasi multi-event
- Sistem manajemen peserta, attendance, dan kompetisi
- Database person universal lintas event
- Platform SaaS modern dengan workspace concept

### 1.5 What KJA Event Manager IS NOT

- BUKAN aplikasi absensi saja
- BUKAN aplikasi khusus event keagamaan
- BUKAN ERP, HR System, atau Accounting Software
- BUKAN Learning Management System
- BUKAN aplikasi sosial media

---

## 2. Logo & Visual Identity

### 2.1 Logo Philosophy

Logo KJA Event Manager harus mencerminkan:
- **Konektivitas** — menghubungkan orang, event, dan data
- **Struktur** — organisasi yang rapi dan teratur
- **Modernitas** — platform digital profesional
- **Universalitas** — tidak mengandung simbol agama, budaya spesifik, atau event tertentu

### 2.2 Logo Usage Rules

| Context | Element |
|---------|---------|
| Sidebar / App | `app-logo` Blade component — teks "KJA Event Manager" + icon |
| Auth pages | `app-logo` with larger sizing |
| Print / PDF | Full logo with tagline |
| Favicon | `app-logo-icon` SVG |
| Mobile header | `app-logo-icon` only |

### 2.3 Logo Prohibitions

- Jangan menambahkan ilustrasi masjid, salib, atau simbol agama
- Jangan mengganti teks "KJA Event Manager" dengan nama event
- Jangan menggunakan logo CAI lama
- Jangan mengubah rasio logo
- Jangan menambahkan efek drop shadow, glow, atau gradient pada logo di UI
- Jangan meletakkan logo di atas background dengan kontras rendah

### 2.4 Clear Space

Minimal clear space di sekitar logo = 1× tinggi logo pada semua sisi.

---

## 3. Color Palette

### 3.1 Primary Brand Color

| Token | Value | Usage |
|-------|-------|-------|
| `brand-50` | `#EFF6FF` | Background light |
| `brand-100` | `#DBEAFE` | Hover light background |
| `brand-200` | `#BFDBFE` | Selected state |
| `brand-300` | `#93C5FD` | Border light |
| `brand-400` | `#60A5FA` | Border, icon |
| `brand-500` | `#3B82F6` | Primary default |
| `brand-600` | `#2563EB` | Primary hover, active |
| `brand-700` | `#1D4ED8` | Primary text on light |
| `brand-800` | `#1E40AF` | Dark mode accent |
| `brand-900` | `#1E3A8A` | Deep accent |
| `brand-950` | `#172554` | Darkest accent |

### 3.2 Semantic Colors

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `success` | `#16A34A` | `#22C55E` | Success state, hadir |
| `warning` | `#D97706` | `#FBBF24` | Warning, pending |
| `danger` | `#DC2626` | `#EF4444` | Error, danger, reject |
| `info` | `#2563EB` | `#60A5FA` | Information, CAI event |
| `neutral` | `#737373` | `#A3A3A3` | Secondary text, inactive |

### 3.3 Neutral / Surface Colors

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `surface` | `#FFFFFF` | `#0A0A0A` | Page background |
| `surface-elevated` | `#FAFAFA` | `#171717` | Card background |
| `surface-hover` | `#F5F5F5` | `#262626` | Hover state |
| `border` | `#E5E5E5` | `#404040` | Borders, dividers |
| `text-primary` | `#171717` | `#FAFAFA` | Primary text |
| `text-secondary` | `#737373` | `#A3A3A3` | Secondary text |
| `text-muted` | `#A3A3A3` | `#525252` | Muted text |

### 3.4 Event Type Colors

| Event Type | Badge Color | Usage |
|------------|-------------|-------|
| CAI | `brand` (blue) | Badge, identifier |
| Pengajian | `emerald` | Badge, identifier |
| Festival | `violet` | Future |
| Seminar | `amber` | Future |
| Competition | `rose` | Future |

### 3.5 Badge / Status Color Mapping

| Status | Color | Example |
|--------|-------|---------|
| Active / Hadir | `emerald` | Kehadiran, status aktif |
| Inactive / Tidak Hadir | `zinc` | Status non-aktif |
| Danger / Rejected | `red` | Ditolak, error |
| Pending / Warning | `amber` | Menunggu, warning |
| Info / CAI Event | `blue` | Informasi, tipe event CAI |
| Pengajian Event | `emerald` | Tipe event Pengajian |
| Arsiip / Archived | `zinc` | Event diarsipkan |

### 3.6 Color Application Rules

1. **Primary blue** digunakan untuk: tombol utama, link, active state, brand elements
2. **Green/emerald** hanya digunakan untuk: success state, attendance hadir, Pengajian event type badge
3. **Red** hanya untuk: error, danger, reject, hapus
4. **Amber** hanya untuk: pending, warning
5. Jangan menggunakan green sebagai primary brand color
6. Jangan mencampur aksen hijau dengan elemen brand utama
7. Dark mode menggunakan shade yang lebih terang untuk aksesibilitas

---

## 4. Typography

### 4.1 Font Family

| Usage | Font | Fallback |
|-------|------|----------|
| UI / Display | Instrument Sans | `ui-sans-serif, system-ui, sans-serif` |
| Code / Monospace | JetBrains Mono (future) | `ui-monospace, monospace` |

### 4.2 Type Scale

| Level | Size | Weight | Line Height | Usage |
|-------|------|--------|-------------|-------|
| Display 1 | `3xl` / `1.875rem` | Bold (700) | `1.2` | Landing page hero |
| Heading 1 | `2xl` / `1.5rem` | Bold (700) | `1.3` | Page title |
| Heading 2 | `xl` / `1.25rem` | Semibold (600) | `1.4` | Section title |
| Heading 3 | `lg` / `1.125rem` | Semibold (600) | `1.4` | Card title |
| Body | `base` / `1rem` | Normal (400) | `1.5` | Paragraph |
| Body Small | `sm` / `0.875rem` | Normal (400) | `1.5` | Table cell, form label |
| Caption | `xs` / `0.75rem` | Medium (500) | `1.5` | Badge, helper text |
| Overline | `xs` / `0.75rem` | Semibold (600) | `1.5` | Table header, uppercase |

---

## 5. Imagery & Iconography

### 5.1 Icon Library

| Source | Usage |
|--------|-------|
| Lucide Icons (via Flux) | UI icons — sidebar, buttons, empty states |
| Custom SVG (via stub) | Brand icon, app logo, event-type icons |

### 5.2 Illustration Style

- Gunakan ilustrasi abstract/geometric — bukan figur manusia atau simbol agama
- Warna ilustrasi menggunakan brand blue palette + neutral
- Hindari foto stok yang terlihat generik
- Empty state: ilustrasi sederhana dengan teks informatif

### 5.3 Photography (Future)

- Foto event dari pengguna (dengan izin)
- Jangan gunakan foto stok yang mengandung simbol agama
- Foto harus relevan dengan konteks event management

### 5.4 Prohibited Imagery

Berikut TIDAK BOLEH digunakan sebagai identitas visual utama:
- Ilustrasi masjid, kubah, atau menara
- Simbol salib, bintang David, atau simbol agama lainnya
- Ilustrasi orang sedang berdoa/beribadah
- Ornamen kaligrafi
- Simbol organisasi tertentu
- Gambar yang mengandung unsur politik atau SARA

---

## 6. Tone of Communication

### 6.1 UI Labels

| Context | Language | Example |
|---------|----------|---------|
| Navigation | Indonesia | Dashboard, Absensi, Registrasi |
| Button | Indonesia | Simpan, Batal, Hapus, Tambah |
| Form Label | Indonesia | Nama Lengkap, Jenis Kelamin |
| Error Message | Indonesia | Data tidak ditemukan. |
| Empty State | Indonesia | Belum ada data. |
| System Message | Indonesia | Berhasil disimpan. |

### 6.2 Code & Documentation

| Context | Language |
|---------|----------|
| Variable names | English |
| Class names | English |
| Documentation | English |
| Comments | English |
| Git commits | English (Conventional Commits) |

---

## 7. Brand Application

### 7.1 App Logo

Currently implemented as Blade components:
- `components/app-logo.blade.php` — text + icon
- `components/app-logo-icon.blade.php` — icon only (SVG)

### 7.2 Landing Page (Future SaaS)

- Hero section dengan ilustrasi abstract blue-themed
- Feature highlights dalam card grid
- CTA button dengan brand blue
- Testimonial section (future)
- Pricing section (future)

### 7.3 Workspace Dashboard (Future)

- Replaces current dashboard
- Grid of workspace cards
- Each card = one event with quick stats
- "Create Event" as primary action
- Recent activity feed

### 7.4 Marketing Collateral (Future)

- Social media graphics: blue theme, geometric patterns
- Presentation templates: brand blue + white
- Email templates: clean, minimal, blue CTA buttons
- Documentation: brand header/footer

---

## 8. Brand Assets

| Asset | Location | Format |
|-------|----------|--------|
| App Logo | `components/app-logo.blade.php` | Blade SVG |
| App Logo Icon | `components/app-logo-icon.blade.php` | Blade SVG |
| Favicon | (in public/) | SVG / PNG |

---

## 9. Version & History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-07-25 | Initial brand guidelines — blue theme, universal branding, SaaS direction |
