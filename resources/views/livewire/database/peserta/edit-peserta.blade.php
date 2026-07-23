<div>
<flux:modal name="edit-peserta" class="md:w-96">
    <div class="space-y-4">
        <flux:input wire:model="nama" label="Nama Peserta" placeholder="Masukkan nama peserta" />
        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Kelamin</label>
            <select wire:model="jenis_kelamin" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="Laki - Laki">Laki - Laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Peserta</label>
            <select wire:model="jenis_peserta" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="Wajib">Wajib</option>
                <option value="Kiriman">Kiriman</option>
                <option value="Person">Person</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Desa</label>
            <select wire:model="desa_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">-- Pilih Desa --</option>
                @foreach($daftarDesa as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Kelompok</label>
            <select wire:model="kelompok_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">-- Pilih Kelompok --</option>
                @foreach($daftarKelompok as $kelompok)
                    <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Regu</label>
            <select wire:model="regu_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">-- Pilih Regu --</option>
                @foreach($daftarRegu as $regu)
                    <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" wire:click='update' wire:loading.attr="disabled" wire:target="update">Update</flux:button>
        </div>
    </div>
</flux:modal>
</div>
