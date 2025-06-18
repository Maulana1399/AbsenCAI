<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('DATA KELOMPOK') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ __('DATA KELOMPOK') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>

<livewire:tambah-kelompok />
@livewire('data-kelompok')


</x-layouts.app>
