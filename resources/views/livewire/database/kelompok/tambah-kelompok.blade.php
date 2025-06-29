<div>
<flux:modal.trigger name="tambah-kelompok">
    <flux:button>Tambah Kelompok</flux:button>
</flux:modal.trigger>

<flux:modal name="tambah-kelompok" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Kelompok</flux:heading>
        </div>

        <flux:input wire:model="Kelompok" label="Name Kelompok" placeholder="Masukkan nama kelompok" />
        
        {{-- Dropdown Desa --}}
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Pilih Desa</label>
            <select wire:model="desa_id" class="w-full px-3 py-2 border rounded">
                <option value="">-- Pilih Desa --</option>
                @foreach($daftarDesa as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan'>Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
