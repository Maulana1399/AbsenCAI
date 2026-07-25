# TYPOGRAPHY

> Type system for KJA Event Manager.

---

## 1. Font Selection

### 1.1 Primary Font: Instrument Sans

| Property | Value |
|----------|-------|
| Font Family | `'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'` |
| Weights Used | 400 (Normal), 500 (Medium), 600 (Semibold), 700 (Bold) |
| Type | Sans-serif, geometric humanist |
| Source | Google Fonts / self-hosted |
| Loading | Preloaded via `<link>` in layout head |
| License | Open Font License (OFL) |
| CSS Definition | `--font-sans` in `resources/css/app.css` @theme |

### 1.2 Monospace Font (Future)

| Property | Value |
|----------|-------|
| Font Family | `'JetBrains Mono', ui-monospace, monospace` |
| Usage | Code blocks, participant numbers (future) |
| Status | Not yet included — deferred |

---

## 2. Type Scale

### 2.1 Complete Scale

| Level | Tailwind Class | Size | Weight | Line Height | Letter Spacing |
|-------|---------------|------|--------|-------------|----------------|
| Display 1 | `text-4xl` | `2.25rem` / 36px | Bold (700) | `1.2` | `-0.02em` |
| Display 2 | `text-3xl` | `1.875rem` / 30px | Bold (700) | `1.2` | `-0.02em` |
| Heading 1 | `text-2xl` | `1.5rem` / 24px | Bold (700) | `1.3` | `-0.01em` |
| Heading 2 | `text-xl` | `1.25rem` / 20px | Semibold (600) | `1.4` | `0` |
| Heading 3 | `text-lg` | `1.125rem` / 18px | Semibold (600) | `1.4` | `0` |
| Body | `text-base` | `1rem` / 16px | Normal (400) | `1.5` | `0` |
| Body Small | `text-sm` | `0.875rem` / 14px | Normal (400) | `1.5` | `0` |
| Caption | `text-xs` | `0.75rem` / 12px | Medium (500) | `1.5` | `0.01em` |
| Overline | `text-xs` | `0.75rem` / 12px | Semibold (600) | `1.5` | `0.05em` |

### 2.2 Scale Rationale

- Base size `16px` (text-base) untuk body — optimal untuk readability
- Scale ratio ~1.25 (Major Third) — harmonious progression
- Line height lebih longgar untuk readability di layar
- Display sizes memiliki letter-spacing negatif untuk compact hero text

---

## 3. Typographic Hierarchy

### 3.1 Page Title

```blade
<flux:heading size="xl" level="1">{{ __('Page Title') }}</flux:heading>
```

- Class: `flux:heading size="xl" level="1"`
- Weight: Bold (700)
- Margin bottom: `mb-6` atau `mb-8`
- Digunakan di: setiap halaman utama setelah layout wrapper

### 3.2 Section Title

```blade
<flux:heading size="lg">{{ __('Section Title') }}</flux:heading>
<flux:subheading size="lg" class="mb-6">{{ __('Section description') }}</flux:subheading>
```

- Class: `flux:heading size="lg"` atau `text-xl font-semibold`
- Weight: Semibold (600)
- Digunakan di: dalam card, modal, group section

### 3.3 Card Title

```blade
<h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Card Title') }}</h2>
```

- Class: `text-lg font-semibold`
- Weight: Semibold (600)
- Digunakan di: stat card, navigation card, summary card

### 3.4 Body Text

```blade
<p class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Body text') }}</p>
```

- Class: `text-sm` (default) atau `text-base` (long form)
- Weight: Normal (400)
- Warna: `text-zinc-700` (light), `text-zinc-300` (dark)

### 3.5 Table Header

```blade
<thead class="text-xs uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
```

- Class: `text-xs uppercase tracking-wider`
- Weight: Semibold (600) implicit via flux or custom
- Letter spacing: `tracking-wider` (0.05em)
- Warna: `text-zinc-500` / `text-zinc-400`

### 3.6 Table Cell

```blade
<td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
```

- Class: `text-sm`
- Weight: Normal (400)
- Padding: `px-4 py-3` (standard)

### 3.7 Form Label

```blade
<flux:input label="Nama Lengkap" placeholder="Masukkan nama" />
```

- Class: handled by Flux — `text-sm font-medium`
- Weight: Medium (500)
- Posisi: di atas input field

### 3.8 Helper / Caption Text

```blade
<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Helper text') }}</p>
```

