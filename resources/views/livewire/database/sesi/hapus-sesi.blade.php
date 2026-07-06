<flux:modal name="hapus-sesi" class="min-w-[22rem]">
    <flux:heading size="lg">Hapus Sesi ?</flux:heading>

    <div class="mt-4">
        <p>Anda akan menghapus sesi: <strong>{{ $sesi_nama }}</strong></p>
    </div>

    <div class="mt-4 flex justify-end gap-2">
        <flux:button variant="danger" wire:click.prevent="delete">Hapus</flux:button>
    </div>
</flux:modal>
