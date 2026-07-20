<div class="flex flex-col gap-6">
    <div class="text-center mb-2">
        <div class="mx-auto h-20 w-20 rounded-full bg-emerald-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
            KJA
        </div>

        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">
            Dashboard Desa
        </h1>

        <p class="text-emerald-600 text-lg mt-1">
            {{ $eventName }}
        </p>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            Informasi Akses
        </h2>

        <div class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Desa</span>
                <span class="font-semibold text-zinc-900 dark:text-white">{{ $desaName }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                    Aktif
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Berlaku Dari</span>
                <span class="text-zinc-900 dark:text-white">{{ $validFrom }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Berlaku Sampai</span>
                <span class="text-zinc-900 dark:text-white">{{ $validUntil }}</span>
            </div>
        </div>
    </div>

    {{-- QR Section --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            QR Akses
        </h2>

        <div class="mt-4 flex flex-col items-center gap-3">
            @if ($qrBase64)
                <img src="data:image/png;base64,{{ $qrBase64 }}"
                     alt="QR Absen"
                     class="h-56 w-56">
            @else
                <div class="flex h-56 w-56 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <p class="text-xs text-zinc-400">QR tidak tersedia</p>
                </div>
            @endif

            <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center">
                Scan QR untuk melakukan absensi mandiri
            </p>

            <div class="flex gap-2">
                <flux:button
                    wire:click="refreshNonce"
                    variant="ghost"
                    size="sm"
                    :loading="$processing"
                >
                    Segarkan QR
                </flux:button>

                <flux:button
                    onclick="window.open('{{ route('pengajian.qr-print', absolute: false) }}', 'print', 'width=600,height=800')"
                    variant="ghost"
                    size="sm"
                >
                    Cetak QR
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Public URL --}}
    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-900/50">
        <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
            URL Absensi
        </h3>
        @if ($qrUrl)
            <p class="mt-2 text-xs text-zinc-400 break-all font-mono">
                {{ url($qrUrl) }}
            </p>
        @endif
    </div>

    <div class="flex flex-col gap-3">
        <button
            type="button"
            wire:click="logout"
            wire:loading.attr="disabled"
            class="w-full rounded-xl border border-red-300 px-4 py-3 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950"
        >
            Keluar dari Dashboard Desa
        </button>
    </div>
</div>
