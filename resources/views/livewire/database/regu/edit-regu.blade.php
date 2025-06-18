<div>
{{-- <flux:modal.trigger name="edit-regu">
    <flux:button>Edit Regu</flux:button>
</flux:modal.trigger> --}}

<flux:modal name="edit-regu" class="md:w-96">
    <div class="space-y-6">
        <flux:input wire:model="regu" label="Name Regu" placeholder="Masukkan nama regu" />

        <div class="flex">
            <flux:button type="submit" variant="primary" wire:click='update'>Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
