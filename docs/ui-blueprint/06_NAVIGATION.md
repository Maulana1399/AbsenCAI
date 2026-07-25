# 06 — NAVIGATION BLUEPRINT

> Complete navigation architecture.
> Evaluasi ulang struktur sidebar berdasarkan UX reasoning. Bukan terpaku pada review sebelumnya.

---

## 1. UX REASONING — WHY THIS STRUCTURE

### 1.1 Task Analysis

Kita urutkan berdasarkan frekuensi tugas harian sekretariat/operator:

| Rank | Task | Frekuensi | Lokasi Terbaik |
|------|------|-----------|----------------|
| 1 | Lihat ringkasan & jadwal hari ini | Sangat Sering | **Workspace** (first page) |
| 2 | Absensi / scan peserta | Sangat Sering (event-day) | **Event Home** → Modul |
| 3 | Registrasi peserta | Sering | **Event Home** → Modul |
| 4 | Cari data person | Sering | **People** (top-level) |
| 5 | Laporan & ekspor | Sering | **Analytics** (top-level) |
| 6 | Kelola event | Kadang | **Administration** |
| 7 | Kelola user | Jarang | **Administration** |
| 8 | Pengaturan sistem | Jarang | **Administration** |

### 1.2 Mental Model

```
"Lihat keadaan hari ini"          → Workspace
"Kerjakan sesuatu di event X"     → Events → Event Home → Module
"Cari data seseorang"             → People
"Buat laporan"                    → Analytics
"Atur pengaturan"                 → Administration
```

### 1.3 Final Navigation Decision

Setelah evaluasi, struktur:

```
WORKSPACE
EVENTS
PEOPLE
ANALYTICS
ADMINISTRATION
```

**Alasan memilih 5 item:**

1. **Workspace** — Pusat operasional, kebutuhan #1. Wajib top-level.
2. **Events** — Inti platform, semua modul operasional di sini. Wajib top-level.
3. **People** — Database person adalah aset utama platform. Cukup penting untuk top-level. Person adalah canonical identity. Di sinilah user mencari data person lintas event.
4. **Analytics** — Laporan, statistik, ekspor. Cross-event. Cukup penting untuk top-level.
5. **Administration** — Settings, user management, master data (Desa, Kelompok). Pengaturan.

**Mengapa People dipisah dari Administration?**

UX Reasoning: People adalah data operasional yang diakses setiap hari (cari person, edit identitas). Administration adalah data konfigurasi yang jarang diubah (user, role, settings). Menyatukan keduanya akan membuat "Pengaturan" terlalu panjang dan mencampur frekuensi akses.

---

## 2. FINAL SIDEBAR STRUCTURE

```
┌─────────────────────────────────┐
│  ● KJA Event Manager            │
│                                 │
│  ─────────────────────────      │
│                                 │
│  ☰ Workspace                    │  ← Active state (halaman ini)
│                                 │
│  ▾ Events                    ▼  │  ← Expanded
│    ○ Pengajian Ramadhan 2026    │  ← Active event
│    ○ CAI 2026                   │
│    ○ PAUD ...                   │
│    ○ [+ Buat Event]             │
│                                 │
│  👥 People                      │
│                                 │
│  📊 Analytics                   │
│                                 │
│  ⚙ Administration               │
│    ▸ Master Data                │  ← Expandable
│      Desa                       │
│      Kelompok                   │
│    ▸ Users                      │
│    ▸ Settings                   │
│                                 │
│  ─────────────────────────      │
│                                 │
│  👤 Rina                        │
│     Admin                       │
│     [Logout]                    │
└─────────────────────────────────┘
```

### 2.1 Collapsed State

```
┌─────────────────────────────────┐
│  ● KJA                          │
│                                 │
│  ☐                             │
│  ▾                             │
│    Pengajian                    │
│    CAI                          │
│    PAUD                         │
│  👥                             │
│  📊                             │
│  ⚙                              │
│                                 │
│  👤                             │
└─────────────────────────────────┘
```

---

## 3. EVENTS EXPANDED STRUCTURE

```
▾ Events
  ○ Pengajian Ramadhan 2026    [● Act]  ← active event
  ○ CAI 2026                   [○]
  ○ PAUD ...                   [○]
  ─────────────────────
  ○ [+ Buat Event Baru]        ← quick action
```

**Key decision:** Events section menunjukkan daftar event. Klik event → masuk ke Event Home. Event yang sedang aktif (active context) ditandai. User dapat mengganti active event dari sini.

---

## 4. TOP NAVIGATION (LANDING PAGE)

```
┌──────────────────────────────────────────────────────────────┐
│  ● KJA Event Manager     Features  Events  Login  [Coba Gratis] │
└──────────────────────────────────────────────────────────────┘
```

