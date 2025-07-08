<div>
<flux:modal.trigger name="tambah-desa">
    <flux:button>Tambah Desa</flux:button>
</flux:modal.trigger>

<flux:modal name="tambah-desa" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Desa</flux:heading>
        </div>

        <flux:input wire:model="Desa" label="Name Desa" placeholder="Masukkan nama desa" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan'>Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
