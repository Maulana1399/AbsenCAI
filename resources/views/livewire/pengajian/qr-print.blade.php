<div class="flex flex-col items-center gap-6 py-4 print-section">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            PENGAJIAN DESA
        </h1>
        <p class="text-emerald-600 text-lg mt-1">{{ $desaName }}</p>
    </div>

    @if ($qrBase64)
        <div class="qr-container">
            <img src="data:image/png;base64,{{ $qrBase64 }}"
                 alt="QR Absen"
                 class="h-72 w-72">
        </div>
    @endif

    <div class="text-center">
        <p class="text-base text-zinc-600 dark:text-zinc-400">
            Scan QR untuk melakukan absensi
        </p>
        <p class="text-sm text-zinc-400 mt-1">{{ $eventName }}</p>
    </div>

    <div class="flex gap-3 mt-4 print-hidden">
        <flux:button onclick="window.print()" variant="primary">
            Cetak
        </flux:button>
        <flux:button onclick="window.close()" variant="ghost">
            Tutup
        </flux:button>
    </div>
</div>

<style>
    @media print {
        .print-hidden { display: none !important; }
        .print-section { padding: 0 !important; }
        body { background: white !important; }
    }
</style>
