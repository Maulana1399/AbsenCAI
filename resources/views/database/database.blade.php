<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-8 w-full">
    <flux:heading size="xl" level="1">{{ __('DATABASE') }}</flux:heading>
    <flux:subheading size="lg" class="mb-8">{{ __('DATA PESERTA CAI 2025') }}</flux:subheading>
    <flux:separator variant="subtle" />
    </div>

<livewire:database.peserta.tambah-peserta />
@livewire('database.peserta.database')

</x-layouts.app>
