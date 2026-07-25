# COMPONENT LIBRARY

> Complete component catalog for KJA Event Manager.
> Reference untuk AI coding — semua komponen yang tersedia dan pattern penggunaannya.

---

## 1. Component Architecture

### 1.1 Layer Stack

```
Flux UI Pro v2 Components (primary)
    └── Tailwind CSS v4 Utilities
        └── Blade Components (custom reusable)
            └── Livewire Components (page/feature specific)
```

### 1.2 Selection Priority

1. **Flux UI component** — jika tersedia, gunakan Flux
2. **Custom Blade component** — jika sudah ada di project
3. **Tailwind utility classes** — untuk layout dan spacing
4. **Custom CSS** — hanya jika benar-benar diperlukan

---

## 2. Flux UI Components (Available)

### 2.1 Layout Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:sidebar` | Main app sidebar | Sticky, stashable |
| `flux:sidebar.group` | Sidebar section group | Collapsible groups |
| `flux:sidebar.toggle` | Mobile sidebar toggle | |
| `flux:header` | Mobile header | Visible on mobile only |
| `flux:navbar` / `flux:navbar.item` | Top navigation bar | |
| `flux:separator` | Section divider | Use `variant="subtle"` |

### 2.2 Navigation Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:navlist` | Navigation list container | |
| `flux:navlist.item` | Navigation item | Icon + label + href |
| `flux:navlist.group` | Navigation group with heading | |
| `flux:breadcrumbs` | Breadcrumb navigation | |
| `flux:breadcrumbs.item` | Individual breadcrumb | |

### 2.3 Form Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:input` | Text input with label | type="text", "email", "date", "number" |
| `flux:input.group` | Input with prefix/suffix | |
| `flux:select` | Dropdown select | Use instead of raw `<select>` |
| `flux:select.option` | Select option | |
| `flux:checkbox` | Checkbox | |
| `flux:checkbox.group` | Checkbox group | |
| `flux:radio` | Radio button | |
| `flux:radio.group` | Radio group | |
| `flux:textarea` | Textarea | |
| `flux:switch` | Toggle switch | |
| `flux:field` | Form field wrapper | For custom field layouts |

### 2.4 Action Components

| Component | Variants | Usage |
|-----------|----------|-------|
| `flux:button` | `primary`, `danger`, `ghost`, (default) | Tombol aksi |
| `flux:button` size | `sm`, `md` (default), `lg` | Ukuran tombol |
| `flux:button` grouped | `flux:button.group` | Button group |
| `flux:dropdown` | — | Dropdown menu container |
| `flux:menu` | — | Menu items within dropdown |
| `flux:menu.item` | — | Individual menu item |

### 2.5 Display Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:heading` | Heading text | `size="xl"`, `size="lg"`, default |
| `flux:subheading` | Subheading / description | |
| `flux:text` | Paragraph body text | |
| `flux:badge` | Status / label badge | `color="emerald\|red\|amber\|blue\|zinc\|violet"` |
| `flux:badge` size | `sm`, `md` (default) | |
| `flux:icon.*` | Lucide icons | `flux:icon.user`, `flux:icon.bell`, etc. |

### 2.6 Feedback Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:modal` | Modal dialog | `name="..."`, `class="md:w-96"` |
| `flux:modal.trigger` | Open modal button | Wraps trigger element |
| `flux:modal.close` | Close modal button | Wraps close element |
| `flux:toast` | Toast notification (future) | |
| `flux:tooltip` | Hover tooltip | |
| `flux:spinner` | Loading spinner | |

### 2.7 Data Components

| Component | Usage | Notes |
|-----------|-------|-------|
| `flux:table` | Data table (future) | Currently using custom table |
| `flux:pagination` | Pagination | Currently using Laravel default |
| `flux:profile` | User profile avatar | |
| `flux:avatar` | Avatar component | |

### 2.8 Utility Components

| Component | Usage |
|-----------|-------|
| `flux:spacer` | Flex spacer |
| `flux:separator` | Horizontal divider |
| `flux:container` | Content container |

