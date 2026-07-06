<flux:modal name="edit-sesi" class="md:w-96">
    <flux:heading size="lg">Edit Sesi</flux:heading>

    <flux:input wire:model="nama_sesi" label="Nama Sesi" placeholder="Misalnya: Sesi Pagi" />
    <flux:input wire:model="tanggal" type="date" label="Tanggal" />
    <div class="mt-4">
        <flux:checkbox wire:model="aktif">Aktifkan sesi ini</flux:checkbox>
    </div>

    <div class="mt-4 flex justify-end gap-2">
        <flux:button wire:click.prevent="update">Update</flux:button>
    </div>
</flux:modal>
