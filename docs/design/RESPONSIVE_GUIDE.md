# RESPONSIVE GUIDE

> Responsive design strategy for KJA Event Manager.

---

## 1. Responsive Philosophy

### 1.1 Mobile-First Operational

KJA Event Manager digunakan di berbagai perangkat, termasuk di lapangan saat event.

Prinsip:
- **Mobile-first** — desain dimulai dari mobile, kemudian diperkaya untuk desktop
- **Field-ready** — tombol besar, target sentuh minimal 44px, form mudah diisi
- **Scanning priority** — QR scanner harus optimal di mobile
- **Data density** — tabel dan data grid menyesuaikan dengan viewport

### 1.2 Device Profiles

| Device | Typical Usage | Key Consideration |
|--------|---------------|-------------------|
| Smartphone (360-428px) | QR scan, quick check, attendance | One thumb reach, large targets |
| Tablet (768-1024px) | Report review, registration | Split layouts, sidebar as drawer |
| Laptop (1280-1440px) | Data entry, management | Full sidebar, multi-column |
| Desktop (1920px+) | Dashboard monitoring, reports | Max content width, whitespace |
| TV / Projector (future) | Live monitoring display | Large text, auto-scroll |

---

## 2. Breakpoints

### 2.1 Tailwind Default Breakpoints

| Breakpoint | Min Width | Target Device | Layout Change |
|------------|-----------|---------------|---------------|
| `sm` | 640px | Large phone / phablet | 2-column grids start |
| `md` | 768px | Tablet portrait | 2-3 column grids |
| `lg` | 1024px | Tablet landscape / small laptop | Sidebar visible |
| `xl` | 1280px | Laptop / desktop | 3-4 column grids |
| `2xl` | 1536px | Large desktop | Max width containers |

### 2.2 KJA-Specific Breakpoint Usage

```blade
{{-- Stat card grid --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- cards --}}
</div>

{{-- Sidebar --}}
{{-- Mobile: hidden, Desktop: sticky --}}
<flux:sidebar class="lg:sticky">
{{-- Flux handles this internally --}}

{{-- Two-column layout --}}
<div class="flex flex-col gap-6 lg:flex-row">
    <div class="w-full lg:w-1/3">{{-- sidebar content --}}</div>
    <div class="w-full lg:w-2/3">{{-- main content --}}</div>
</div>

{{-- Responsive typography --}}
<h1 class="text-xl font-bold md:text-2xl text-zinc-900 dark:text-white">
    {{ __('Page Title') }}
</h1>

{{-- Responsive button width --}}
<flux:button variant="primary" class="w-full sm:w-auto">
    {{ __('Simpan') }}
</flux:button>
```

---

## 3. Responsive Patterns

### 3.1 Navigation

| Element | Mobile (< lg) | Desktop (lg+) |
|---------|---------------|---------------|
| Sidebar | Hidden — accessible via hamburger | Fixed, visible |
| Header | Visible — shows logo + hamburger + profile | Hidden |
| Event Switcher | In sidebar drawer | In sidebar |
| User Menu | In sidebar drawer (bottom) | In sidebar (dropdown) |
| Breadcrumbs | Hidden | Visible |

### 3.2 Data Tables

| Feature | Mobile (< lg) | Desktop (lg+) |
|---------|---------------|---------------|
| Scroll | Horizontal scroll (`overflow-x-auto`) | Normal |
| Column display | Minimal columns, hide non-essential | Full columns |
| Actions | Icon-only buttons | Text + icon buttons |
| Search | Full width | Fixed width (72) |
| Pagination | Simple prev/next | Full pagination with numbers |

Mobile table pattern:
```blade
<div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
        {{-- Always use overflow-x-auto for tables --}}
    </table>
</div>
```

### 3.3 Forms

| Feature | Mobile | Desktop |
|---------|--------|---------|
| Field width | Full width | Fixed or fluid |
| Layout | Single column stacked | Multi-column if needed |
| Buttons | Full width, stacked | Auto width, inline |
| Modal | Full screen (`sm:width`) | Centered card |

