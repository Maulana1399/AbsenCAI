<div class="space-y-6">
    {{-- FILTERS --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Sesi Absensi</label>
                <select wire:model.live="sesi_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Pilih Sesi --</option>
                    @foreach($daftarSesi as $sesi)
                        <option value="{{ $sesi->id }}">{{ $sesi->nama_sesi }} — {{ $sesi->tanggal }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Regu</label>
                <select wire:model.live="regu_id" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Semua Regu --</option>
                    @foreach($daftarRegu as $regu)
                        <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($sesi_id)
        {{-- STAT CARDS --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPeserta }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Hadir</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $sudahAbsenCount }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Izin</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $izinCount }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Alfa</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $belumAbsenCount }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">% Kehadiran</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $persentase }}%</div>
            </div>
        </div>

        {{-- TABLE: HADIR --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-zinc-800 dark:text-zinc-200">Peserta Hadir</h3>
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                        <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">NIP</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jam Scan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sudahAbsen as $entry)
                                @php $lp = $entry->legacyPeserta; @endphp
                                <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-2 font-medium text-zinc-900 dark:text-white">{{ $entry->person?->nama ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->person?->nip ?? ($lp->nip ?? '-') }}</td>
                                    <td class="px-4 py-2">{{ $entry->participation->regu->regu ?? $lp->regu->regu ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $lp->kelompok->kelompok_asal ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->person?->desa?->desa_asal ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->jam_scan ? \Illuminate\Support\Carbon::parse($entry->jam_scan)->format('H:i:s') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TABLE: IZIN --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-zinc-800 dark:text-zinc-200">Peserta Izin</h3>
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                        <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">NIP</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Waktu Dicatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pesertaIzin as $entry)
                                @php $lp = $entry->legacyPeserta; @endphp
                                <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-2 font-medium text-zinc-900 dark:text-white">{{ $entry->person?->nama ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->person?->nip ?? ($lp->nip ?? '-') }}</td>
                                    <td class="px-4 py-2">{{ $entry->participation->regu->regu ?? $lp->regu->regu ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $lp->kelompok->kelompok_asal ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->person?->desa?->desa_asal ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $entry->jam_scan ? \Illuminate\Support\Carbon::parse($entry->jam_scan)->format('H:i:s') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        Tidak ada peserta izin pada sesi ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TABLE: ALFA --}}
        <div>
            <h3 class="mb-3 text-base font-semibold text-zinc-800 dark:text-zinc-200">Peserta Alfa</h3>
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                        <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">NIP</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                                <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pesertaBelumAbsen as $peserta)
                                <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-2 font-medium text-zinc-900 dark:text-white">{{ $peserta->nama ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $peserta->nip }}</td>
                                    <td class="px-4 py-2">{{ $peserta->regu->regu ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $peserta->kelompok->kelompok_asal ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $peserta->desa->desa_asal ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            Pilih sesi absensi untuk melihat rekap.
        </div>
    @endif
</div>
