# PAGE LAYOUTS

> Complete page layout catalog for KJA Event Manager.

---

## 1. Layout Architecture

### 1.1 Layout Hierarchy

```
├── Main App Layout (x-layouts.app)
│   ├── Auth Layouts
│   │   ├── Auth Card (x-layouts.auth.card)
│   │   ├── Auth Simple (x-layouts.auth.simple)
│   │   └── Auth Split (x-layouts.auth.split)
│   ├── Pengajian Layout (x-layouts.pengajian)
│   └── Settings Layout (x-settings.layout)
│
└── Landing Page Layout (future — v3.0+)
```

### 1.2 Layout Selection Matrix

| Page Type | Layout | Notes |
|-----------|--------|-------|
| Authenticated app pages | `x-layouts.app` | Default untuk semua halaman internal |
| Auth / Login | `x-layouts.auth.card` | Centered card |
| Auth / Register | `x-layouts.auth.simple` | Simple form |
| Auth / Branded | `x-layouts.auth.split` | Split with branding |
| Settings | `x-settings.layout` | Settings with sidebar |
| Pengajian Desa | `x-layouts.pengajian` | Minimal layout for token entry |
| Print | None / Print layout | Browser-native print |
| Landing (future) | Custom | SaaS marketing layout |

---

## 2. Main App Layout (`x-layouts.app`)

### 2.1 Structure

```
┌──────────────────────────────────────────┐
│  Header (mobile only, lg:hidden)         │
├──────────┬───────────────────────────────┤
│          │                               │
│  Sidebar │  Content Area                 │
│  (fixed) │  {{ $slot }}                  │
│          │                               │
│          │  ┌─────────────────────────┐  │
│          │  │  Page Header            │  │
│          │  │  (heading + subtitle)   │  │
│          │  ├─────────────────────────┤  │
│          │  │                         │  │
│          │  │  Page Content           │  │
│          │  │  (space-y-6)            │  │
│          │  │                         │  │
│          │  └─────────────────────────┘  │
│          │                               │
│          │  Footer (optional)            │
├──────────┴───────────────────────────────┤
```

### 2.2 Sidebar Composition

```
flux:sidebar (sticky, stashable)
├── app-logo (brand)
├── livewire:event-switcher
├── flux:navlist (navigasi)
│   ├── navlist.group (Master Data)
│   │   └── navlist.item (Person, Desa, Kelompok)
│   ├── navlist.group (Operational — event-type dependent)
│   │   └── navlist.item (various)
│   └── flux:spacer
├── flux:separator
└── flux:dropdown (user menu)
    ├── Profile
    ├── Settings (if applicable)
    └── Logout
```

### 2.3 Sidebar Behavior

| Device | Behavior |
|--------|----------|
| Desktop (lg+) | Fixed sidebar, always visible |
| Mobile (< lg) | Hidden drawer, toggled via hamburger |
| Toggle | `flux:sidebar.toggle` in mobile header |

Sidebar uses `stashable` — user's scroll position is preserved.

### 2.4 Event-Type Contextual Sidebar

Sidebar content changes based on active event type:

**CAI Event Active:**
```
├── Master Data
├── Dashboard
├── Absensi (Scan, Sesi)
├── Registrasi
├── Database Peserta
├── Laporan
├── QR & Label
├── Sekretariat (Surat Izin, Activity Log)
├── Event
└── User Management (Super Admin only)
```

**Pengajian Event Active:**
```
├── Master Data
├── Pengajian (Regional Report)
├── Peserta (Daftar Peserta, Import Massal)
├── Operasional Desa (Akses Desa)
├── Event
└── User Management (Super Admin only)
```

**No Active Event:**
```
├── Master Data
├── Event
└── User Management (Super Admin only)
```

---

## 3. Auth Layouts

### 3.1 Auth Card (`x-layouts.auth.card`)

```
┌──────────────────────────────────────────┐
│                                          │
│    ┌────────────────────────────┐        │
│    │                            │        │
│    │   app-logo                 │        │
│    │                            │        │
│    │   ┌────────────────────┐  │        │
│    │   │                    │  │        │
│    │   │   Auth Form        │  │        │
│    │   │                    │  │        │
│    │   └────────────────────┘  │        │
│    │                            │        │
│    └────────────────────────────┘        │
│                                          │
└──────────────────────────────────────────┘
```

- Centered card dengan max-width
- Background: gradient atau solid neutral light
- Card dengan shadow dan border
- Digunakan untuk: login, forgot password

### 3.2 Auth Split (`x-layouts.auth.split`)

```
┌──────────────────────┬───────────────────┐
│                      │                   │
│   Brand Panel        │   Auth Form       │
│                      │                   │
│   - Logo             │   - Form fields   │
│   - Illustration     │   - Button        │
│   - Tagline          │   - Links         │
│                      │                   │
└──────────────────────┴───────────────────┘
```

- Split screen: 40% brand / 60% form
- Brand panel dengan blue gradient atau ilustrasi
- Digunakan untuk: landing + login combined (future)

