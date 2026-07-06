<x-layouts.app :title="__('Dashboard')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">Sesi Absensi</flux:heading>
        <flux:subheading size="lg" class="mb-8">Kelola sesi absensi</flux:subheading>
    </div>

    <div class="flex items-center justify-between mb-6">
        <livewire:database.sesi.tambah-sesi />
    </div>

    <livewire:database.sesi.data-sesi />
    <livewire:database.sesi.edit-sesi />
    <livewire:database.sesi.hapus-sesi />
</x-layouts.app>
