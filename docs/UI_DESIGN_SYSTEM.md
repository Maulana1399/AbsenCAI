# UI Design System

> Actual standard project — berdasarkan audit codebase.
> Dokumen ini mencatat standard yang SUDAH digunakan, BUKAN target ideal.

## Framework UI

| Layer | Teknologi | Keterangan |
|-------|-----------|------------|
| CSS Framework | **Tailwind CSS v4** | Styling utama via utility classes |
| Component Library | **Livewire Flux UI Pro v2** | Komponen UI: button, modal, input, select, badge, sidebar, navlist, dropdown, menu, heading, subheading, separator, dll |
| Icons | **Lucide Icons** via Flux | Custom icon stubs di `resources/views/flux/icon/` |
| Frontend Build | **Vite** + `@tailwindcss/vite` | |
| JS | **Vanilla JS** + Livewire | ZXing untuk QR scan saja |

### Styling Priority

1. Flux UI components
2. Tailwind utility classes
3. Blade components (layouts, partials)
4. Custom CSS (hanya jika benar-benar diperlukan)

## Layout Convention

### Layout Utama: `components/layouts/app.blade.php`

```
flux:sidebar (sticky, stashable)
  ├── app-logo
  ├── livewire:event-switcher
  ├── flux:navlist (navigasi)
  ├── flux:spacer
  └── flux:dropdown (user menu)
flux:header (mobile only)
{{ $slot }}
```

digunakan via: `<x-layouts.app :title="...">`

### Layout Auth

- `components/layouts/auth/card.blade.php` — card terpusat
- `components/layouts/auth/simple.blade.php` — form sederhana
- `components/layouts/auth/split.blade.php` — split screen (branding + form)

### Layout Settings

`components/settings/layout.blade.php` — sidebar nav + content area.
digunakan via: `<x-settings.layout :heading="..." :subheading="...">`

### Page Container Pattern

```blade
<x-layouts.app :title="__('Page Title')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('PAGE TITLE') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Deskripsi halaman') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    {{-- content --}}
</x-layouts.app>
```

## Component Patterns

### Heading

| Level | Pattern | Contoh |
|-------|---------|--------|
| Page title | `flux:heading size="xl" level="1"` | Halaman master data, user, database |
| Section title | `flux:heading size="lg"` | Dalam modal, settings |
| Card title | `h2` with custom classes | Pengajian pages |
| Livewire component heading | varies | Inconsistent — lihat catatan |

### Button

| Variant | Pattern | Penggunaan |
|---------|---------|------------|
| Primary | `flux:button variant="primary"` | Simpan, Tambah, Submit |
| Default | `flux:button` (no variant) | Edit, Batal, generic |
| Danger | `flux:button variant="danger"` | Hapus, Arsipkan |
| Ghost | `flux:button variant="ghost"` | Batal (dalam modal), Tutup |
| Size sm | `flux:button size="sm"` | Tabel aksi, tombol kecil |
| Loading | `wire:loading.attr="disabled" wire:target="..."` | Indikasi loading |

JANGAN gunakan custom styling seperti `bg-blue-500 text-white hover:bg-blue-600` — sudah ada kasus di scan dan tambah-peserta.

### Form & Input

| Elemen | Pattern | Keterangan |
|--------|---------|------------|
| Text input | `flux:input label="..." placeholder="..."` | Standard Flux |
| Select | `flux:select wire:model="..." label="..."` | Standard Flux |
| Textarea | raw `<textarea>` with Tailwind classes | Belum ada Flux wrapper |
| Date | `flux:input type="date" label="..."` | Standard Flux |
| Error | `@error('field') <p class="mt-1 text-sm text-red-600">...` | Standard pattern |

JANGAN gunakan raw `<select>` dengan custom Tailwind jika `flux:select` sudah mencukupi.
Kasus raw select ditemukan di: TambahPeserta, EditPeserta, GantiPeserta, SuratIzin, QR Label, Event form.

### Table

Standard table wrapper:

```blade
<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Nama') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                ...
            </tbody>
        </table>
    </div>
</div>
```

**Padding standard: `px-4 py-3`** (header) dan **`px-4 py-3`** (cell).

CATATAN: Database (Desa, Kelompok, Regu, Sesi, Peserta) masih menggunakan `px-6 py-3` / `px-6 py-2`.
Ini perlu diseragamkan ke `px-4 py-3`.

