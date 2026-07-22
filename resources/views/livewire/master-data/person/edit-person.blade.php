<div>
<flux:modal name="edit-person" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Edit Person</flux:heading>
            @if ($hasLegacyMapping)
                <flux:badge color="emerald" inset="top" class="mt-2">Terhubung dengan data peserta legacy</flux:badge>
            @endif
        </div>

        <flux:input wire:model="nama" label="Nama" placeholder="Masukkan nama lengkap" />

        <flux:select wire:model="jenis_kelamin" label="Jenis Kelamin" placeholder="Pilih jenis kelamin">
            <flux:select.option value="L">Laki - Laki</flux:select.option>
            <flux:select.option value="P">Perempuan</flux:select.option>
        </flux:select>

        <flux:input wire:model="tanggal_lahir" type="date" label="Tanggal Lahir" />

        <flux:select wire:model="desa_id" label="Desa" placeholder="Pilih desa">
            @foreach ($daftarDesa as $desa)
                <flux:select.option value="{{ $desa->id }}">{{ $desa->desa_asal }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="kelompok_id" label="Kelompok" placeholder="Pilih kelompok">
            @foreach ($daftarKelompok as $kel)
                <flux:select.option value="{{ $kel->id }}">{{ $kel->kelompok_asal }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="nip"
            type="number"
            label="NIP"
            placeholder="Opsional"
            :disabled="$nipLocked"
        />
        @if ($nipLocked)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">NIP tidak dapat diubah karena Person terhubung dengan data legacy.</p>
        @endif

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
