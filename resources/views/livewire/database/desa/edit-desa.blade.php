<div>
    <flux:modal.trigger name="edit-desa">
    <flux:button>Edit Desa</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-desa" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Edit Desa</flux:heading>
        </div>

        <flux:input wire:model="Desa" label="Name Desa" placeholder="Masukkan nama desa" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan'>Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
