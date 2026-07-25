# COLOR SYSTEM

> Complete color architecture for KJA Event Manager.
> Reference for all UI color decisions — light mode and dark mode.

---

## 1. Architecture Overview

```
Brand Blue (Primary Identity)
    ├── Surface Colors (Neutral/Zinc)
    ├── Semantic Colors (Success, Warning, Danger, Info)
    ├── Accent Colors (per event type)
    └── Social / Misc (future)
```

### Guiding Principles

1. **Blue is primary** — semua elemen brand menggunakan blue
2. **Green is semantic** — green hanya untuk success state dan Pengajian badge
3. **Zinc is neutral** — semua surface, border, dan text menggunakan zinc palette
4. **Dark mode first** — dark mode adalah first-class citizen, bukan afterthought
5. **Accessibility** — semua kombinasi warna memenuhi WCAG AA contrast ratio

---

## 2. Brand Blue Palette

| Token | Hex Light | Hex Dark | Usage |
|-------|-----------|----------|-------|
| `blue-50` | `#EFF6FF` | — | Background ringan, highlight area |
| `blue-100` | `#DBEAFE` | — | Hover pada background ringan |
| `blue-200` | `#BFDBFE` | — | Selected state, subtle border |
| `blue-300` | `#93C5FD` | — | Border, icon ringan |
| `blue-400` | `#60A5FA` | `#60A5FA` | Icon, link, badge |
| `blue-500` | `#3B82F6` | `#3B82F6` | Primary button default |
| `blue-600` | `#2563EB` | `#2563EB` | Primary button hover, active state |
| `blue-700` | `#1D4ED8` | `#3B82F6` | Text link, solid badge |
| `blue-800` | `#1E40AF` | `#1D4ED8` | Dark mode accent element |
| `blue-900` | `#1E3A8A` | `#2563EB` | Deepest brand accent |
| `blue-950` | `#172554` | — | Dark mode background accent |

---

## 3. Neutral / Zinc Palette

### 3.1 Standard Zinc

| Token | Hex Light | Hex Dark | Usage |
|-------|-----------|----------|-------|
| `zinc-50` | `#FAFAFA` | — | Surface elevated, card bg light |
| `zinc-100` | `#F5F5F5` | — | Hover light, table header light |
| `zinc-200` | `#E5E5E5` | — | Border light, divider |
| `zinc-300` | `#D4D4D4` | — | Border muted, disabled |
| `zinc-400` | `#A3A3A3` | — | Placeholder text, muted icon |
| `zinc-500` | `#737373` | — | Secondary text |
| `zinc-600` | `#525252` | `#525252` | Disabled dark, muted dark text |
| `zinc-700` | `#404040` | `#404040` | Border dark |
| `zinc-800` | `#262626` | `#262626` | Surface dark, card bg dark |
| `zinc-900` | `#171717` | `#171717` | Surface elevated dark |
| `zinc-950` | `#0A0A0A` | `#0A0A0A` | Page background dark |

### 3.2 Surface Mapping

| Context | Light | Dark |
|---------|-------|------|
| Page background | `bg-white` | `bg-zinc-950` |
| Card / panel | `bg-white` | `bg-zinc-900` |
| Card elevated | `bg-zinc-50` | `bg-zinc-800` |
| Sidebar | `bg-white` | `bg-zinc-950` |
| Modal overlay | `bg-black/50` | `bg-black/70` |
| Table header | `bg-zinc-50` | `bg-zinc-800` |
| Table row hover | `bg-zinc-50` | `bg-zinc-800/50` |
| Input background | `bg-white` | `bg-zinc-900` |
| Disabled input | `bg-zinc-100` | `bg-zinc-800` |

---

## 4. Semantic Colors

### 4.1 Green / Success

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `green-50` | `#F0FDF4` | — | Alert background |
| `green-200` | `#BBF7D0` | `#22C55E` | Border (dark: solid) |
| `green-500` | `#22C55E` | `#22C55E` | Solid badge, icon |
| `green-600` | `#16A34A` | — | Text success light |
| `green-700` | `#15803D` | `#4ADE80` | Solid badge dark |
| `green-800` | — | `#166534` | Alert bg dark |
| `green-950` | — | `#052E16` | Dark alert bg |

