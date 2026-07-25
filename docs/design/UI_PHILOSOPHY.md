# UI PHILOSOPHY

> Design principles and experience philosophy for KJA Event Manager.

---

## 1. Design Principles

### 1.1 Operational Clarity

Setiap elemen UI harus memiliki tujuan operasional yang jelas.

- Jangan menambahkan dekorasi yang tidak memiliki fungsi
- Setiap klik harus menghasilkan aksi yang predictable
- Informasi yang paling sering digunakan harus paling mudah diakses
- Mode operasional (event day) membutuhkan interface yang lebih besar dan jelas

### 1.2 Progressive Disclosure

Tampilkan informasi secara bertahap sesuai konteks dan peran.

- Dashboard hanya menampilkan ringkasan — detail di halaman masing-masing
- Modal untuk aksi kontekstual, bukan navigasi penuh
- Formulir panjang dipecah menjadi step atau section yang jelas
- Data historis dapat diakses tetapi tidak ditampilkan di default view

### 1.3 Consistency Over Novelty

Konsistensi lebih penting daripada inovasi visual.

- Gunakan pattern yang sama untuk fungsi yang sama di seluruh app
- Satu tombol = satu aksi
- Warna memiliki makna yang tetap dan tidak berubah antar halaman
- Posisi elemen navigasi tidak berubah antar halaman

### 1.4 Mobile-First Operational

Aplikasi harus dapat digunakan di lapangan (field operation).

- Tombol aksi utama harus mudah diakses dengan satu tangan
- Scanning QR harus menjadi pengalaman utama di mobile
- Formulir harus mudah diisi di layar kecil
- Loading state harus informatif, tidak mengganggu

### 1.5 Minimum Clicks

Setiap tugas operasional harus diselesaikan dalam jumlah klik minimum.

- Absensi QR: scan → konfirmasi (2 langkah)
- Cari peserta: ketik → enter (2 langkah)
- Tambah data: buka modal → isi → simpan (3 langkah maksimal dari halaman induk)
- Semua aksi umum harus dalam 3 klik dari halaman utama

### 1.6 Forgiving Design

Sistem harus mencegah kesalahan sebelum terjadi.

- Konfirmasi untuk aksi destruktif (hapus, reject, archive)
- Validasi real-time di form (bukan setelah submit)
- Undo/rollback untuk operasi kritikal
- Pesan error yang jelas dan actionable

### 1.7 Universal & Neutral

Platform tidak boleh menunjukkan preferensi terhadap jenis event tertentu.

- Tidak ada simbol agama atau organisasi di UI default
- Warna branding netral (blue) — warna spesifik event hanya untuk badge
- Terminologi umum: "person" bukan "jamaah", "event" bukan "kegiatan"
- Setiap event type diperlakukan sama di level platform

---

## 2. UX Principles

### 2.1 Event Day Readiness

- Tombol aksi besar dan mudah dikenali
- Scanning mode harus segera siap (buka halaman → kamera aktif)
- Informasi peserta tampil jelas setelah scan
- Mode offline handling untuk skenario jaringan tidak stabil

### 2.2 Workspace Concept

Dashboard berubah menjadi Workspace — pusat kendali operasional.

- Workspace menampilkan semua event yang sedang aktif
- Setiap event adalah "card" dengan status dan quick-aksi
- Navigator event (EventSwitcher) untuk berpindah antar event
- Quick-create event dari workspace

### 2.3 Context-Aware Navigation

Sidebar berubah berdasarkan event type yang sedang aktif.

- CAI event → menu operasional CAI
- Pengajian event → menu operasional Pengajian
- Master Data tetap muncul di semua konteks
- Event Switcher untuk mengganti event context

### 2.4 Role-Based Experience

UI menyesuaikan dengan peran pengguna.

- Super Admin: full access, user management
- Admin: operational control, master data
- Sekretariat: registration, reports, surat izin
- Operator Scan: scan QR only
- Juri: scoring/penilaian (future)
- Viewer: read-only dashboard dan report

---

## 3. Visual Design Principles

### 3.1 Clean & Professional

- White space yang cukup — jangan padatkan UI
- Gunakan cards untuk mengelompokkan informasi
- Hierarki visual yang jelas melalui typography
- Batasi penggunaan warna — blue sebagai primary, zinc sebagai neutral

### 3.2 Dark Mode as First Class

- Dark mode bukan fitur tambahan — adalah standar
- Semua komponen harus didesain untuk dark mode sejak awal
- Dark mode switch via Flux appearance
- Contrast ratio yang memadai di kedua mode

### 3.3 Typography-Driven Hierarchy

Gunakan ukuran dan weight font untuk menunjukkan hierarki, bukan warna atau dekorasi.

- Page title: text-2xl + bold
- Section title: text-xl + semibold
- Card title: text-lg + semibold
- Body: text-sm + normal

### 3.4 Intentional Color Usage

Setiap warna memiliki makna yang jelas dan terbatas.

| Color | Meaning |
|-------|---------|
| Blue | Brand identity, primary action |
| Green | Success, present, active |
| Red | Error, danger, rejected |
| Amber | Warning, pending |
| Zinc | Neutral, inactive, default |

Jangan gunakan warna di luar makna ini.

