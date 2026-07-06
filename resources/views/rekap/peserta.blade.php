<x-layouts.app :title="__('Rekap Peserta')">
    <div class="relative mb-8 w-full">
        <flux:heading size="xl" level="1">Rekap Peserta</flux:heading>
        <flux:subheading size="lg" class="mb-8">Filter dan pantau peserta per Regu/Kelompok/Desa</flux:subheading>
    </div>

    <div class="mb-6">
        <livewire:rekap.peserta.rekap-peserta />
    </div>

</x-layouts.app>