### 4.2 Red / Danger

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `red-50` | `#FEF2F2` | — | Alert background |
| `red-200` | `#FECACA` | `#EF4444` | Border (dark: solid) |
| `red-500` | `#EF4444` | `#EF4444` | Solid badge, icon |
| `red-600` | `#DC2626` | — | Text danger light |
| `red-700` | `#B91C1C` | `#F87171` | Solid badge dark |
| `red-800` | — | `#991B1B` | Alert bg dark |
| `red-950` | — | `#450A0A` | Dark alert bg |

### 4.3 Amber / Warning

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `amber-50` | `#FFFBEB` | — | Alert background |
| `amber-200` | `#FDE68A` | `#FBBF24` | Border (dark: solid) |
| `amber-500` | `#F59E0B` | `#FBBF24` | Solid badge, icon |
| `amber-600` | `#D97706` | — | Text warning light |
| `amber-700` | `#B45309` | `#FCD34D` | Solid badge dark |
| `amber-800` | — | `#92400E` | Alert bg dark |
| `amber-950` | — | `#451A03` | Dark alert bg |

### 4.4 Blue / Info

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `blue-50` | `#EFF6FF` | — | Alert background |
| `blue-200` | `#BFDBFE` | `#60A5FA` | Border (dark: solid) |
| `blue-500` | `#3B82F6` | `#3B82F6` | Solid badge, icon |
| `blue-600` | `#2563EB` | — | Text info light |
| `blue-700` | `#1D4ED8` | `#93C5FD` | Solid badge dark |
| `blue-800` | — | `#1E40AF` | Alert bg dark |
| `blue-950` | — | `#172554` | Dark alert bg |

---

## 5. Event Type Accent Colors

Setiap event type memiliki warna aksen untuk badge dan identifier visual.

| Event Type | Badge Light | Badge Dark | Icon |
|------------|-------------|------------|------|
| CAI | `blue` | `blue` | `calendar-check` |
| Pengajian | `emerald` | `emerald` | `book-open` |
| Festival (future) | `violet` | `violet` | `sparkles` |
| Seminar (future) | `amber` | `amber` | `presentation` |
| Competition (future) | `rose` | `rose` | `trophy` |

### Badge Implementation

```blade
{{-- Standard Flux badge --}}
<flux:badge color="blue" size="sm">CAI</flux:badge>
<flux:badge color="emerald" size="sm">Pengajian</flux:badge>

{{-- Event type badge in table --}}
<flux:badge :color="$event->type === 'cai' ? 'blue' : 'emerald'" size="sm">
    {{ $event->type_label }}
</flux:badge>
```

---

## 6. State Colors

### 6.1 Attendance Status

| Status | Color | Badge Example |
|--------|-------|---------------|
| Hadir (Present) | `emerald` | `flux:badge color="emerald"` |
| Izin (Permitted) | `amber` | `flux:badge color="amber"` |
| Alfa (Absent) | `red` | `flux:badge color="red"` |
| Belum Hadir | `zinc` | `flux:badge color="zinc"` |

### 6.2 Event Status

| Status | Color | Badge Example |
|--------|-------|---------------|
| Active | `emerald` | `flux:badge color="emerald"` |
| Archived | `zinc` | `flux:badge color="zinc"` |
| Draft (future) | `amber` | `flux:badge color="amber"` |

### 6.3 User Role Badge

| Role | Color |
|------|-------|
| Super Admin | `red` |
| Admin | `blue` |
| Sekretariat | `violet` |
| Ketua Event | `amber` |
| PJ Divisi | `cyan` |
| Operator | `zinc` |
| Juri | `emerald` |
| Viewer | `gray` |

---

## 7. Text Colors

| Usage | Light | Dark |
|-------|-------|------|
| Primary heading | `text-zinc-900` | `text-white` |
| Body text | `text-zinc-700` | `text-zinc-300` |
| Secondary text | `text-zinc-500` | `text-zinc-400` |
| Muted / placeholder | `text-zinc-400` | `text-zinc-500` |
| Disabled | `text-zinc-300` | `text-zinc-600` |
| Link | `text-blue-600` | `text-blue-400` |
| Link hover | `text-blue-700` | `text-blue-300` |
| On primary (button) | `text-white` | `text-white` |
| On danger (button) | `text-white` | `text-white` |

---

## 8. Dark Mode Strategy

### 8.1 Principles

1. Dark mode adalah default untuk authenticated UI
2. Semua komponen harus memiliki dark variant
3. Gunakan shade yang lebih terang untuk semantic colors di dark mode
4. Surface hierarchy: `zinc-950` (bg) → `zinc-900` (card) → `zinc-800` (elevated)
5. Text hierarchy: `white` (primary) → `zinc-300` (body) → `zinc-400` (secondary)