---

## 4. Micro-Interactions

### 4.1 Button States

| State | Behavior |
|-------|----------|
| Default | Solid color (blue for primary) |
| Hover | Slightly darker shade |
| Active / Press | Inset shadow or scale(0.97) |
| Disabled | Reduced opacity (opacity-50) |
| Loading | Spinner appears, button disabled |

### 4.2 Navigation Transitions

- Page navigation: instant (no transition) — operational speed
- Modal open: fade + scale (150ms)
- Sidebar toggle: slide (200ms)
- Filter/ search: debounced (300ms), no animation
- Flash message: auto-dismiss after 5 seconds

### 4.3 Feedback

- Success: subtle green flash message
- Error: red flash message with description
- Loading: spinner atau skeleton (jika > 500ms)
- Empty state: icon + message, bukan halaman kosong
- Validation: inline error di bawah field

---

## 5. Content Strategy

### 5.1 Language

- UI labels menggunakan Bahasa Indonesia
- UI labels harus pendek dan jelas (1-3 kata)
- Gunakan istilah yang sudah dikenal: "Simpan", "Batal", "Hapus"
- Hindari istilah teknis di UI pengguna

| Indonesian | English (code) |
|------------|----------------|
| Simpan | Save |
| Batal | Cancel |
| Hapus | Delete |
| Tambah | Add |
| Ubah | Edit |
| Cari | Search |
| Hadir | Present |
| Izin | Permitted |
| Belum Hadir | Not Present |

### 5.2 Error Messages

- Jelaskan apa yang salah
- Berikan solusi
- Gunakan bahasa yang sopan

| Bad | Good |
|-----|------|
| "Error: 500" | "Terjadi kesalahan. Silakan coba lagi." |
| "Validation failed" | "Nama lengkap harus diisi." |
| "Forbidden" | "Anda tidak memiliki akses." |

### 5.3 Empty States

- Jangan tampilkan tabel kosong tanpa konteks
- Berikan pesan yang informatif
- Sertakan tombol aksi jika relevan

Examples:
- "Belum ada data. Tambah person baru untuk memulai."
- "Belum ada sesi absensi. Buat sesi baru."
- "Tidak ada hasil untuk pencarian '{query}'."

---

## 6. Accessibility Principles

### 6.1 Keyboard Navigation

- Semua form dapat diisi dengan keyboard
- Tombol aksi dapat di-trigger dengan Enter/Space
- Modal dapat ditutup dengan Escape
- Tab order mengikuti logical flow halaman

### 6.2 Screen Reader

- Gunakan semantic HTML (`<nav>`, `<main>`, `<table>`, `<button>`)
- Setiap ikon harus memiliki `aria-label` atau teks pendamping
- Heading levels harus hierarchical (h1 → h2 → h3)
- Form fields harus memiliki label

### 6.3 Visual Accessibility

- Minimum contrast ratio 4.5:1 untuk text normal
- Minimum contrast ratio 3:1 untuk large text (18px+)
- Jangan gunakan warna sebagai satu-satunya indikator
- Sediakan text label untuk setiap ikon

---

## 7. Performance UX

### 7.1 Perceived Performance

- Livewire memberikan feedback instan via optimistic UI
- Tombol disabled segera setelah diklik (cegah double-click)
- Search menggunakan debounce (300ms)
- Navigasi menggunakan `wire:navigate` (Livewire SPA-like)

### 7.2 Loading States

| Duration | Treatment |
|----------|-----------|
| < 100ms | No feedback needed |
| 100-500ms | Button loading spinner |
| 500ms-2s | Skeleton loading for content area |
| > 2s | Progress bar with cancel option |

### 7.3 Data Freshness

- Dashboard stats di-refresh per page load
- Data peserta di-refresh per search/ filter change
- Attendance status real-time via Livewire polling
- Event context di-refresh via session

---

## 8. Design Evolution

### 8.1 Current State (v1.5)

- Brand: neutral (zinc accent) with inconsistent green
- Layout: traditional sidebar + header layout
- Dashboard: stat cards + table
- Auth: card-centered login
- Pengajian: emerald-green design (distinct from main app)

### 8.2 Target State (v2.0)

- Brand: unified blue system
- Layout: sidebar + header
- Workspace: card-based event grid replacing dashboard
- Auth: SaaS-style landing page with login
- All event types: unified design language with color-coded badges

### 8.3 Future State (v3.0+)

- Landing page: full SaaS marketing site
- White label: organization branding support
- Theme engine: customizable color schemes
- Mobile app: native or PWA

---

## 9. Do Not Deviate

Prinsip berikut TIDAK BOLEH dilanggar dalam desain apapun:

1. **No code before design** — semua fitur harus didesain di dokumentasi dulu
2. **Blue is primary** — brand identity menggunakan blue, bukan warna lain
3. **Green is semantic only** — green hanya untuk success state dan Pengajian badge
4. **No religious symbols** — platform harus universal
5. **Mobile-ready** — semua fitur harus dapat diakses dari mobile
6. **Dark mode mandatory** — semua komponen harus mendukung dark mode
7. **Flux first** — gunakan Flux component sebelum custom solution
8. **Consistency** — jangan buat pattern baru jika pattern sudah ada