### 3.3 Auth Simple (`x-layouts.auth.simple`)

- Minimal layout tanpa sidebar
- Form sederhana dengan judul
- Digunakan untuk: setup wizard, simple auth flows

---

## 4. Page Content Layouts

### 4.1 Standard List Page

```
┌──────────────────────────────────────────┐
│  📄 Page Header                          │
│  ┌──────────────────────────────────────┐│
│  │ Title                    [Tambah]    ││
│  │ Description                          ││
│  └──────────────────────────────────────┘│
│  ──────────────────────────────────────  │
│  🔍 Search & Filter Bar                  │
│  ┌──────────────────────────────────────┐│
│  │ [Search...]     [Filter ▼]          ││
│  └──────────────────────────────────────┘│
│  📊 Data Table                           │
│  ┌──────────────────────────────────────┐│
│  │ Header │ Header │ Header │ Aksi     ││
│  ├──────────────────────────────────────┤│
│  │ Data   │ Data   │ Data   │ [Edit]   ││
│  │ Data   │ Data   │ Data   │ [Edit]   ││
│  │ Data   │ Data   │ Data   │ [Edit]   ││
│  └──────────────────────────────────────┘│
│  📄 Pagination                           │
└──────────────────────────────────────────┘
```

Used by: Person, Desa, Kelompok, User, Event, Peserta, Access Grants

### 4.2 Dashboard / Workspace Page

