<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500">Total Peserta</div>
                <div class="text-2xl font-bold">{{ $totalPeserta }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500">Total Desa</div>
                <div class="text-2xl font-bold">{{ $totalDesa }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500">Total Kelompok</div>
                <div class="text-2xl font-bold">{{ $totalKelompok }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500">Total Regu</div>
                <div class="text-2xl font-bold">{{ $totalRegu }}</div>
            </div>
        </div>
        <table class="min-w-full border">
            <thead>
                <tr>
                    <th class="border px-2 py-1">No</th>
                    <th class="border px-2 py-1">Nama</th>
                    <th class="border px-2 py-1">Regu</th>
                    <th class="border px-2 py-1">Kelompok</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absensis as $i => $absen)
                    <tr>
                        <td class="border px-2 py-1">{{ $i+1 }}</td>
                        <td class="border px-2 py-1">{{ $absen->nama }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->regu->regu ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $absen->peserta->kelompok->kelompok_asal ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>