<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('DATA KELOMPOK') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ __('DATA KELOMPOK') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>

<livewire:database.kelompok.tambah-kelompok />
@livewire('database.kelompok.data-kelompok')
<livewire:database.kelompok.edit-kelompok />
<livewire:database.kelompok.hapus-kelompok />


</x-layouts.app>
