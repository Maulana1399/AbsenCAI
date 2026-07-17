<x-layouts.app :title="__('Surat Izin')">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('SURAT IZIN') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Kelola surat izin peserta') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    @livewire('surat-izin.index')
</x-layouts.app>
