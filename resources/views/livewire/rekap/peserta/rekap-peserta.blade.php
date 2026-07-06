<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

    {{-- FILTER --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <label class="block text-sm font-medium text-gray-700">Pilih Regu</label>
                <select wire:model.live="regu_id" class="w-full mt-1 rounded border px-3 py-2">
                    <option value="">-- Semua Regu --</option>
                    @foreach($daftarRegu as $r)
                        <option value="{{ $r->id }}">{{ $r->regu }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Pilih Kelompok</label>
                <select wire:model.live="kelompok_id" class="w-full mt-1 rounded border px-3 py-2">
                    <option value="">-- Semua Kelompok --</option>
                    @foreach($daftarKelompok as $k)
                        <option value="{{ $k->id }}">
                            {{ $k->kelompok_asal }} ({{ $k->desa->desa_asal ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Pilih Desa</label>
                <select wire:model.live="desa_id" class="w-full mt-1 rounded border px-3 py-2">
                    <option value="">-- Semua Desa --</option>
                    @foreach($daftarDesa as $d)
                        <option value="{{ $d->id }}">{{ $d->desa_asal }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>


    {{-- STATISTIK --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Total Peserta</div>
            <div class="text-2xl font-bold">{{ $total }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Laki-laki</div>
            <div class="text-2xl font-bold">{{ $totalLaki }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Perempuan</div>
            <div class="text-2xl font-bold">{{ $totalPerempuan }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Registrasi Ulang</div>
            <div class="text-2xl font-bold">{{ $sudahRegUlang }}</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Belum Registrasi</div>
            <div class="text-2xl font-bold">{{ $belumRegUlang }}</div>
        </div>

    </div>


    {{-- EXPORT --}}
    <div class="flex justify-end">
        <flux:button wire:click="exportExcel" variant="primary">
            Export Excel
        </flux:button>
    </div>


    {{-- TABLE --}}
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">NIP</th>
                    <th class="px-4 py-3">Jenis Kelamin</th>
                    <th class="px-4 py-3">Desa</th>
                    <th class="px-4 py-3">Kelompok</th>
                    <th class="px-4 py-3">Regu</th>
                    <th class="px-4 py-3">Status Registrasi</th>
                </tr>
            </thead>

            <tbody>
                @foreach($daftarPeserta as $p)

                <tr class="border-t hover:bg-gray-50">

                    <td class="px-4 py-3">
                        {{ $loop->iteration }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->nama }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->nip }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->jenis_kelamin }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->desa->desa_asal ?? '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->kelompok->kelompok_asal ?? '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->regu->regu ?? '-' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $p->status_registrasi_label }}
                    </td>

                </tr>

                @endforeach
            </tbody>

        </table>

    </div>

</div>