---

## 3. Custom Blade Components (Available)

### 3.1 Layout Components

| Component | Path | Usage |
|-----------|------|-------|
| `x-layouts.app` | `components/layouts/app.blade.php` | Main app layout with sidebar |
| `x-layouts.auth.card` | `components/layouts/auth/card.blade.php` | Auth centered card |
| `x-layouts.auth.simple` | `components/layouts/auth/simple.blade.php` | Auth simple form |
| `x-layouts.auth.split` | `components/layouts/auth/split.blade.php` | Auth split screen |
| `x-layouts.pengajian` | `components/layouts/pengajian.blade.php` | Pengajian minimal layout |
| `x-settings.layout` | `components/settings/layout.blade.php` | Settings page layout |

### 3.2 Brand Components

| Component | Path | Usage |
|-----------|------|-------|
| `x-app-logo` | `components/app-logo.blade.php` | Full brand logo |
| `x-app-logo-icon` | `components/app-logo-icon.blade.php` | Brand icon only |
| `x-auth-header` | `components/auth-header.blade.php` | Auth page branding header |
| `x-footer` | `components/footer.blade.php` | Footer with copyright |

### 3.3 Feedback Components

| Component | Path | Usage |
|-----------|------|-------|
| `x-action-message` | `components/action-message.blade.php` | Flash message display |
| `x-auth-session-status` | `components/auth-session-status.blade.php` | Auth session status |
| `x-placeholder-pattern` | `components/placeholder-pattern.blade.php` | SVG placeholder background |

---

## 4. UI Patterns (Standardized)

### 4.1 Page Container Pattern

```blade
<x-layouts.app :title="__('Page Title')">
    {{-- Page Header --}}
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('PAGE TITLE') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Deskripsi halaman') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Page Content --}}
    <div class="space-y-6">
        {{-- content --}}
    </div>
</x-layouts.app>
```

### 4.2 Data Table Pattern

```blade
<div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
        <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Nama') }}</th>
                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Aksi') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            {{-- rows --}}
        </tbody>
    </table>
</div>
```

Table cell padding standard: `px-4 py-3` (header dan cell sama).
Exception: Dashboard dense table menggunakan `px-4 py-2`.

### 4.3 Form Pattern

```blade
<div class="space-y-6">
    <flux:input label="Nama Lengkap" placeholder="Masukkan nama" wire:model="nama" />

    <flux:select label="Jenis Kelamin" wire:model="jenis_kelamin" placeholder="Pilih jenis kelamin...">
        <flux:select.option value="L">Laki-laki</flux:select.option>
        <flux:select.option value="P">Perempuan</flux:select.option>
    </flux:select>

    @error('nama')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    <div class="flex gap-2">
        <flux:spacer />
        <flux:button variant="primary" wire:click="save">{{ __('Simpan') }}</flux:button>
    </div>
</div>
```

### 4.4 Modal Pattern

```blade
<flux:modal name="nama-modal" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Modal Title') }}</flux:heading>
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

### 4.5 Confirmation Modal Pattern

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

### 4.6 Alert Pattern

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

### 4.7 Empty State Pattern

Table empty:
```blade
<tr>
    <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Belum ada data.') }}
    </td>
</tr>
```

Card empty:
```blade
<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
    <flux:icon.inbox class="w-12 h-12 mb-4 text-zinc-300 dark:text-zinc-600" />
    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tidak ada data.') }}</p>
</div>
```

### 4.8 Badge Pattern

```blade
{{-- Flux badge (preferred) --}}
<flux:badge color="emerald" size="sm">{{ __('Hadir') }}</flux:badge>
<flux:badge color="amber" size="sm">{{ __('Pending') }}</flux:badge>
<flux:badge color="red" size="sm">{{ __('Ditolak') }}</flux:badge>
<flux:badge color="blue" size="sm">{{ __('CAI') }}</flux:badge>
<flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>

{{-- Custom badge (if Flux badge insufficient) --}}
<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
    {{ __('Label') }}
