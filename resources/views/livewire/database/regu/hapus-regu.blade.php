<div>
<flux:modal name="hapus-regu" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Hapus Regu ?</flux:heading>

            <flux:text class="mt-2">
                <p>Apakah kamu yakin ingin menghapus Regu ini.</p>
                <p>Tidak dapat di ctrl Z.</p>
            </flux:text>
        </div>

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="danger" wire:click="destroy">Hapus regu</flux:button>
        </div>
    </div>
</flux:modal>
</div>
