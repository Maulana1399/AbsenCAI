<div>
    <flux:modal.trigger name="tambah-sesi">
        <flux:button>Tambah Sesi</flux:button>
    </flux:modal.trigger>

    <flux:modal name="tambah-sesi" class="md:w-96">
        <flux:heading size="lg">Tambah Sesi</flux:heading>

        <flux:input wire:model="nama_sesi" label="Nama Sesi" placeholder="Misalnya: Sesi Pagi" />
        <flux:input wire:model="tanggal" type="date" label="Tanggal" />
        <div class="mt-4">
            <flux:checkbox wire:model="aktif">Aktifkan sesi ini</flux:checkbox>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <flux:button type="submit" variant="primary" wire:click.prevent="simpan" wire:loading.attr="disabled" wire:target="simpan">Simpan</flux:button>
        </div>
    </flux:modal>
</div>
