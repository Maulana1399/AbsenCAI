<div class="space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">Registrasi Ulang</flux:heading>
        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Cari peserta berdasarkan nama atau NIP.
        </flux:text>
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Cari nama atau NIP"
            icon="magnifying-glass"
        />
    </div>

    <div class="grid gap-4">
        @forelse ($daftarPeserta as $peserta)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="space-y-2">
                    <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ $peserta->nama }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">NIP: {{ $peserta->nip }}</div>
                </div>

                <div class="mt-4 grid gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <div><span class="font-medium">Desa:</span> {{ $peserta->desa->desa_asal ?? '-' }}</div>
                    <div><span class="font-medium">Kelompok:</span> {{ $peserta->kelompok->kelompok_asal ?? '-' }}</div>
                    <div><span class="font-medium">Regu:</span> {{ $peserta->regu->regu ?? '-' }}</div>
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
                {{ $search ? 'Peserta tidak ditemukan.' : 'Mulai ketik nama atau NIP untuk mencari peserta.' }}
            </div>
        @endforelse
    </div>

    @if($showEditModal)

<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">

    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">

        <h2 class="mb-4 text-xl font-bold">
            Edit Peserta
        </h2>


        <div class="space-y-3">

            <div>
                <label>Nama</label>
                <input 
                    wire:model="editNama"
                    class="w-full rounded border px-3 py-2">
            </div>


            <div>
                <label>Jenis Kelamin</label>

                <select 
                    wire:model="editJenisKelamin"
                    class="w-full rounded border px-3 py-2">

                    <option value="Laki - Laki">
                        Laki - Laki
                    </option>

                    <option value="Perempuan">
                        Perempuan
                    </option>

                </select>
            </div>


            <div>
                <label>Desa</label>

                <select 
                    wire:model="editDesa"
                    class="w-full rounded border px-3 py-2">

                    @foreach($daftarDesa as $d)

                    <option value="{{ $d->id }}">
                        {{ $d->desa_asal }}
                    </option>

                    @endforeach

                </select>
            </div>


            <div>
                <label>Kelompok</label>

                <select 
                    wire:model="editKelompok"
                    class="w-full rounded border px-3 py-2">

                    @foreach($daftarKelompok as $k)

                    <option value="{{ $k->id }}">
                        {{ $k->kelompok_asal }}
                    </option>

                    @endforeach

                </select>
            </div>


            <div>
                <label>Regu</label>

                <select 
                    wire:model="editRegu"
                    class="w-full rounded border px-3 py-2">

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
                    class="flex-1 rounded bg-blue-600 px-4 py-2 text-white">

                    Simpan

                </button>


                <button
                    wire:click="$set('showEditModal', false)"
                    class="flex-1 rounded bg-gray-200 px-4 py-2">

                    Batal

                </button>

            </div>


        </div>

    </div>

</div>

@endif

</div>
