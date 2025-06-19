<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('DATA DESA') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ __('DATA DESA') }}</flux:subheading>
    <flux:separator variant="subtle" />
    </div>
    
    <livewire:database.desa.tambah-desa />
    @livewire('database.desa.data-desa')
    <livewire:database.desa.edit-desa />
    <livewire:database.desa.hapus-desa />
    
</x-layouts.app>