</span>
```

---

## 5. Button Variants

| Variant | Usage | Visual |
|---------|-------|--------|
| `primary` | Save, Create, Submit, Approve | Blue filled |
| default (no variant) | Edit, generic action | Zinc outline |
| `danger` | Delete, Reject, Archive | Red filled |
| `ghost` | Cancel, Close, in modal | Transparent |
| `primary` + `size="sm"` | Table action, inline action | Small blue |
| default + `size="sm"` | Table edit, secondary action | Small outline |

---

## 6. Card Patterns

### 6.1 Stat Card

```blade
<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Peserta') }}</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $total }}</p>
        </div>
        <flux:icon.users class="w-8 h-8 text-blue-500" />
    </div>
</div>
```

### 6.2 Navigation Card

```blade
<a href="{{ route('person.index') }}" 
   class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 transition hover:bg-zinc-50 hover:border-blue-200 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800 dark:hover:border-blue-600">
    <flux:icon.user class="w-8 h-8 text-blue-500 shrink-0" />
    <div>
        <p class="font-semibold text-zinc-900 dark:text-white">{{ __('Person') }}</p>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kelola data person') }}</p>
    </div>
</a>
```

### 6.3 Info / Warning Card

```blade
<div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
    <div class="flex items-start gap-3">
        <flux:icon.alert-triangle class="w-5 h-5 mt-0.5 shrink-0" />
        <div>
            <p class="font-medium">{{ __('Perhatian') }}</p>
            <p>{{ __('Deskripsi warning.') }}</p>
        </div>
    </div>
</div>
```

---

## 7. Search / Filter Pattern

```blade
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <flux:input type="search" placeholder="{{ __('Cari...') }}" wire:model.live.debounce.300ms="search"
                class="w-full sm:w-72" />
    
    <flux:select wire:model.live="filterStatus" class="w-full sm:w-48">
        <flux:select.option value="">{{ __('Semua Status') }}</flux:select.option>
        <flux:select.option value="hadir">{{ __('Hadir') }}</flux:select.option>
        <flux:select.option value="izin">{{ __('Izin') }}</flux:select.option>
    </flux:select>
</div>
```

---

## 8. Pagination

```blade
<div class="mt-4">
    {{ $data->links() }}
</div>
```

Pagination menggunakan Laravel default yang sudah di-tailwind-kan via `@source` di app.css.

---

## 9. Future Components (Not Yet Implemented)

| Component | Priority | Notes |
|-----------|----------|-------|
| File picker / upload | Medium | Duplicate pattern in imports |
| Dashboard charts | Medium | For workspace dashboard |
| Timeline / schedule | Low | For competition module |
| Bracket display | Low | For competition module |
| Scoreboard / leaderboard | Low | For scoring module |
| Calendar widget | Low | For event scheduling |
| Toast notification | Medium | Flux Pro may have this |
| Skeleton loading | Low | For dashboard blocks |
| Print template engine | Low | Existing in QR/print |
| Certificate template | Low | Future module |

---

## 10. Component Creation Rules

1. **Reusable component hanya jika pattern digunakan 3+ kali**
2. **Jangan buat komponen jika Flux sudah menyediakan**
3. **Letakkan Blade component di `resources/views/components/`**
4. **Gunakan komposisi, bukan inheritance**
5. **Setiap komponen harus mendukung dark mode**
6. **Setiap komponen harus responsive**
7. **Jangan buat abstraksi berlebihan**
8. **Dokumentasikan komponen baru di file ini**

---

## 11. Do Not Use

- Jangan gunakan raw `<button>` dengan custom styling — gunakan `flux:button`
- Jangan gunakan raw `<select>` — gunakan `flux:select`
- Jangan gunakan raw `<table>` tanpa wrapper yang benar
- Jangan gunakan inline `style=""` attributes
- Jangan gunakan `bg-blue-500 text-white hover:bg-blue-600` — sudah ada Flux variant
- Jangan gunakan custom CSS class untuk komponen yang sudah ada di Flux
- Jangan buat multiple pattern untuk hal yang sama
