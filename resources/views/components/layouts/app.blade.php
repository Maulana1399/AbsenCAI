@livewireStyles
<script>
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>

<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main @class(['!p-0 sm:!p-6 lg:!p-8' => request()->routeIs('absensi')])>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
@livewireScripts
@stack('scripts')
