<div>
<flux:modal.trigger name="tambah-regu">
    <flux:button>Tambah Regu</flux:button>
</flux:modal.trigger>

<flux:modal name="tambah-regu" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Regu</flux:heading>
        </div>

        <flux:input wire:model="regu" label="Nama Regu" placeholder="Misalnya: Grup Biru" />

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Kelamin</label>
            <select wire:model="jenis_kelamin" class="w-full px-3 py-2 border rounded">
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="Laki - Laki">Laki - Laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan' wire:loading.attr="disabled" wire:target="simpan">Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
