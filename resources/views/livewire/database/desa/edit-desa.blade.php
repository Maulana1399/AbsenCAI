<div>
<flux:modal name="edit-desa" class="md:w-96">
    <div class="space-y-6">
        <flux:input wire:model="desa" label="Name Desa" placeholder="Masukkan nama desa" />

        <div class="flex">
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:spacer />
            <flux:button type="submit" variant="primary" wire:click='update' wire:loading.attr="disabled" wire:target="update">Update</flux:button>
        </div>
    </div>
</flux:modal>
</div>
