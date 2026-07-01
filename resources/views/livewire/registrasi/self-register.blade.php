<div class="space-y-6">
    <div class="text-center space-y-2">
        <flux:heading size="xl">Registrasi Peserta</flux:heading>
        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Isi data di bawah untuk registrasi awal.
        </flux:text>
    </div>

    <form wire:submit="register" class="flex flex-col gap-4">
        <flux:input
            wire:model="nama"
            label="Nama Lengkap"
            type="text"
            required
            autofocus
            placeholder="Masukkan nama lengkap"
        />

        <flux:input
            wire:model="nip"
            label="NIP"
            type="number"
            required
            placeholder="Masukkan NIP"
        />

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Desa</label>
            <select wire:model="desa_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">-- Pilih Desa --</option>
                @foreach ($daftarDesa as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                @endforeach
            </select>
            @error('desa_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Kelompok</label>
            <select wire:model="kelompok_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">-- Pilih Kelompok --</option>
                @foreach ($daftarKelompok as $kelompok)
                    <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                @endforeach
            </select>
            @error('kelompok_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div class="pt-2">
            <flux:button type="submit" variant="primary" class="w-full">Daftar Sekarang</flux:button>
        </div>
    </form>
</div>