### 8.2 Implementation

```blade
{{-- Card with dark mode --}}
<div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    {{-- content --}}
</div>

{{-- Text with dark mode --}}
<p class="text-zinc-700 dark:text-zinc-300">
    Body text
</p>

{{-- Button with dark mode --}}
<flux:button variant="primary">
    Simpan
</flux:button>
{{-- Flux handles dark mode internally --}}
```

### 8.3 Accent in Dark Mode

```css
/* Current implementation in app.css */
@layer theme {
    .dark {
        --color-accent: var(--color-white);
        --color-accent-content: var(--color-white);
        --color-accent-foreground: var(--color-neutral-800);
    }
}
```

For blue theme, update to:
```css
@layer theme {
    .dark {
        --color-accent: var(--color-blue-400);
        --color-accent-content: var(--color-blue-400);
        --color-accent-foreground: var(--color-blue-950);
    }
}
```

---

## 9. Alert Pattern

### 9.1 Semantic Alert Colors

| Type | Light Border | Light BG | Light Text | Dark Border | Dark BG | Dark Text |
|------|-------------|----------|------------|-------------|---------|-----------|
| Success | `green-200` | `green-50` | `green-800` | `green-800` | `green-950` | `green-200` |
| Error | `red-200` | `red-50` | `red-800` | `red-800` | `red-950` | `red-200` |
| Warning | `amber-200` | `amber-50` | `amber-800` | `amber-800` | `amber-950` | `amber-200` |
| Info | `blue-200` | `blue-50` | `blue-800` | `blue-800` | `blue-950` | `blue-200` |

### 9.2 Standard Alert Implementation

```blade
@if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 
                dark:border-green-800 dark:bg-green-950 dark:text-green-200">
        {{ session('success') }}
    </div>
@endif
```

---

## 10. Border & Divider Colors

| Context | Light | Dark |
|---------|-------|------|
| Card border | `border-zinc-200` | `border-zinc-700` |
| Table border | `border-zinc-200` | `border-zinc-700` |
| Divider | `border-zinc-200` | `border-zinc-800` |
| Input border | `border-zinc-300` | `border-zinc-600` |
| Input focus | `ring-blue-500` | `ring-blue-400` |
| Dashed (empty state) | `border-zinc-300` | `border-zinc-600` |

---

## 11. Custom Properties

### 11.1 Current app.css

```css
--color-accent: var(--color-neutral-800);
--color-accent-content: var(--color-neutral-800);
--color-accent-foreground: var(--color-white);
```

### 11.2 Proposed for Blue Theme

```css
@theme {
    --color-accent: var(--color-blue-600);
    --color-accent-content: var(--color-blue-600);
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

---

## 12. Accessibility Compliance

| Requirement | Standard | Status |
|-------------|----------|--------|
| Text on primary button | White on blue-600 (`#2563EB`) | ✅ 4.6:1 contrast |
| Text on surface | Zinc-900 on white | ✅ 15:1 contrast |
| Secondary text | Zinc-500 on white | ✅ 4.5:1 contrast |
| Link text in body | Blue-600 on white | ✅ 4.6:1 contrast |
| Disabled text | Zinc-300 on white | ⚠️ 3.0:1 (acceptable for disabled) |
| All dark mode combos | Adjusted shades | ✅ meets WCAG AA |

---

## 13. Color Migration from Green to Blue

### Current State (Before)
- `--color-accent: var(--color-neutral-800)` — neutral accent
- Primary buttons: `bg-blue-500` (inconsistent — some use blue, some use neutral)
- Success: green (`#16A34A`)
- CAI brand: implicitly neutral/green
- Pengajian brand: emerald green
- No unified brand blue

### Target State (After)
- `--color-accent: var(--color-blue-600)` — blue accent
- All primary buttons: `variant="primary"` (Flux blue)
- Brand elements: unified blue from `blue-50` to `blue-950`
- Success remains green — semantic, not brand
- Each event type gets its own accent (blue for CAI, emerald for Pengajian)

---

## 14. Do Not Use

| Color | Reason |
|-------|--------|
| `emerald` as primary brand | Green is reserved for Pengajian badge + success state only |
| `#10B981` (emerald-500) as button primary | Blue is the new primary |
| Green background on cards | Creates confusion with success state |
| Purple as primary | Reserved for future event types / roles |
| Pink / Rose | Reserved for future Competition event type |
| Raw hex without semantic mapping | Always use color tokens |
