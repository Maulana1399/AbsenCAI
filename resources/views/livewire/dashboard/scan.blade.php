<div>
    <button wire:click="restartScan" type="button" class="mb-4 px-4 py-2 bg-blue-500 text-white rounded">
        Scan Lagi
    </button>
    <h2 class="text-xl font-bold mb-4">Scan QR Absensi</h2>
    <div id="qr-reader" style="width:300px"></div>
    <div class="mt-4">
        <div>Nama: <span class="font-bold">{{ $nama }}</span></div>
        <div>NIP: <span class="font-bold">{{ $nip }}</span></div>
        <div>Jam Scan: <span class="font-bold">{{ $jam_scan }}</span></div>
        @if($message)
            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let qrInstance = null;

    function startQrScanner() {
        // Hapus elemen scanner lama jika ada
        const qrDiv = document.getElementById('qr-reader');
        if (qrDiv) {
            qrDiv.innerHTML = '';
        }
        if (qrInstance) {
            try {
                qrInstance.clear();
            } catch (e) {}
            qrInstance = null;
        }
        initScanner();
    }

    function initScanner() {
        if (!document.getElementById('qr-reader')) return;
        qrInstance = new Html5Qrcode("qr-reader");
        qrInstance.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            qrCodeMessage => {
                Livewire.find(@this.__instance.id).scanPeserta(qrCodeMessage);
                qrInstance.stop().then(() => qrInstance.clear());
            }
        ).catch(err => {
            // Optional: tampilkan pesan error ke user
            console.error('Camera error:', err);
        });
    }

    document.addEventListener('livewire:load', startQrScanner);

    Livewire.on('restartScanner', () => {
        startQrScanner();
    });
</script>
@endpush