- Class: `text-xs`
- Weight: Medium (500)
- Warna: `text-zinc-500` / `text-zinc-400`

### 3.9 Code / Monospace

```blade
<code class="text-sm font-mono text-blue-600 dark:text-blue-400">{{ $code }}</code>
```

- Class: `text-sm font-mono`
- Weight: Normal (400)
- Warna: `text-blue-600` (menandai ini sebagai data teknis)
- Font family: monospace fallback (JetBrains Mono future)

---

## 4. Text Color Mapping

| Usage | Light | Dark |
|-------|-------|------|
| Page heading | `text-zinc-900` | `text-white` |
| Section title | `text-zinc-900` | `text-white` |
| Card title | `text-zinc-900` | `text-white` |
| Body text | `text-zinc-700` | `text-zinc-300` |
| Secondary text | `text-zinc-500` | `text-zinc-400` |
| Placeholder | `text-zinc-400` | `text-zinc-500` |
| Disabled | `text-zinc-300` | `text-zinc-600` |
| Link | `text-blue-600` | `text-blue-400` |
| Link hover | `text-blue-700` or `underline` | `text-blue-300` |
| Error message | `text-red-600` | `text-red-400` |
| Success message | `text-green-600` | `text-green-400` |
| On primary button | `text-white` | `text-white` |
| On danger button | `text-white` | `text-white` |

---

## 5. Implementation via Flux Components

### 5.1 Flux Heading Components

| Component | Maps To | Usage |
|-----------|---------|-------|
| `flux:heading size="xl" level="1"` | H1 | Page title |
| `flux:heading size="lg"` | H2 | Section title |
| `flux:heading` (default) | H3 | Subsection title |
| `flux:subheading size="lg"` | P | Page description |
| `flux:subheading` | P | Section description |
| `flux:text` | P | Paragraph body text |

### 5.2 Raw Classes (When Flux Unavailable)

Gunakan pattern berikut jika Flux heading component tidak sesuai:

```blade
{{-- H1 equivalent --}}
<h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Title') }}</h1>

{{-- H2 equivalent --}}
<h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Section') }}</h2>

{{-- H3 equivalent --}}
<h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Subsection') }}</h3>
```

---

## 6. Line Length (Measure)

| Context | Max Width | Class |
|---------|-----------|-------|
| Page content | 80ch (approximate) | `max-w-5xl` or `max-w-6xl` |
| Long form text | 70ch | `max-w-prose` or `max-w-3xl` |
| Card content | 60ch | Natural card bounds |
| Sidebar nav | n/a | Fixed width |
| Modal content | 50ch | `md:w-96` or `md:w-[32rem]` |

---

## 7. Responsive Typography

| Breakpoint | H1 Size | H2 Size | Body Size |
|------------|---------|---------|-----------|
| Default (mobile) | `text-xl` (1.25rem) | `text-lg` (1.125rem) | `text-sm` (0.875rem) |
| `md:` (768px) | `text-2xl` (1.5rem) | `text-xl` (1.25rem) | `text-sm` (0.875rem) |
| `lg:` (1024px) | `text-2xl` (1.5rem) | `text-xl` (1.25rem) | `text-base` (1rem) |

Implementasi:

```blade
<h1 class="text-xl font-bold md:text-2xl text-zinc-900 dark:text-white">
    {{ __('Page Title') }}
</h1>
```

---

## 8. Text Truncation

| Context | Pattern |
|---------|---------|
| Table cell (long text) | `class="max-w-[200px] truncate"` |
| Sidebar nav item | `class="truncate"` (Flux internal) |
| Card title (overflow) | `class="truncate"` |
| Badge (long label) | `class="max-w-[120px] truncate"` |

---

## 9. Internationalization

- All UI text in Bahasa Indonesia
- Use `__()` helper for translatable strings
- Number formatting: Indonesian locale (`1.234,56`)
- Date format: `d M Y` (e.g., `25 Jul 2026`)
- Time format: `H:i` (24-hour)

---

## 10. Do Not Use

- Jangan gunakan font weight `light` (300) atau `thin` (200) — readability
- Jangan gunakan justify alignment — gunakan left alignment
- Jangan gunakan `font-serif` — font system adalah sans-serif
- Jangan gunakan `italic` untuk body text — reserved for emphasis only
- Jangan gunakan `uppercase` untuk body text — reserved for table headers only
- Jangan gunakan line height di bawah `1.4` untuk body text
- Jangan gunakan ukuran font di bawah `12px` (text-xs) untuk UI
