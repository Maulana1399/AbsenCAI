# ICONOGRAPHY

> Icon system for KJA Event Manager.

---

## 1. Icon Library

### 1.1 Primary: Lucide Icons (via Flux)

| Detail | Value |
|--------|-------|
| Library | Lucide Icons |
| Access | Via `flux:icon.*` Blade components |
| Bundle | Bundled with Livewire Flux UI Pro |
| Format | Inline SVG |
| License | ISC License |
| Sizing | Via Flux props or Tailwind classes |
| Color | Inherits current text color by default |

### 1.2 Custom Icons

| Detail | Value |
|--------|-------|
| Location | `resources/views/flux/icon/` |
| Format | Blade SVG stub files |
| Naming | `{name}.blade.php` — lowercase, hyphenated |
| Auto-discovery | Flux scans the icon stub directory |
| Usage | `flux:icon.{name}` after stub is created |
| Use Case | Brand icon, event-type icons not in Lucide |

### 1.3 Third-Party / Inline SVG

Hanya digunakan jika:
- Ikon tidak tersedia di Lucide atau custom stub
- Diperlukan untuk QR code rendering (baconqrcode)
- Diperlukan untuk ilustrasi empty state (future)

---

## 2. Icon Naming Convention

### 2.1 Flux / Lucide

Use Lucide icon names directly via Flux:

```blade
<flux:icon.user class="w-5 h-5" />
<flux:icon.bell class="w-5 h-5" />
<flux:icon.settings class="w-5 h-5" />
```

### 2.2 Custom Stubs

Custom icons in `resources/views/flux/icon/`:

```blade
{{-- resources/views/flux/icon/kja-logo.blade.php --}}
@php declare(strict_types=1); @endphp
<svg ...>{{-- SVG content --}}</svg>
```

Usage:
```blade
<flux:icon.kja-logo class="w-8 h-8" />
```

---

## 3. Icon Sizing

| Context | Size Class | Icon Size |
|---------|-----------|-----------|
| Sidebar nav item | `w-5 h-5` | 20px |
| Button (with text) | `w-4 h-4` or `w-5 h-5` | 16-20px |
| Icon only button | `w-5 h-5` | 20px |
| Table action icon | `w-4 h-4` | 16px |
| Empty state | `w-12 h-12` to `w-16 h-16` | 48-64px |
| Stat card icon | `w-8 h-8` | 32px |
| Alert icon | `w-5 h-5` | 20px |
| Form input icon | `w-4 h-4` | 16px |
| Badge / tag icon | `w-3 h-3` | 12px |
| Loading spinner | `w-5 h-5` | 20px |
| Page / section header | `w-6 h-6` | 24px |
| Mobile header | `w-6 h-6` | 24px |
| User avatar | `w-8 h-8` | 32px |

---

## 4. Icon Color

### 4.1 Default Behavior

Icons inherit `currentColor` from parent text color:

```blade
{{-- Icon will be white --}}
<flux:button variant="primary">
    <flux:icon.plus class="w-4 h-4" />
    {{ __('Tambah') }}
</flux:button>

{{-- Icon will be zinc-500 --}}
<div class="text-zinc-500">
    <flux:icon.search class="w-4 h-4" />
</div>
```

### 4.2 Semantic Color Mapping

| Context | Color Class |
|---------|-------------|
| Normal / default | `text-zinc-500` or inherited |
| Active / hover | `text-blue-600` |
| Disabled | `text-zinc-300` |
| Success | `text-green-500` |
| Error | `text-red-500` |
| Warning | `text-amber-500` |
| Brand accent | `text-blue-600` |
| On primary button | `text-white` (inherited) |

---

## 5. Master Icon Inventory

### 5.1 Navigation Icons

| UI Element | Lucide Icon | Custom Stub |
|------------|-------------|-------------|
| Dashboard / Workspace | `layout-dashboard` | — |
| Absensi (Attendance) | `clipboard-check` | — |
| Registrasi | `user-plus` | — |
| Database / Peserta | `users` | — |
| Laporan (Reports) | `file-text` | — |
| QR & Label | `qr-code` | — |
| Event | `calendar` | — |
| Sekretariat | `folder-closed` | — |
| Master Data | `database` | — |
| User Management | `user-cog` | — |
| Settings | `settings` | — |
| Pengajian | — | `book-open` (Lucide) |
| Activity Log | `history` | — |
| Surat Izin | `file-pen-line` | — |

### 5.2 Action Icons

| Action | Lucide Icon |
|--------|-------------|
| Tambah (Add) | `plus` |
| Edit | `pencil` |
| Hapus (Delete) | `trash-2` |
| Simpan (Save) | `save` |
| Batal (Cancel) | `x` |
| Cari (Search) | `search` |
| Filter | `filter` |
| Export / Download | `download` |
| Upload | `upload` |
| Print | `printer` |
| Copy | `copy` |
| Refresh / Reload | `refresh-cw` |
| Close | `x` |
| Back | `arrow-left` |
| Next | `arrow-right` |
| Submit | `send` |
| Approve | `check` |
| Reject | `x-circle` |
| Return | `undo-2` |
| Archive | `archive` |
| Activate | `play` |
| More / Menu | `ellipsis-vertical` |
| View / Show | `eye` |
| Hide | `eye-off` |
| Sort | `arrow-up-down` |

