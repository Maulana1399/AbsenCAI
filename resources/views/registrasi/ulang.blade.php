<x-layouts.app :title="__('Registrasi Ulang')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('REGISTRASI ULANG') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Halaman sederhana untuk panitia saat hari acara') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <livewire:registrasi.ulang />
</x-layouts.app>
