<flux:modal name="hapus-sesi" class="min-w-[22rem]">
    <flux:heading size="lg">Hapus Sesi ?</flux:heading>

    <div class="mt-4">
        <p>Anda akan menghapus sesi: <strong>{{ $sesi_nama }}</strong></p>
    </div>

    <div class="flex gap-2">
        <flux:modal.close>
            <flux:button variant="ghost">Batal</flux:button>
        </flux:modal.close>
        <flux:spacer />
        <flux:button variant="danger" wire:click.prevent="delete">Hapus</flux:button>
    </div>
</flux:modal>