Mobile form pattern:
```blade
<form class="space-y-4">
    <flux:input label="Nama" wire:model="nama" class="w-full" />
    <flux:select label="Tipe" wire:model="tipe" class="w-full">
        {{-- options --}}
    </flux:select>
    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <flux:button variant="ghost" class="w-full sm:w-auto">{{ __('Batal') }}</flux:button>
        <flux:button variant="primary" class="w-full sm:w-auto">{{ __('Simpan') }}</flux:button>
    </div>
</form>
```

### 3.4 Cards & Grids

| Grid Type | Mobile | sm | md | lg | xl |
|-----------|--------|----|----|----|----|
| Stat cards | 1 col | 2 col | 2 col | 3-4 col | 4 col |
| Navigation cards | 1 col | 2 col | 2 col | 3 col | 3-4 col |
| Desa breakdown | 1 col | 2 col | 2 col | 3 col | 3 col |
| Event cards (workspace) | 1 col | 2 col | 2 col | 3 col | 3-4 col |

### 3.5 Modals

| Breakpoint | Width Class | Behavior |
|------------|-------------|----------|
| Default | `w-full` | Full screen width with margin |
| `sm:` | `sm:max-w-sm` | Narrow centered card |
| `md:` | `md:max-w-md` | Medium card |
| `lg:` | `lg:max-w-lg` | Large card |
| `xl:` | `xl:max-w-xl` | Extra large card |

```blade
<flux:modal name="example-modal" class="w-full sm:max-w-md">
    {{-- content --}}
</flux:modal>
```

### 3.6 Search & Filter Bar

Mobile (stacked):
```
┌──────────────────────────────────┐
│ [Search.......................]  │
│ [Filter ▼]                       │
└──────────────────────────────────┘
```

Desktop (horizontal):
```
┌──────────────────────────────────────────────┐
│ [Search........]    [Filter ▼]    [Tambah]   │
└──────────────────────────────────────────────┘
```

Implementation:
```blade
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <flux:input type="search" placeholder="{{ __('Cari...') }}" class="w-full sm:w-72" />
    <div class="flex flex-col gap-2 sm:flex-row">
        <flux:select class="w-full sm:w-48">{{-- filter --}}</flux:select>
        <flux:button variant="primary" class="w-full sm:w-auto">{{ __('Tambah') }}</flux:button>
    </div>
</div>
```

### 3.7 Page Header

Mobile:
```
┌──────────────────────────────────┐
│ Page Title                       │
│ Description text                 │
│ ─────────────────────────────    │
│ [Tambah] (full width)            │
└──────────────────────────────────┘
```

Desktop:
```
┌──────────────────────────────────────────────┐
│ Page Title                  [Tambah]         │
│ Description text                              │
│ ─────────────────────────────────────────    │
└──────────────────────────────────────────────┘
```

---

## 4. Component-Specific Responsive Behavior

### 4.1 Sidebar (`flux:sidebar`)

| Property | Mobile | Desktop |
|----------|--------|---------|
| Display | Hidden (`lg:hidden`) | Fixed, visible |
| Width | 16rem (256px) | 16rem (256px) |
| Toggle | Hamburger icon in header | N/A |
| Overlay | Dark overlay when open | N/A |
| Stashable | No | Yes (scroll position) |

### 4.2 Stat Cards

```blade
{{-- Responsive stat card grid --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($stats as $stat)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 sm:p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
                    <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $stat['value'] }}</p>
                </div>
                <flux:icon.{{ $stat['icon'] }} class="w-6 h-6 sm:w-8 sm:h-8 text-blue-500" />
            </div>
        </div>
    @endforeach
</div>
```

### 4.3 QR Scanner

| Feature | Mobile | Desktop |
|---------|--------|---------|
| Scanner width | 100% of viewport | 400px fixed |
| Camera | Rear camera preferred | Front/default |
| Result display | Below scanner | Side or below |
| Buttons | Large, full width | Normal |

Current CSS (retained from existing):
```css
#qr-reader {
    width:400px !important;
    max-width:100% !important;
}
```

### 4.4 Filter Tabs (Pengajian Report)

Mobile: horizontal scroll tabs
Desktop: inline tabs

```blade
<div class="overflow-x-auto -mx-4 sm:mx-0">
    <div class="flex gap-2 px-4 sm:px-0 min-w-max sm:min-w-0">
        {{-- tab items --}}
    </div>
</div>
```

### 4.5 Token Entry Page (Pengajian)

