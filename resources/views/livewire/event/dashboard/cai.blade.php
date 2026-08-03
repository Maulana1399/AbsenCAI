@php \Carbon\Carbon::setLocale('id'); @endphp

{{-- Statistik Peserta & Absensi --}}
<div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta</div>
        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPeserta }}</div>
    </div>
    <div class="col-span-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900 md:col-span-1">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">Sesi Aktif</div>
        <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ $sesiAktif?->nama_sesi ?? 'Belum ada sesi aktif' }}</div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sesiAktif?->tanggal ?? '' }}</div>
        @if ($sesiAktif)
            <flux:modal.trigger name="ganti-sesi">
                <flux:button size="sm" variant="primary" class="mt-3">Ganti Sesi</flux:button>
            </flux:modal.trigger>
        @endif
    </div>
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">Hadir</div>
        <div class="mt-1 text-2xl font-bold text-green-600">{{ $sudahAbsenCount }}</div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">Izin</div>
        <div class="mt-1 text-2xl font-bold text-amber-600">{{ $izinCount }}</div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="text-sm text-zinc-500 dark:text-zinc-400">Belum Absen</div>
        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $belumAbsenCount }}</div>
    </div>
</div>

{{-- Peserta Belum Absen --}}
@if ($pesertaBelumAbsen->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Peserta Belum Absen</h3>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($pesertaBelumAbsen as $p)
                <div class="px-4 py-2.5 text-sm text-zinc-900 dark:text-white">{{ $p->person?->nama ?? '-' }}</div>
            @endforeach
        </div>
    </div>
@endif

{{-- Modal Ganti Sesi --}}
@if ($sesiAktif)
    <flux:modal name="ganti-sesi" class="w-full max-w-md">
        <div class="space-y-4 p-4">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Pilih Sesi Aktif</h3>
            <div class="space-y-2">
                @foreach ($daftarSesi as $sesi)
                    <button
                        type="button"
                        wire:click="activateSesi({{ $sesi->id }})"
                        class="flex w-full items-center justify-between rounded-lg border px-4 py-3 text-left transition
                               {{ $sesi->aktif ? 'border-blue-400 bg-blue-50 dark:border-blue-700 dark:bg-blue-950' : 'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800' }}"
                    >
                        <div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $sesi->nama_sesi }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sesi->tanggal }}</div>
                        </div>
                        @if ($sesi->aktif)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-200">Aktif</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </flux:modal>
@endif
