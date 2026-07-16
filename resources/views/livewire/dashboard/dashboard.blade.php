@php
    \Carbon\Carbon::setLocale('id');
@endphp

<div class="space-y-6">
    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPesertaFiltered }}</div>
        </div>
        <div class="col-span-2 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900 md:col-span-1">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Sesi Aktif</div>
            <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ $sesiAktif?->nama_sesi ?? 'Belum ada sesi aktif' }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sesiAktif?->tanggal ?? '' }}</div>
            <flux:modal.trigger name="ganti-sesi">
                <button class="mt-3 inline-flex items-center rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">
                    Ganti Sesi
                </button>
            </flux:modal.trigger>
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
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $persentaseKehadiran }}%</div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Filter Regu</label>
        <select
            wire:model.live="regu_id"
            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:max-w-xs"
        >
            <option value="">-- Semua Regu --</option>
            @foreach($daftarRegu as $regu)
                <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
            @endforeach
        </select>
    </div>

    {{-- MODAL GANTI SESI --}}
    <flux:modal name="ganti-sesi" class="md:w-96">
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Pilih Sesi Aktif</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Aktifkan sesi untuk dashboard dan scan absensi.</p>
            </div>

            @foreach($daftarSesi as $sesi)
                <div class="flex items-center justify-between rounded-xl border border-zinc-200 dark:border-zinc-700 p-3">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $sesi->nama_sesi }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $sesi->tanggal }}</div>
                    </div>
                    @if($sesi->aktif)
                        <span class="rounded-lg bg-green-100 dark:bg-green-900/40 px-3 py-1 text-sm text-green-700 dark:text-green-300">Aktif</span>
                    @else
                        <button
                            wire:click="activateSesi({{ $sesi->id }})"
                            class="rounded-xl bg-blue-600 px-3 py-1.5 text-sm text-white transition hover:bg-blue-700">
                            Aktifkan
                        </button>
                    @endif
                </div>
            @endforeach

            @if($daftarSesi->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Tidak ada sesi.
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- TABLE: PESERTA HADIR --}}
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
                    @foreach($absensis as $absen)
                        <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 font-medium text-zinc-900 dark:text-white">{{ $absen->peserta->nama ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $absen->nip }}</td>
                            <td class="px-4 py-2">{{ $absen->peserta->regu->regu ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $absen->peserta->kelompok->kelompok_asal ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $absen->peserta->desa->desa_asal ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $absen->jam_scan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- TABLE: PESERTA BELUM ABSEN --}}
    <div>
        <h3 class="mb-3 text-base font-semibold text-zinc-800 dark:text-zinc-200">Peserta yang Belum Absen</h3>
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
</div>
