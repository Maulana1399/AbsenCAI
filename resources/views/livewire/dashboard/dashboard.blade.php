@php
    \Carbon\Carbon::setLocale('id');
@endphp

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

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Absen Subuh</div>
            <div class="text-2xl font-bold">{{ $rekapAbsenPerSesi['Subuh'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Absen Pagi</div>
            <div class="text-2xl font-bold">{{ $rekapAbsenPerSesi['Pagi'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Absen Siang</div>
            <div class="text-2xl font-bold">{{ $rekapAbsenPerSesi['Siang'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Absen Malam</div>
            <div class="text-2xl font-bold">{{ $rekapAbsenPerSesi['Malam'] ?? 0 }}</div>
        </div>
    </div>

    
    <table class="min-w-full border">
        <thead>
            <tr>
                <th class="border px-2 py-1">No</th>
                <th class="border px-2 py-1">Nama</th>
                <th class="border px-2 py-1">Regu</th>
                <th class="border px-2 py-1">Kelompok</th>
                <th class="border px-2 py-1">Jam Scan</th>
                <th class="border px-2 py-1">Hari</th>
                <th class="border px-2 py-1">Sesi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensis as $i => $absen)
                <tr>
                    <td class="border px-2 py-1">{{ $i+1 }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->nama ?? '-' }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->regu->regu ?? '-' }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->kelompok->kelompok_asal ?? '-' }}</td>
                    <td class="border px-2 py-1">{{ $absen->jam_scan }}</td>
                    <td class="border px-2 py-1">
                        {{ \Carbon\Carbon::parse($absen->jam_scan)->translatedFormat('l') }}
                    </td>
                    <td class="border px-2 py-1">{{ $absen->sesi }}</td> <!-- Menampilkan sesi -->
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        <h3 class="text-xl font-bold">Peserta yang Belum Absen:</h3>
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
            @foreach($pesertaBelumAbsen as $peserta)
                <tr>
                    <td class="border px-2 py-1">{{ $i+1 }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->nama ?? '-' }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->regu->regu ?? '-' }}</td>
                    <td class="border px-2 py-1">{{ $absen->peserta->kelompok->kelompok_asal ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