```
┌──────────────────────────────────────────┐
│  📄 Page Header                          │
│  ┌──────────────────────────────────────┐│
│  │ Title                    [Actions]   ││
│  └──────────────────────────────────────┘│
│  ──────────────────────────────────────  │
│  📊 Stat Cards Grid (2-4 columns)        │
│  ┌────────┬────────┬────────┬────────┐  │
│  │ Card 1 │ Card 2 │ Card 3 │ Card 4 │  │
│  └────────┴────────┴────────┴────────┘  │
│  ──────────────────────────────────────  │
│  Main Content (table / chart / list)     │
│  ┌──────────────────────────────────────┐│
│  │                                      ││
│  │   Content area                       ││
│  │                                      ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

Used by: Dashboard CAI, Workspace (future), Regional Report

### 4.3 Detail Page

```
┌──────────────────────────────────────────┐
│  📄 Page Header                    [Back]│
│  ┌──────────────────────────────────────┐│
│  │ Title / Entity Name         [Edit]  ││
│  │ Subtitle / description              ││
│  └──────────────────────────────────────┘│
│  ──────────────────────────────────────  │
│  📋 Detail Card                          │
│  ┌──────────────────────────────────────┐│
│  │ Field: Value                         ││
│  │ Field: Value                         ││
│  │ Field: Value                         ││
│  └──────────────────────────────────────┘│
│  📊 Related Data (tabs / sections)       │
│  ┌──────────────────────────────────────┐│
│  │ Tab1 │ Tab2 │ Tab3                   ││
│  ├──────────────────────────────────────┤│
│  │ Tab content                          ││
│  └──────────────────────────────────────┘│
└──────────────────────────────────────────┘
```

Used by: Event Detail, Person Detail (future), Participation Detail (future)

### 4.4 Modal-Centric Page (Scan / Entry)

```
┌──────────────────────────────────────────┐
│  Minimal Header                          │
│                                          │
│  ┌──────────────────────────────────────┐│
│  │                                      ││
│  │   Main Action Area                   ││
│  │   (QR Scanner / Search / Form)       ││
│  │                                      ││
│  │   ┌──────────────────────────────┐  ││
│  │   │                              │  ││
│  │   │   Scanner / Input            │  ││
│  │   │                              │  ││
│  │   └──────────────────────────────┘  ││
│  │                                      ││
│  └──────────────────────────────────────┘│
│                                          │
│  Result / Status below                   │
└──────────────────────────────────────────┘
```

Used by: QR Scan Page, Token Entry, Manual Attendance

### 4.5 Settings Page

```
┌──────────────────────────────────────────┐
│  Settings Layout (x-settings.layout)     │
│  ┌──────┬───────────────────────────────┐│
│  │      │                               ││
│  │ Nav  │  Content                      ││
│  │      │  ┌─────────────────────────┐  ││
│  │ • Prf│  │  Heading                │  ││
│  │ • Pwd│  │  Subheading             │  ││
│  │ • ...│  │                         │  ││
│  │      │  │  Form fields            │  ││
│  │      │  │                         │  ││
│  │      │  └─────────────────────────┘  ││
│  └──────┴───────────────────────────────┘│
└──────────────────────────────────────────┘
```

Used by: Profile Settings, Password Change

---

## 5. Special Page Layouts

### 5.1 Landing Page (SaaS Style — Future)

```
┌──────────────────────────────────────────┐
│  🎯 Hero Section                         │
│  ┌──────────────────────────────────────┐│
│  │  Headline                            ││
│  │  Subheadline                         ││
│  │  [CTA Button]                        ││
│  │  Illustration / Mockup               ││
│  └──────────────────────────────────────┘│
│  ✨ Features Section                     │
│  ┌──────┬──────┬──────┬──────┐          │
│  │Feat 1│Feat 2│Feat 3│Feat 4│          │
│  └──────┴──────┴──────┴──────┘          │
│  🏢 Use Cases Section                    │
│  ┌──────┬──────┬──────┐                 │
│  │Case1 │Case2 │Case3 │                 │
│  └──────┴──────┴──────┘                 │
│  💰 Pricing Section (future)             │
│  ┌──────┬──────┬──────┐                 │
│  │Plan A│Plan B│Plan C│                 │
│  └──────┴──────┴──────┘                 │
│  📝 Footer                               │
└──────────────────────────────────────────┘
```

### 5.2 Workspace Page (Future — Replaces Dashboard)

```
┌──────────────────────────────────────────┐
│  🔲 Workspace Header                     │
│  ┌──────────────────────────────────────┐│
│  │  Workspace              [Create]    ││
│  │  Selamat datang, {user}             ││
│  └──────────────────────────────────────┘│
│  ──────────────────────────────────────  │
│  🗂️ Event Cards Grid                     │
│  ┌──────────┬──────────┬──────────┐     │
│  │ Event A  │ Event B  │ Event C  │     │
│  │ Active   │ Archived │ Active   │     │
│  │ Stats    │ (hidden) │ Stats    │     │
│  │ [Enter]  │          │ [Enter]  │     │
│  ├──────────┼──────────┼──────────┤     │
│  │ Event D  │ Event E  │ [+ Buat  │     │
│  │ Draft    │ Active   │  Event]  │     │
│  │ [Enter]  │ Stats    │         │     │
│  └──────────┴──────────┴──────────┘     │
│  📊 Recent Activity Feed                  │
└──────────────────────────────────────────┘
```

### 5.3 Print Layout

```blade
{{-- Print layout: no sidebar, no header --}}
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 0; }
        body { font-family: sans-serif; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
```

Used by: QR Label Print, Surat Izin Print, Label 4×4

---

## 6. Responsive Layout Behavior

### 6.1 Breakpoint Adjustments

| Element | Mobile (< lg) | Desktop (lg+) |
|---------|---------------|---------------|
| Sidebar | Hidden (drawer) | Fixed, visible |
| Header | Visible with hamburger | Hidden |
| Content | Full width | Width - sidebar |
| Stat grid | 1-2 columns | 3-4 columns |
| Table | Horizontal scroll | Normal |
| Search/Filter | Stacked vertical | Horizontal row |
| Buttons | Full width | Auto width |
| Modal | Full screen (sm:width) | Centered card |

### 6.2 Mobile Navigation

```
┌──────────────────────────────────────────┐
│ [☰]  KJA Event Manager    [Profile]     │
├──────────────────────────────────────────┤
│                                          │
│   (drawer slides in from left)           │
│                                          │
│  ┌──────────────────────────────────┐    │
│  │ Logo                             │    │
│  │ Event Switcher                   │    │
│  │ ─────────────────────────────    │    │
│  │ Master Data                      │    │
│  │ Dashboard                        │    │
│  │ ...                              │    │
│  │ ─────────────────────────────    │    │
│  │ User Menu                        │    │
│  └──────────────────────────────────┘    │
└──────────────────────────────────────────┘
```

---

## 7. Layout Implementation Rules

### 7.1 Using Layouts

```blade
{{-- Standard app page --}}
<x-layouts.app :title="__('Person')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('Person') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Kelola data person global') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="space-y-6">
        {{-- page content --}}
    </div>
</x-layouts.app>

{{-- Auth page --}}
<x-layouts.auth.card :title="__('Login')">
    {{-- auth form --}}
</x-layouts.auth.card>

{{-- Settings page --}}
<x-settings.layout :heading="__('Profile')" :subheading="__('Kelola profil')">
    {{-- settings content --}}
</x-settings.layout>
```

### 7.2 Title Handling

- `x-layouts.app` menerima `:title` parameter untuk `<title>` tag
- Title format: `Page Title | KJA Event Manager`
- Layout secara otomatis menambahkan suffix brand name

### 7.3 Spacing Conventions

| Context | Spacing |
|---------|---------|
| Page content wrapper | `space-y-6` |
| Between page header and content | `mb-8` + separator |
| Card padding | `p-6` (default), `p-4` (compact) |
| Modal content | `space-y-6` |
| Form fields | `space-y-4` or `space-y-6` |
| Between sections | `mb-6` |
| Table cell padding | `px-4 py-3` (standard) |
| Button groups | `gap-2` (flex) |

---

## 8. Do Not Use

- Jangan buat layout baru jika layout yang sesuai sudah ada
- Jangan gunakan `x-layouts.app` untuk halaman auth
- Jangan gunakan `x-layouts.auth.*` untuk halaman app
- Jangan override struktur sidebar — gunakan flux navlist
- Jangan sembunyikan sidebar di desktop
- Jangan buat multiple layout untuk fungsi yang sama