Hanya muncul di Landing Page (tidak login). Setelah login, top nav menghilang, sidebar muncul.

---

## 5. MOBILE NAVIGATION — BOTTOM TAB BAR

Untuk mobile, sidebar diganti dengan bottom tab bar + hamburger drawer:

```
+----------------------------------+
|                                  |
|  CONTENT AREA                    |
|                                  |
|                                  |
+----------------------------------+
|  ☐  │  ▸  │  👥  │  📊  │  ⚙  |
|Home │Event│People│Analyt│Admin  |
+----------------------------------+
```

| Tab | Icon | Label | Navigasi |
|-----|------|-------|----------|
| Home | ☐ | Beranda | Workspace |
| Event | ▸ | Event | Event list (modal/drawer) |
| People | 👥 | Orang | People |
| Analytics | 📊 | Analitik | Analytics |
| Admin | ⚙ | Atur | Administration |

**Mengapa bottom tab?**
- Mobile usage tinggi untuk scan dan cek cepat
- Bottom tab mudah dijangkau dengan satu tangan
- Sidebar drawer membutuhkan dua langkah (buka → pilih)
- Bottom tab selalu visible

---

## 6. BREADCRUMB PATTERN

Breadcrumb muncul di content header untuk menunjukkan posisi:

```
Workspace  >  Events  >  Pengajian Ramadhan 2026  >  Absensi
```

| Level | Contoh |
|-------|--------|
| 1 | `Workspace` |
| 2 | `Workspace > Events` |
| 3 | `Workspace > Events > Pengajian 2026` |
| 4 | `Workspace > Events > Pengajian 2026 > Absensi` |

Breadcrumb tidak muncul di halaman level 1 (Workspace).

---

## 7. NAVIGATION BEHAVIOR

| Context | Sidebar | Top Nav | Bottom Tab (Mobile) |
|---------|---------|---------|---------------------|
| Landing Page (guest) | Hidden | Visible | Hidden |
| Login / Auth | Hidden | Hidden | Hidden |
| Workspace | Visible | Hidden | Visible |
| Event Home | Visible | Hidden | Visible |
| People | Visible | Hidden | Visible |
| Analytics | Visible | Hidden | Visible |
| Administration | Visible | Hidden | Visible |

---

## 8. QUICK NAVIGATION

### 8.1 Global Search (Future)

```
┌─────────────────────────────────┐
│  🔍 Cari person, event, ...    │
└─────────────────────────────────┘
```

- Shortcut: `Ctrl+K` atau `Cmd+K`
- Mencari: Person, Event, Module, Menu
- Navigasi ke hasil langsung

### 8.2 Keyboard Shortcuts

| Shortcut | Aksi |
|----------|------|
| `G → W` | Go to Workspace |
| `G → E` | Go to Events |
| `G → P` | Go to People |
| `G → A` | Go to Analytics |
| `G → S` | Go to Administration/Settings |
| `E → N` | Create new Event |
| `Ctrl+K` | Global search |

---

## 9. ROLE-BASED NAVIGATION VISIBILITY

| Role | Workspace | Events | People | Analytics | Administration |
|------|-----------|--------|--------|-----------|----------------|
| Super Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Sekretariat | ✅ | ✅ | ✅ | ✅ | Limited |
| Ketua Event | ✅ | ✅ (own) | ✅ | ✅ (own) | ❌ |
| PJ Divisi | ✅ | ✅ (own) | ✅ | ✅ (own) | ❌ |
| Operator Scan | ✅ | ✅ (scan only) | ❌ | ❌ | ❌ |
| Operator Registrasi | ✅ | ✅ (reg only) | ✅ | ❌ | ❌ |
| Juri | ✅ | ✅ (competition) | ❌ | ❌ | ❌ |
| Viewer | ✅ | ✅ (read) | ✅ (read) | ✅ (read) | ❌ |

---

## 10. RULES

| Rule | Detail |
|------|--------|
| Sidebar adalah navigation primer | Bukan top nav, bukan breadcrumb |
| Bottom tab adalah navigation primer mobile | Bukan hamburger drawer |
| Events section menunjukkan daftar event | Bukan hanya "Event Management" link |
| Active event ditandai di sidebar | Visual indicator (dot, highlight) |
| Breadcrumb max 4 level | Lebih dari 4 berarti perlu restruktur |
| Global search adalah future | Prioritaskan navigasi struktural dulu |
| Navigation harus role-aware | Jangan tampilkan menu yang tidak bisa diakses |
| Jangan gunakan mega-menu | Sidebar sederhana, 5 item |
