<div>
    <flux:modal name="ganti-peserta" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">
                    Ganti Peserta CAI
                </flux:heading>

                <flux:subheading>
                    Mengganti orang pada slot peserta CAI tanpa mengubah identitas slot.
                </flux:subheading>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Peserta Lama</div>
                        <div class="font-medium">{{ $nama_lama }}</div>
                    </div>

                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">No. Peserta</div>
                        <div class="font-medium">{{ $participant_number }}</div>
                    </div>

                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Desa</div>
                        <div class="font-medium">{{ $desa }}</div>
                    </div>

                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Kelompok</div>
                        <div class="font-medium">{{ $kelompok }}</div>
                    </div>

                    <div>
                        <div class="text-zinc-500 dark:text-zinc-400">Regu</div>
                        <div class="font-medium">{{ $regu }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                Nomor peserta, kode absensi, Desa, Kelompok, dan Regu tetap mengikuti slot peserta lama.
            </div>

            <flux:input
                wire:model="nama"
                label="Nama Peserta Pengganti"
                placeholder="Masukkan nama peserta pengganti"
            />

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Jenis Kelamin
                </label>

                <select
                    wire:model="jenis_kelamin"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                >
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki - Laki">Laki - Laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>

                @error('jenis_kelamin')
                    <div class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <flux:input
                wire:model="tanggal_lahir"
                type="date"
                label="Tanggal Lahir"
            />

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Alasan Penggantian
                </label>

                <textarea
                    wire:model="reason"
                    rows="3"
                    placeholder="Contoh: Peserta lama berhalangan hadir"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                ></textarea>

                @error('reason')
                    <div class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="primary"
                    wire:click="replace"
                    wire:loading.attr="disabled"
                    wire:target="replace"
                >
                    <span wire:loading.remove wire:target="replace">
                        Ganti Peserta
                    </span>

                    <span wire:loading wire:target="replace">
                        Memproses...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="ganti-peserta-error" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">
                Peserta Tidak Dapat Diganti
            </flux:heading>

            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ $errorMessage }}
            </div>

            <div class="flex justify-end">
                <flux:button
                    wire:click="$dispatch('close-modal', { name: 'ganti-peserta-error' })"
                >
                    Tutup
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>