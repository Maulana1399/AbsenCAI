<div class="space-y-6">

    {{-- FILTER --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Regu</label>
                <select wire:model.live="regu_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua Regu --</option>
                    @foreach($daftarRegu as $r)
                        <option value="{{ $r->id }}">{{ $r->regu }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Kelamin</label>
                <select wire:model.live="jenis_kelamin" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua --</option>
                    <option value="Laki - Laki">Laki - Laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Jenis Peserta</label>
                <select wire:model.live="jenis_peserta" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua --</option>
                    <option value="Wajib">Wajib</option>
                    <option value="Kiriman">Kiriman</option>
                    <option value="Person">Person</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Kelompok</label>
                <select wire:model.live="kelompok_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua Kelompok --</option>
                    @foreach($daftarKelompok as $k)
                        <option value="{{ $k->id }}">{{ $k->kelompok_asal }} ({{ $k->desa->desa_asal ?? '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Desa</label>
                <select wire:model.live="desa_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua Desa --</option>
                    @foreach($daftarDesa as $d)
                        <option value="{{ $d->id }}">{{ $d->desa_asal }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- STATISTIK --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Laki-laki</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalLaki }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Perempuan</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPerempuan }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Registrasi Ulang</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $sudahRegUlang }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Belum Registrasi</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $belumRegUlang }}</div>
        </div>
    </div>

    {{-- EXPORT --}}
    <div class="flex justify-end">
        <flux:button wire:click="exportExcel" variant="primary">Export Excel</flux:button>
    </div>

    {{-- TABLE --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">NIP</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Kelamin</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Peserta</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Status Registrasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daftarPeserta as $p)
                        <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 font-medium text-zinc-900 dark:text-white">{{ $p->nama }}</td>
                            <td class="px-4 py-2">{{ $p->nip }}</td>
                            <td class="px-4 py-2">{{ $p->jenis_kelamin }}</td>
                            <td class="px-4 py-2">{{ $p->jenis_peserta }}</td>
                            <td class="px-4 py-2">{{ $p->desa->desa_asal ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $p->kelompok->kelompok_asal ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $p->regu->regu ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $p->status_registrasi_label }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
