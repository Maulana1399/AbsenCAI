<div>
<flux:modal name="edit-peserta" class="md:w-96">
    <div class="space-y-4">
        <flux:input wire:model="nama" label="Nama Peserta" placeholder="Masukkan nama peserta" />
        <flux:select wire:model="jenis_kelamin" label="Jenis Kelamin" placeholder="-- Pilih Jenis Kelamin --">
            <flux:select.option value="Laki - Laki">Laki - Laki</flux:select.option>
            <flux:select.option value="Perempuan">Perempuan</flux:select.option>
        </flux:select>

        <flux:select wire:model="jenis_peserta" label="Jenis Peserta" placeholder="-- Pilih Jenis Peserta --">
            <flux:select.option value="Wajib">Wajib</flux:select.option>
            <flux:select.option value="Kiriman">Kiriman</flux:select.option>
            <flux:select.option value="Person">Person</flux:select.option>
        </flux:select>

        <flux:select wire:model="desa_id" label="Pilih Desa" placeholder="-- Pilih Desa --">
            @foreach($daftarDesa as $desa)
                <flux:select.option value="{{ $desa->id }}">{{ $desa->desa_asal }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="kelompok_id" label="Pilih Kelompok" placeholder="-- Pilih Kelompok --">
            @foreach($daftarKelompok as $kelompok)
                <flux:select.option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="regu_id" label="Pilih Regu" placeholder="-- Pilih Regu --">
            @foreach($daftarRegu as $regu)
                <flux:select.option value="{{ $regu->id }}">{{ $regu->regu }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end pt-2">
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" wire:click='update' wire:loading.attr="disabled" wire:target="update">Update</flux:button>
        </div>
    </div>
</flux:modal>
</div>