- Mobile: full-width card, centered
- Desktop: max-w-md card, centered

---

## 5. Touch Targets

### 5.1 Minimum Touch Target Size

| Element | Minimum Size |
|---------|-------------|
| Buttons | 44×44px |
| Icon-only buttons | 44×44px (with proper padding) |
| Links in content | 44×44px (minimum) |
| Form inputs | 44px height |
| Select dropdowns | 44px height |
| Toggle switches | 44px height |
| Table row actions | 44×44px |
| Sidebar nav items | 44px height |
| Modal close (×) | 44×44px |

### 5.2 Touch Target Spacing

| Context | Minimum Gap |
|---------|-------------|
| Between buttons | 8px (gap-2) |
| Between form fields | 16px (space-y-4) |
| Between table rows | 0px (standard) |
| Between card items | 16px (gap-4) |

---

## 6. Content Density

### 6.1 Density Levels

| Level | Usage | Characteristic |
|-------|-------|----------------|
| Comfortable | Dashboard, workspace, landing | More whitespace, larger elements |
| Standard | Data tables, lists | Default padding (px-4 py-3) |
| Compact | Attendance recap, dense data | Reduced padding (px-4 py-2) |

### 6.2 Density by Device

| Device | Density Level | Rationale |
|--------|---------------|-----------|
| Mobile | Comfortable to Standard | Touch targets need space |
| Tablet | Standard | Balance of data and usability |
| Desktop | Standard to Compact | Data density preferred |

---

## 7. Responsive Text

### 7.1 Text Scaling

| Element | Mobile | sm | md | lg+ |
|---------|--------|----|----|-----|
| Page title (H1) | `xl` (1.25rem) | `xl` | `2xl` (1.5rem) | `2xl` |
| Section title (H2) | `lg` (1.125rem) | `lg` | `xl` (1.25rem) | `xl` |
| Card title (H3) | `base` (1rem) | `base` | `lg` (1.125rem) | `lg` |
| Body | `sm` (0.875rem) | `sm` | `sm` | `base` (1rem) |
| Stat number | `xl` (1.25rem) | `xl` | `2xl` (1.5rem) | `2xl` |

### 7.2 Implementation

```blade
<h1 class="text-xl font-bold md:text-2xl text-zinc-900 dark:text-white">
    {{ __('Page Title') }}
</h1>

<p class="text-sm lg:text-base text-zinc-700 dark:text-zinc-300">
    {{ __('Content text') }}
</p>
```

---

## 8. Testing Checklist

### 8.1 Responsive Breakpoint Testing

| Breakpoint | Width | Test Items |
|------------|-------|------------|
| Mobile | 375px | Sidebar hidden, content full width, buttons full width, stacked layout |
| Mobile large | 428px | Same as mobile, touch targets accessible |
| Tablet | 768px | 2-column grid starts, sidebar still hidden |
| Tablet landscape | 1024px | Sidebar becomes visible, 3-column grids |
| Laptop | 1280px | Full layout, 4-column grids |
| Desktop | 1440px+ | Max-width containers comfortable |

### 8.2 Test Per Page Type

- [ ] Sidebar toggle works on mobile
- [ ] Tables have horizontal scroll on mobile
- [ ] Forms are single-column on mobile
- [ ] Buttons are full-width on mobile
- [ ] Modal is full-screen on mobile
- [ ] Stat grid adapts to columns
- [ ] Search/filter bar stacks on mobile
- [ ] Page title + action button stack on mobile
- [ ] QR scanner fits mobile viewport
- [ ] Touch targets are at least 44px on mobile
- [ ] No horizontal overflow on any breakpoint
- [ ] Text is readable without zoom on mobile

---

## 9. Do Not Use

- Jangan gunakan `overflow-x-hidden` pada body — akan memotong sidebar drawer
- Jangan gunakan fixed width on mobile elements — gunakan `w-full` + `max-w-*`
- Jangan sembunyikan informasi penting di mobile — prioritaskan konten
- Jangan gunakan hover-only interactions — tidak berfungsi di touch devices
- Jangan gunakan font size di bawah `12px` di mobile — readability issue
- Jangan gunakan horizontal scroll untuk element selain tabel
- Jangan gunakan `min-h-screen` pada content jika sidebar memakan ruang vertikal
