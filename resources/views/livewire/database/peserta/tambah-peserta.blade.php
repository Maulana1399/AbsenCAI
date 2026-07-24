<div>
    <flux:modal.trigger name="tambah-peserta">
    <flux:button variant="primary">Tambah Peserta</flux:button>
    </flux:modal.trigger>

<flux:modal name="tambah-peserta" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Tambah Peserta</flux:heading>
        </div>

        @if ($errorMessage)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="flex gap-2">
            <button type="button" wire:click="switchMode('baru')"
                class="flex-1 rounded-xl px-3 py-2 text-sm font-medium transition
                    {{ $mode === 'baru'
                        ? 'bg-blue-500 text-white'
                        : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                Tambah Orang Baru
            </button>
            <button type="button" wire:click="switchMode('existing')"
                class="flex-1 rounded-xl px-3 py-2 text-sm font-medium transition
                    {{ $mode === 'existing'
                        ? 'bg-blue-500 text-white'
                        : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                Tambahkan Peserta yang Sudah Ada
            </button>
        </div>

        @if ($mode === 'baru')
            <flux:input wire:model="nama" label="Nama Peserta" placeholder="Masukkan nama peserta" />

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-300">
                Regu otomatis: <span class="font-medium">{{ $regu_nama }}</span>
            </div>

            <flux:select wire:model.live="jenis_kelamin" label="Jenis Kelamin" placeholder="-- Pilih Jenis Kelamin --">
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

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click='simpan' wire:loading.attr="disabled" wire:target="simpan">Simpan</flux:button>
            </div>
        @else
            <flux:input wire:model.live="searchPerson" label="Cari Peserta" placeholder="Cari berdasarkan nama..." />

            @if (!empty($searchResults))
                <div class="max-h-60 space-y-2 overflow-y-auto">
                    @foreach($searchResults as $result)
                        <button type="button" wire:click="selectPerson({{ $result['id'] }})"
                            class="w-full rounded-xl border border-zinc-200 p-3 text-left transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $result['nama'] }}</div>
                            <div class="mt-0.5 text-sm text-zinc-500">
                                @if($result['desa']) {{ $result['desa'] }} @endif
                                @if($result['kelompok']) | {{ $result['kelompok'] }} @endif
                                @if($result['regu']) | {{ $result['regu'] }} @endif
                            </div>
                            <div class="mt-0.5 text-xs text-zinc-400">
                                {{ $result['jenis_kelamin'] }}
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($selectedPerson)
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <div class="text-sm font-medium text-green-800 dark:text-green-200">Peserta Dipilih</div>
                    <div class="mt-1 text-sm text-green-700 dark:text-green-300">
                        <div class="font-semibold">{{ $selectedPerson['nama'] }}</div>
                        <div>
                            {{ $selectedPerson['desa'] ?? '-' }},
                            {{ $selectedPerson['kelompok'] ?? '-' }},
                            {{ $selectedPerson['regu'] ?? '-' }}
                        </div>
                        <div>{{ $selectedPerson['jenis_kelamin'] }}</div>
                    </div>
                    <button type="button" wire:click="switchMode('existing')"
                        class="mt-2 text-xs text-green-600 underline hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                        Ganti pilihan
                    </button>
                </div>

                <flux:select wire:model="existingJenisPeserta" label="Jenis Peserta untuk Event Ini">
                    <flux:select.option value="Wajib">Wajib</flux:select.option>
                    <flux:select.option value="Kiriman">Kiriman</flux:select.option>
                    <flux:select.option value="Person">Person</flux:select.option>
                </flux:select>

                <div class="flex">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" wire:click="tambahkanKeEvent" wire:loading.attr="disabled" wire:target="tambahkanKeEvent">
                        Tambahkan ke Event
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</flux:modal>
</div>
