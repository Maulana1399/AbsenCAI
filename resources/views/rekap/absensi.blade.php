<x-layouts.app :title="__('Rekap Absensi')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">Rekap Absensi</flux:heading>
        <flux:subheading size="lg" class="mb-8">Rekap kehadiran peserta per sesi absensi</flux:subheading>
    </div>

    <div class="mb-6">
        <livewire:rekap.absensi.rekap-absensi />
    </div>

</x-layouts.app>
