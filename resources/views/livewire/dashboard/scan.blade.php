<div class="min-h-screen w-full overflow-x-hidden bg-zinc-50 px-4 py-6 dark:bg-zinc-900 sm:min-h-0 sm:rounded-2xl sm:px-6 lg:bg-transparent lg:p-0 lg:dark:bg-transparent">
    <div class="mx-auto flex w-full max-w-md flex-col items-center gap-6">
        <h2 class="w-full text-center text-2xl font-bold text-zinc-900 dark:text-white">
            Scan QR Absensi
        </h2>

        <div class="flex w-full justify-center" wire:ignore>
            <div class="w-[400px] max-w-full overflow-hidden rounded-2xl">
                <div id="qr-reader"></div>
            </div>
        </div>

        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
            <div class="space-y-3 text-sm text-zinc-700 dark:text-zinc-300">
                <div class="flex justify-between gap-4">
                    <span class="font-medium">Nama:</span>
                    <span class="font-bold">{{ $nama }}</span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="font-medium">NIP:</span>
                    <span class="font-bold">{{ $nip }}</span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="font-medium">Jam Scan:</span>
                    <span class="font-bold">{{ $jam_scan }}</span>
                </div>
            </div>

            @if($message)
                <div class="mt-4 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $message }}
                </div>
            @endif
        </div>

        <button wire:click="restartScan"
            type="button"
            class="w-full rounded-xl bg-blue-500 px-4 py-3 font-medium text-white shadow-sm hover:bg-blue-600">
            Scan Lagi
        </button>
    </div>
</div>


@push('scripts')

<script src="https://unpkg.com/html5-qrcode"></script>

<script>

let qrInstance = null;
let scannerRunning = false;


function fixScannerUI(){

    setTimeout(() => {

        const video = document.querySelector('#qr-reader video');

        if(video){
            video.style.width = "100%";
            video.style.height = "300px";
            video.style.objectFit = "cover";
            video.style.borderRadius = "16px";
        }


        const dashboard = document.querySelector('#qr-reader__dashboard');

        if(dashboard){
            dashboard.style.display="none";
        }

    },300);
}



async function startQrScanner(){

    if(scannerRunning){
        return;
    }


    const qrDiv = document.getElementById('qr-reader');

    if(!qrDiv) return;


    qrDiv.innerHTML="";


    qrInstance = new Html5Qrcode("qr-reader");


    try {

        scannerRunning=true;


        await qrInstance.start(

            {
                facingMode:"environment"
            },

            {
                fps:10,
                qrbox:{
                    width:250,
                    height:250
                }
            },

            async qrCodeMessage => {


                await qrInstance.stop();
                await qrInstance.clear();


                scannerRunning=false;


                Livewire.find(@this.__instance.id)
                    .scanPeserta(qrCodeMessage);

            }

        );


        fixScannerUI();


    } catch(error){

        scannerRunning=false;

        console.error(error);

    }

}



window.addEventListener('load', () => {

    setTimeout(() => {
        startQrScanner();
    },1000);

});

document.addEventListener('livewire:navigated', () => {

    setTimeout(() => {
        startQrScanner();
    },300);

});



Livewire.on('restartScanner', async ()=>{


    if(qrInstance && scannerRunning){

        await qrInstance.stop();

        await qrInstance.clear();

        scannerRunning=false;

    }


    startQrScanner();

});

</script>


<!-- <style>

#qr-reader {
    width:400px !important;
    max-width:100% !important;
    overflow:hidden !important;
    border:none !important;
}


#qr-reader video {
    width:400px !important;
    max-width:100% !important;
    height:300px !important;
    object-fit:cover !important;
    border-radius:16px;
}


#qr-reader__dashboard {
    display:none !important;
}

</style> -->

@endpush