### Modal

Standard pattern:

```blade
<flux:modal name="nama-modal" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Title') }}</flux:heading>
        </div>

        {{-- form fields --}}

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

Trigger button:
```blade
<flux:modal.trigger name="nama-modal">
    <flux:button variant="primary">{{ __('Tambah') }}</flux:button>
</flux:modal.trigger>
```

### Confirmation Modal

```blade
<flux:modal name="hapus-data" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Hapus Data?') }}</flux:heading>
            <flux:text class="mt-2">
                <p>Yakin ingin menghapus <strong>{{ $nama }}</strong>?</p>
                <p>Tidak dapat diurungkan.</p>
            </flux:text>
        </div>
        @if ($blockReason)
            <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                {!! $blockReason !!}
            </div>
        @endif
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" wire:click="destroy">{{ __('Hapus') }}</flux:button>
        </div>
    </div>
</flux:modal>
```

### Badge / Status

Standard menggunakan `flux:badge`:

```blade
<flux:badge color="emerald" size="sm">Active</flux:badge>
<flux:badge color="red" size="sm">Danger</flux:badge>
<flux:badge color="zinc" size="sm">Inactive</flux:badge>
```

Jika Flux badge tidak mencukupi, gunakan pattern custom:

```blade
<span class="inline-flex items-center rounded-full bg-{{color}}-100 px-2.5 py-0.5 text-xs font-medium text-{{color}}-800 dark:bg-{{color}}-900/30 dark:text-{{color}}-300">
    Label
