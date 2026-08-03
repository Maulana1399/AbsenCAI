{{-- Ringkasan Pengajian --}}
@if (!empty($summary))
    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-4 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $summary['total_warga'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Total Warga</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:p-4 text-center shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
            <p class="text-xl sm:text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $summary['sudah_hadir'] }}</p>
            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-500">Sudah Hadir</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 sm:p-4 text-center shadow-sm dark:border-blue-900 dark:bg-blue-950">
            <p class="text-xl sm:text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $summary['izin'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-blue-600 dark:text-blue-500">Izin</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 sm:p-4 text-center shadow-sm dark:border-amber-900 dark:bg-amber-950">
            <p class="text-xl sm:text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $summary['belum_hadir'] }}</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-500">Belum Hadir</p>
        </div>
    </div>

    {{-- Statistik Kehadiran per Metode --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-base sm:text-lg font-bold text-blue-700 dark:text-blue-400">{{ $summary['self'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Self</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-base sm:text-lg font-bold text-purple-700 dark:text-purple-400">{{ $summary['operator'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Operator</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-base sm:text-lg font-bold text-indigo-700 dark:text-indigo-400">{{ $summary['desa_hadir'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Desa dgn Hadir</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-base sm:text-lg font-bold text-zinc-700 dark:text-zinc-400">{{ $summary['total_desa'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Desa</p>
        </div>
    </div>
@endif

{{-- Regional — per Desa --}}
@if (!empty($desaBreakdown))
    <div>
        <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-white">Kehadiran per Desa</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($desaBreakdown as $desa)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $desa['desa_name'] }}</h3>
                        <span class="text-xs text-zinc-400">{{ $desa['sudah_hadir'] }}/{{ $desa['total_warga'] }}</span>
                    </div>
                    <div class="mt-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div
                            class="h-2 rounded-full bg-emerald-500 transition-all"
                            style="width: {{ $desa['total_warga'] > 0 ? ($desa['sudah_hadir'] / $desa['total_warga']) * 100 : 0 }}%"
                        ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $desa['sudah_hadir'] }} Hadir</span>
                        <span>{{ $desa['belum_hadir'] }} Belum</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
        <p class="text-sm text-zinc-400">Belum ada data kehadiran desa.</p>
    </div>
@endif
