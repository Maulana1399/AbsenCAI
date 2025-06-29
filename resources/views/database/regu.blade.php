<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('DATA REGU') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('DATA REGU') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex items-center justify-between mb-6">
        <livewire:database.regu.tambah-regu />
        <livewire:database.regu.import-regu />
    </div>
    @livewire('database.regu.data-regu')
    <livewire:database.regu.edit-regu />
    <livewire:database.regu.hapus-regu />

</x-layouts.app>