<div class="space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">Registrasi Ulang</flux:heading>
        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Cari peserta berdasarkan nama.
        </flux:text>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Cari nama peserta"
            icon="magnifying-glass"
        />
    </div>

    <div class="grid gap-4">
        @forelse ($daftarPeserta as $peserta)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="space-y-2">
                    <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ $peserta->nama }}</div>
                </div>

                <div class="mt-4 grid gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <div><span class="font-medium">Desa:</span> {{ $peserta->desa?->desa_asal ?? '-' }}</div>
                    <div><span class="font-medium">Kelompok:</span> {{ $peserta->kelompok?->kelompok_asal ?? '-' }}</div>
                    <div><span class="font-medium">Regu:</span> {{ $peserta->regu?->regu ?? '-' }}</div>
                    <div><span class="font-medium">Status Registrasi:</span> {{ $peserta->status_registrasi_label }}</div>
                </div>

            <div class="mt-5 flex gap-3">

                <flux:button 
                    type="button" 
                    variant="primary" 
                    class="flex-1"
                    wire:click="registrasiUlang({{ $peserta->id }})">
                    Registrasi Ulang
                </flux:button>


                <flux:button
                    type="button"
                    variant="filled"
                    class="flex-1"
                    wire:click="editPeserta({{ $peserta->id }})">
                    Edit Data
                </flux:button>

            </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                {{ $search ? 'Peserta tidak ditemukan.' : 'Mulai ketik nama untuk mencari peserta.' }}
            </div>
        @endforelse
    </div>

@if($showEditModal)

<div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4">

    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-xl">

        <h2 class="mb-5 text-xl font-bold text-zinc-900 dark:text-white">
            Edit Peserta
        </h2>


        <div class="space-y-4">

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama</label>
                <input
                    wire:model="editNama"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Kelamin</label>

                <select
                    wire:model="editJenisKelamin"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">

                    <option value="Laki - Laki">
                        Laki - Laki
                    </option>

                    <option value="Perempuan">
                        Perempuan
                    </option>

                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Peserta</label>

                <select
                    wire:model="editJenisPeserta"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">

                    <option value="Wajib">Wajib</option>
                    <option value="Kiriman">Kiriman</option>
                    <option value="Person">Person</option>

                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Desa</label>

                <select
                    wire:model="editDesa"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">

                    @foreach($daftarDesa as $d)
                        <option value="{{ $d->id }}">
                            {{ $d->desa_asal }}
                        </option>
                    @endforeach

                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelompok</label>

                <select
                    wire:model="editKelompok"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">

                    @foreach($daftarKelompok as $k)
                        <option value="{{ $k->id }}">
                            {{ $k->kelompok_asal }}
                        </option>
                    @endforeach

                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Regu</label>

                <select
                    wire:model="editRegu"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">

                    @foreach($daftarRegu as $r)
                        <option value="{{ $r->id }}">
                            {{ $r->regu }}
                        </option>
                    @endforeach

                </select>
            </div>


            <div class="flex gap-3 pt-4">

                <button
                    wire:click="updatePeserta"
                    class="flex-1 rounded-xl bg-blue-600 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                    Simpan
                </button>

                <button
                    wire:click="$set('showEditModal', false)"
                    class="flex-1 rounded-xl bg-zinc-900 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                    Batal
                </button>

            </div>

        </div>

    </div>

</div>

@endif

</div>
