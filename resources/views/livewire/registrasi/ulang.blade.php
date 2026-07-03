<div class="space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">Registrasi Ulang</flux:heading>
        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Cari peserta berdasarkan nama atau NIP.
        </flux:text>
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Cari nama atau NIP"
            icon="magnifying-glass"
        />
    </div>

    <div class="grid gap-4">
        @forelse ($daftarPeserta as $peserta)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="space-y-2">
                    <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ $peserta->nama }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">NIP: {{ $peserta->nip }}</div>
                </div>

                <div class="mt-4 grid gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <div><span class="font-medium">Desa:</span> {{ $peserta->desa->desa_asal ?? '-' }}</div>
                    <div><span class="font-medium">Kelompok:</span> {{ $peserta->kelompok->kelompok_asal ?? '-' }}</div>
                    <div><span class="font-medium">Regu:</span> {{ $peserta->regu->regu ?? '-' }}</div>
                    <div><span class="font-medium">Status Registrasi:</span> {{ $peserta->status_registrasi_label }}</div>
                </div>

                <div class="mt-5">
                    <flux:button type="button" variant="primary" class="w-full" wire:click="registrasiUlang({{ $peserta->id }})" wire:loading.attr="disabled" wire:target="registrasiUlang">
                        Registrasi Ulang
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                {{ $search ? 'Peserta tidak ditemukan.' : 'Mulai ketik nama atau NIP untuk mencari peserta.' }}
            </div>
        @endforelse
    </div>
</div>