### 5.3 Status Icons

| Status | Lucide Icon |
|--------|-------------|
| Success | `check-circle` |
| Error | `alert-circle` |
| Warning | `alert-triangle` |
| Info | `info` |
| Question | `help-circle` |
| Loading | `loader-circle` |
| Empty | `inbox` |

### 5.4 Attendance Icons

| Status | Lucide Icon |
|--------|-------------|
| Hadir (Present) | `check-circle` |
| Izin (Permitted) | `clock` |
| Alfa (Absent) | `x-circle` |
| Scan QR | `scan` |
| Manual Entry | `pen-line` |

### 5.5 Event Type Icons

| Event Type | Lucide Icon |
|------------|-------------|
| CAI | `calendar-check` |
| Pengajian | `book-open` |
| Generic Event | `calendar` |
| Competition (future) | `trophy` |
| Seminar (future) | `presentation` |
| Festival (future) | `sparkles` |

### 5.6 Person / Participant Icons

| Context | Lucide Icon |
|---------|-------------|
| Person | `user` |
| Group / Kelompok | `users` |
| Desa / Location | `map-pin` |
| Gender Male | `mars` |
| Gender Female | `venus` |
| Role / Badge | `badge-check` |
| Participant Number | `hash` |

---

## 6. Icon in Sidebar Navigation

```blade
<flux:navlist.item icon="layout-dashboard" wire:navigate href="{{ route('dashboard') }}">
    {{ __('Dashboard') }}
</flux:navlist.item>
```

Flux `navlist.item` handles icon sizing and active state automatically.

---

## 7. Icon Usage Rules

1. **Ikon harus memiliki arti** — jangan gunakan ikon dekoratif tanpa makna
2. **Ikon + label** — selalu sertakan label teks (kecuali icon-only button yang sudah dikenal)
3. **Icon-only button harus memiliki tooltip** untuk aksesibilitas
4. **Jangan mencampur ikon dari library berbeda** — gunakan Lucide saja
5. **Jangan override ikon Flux internal** — biarkan Flux mengelola ikon komponennya
6. **Custom stub hanya jika diperlukan** — prioritaskan Lucide
7. **Ukuran ikon konsisten per konteks** — lihat tabel sizing di atas
8. **Ikon pada tombol** diletakkan di kiri teks (sebelum teks)
9. **Ikon pada action table** diletakkan di kanan (setelah teks) atau tanpa teks

---

## 8. Empty State Icons

| Empty State | Icon | Size |
|-------------|------|------|
| No data in table | `inbox` | `w-12 h-12` |
| No search results | `search-x` | `w-12 h-12` |
| No attendance | `clipboard-x` | `w-12 h-12` |
| No registration | `user-x` | `w-12 h-12` |
| No event selected | `calendar-x` | `w-12 h-12` |

### Empty State Pattern

```blade
<div class="flex flex-col items-center justify-center py-12 text-center">
    <flux:icon.inbox class="w-12 h-12 mb-4 text-zinc-300 dark:text-zinc-600" />
    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Belum ada data.') }}
    </p>
</div>
```

---

## 9. Loading / Spinner

```blade
{{-- Loading state for button --}}
<flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
    <flux:icon.loader-circle class="w-4 h-4 animate-spin" wire:loading />
    <span wire:loading.remove>{{ __('Simpan') }}</span>
    <span wire:loading>{{ __('Menyimpan...') }}</span>
</flux:button>

{{-- Loading spinner standalone --}}
<flux:icon.loader-circle class="w-5 h-5 animate-spin text-blue-600" />
```

---

## 10. Custom Icon Creation

### 10.1 When to Create Custom Icons

- Brand logo / app icon
- Event type icon not available in Lucide
- Illustration elements for empty states (future)
- Diagram / chart elements (future)

### 10.2 Custom Icon Template

```blade
{{-- resources/views/flux/icon/custom-name.blade.php --}}
{{-- Icon: custom-name --}}
{{-- Source: [source URL or author] --}}
@php declare(strict_types=1); @endphp
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    {{ $attributes }}
>
    {{-- SVG paths --}}
</svg>
```

---

## 11. Do Not Use

- Jangan gunakan ikon dari Font Awesome, Material Icons, atau library lain
- Jangan gunakan ikon berwarna-warni (multi-color) — ikon harus single-color via stroke
- Jangan gunakan ikon animated selain loading spinner
- Jangan gunakan emoji sebagai pengganti ikon UI
- Jangan gunakan `<img>` tags untuk ikon — selalu gunakan SVG inline atau Flux component
- Jangan gunakan `stroke-width` yang berbeda dalam satu halaman — konsisten `stroke-width="2"`
