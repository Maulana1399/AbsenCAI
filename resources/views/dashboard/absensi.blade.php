<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('ABSENSI') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('ABSENSI') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    <livewire:dashboard.scan />
</x-layouts.app>
