@php
    \Carbon\Carbon::setLocale('id');
@endphp

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Total Peserta</div>
            <div class="text-2xl font-bold">{{ $totalPesertaFiltered }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Sesi Aktif</div>
            <div class="text-2xl font-bold">{{ $sesiAktif?->nama_sesi ?? 'Belum ada sesi aktif' }}</div>
            <div class="text-sm text-gray-500">{{ $sesiAktif?->tanggal ?? '' }}</div>
            <flux:modal.trigger name="ganti-sesi">
    <button class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
        Ganti Sesi
    </button>
</flux:modal.trigger>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Sudah Absen</div>
            <div class="text-2xl font-bold">{{ $sudahAbsenCount }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Belum Absen</div>
            <div class="text-2xl font-bold">{{ $belumAbsenCount }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="text-gray-500">Persentase Kehadiran</div>
            <div class="text-2xl font-bold">{{ $persentaseKehadiran }}%</div>
        </div>
    </div>

    <div class="mb-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <label class="block text-sm font-medium text-gray-700">Filter Regu</label>
                <select wire:model="regu_id" class="w-full mt-1 rounded border px-3 py-2">
                    <option value="">-- Semua Regu --</option>
                    @foreach($daftarRegu as $regu)
                        <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <flux:modal name="ganti-sesi" class="md:w-96">
    <div class="space-y-4">

        <div>
            <h2 class="text-lg font-bold">Pilih Sesi Aktif</h2>
            <p class="text-sm text-gray-500">
                Aktifkan sesi untuk dashboard dan scan absensi.
            </p>
        </div>

        @foreach($daftarSesi as $sesi)
            <div class="flex items-center justify-between rounded-xl border p-3">

                <div>
                    <div class="font-semibold">
                        {{ $sesi->nama_sesi }}
                    </div>

                    <div class="text-sm text-gray-500">
                        {{ $sesi->tanggal }}
                    </div>
                </div>


                @if($sesi->aktif)

                    <span class="rounded bg-green-100 px-3 py-1 text-green-700">
                        Aktif
                    </span>

                @else

                    <button
                        wire:click="activateSesi({{ $sesi->id }})"
                        class="rounded bg-blue-600 px-3 py-2 text-white">
                        Aktifkan
                    </button>

                @endif

            </div>
        @endforeach


        @if($daftarSesi->isEmpty())
            <div class="text-gray-500">
                Tidak ada sesi.
            </div>
        @endif

    </div>
</flux:modal>

    <div class="overflow-x-auto bg-white rounded shadow mb-6">
        <table class="min-w-full border">
            <thead>
                <tr>
                    <th class="border px-2 py-1">No</th>
                    <th class="border px-2 py-1">Nama</th>
                    <th class="border px-2 py-1">NIP</th>
                    <th class="border px-2 py-1">Regu</th>
                    <th class="border px-2 py-1">Kelompok</th>
                    <th class="border px-2 py-1">Desa</th>
                    <th class="border px-2 py-1">Jam Scan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absensis as $absen)
                    <tr>
                        <td class="border px-2 py-1">{{ $loop->iteration }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->nama ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $absen->nip }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->regu->regu ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->kelompok->kelompok_asal ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->desa->desa_asal ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $absen->jam_scan }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>
        <h3 class="text-xl font-bold mb-3">Peserta yang Belum Absen</h3>
        <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full border">
                <thead>
                    <tr>
                        <th class="border px-2 py-1">No</th>
                        <th class="border px-2 py-1">Nama</th>
                        <th class="border px-2 py-1">NIP</th>
                        <th class="border px-2 py-1">Regu</th>
                        <th class="border px-2 py-1">Kelompok</th>
                        <th class="border px-2 py-1">Desa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pesertaBelumAbsen as $peserta)
                        <tr>
                            <td class="border px-2 py-1">{{ $loop->iteration }}</td>
                            <td class="border px-2 py-1">{{ $peserta->nama ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $peserta->nip }}</td>
                            <td class="border px-2 py-1">{{ $peserta->regu->regu ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $peserta->kelompok->kelompok_asal ?? '-' }}</td>
                            <td class="border px-2 py-1">{{ $peserta->desa->desa_asal ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
