<div>
<flux:modal.trigger name="tambah-regu">
    <flux:button>Tambah Regu</flux:button>
</flux:modal.trigger>

<flux:modal name="tambah-regu" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Regu</flux:heading>
        </div>

        <flux:input wire:model="regu" label="Name Regu" placeholder="Masukkan nama regu" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan'>Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
