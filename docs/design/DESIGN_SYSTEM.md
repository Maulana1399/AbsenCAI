# DESIGN SYSTEM

> Master design system reference for KJA Event Manager.
> This document is the single source of truth for all UI design decisions.

---

## 1. Design System Overview

### 1.1 Purpose

Dokumen ini menjembatani antara UI/UX design decisions dan implementasi teknis.
AI coding / developer harus merujuk ke dokumen ini sebagai primary reference
sebelum membuat keputusan desain atau implementasi UI.

### 1.2 Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| CSS Framework | Tailwind CSS | v4 |
| Component Library | Livewire Flux UI Pro | v2 |
| Icon Library | Lucide Icons | (bundled with Flux) |
| Frontend Framework | Livewire | v3 |
| Build Tool | Vite | latest |
| PHP Framework | Laravel | 12 |

### 1.3 Core Design Tokens

```css
/* Defined in resources/css/app.css */

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --color-accent: var(--color-blue-600);        /* Proposed: change from neutral-800 */
    --color-accent-content: var(--color-blue-600); /* Proposed: change from neutral-800 */
    --color-accent-foreground: var(--color-white);
}

@layer theme {
    .dark {
        --color-accent: var(--color-blue-400);
        --color-accent-content: var(--color-blue-400);
        --color-accent-foreground: var(--color-blue-950);
    }
}
```

### 1.4 Document Hierarchy

```
DESIGN_SYSTEM.md (you are here — master reference)
├── BRAND_GUIDELINES.md (brand identity, logo, voice)
├── COLOR_SYSTEM.md (complete color palette)
├── TYPOGRAPHY.md (type scale, hierarchy)
├── ICONOGRAPHY.md (icon usage, inventory)
├── COMPONENT_LIBRARY.md (component patterns, usage)
├── UI_PHILOSOPHY.md (design principles, UX)
├── PAGE_LAYOUTS.md (layout architecture, page types)
└── RESPONSIVE_GUIDE.md (breakpoints, responsive patterns)
```

---

## 2. Design System Principles

1. **Flux-First** — Gunakan Flux UI component sebagai default untuk semua elemen UI
2. **Tailwind for Layout** — Gunakan Tailwind utility untuk layout, spacing, dan grid
3. **Custom Only When Necessary** — Custom CSS hanya jika Flux + Tailwind tidak mencukupi
4. **Dark Mode Native** — Semua komponen didesain untuk light dan dark mode
5. **Operational Focus** — Desain mengutamakan kecepatan operasional di lapangan
6. **Universal Branding** — Tidak bergantung pada jenis event atau organisasi tertentu

---

## 3. Framework Configuration

### 3.1 Tailwind CSS v4 Configuration

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/livewire/flux-pro/stubs/**/*.blade.php';
@source '../../vendor/livewire/flux/stubs/**/*.blade.php';

@custom-variant dark (&:where(.dark, .dark *));
```

### 3.2 Flux UI Configuration

Flux UI Pro v2 dikonfigurasi secara default melalui vendor.
Tidak ada konfigurasi tambahan yang diperlukan.
Appearance toggle (light/dark) disediakan oleh Flux.

---

## 4. Theming Architecture

### 4.1 Theme Layers

```
Layer 1: Brand Theme (blue identity)
    ↓ defines
Layer 2: Color Tokens (semantic + neutral)
    ↓ defines
Layer 3: Component Theme (Flux + custom)
    ↓ defines
