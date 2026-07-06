<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih Regu</label>
            <select wire:model="regu_id" class="w-full mt-1 rounded border px-3 py-2">
                <option value="">-- Semua Regu --</option>
                @foreach($daftarRegu as $r)
                    <option value="{{ $r->id }}">{{ $r->regu }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih Kelompok</label>
            <select wire:model="kelompok_id" class="w-full mt-1 rounded border px-3 py-2">
                <option value="">-- Semua Kelompok --</option>
                @foreach($daftarKelompok as $k)
                    <option value="{{ $k->id }}">{{ $k->kelompok_asal }} ({{ $k->desa->desa_asal ?? '-' }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih Desa</label>
            <select wire:model="desa_id" class="w-full mt-1 rounded border px-3 py-2">
                <option value="">-- Semua Desa --</option>
                @foreach($daftarDesa as $d)
                    <option value="{{ $d->id }}">{{ $d->desa_asal }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white p-3 rounded shadow">Total: <strong>{{ $total }}</strong></div>
        <div class="bg-white p-3 rounded shadow">Laki-laki: <strong>{{ $totalLaki }}</strong></div>
        <div class="bg-white p-3 rounded shadow">Perempuan: <strong>{{ $totalPerempuan }}</strong></div>
        <div class="bg-white p-3 rounded shadow">Registrasi Ulang: <strong>{{ $sudahRegUlang }}</strong></div>
        <div class="bg-white p-3 rounded shadow">Belum Registrasi: <strong>{{ $belumRegUlang }}</strong></div>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="w-full text-sm text-left">
            <thead class="text-xs bg-gray-50">
                <tr>
                    <th class="px-4 py-2">No</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">NIP</th>
                    <th class="px-4 py-2">Jenis Kelamin</th>
                    <th class="px-4 py-2">Desa</th>
                    <th class="px-4 py-2">Kelompok</th>
                    <th class="px-4 py-2">Regu</th>
                    <th class="px-4 py-2">Status Registrasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daftarPeserta as $i => $p)
                    <tr class="odd:bg-white even:bg-gray-50">
                        <td class="px-4 py-2">{{ $loop->iteration }}</td>
                        <td class="px-4 py-2">{{ $p->nama }}</td>
                        <td class="px-4 py-2">{{ $p->nip }}</td>
                        <td class="px-4 py-2">{{ $p->jenis_kelamin }}</td>
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
