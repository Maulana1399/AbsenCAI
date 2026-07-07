<div>
    <flux:modal.trigger name="tambah-peserta">
    <flux:button class="bg-blue-500 text-white hover:bg-blue-600">Tambah Peserta</flux:button>
    </flux:modal.trigger>

<flux:modal name="tambah-peserta" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Peserta</flux:heading>
        </div>

        {{-- Input Nama Peserta --}}
        <flux:input wire:model="nama" label="Nama Peserta" placeholder="Masukkan nama peserta" />

        {{-- Input NIP Peserta --}}
        <flux:input wire:model="nip" label="NIP Peserta" readonly />

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-300">
            Regu otomatis: <span class="font-medium">{{ $regu_nama }}</span>
        </div>

        {{-- Input Jenis Kelamin --}}
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Kelamin</label>
            <select wire:model.live="jenis_kelamin" class="w-full px-3 py-2 border rounded">
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="Laki - Laki">Laki - Laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        {{-- Input Jenis Peserta --}}
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Peserta</label>
            <select wire:model="jenis_peserta" class="w-full px-3 py-2 border rounded">
                <option value="Wajib">Wajib</option>
                <option value="Kiriman">Kiriman</option>
                <option value="Person">Person</option>
            </select>
        </div>

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

        {{-- Dropdown Kelompok --}}
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Pilih Kelompok</label>
            <select wire:model="kelompok_id" class="w-full px-3 py-2 border rounded">
                <option value="">-- Pilih Kelompok --</option>
                @foreach($daftarKelompok as $kelompok)
                    <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary" wire:click='simpan' wire:loading.attr="disabled" wire:target="simpan">Simpan</flux:button>
        </div>
    </div>
</flux:modal>
</div>