Layer 4: Page Layouts (templates)
```

### 4.2 Event-Type Theming

Setiap event type memiliki variasi warna badge tetapi tetap dalam kerangka yang sama:

| Component | CAI | Pengajian | Generic |
|-----------|-----|-----------|---------|
| Sidebar | Full CAI menu | Pengajian menu | Minimal |
| Badge | Blue | Emerald | Zinc |
| Dashboard | CAI stats | Regional report | Workspace |
| Theme | Standard blue | Standard blue | Standard blue |

Seluruh UI tetap menggunakan brand blue — hanya badge yang membedakan event type.

---

## 5. Coding Patterns

### 5.1 Page Header Pattern

```blade
<div class="relative mb-8 w-full">
    <flux:heading size="xl" level="1">{{ __('PAGE TITLE') }}</flux:heading>
    <flux:subheading size="lg" class="mb-8">{{ __('Page description') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>
```

### 5.2 Page with Action Button

```blade
<div class="relative mb-8 w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('PAGE TITLE') }}</flux:heading>
            <flux:subheading size="lg" class="mt-1">{{ __('Page description') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="openCreateModal">
            <flux:icon.plus class="w-4 h-4" />
            {{ __('Tambah') }}
        </flux:button>
    </div>
    <flux:separator variant="subtle" class="mt-8" />
</div>
```

### 5.3 Search + Filter + Table Pattern

```blade
<div class="space-y-6">
    {{-- Search & Filter --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:input type="search" placeholder="{{ __('Cari...') }}" wire:model.live.debounce.300ms="search"
                    class="w-full sm:w-72" />
        <div class="flex gap-2">
            <flux:select wire:model.live="filterStatus" class="w-full sm:w-48">
                <flux:select.option value="">{{ __('Semua') }}</flux:select.option>
                {{-- options --}}
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
            {{-- ... --}}
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $data->links() }}
    </div>
</div>
```

### 5.4 Modal Form Pattern

```blade
<flux:modal name="form-modal" class="md:w-96">
    <div class="space-y-6">
        <flux:heading size="lg">{{ __('Form Title') }}</flux:heading>

        <div class="space-y-4">
            <flux:input label="Nama" wire:model="nama" />
            <flux:select label="Tipe" wire:model="tipe">
                <flux:select.option value="a">Option A</flux:select.option>
                <flux:select.option value="b">Option B</flux:select.option>
            </flux:select>
        </div>

        <div class="flex gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
            </flux:modal.close>
            <flux:spacer />
            <flux:button variant="primary" wire:click="save">{{ __('Simpan') }}</flux:button>
        </div>
    </div>
</flux:modal>
```

### 5.5 Stat Card Grid Pattern

```blade
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $total }}</p>
            </div>
            <flux:icon.users class="w-8 h-8 text-blue-500" />
        </div>
    </div>
    {{-- more stat cards --}}
</div>
```

---

## 6. Design Standard Enforcement

### 6.1 Code Review Checklist

- [ ] Menggunakan Flux component (bukan raw HTML)?
- [ ] Dark mode variant sudah diimplementasikan?
- [ ] Responsive (mobile + desktop)?
- [ ] Tidak ada inline `style=""`?
- [ ] Tidak ada custom CSS yang bisa diganti Tailwind/Flux?
- [ ] Warna menggunakan semantic tokens?
- [ ] Ikon menggunakan Lucide via Flux?
- [ ] Table padding `px-4 py-3`?
- [ ] Modal footer menggunakan `flex gap-2` + `flux:spacer`?
- [ ] Alert menggunakan pattern standar?

### 6.2 Violation Categories

| Level | Action | Example |
|-------|--------|---------|
| **Blocker** | Harus diperbaiki sebelum merge | Raw `<select>` instead of `flux:select` |
| **Warning** | Perlu diperbaiki | Missing dark mode class |
| **Info** | Improvement | Inconsistent padding |

### 6.3 Migration Rules

Saat migrasi dari desain lama ke desain baru:

1. **Green to Blue** — Primary buttons, links, accent elements
   - Old: `bg-emerald-500`, `text-emerald-600`
   - New: `variant="primary"` (Flux blue), `text-blue-600`
   
2. **Neutral Accent to Blue Accent**
   - Old: `--color-accent: var(--color-neutral-800)`
   - New: `--color-accent: var(--color-blue-600)`
   
3. **Green Badge → Blue or Semantic**
   - Old: Green badge for CAI events
   - New: Blue badge for CAI, emerald only for success/Pengajian

---

## 7. Cross-Reference Index

### 7.1 Decision Flow

```
Need a UI element?
    ↓
Is there a Flux component?
    ├── YES → Use Flux component
    └── NO
        ↓
Is there a custom Blade component?
    ├── YES → Use custom component
    └── NO
        ↓
Is the pattern used 3+ times?
    ├── YES → Create reusable component
    └── NO
        ↓
Use Tailwind utilities + standard pattern from COMPONENT_LIBRARY.md
```

### 7.2 Quick Reference

| Topic | Primary Document | Secondary |
|-------|-----------------|-----------|
| Brand logo usage | BRAND_GUIDELINES.md | COMPONENT_LIBRARY.md (app-logo) |
| Color tokens | COLOR_SYSTEM.md | BRAND_GUIDELINES.md (section 3) |
| Typography | TYPOGRAPHY.md | — |
| Icons | ICONOGRAPHY.md | COMPONENT_LIBRARY.md (flux:icon) |
| Buttons | COMPONENT_LIBRARY.md (section 2.4) | UI_PHILOSOPHY.md (section 4.1) |
| Forms | COMPONENT_LIBRARY.md (section 2.3) | — |
| Tables | COMPONENT_LIBRARY.md (section 4.2) | — |
| Modals | COMPONENT_LIBRARY.md (section 4.4) | — |
| Alerts | COMPONENT_LIBRARY.md (section 4.6) | COLOR_SYSTEM.md (section 9) |
| Badges | COMPONENT_LIBRARY.md (section 4.8) | COLOR_SYSTEM.md (section 6) |
| Layouts | PAGE_LAYOUTS.md | COMPONENT_LIBRARY.md (section 3.1) |
| Responsive | RESPONSIVE_GUIDE.md | PAGE_LAYOUTS.md (section 6) |
| Design principles | UI_PHILOSOPHY.md | — |
| Dark mode | COLOR_SYSTEM.md (section 8) | UI_PHILOSOPHY.md (section 3.2) |

---

## 8. Version & Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-07-25 | Initial design system — blue theme, universal branding, SaaS direction |
