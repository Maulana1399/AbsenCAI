<div>
    <h2 class="text-xl font-bold mb-4">Scan QR Absensi</h2>
    <div id="qr-reader" style="width:300px"></div>
    <div class="mt-4">
        <div>Nama: <span class="font-bold">{{ $nama }}</span></div>
        <div>NIP: <span class="font-bold">{{ $nip }}</span></div>
        <div>Jam Scan: <span class="font-bold">{{ $jam_scan }}</span></div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    function startQrScanner() {
        if (window.qrScannerStarted) return;
        window.qrScannerStarted = true;
        let qr = new Html5Qrcode("qr-reader");
        qr.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            qrCodeMessage => {
                Livewire.find(@this.__instance.id).scanPeserta(qrCodeMessage);
                qr.stop();
                window.qrScannerStarted = false; // allow restart after scan
            }
        );
    }

    document.addEventListener('livewire:load', startQrScanner);
    document.addEventListener("livewire:navigated", startQrScanner);
    window.addEventListener('livewire:message.processed', startQrScanner);
</script>
@endpush
