<x-layouts.app :title="__('Registrasi Peserta')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">{{ __('REGISTRASI PESERTA') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Fokus untuk proses registrasi peserta saat acara') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex items-center justify-between mb-6">
        <livewire:database.peserta.tambah-peserta />
        <livewire:database.peserta.import-peserta />
    </div>

    <livewire:database.peserta.database />
    <livewire:database.peserta.edit-peserta />
    <livewire:database.peserta.hapus-peserta />
</x-layouts.app>