</span>
```

Warna yang digunakan: emerald (hadir/active), red (danger/rejected), amber (pending/warning), blue (info/CAI), zinc (default/inactive), yellow (pending), green (approved), purple (keluar/pengajian), violet (kelompok).

### Flash Messages / Alert

Standard:

```blade
@if (session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
        {{ session('error') }}
    </div>
@endif
```

### Empty State

Standard untuk tabel:

```blade
<tr>
    <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Belum ada data.') }}
    </td>
</tr>
```

Standard untuk card/daftar:

```blade
<div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
    <p class="text-sm text-zinc-400">{{ __('Tidak ada data.') }}</p>
</div>
```

### Pagination

`{{ $data->links() }}` — menggunakan default Laravel pagination yang sudah di-tailwind-kan via `@source` di app.css.

## Spacing Convention

| Konteks | Spacing |
|---------|---------|
| Page content wrapper | `space-y-6` |
| Card padding | `p-4`, `p-5`, `p-6` |
| Modal content | `space-y-6` |
| Form fields | `space-y-4` atau `space-y-6` |
| Table cell padding | `px-4 py-3` (standard) |
| Section separator | `flux:separator variant="subtle"` |
| Between sections | `mb-6` atau `mb-8` |

## Typography

| Elemen | Class |
|--------|-------|
| Font family | `Instrument Sans` (via CSS @theme) |
| Page heading | `flux:heading size="xl" level="1"` |
| Section heading | `flux:heading size="lg"` |
| Subheading | `flux:subheading size="lg"` |
| Table header | `text-xs uppercase` |
| Table cell | `text-sm` |
| Form label | `text-sm font-medium` |

## Responsive Convention

| Breakpoint | Target |
|------------|--------|
| Default (mobile) | Single column, stacked |
| `sm:` (640px) | Tablet kecil, horizontal layout mulai |
| `md:` (768px) | Tablet, grid 2 kolom |
| `lg:` (1024px) | Desktop, sidebar visible, 3+ kolom |
| `xl:` (1280px) | Desktop lebar |

Sidebar: hidden di mobile (`lg:hidden`), visible di desktop. Mobile menggunakan `flux:header` dengan toggle.

## Dark/Light Mode

- Menggunakan class `dark` pada `<html>` (via Flux appearance)
- Semua warna menggunakan skema `dark:` variant Tailwind
- Background: `bg-white dark:bg-zinc-800` / `bg-zinc-50 dark:bg-zinc-900` / `bg-zinc-950 dark:bg-zinc-950`
- Border: `border-zinc-200 dark:border-zinc-700` / `border-zinc-800`
- Text: `text-zinc-900 dark:text-white` / `text-zinc-500 dark:text-zinc-400`
- Custom accent via CSS custom properties (`--color-accent`)

## Custom CSS Rules

1. **Hanya jika diperlukan** — Gunakan Tailwind/Flux terlebih dahulu
2. **Letakkan di `resources/css/app.css`** — jangan di inline `<style>` atau file terpisah
3. **Untuk library eksternal** (QR scanner, dll) — justified
4. **Jangan gunakan `!important`** — kecuali untuk override library CSS
5. **Jangan duplikasi** — cek app.css sebelum menambah CSS baru

## Reusable Components

### Blade Components (available)

| Component | Path | Fungsi |
|-----------|------|--------|
| `x-layouts.app` | `components/layouts/app.blade.php` | Layout utama dengan sidebar |
| `x-layouts.auth` | `components/layouts/auth/*.blade.php` | Layout auth (card/simple/split) |
| `x-layouts.pengajian` | `components/layouts/pengajian.blade.php` | Layout Pengajian (minimal) |
| `x-settings.layout` | `components/settings/layout.blade.php` | Layout settings sidebar |
| `x-app-logo` | `components/app-logo.blade.php` | Logo + brand text |
| `x-app-logo-icon` | `components/app-logo-icon.blade.php` | Logo icon SVG |
| `x-action-message` | `components/action-message.blade.php` | Flash action message |
| `x-auth-header` | `components/auth-header.blade.php` | Auth page header |
| `x-auth-session-status` | `components/auth-session-status.blade.php` | Auth session status |
| `x-footer` | `components/footer.blade.php` | Footer copyright |
| `x-placeholder-pattern` | `components/placeholder-pattern.blade.php` | SVG placeholder pattern |

### Flux Components (primary)

| Component | Penggunaan |
|-----------|------------|
| `flux:sidebar` | Layout sidebar |
| `flux:header` | Mobile header |
| `flux:navlist` / `flux:navlist.item` / `flux:navlist.group` | Navigasi sidebar |
| `flux:button` | Tombol (default, primary, danger, ghost) |
| `flux:input` | Input field dengan label |
| `flux:select` / `flux:select.option` | Dropdown select |
| `flux:modal` / `flux:modal.trigger` / `flux:modal.close` | Modal dialog |
| `flux:badge` | Status badge |
| `flux:heading` / `flux:subheading` | Heading typography |
| `flux:separator` | Divider line |
| `flux:dropdown` / `flux:menu` | Dropdown menu |
| `flux:profile` | User profile avatar |
| `flux:spacer` | Spacer flex |
| `flux:icon.*` | Icons (Lucide) |
| `flux:radio.group` / `flux:radio` | Radio button group |
| `flux:tooltip` | Tooltip |
| `flux:navbar` / `flux:navbar.item` | Navbar (header) |

## Inconsistencies Known (Perlu Diperbaiki)

1. **Table padding**: Database (Desa, Kelompok, Regu, Sesi, Peserta) = `px-6 py-3`/`px-6 py-2`; Master Data, User, Event, Surat Izin = `px-4 py-3`
2. **Select pattern**: Mixed between `flux:select` and raw `<select>` with custom classes
3. **Button custom styling**: Scan page, Tambah Peserta trigger, Surat Izin actions, QR Label buttons
4. **Heading pattern**: Some use `flux:heading`, some use raw `<h1>`/`<h2>` with custom classes
5. **Alert messages**: Multiple variations of border-radius (`rounded-lg` vs `rounded-xl`), padding, color classes
6. **Empty state**: Some use simple text, some use dashed border container
7. **Badge pattern**: Mixed between `flux:badge` and custom `<span>` with `rounded-full`
8. **Pengajian module**: Distinct design language (emerald, centered, card-based, custom tabs)
9. **Dashboard page**: Custom stat cards (not using Flux card components)
10. **QR Scanner styles**: Duplicated in app.css AND commented out in scan.blade.php AND set via JavaScript

## Engineering Rules

> Gunakan Tailwind/Flux dan reusable project components sebagai default.
> Custom CSS hanya boleh ditambahkan jika kebutuhan tidak dapat dipenuhi secara wajar oleh framework atau komponen existing.
> Selalu periksa pattern yang sudah ada sebelum membuat komponen baru.
> Jangan membuat abstraksi berlebihan — buat reusable component hanya jika pattern digunakan berulang (3+ kali).
