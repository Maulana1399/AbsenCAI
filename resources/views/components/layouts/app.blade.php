@livewireStyles
<script>
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>

<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
@livewireScripts
@stack('scripts')
