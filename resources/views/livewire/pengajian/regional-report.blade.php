<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Rekap Kehadiran Daerah</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Laporan kehadiran seluruh Desa dalam Event aktif.</p>
        </div>
    </div>

    @if ($noActiveEvent)
        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                Tidak ada event aktif. Silakan pilih atau aktifkan event terlebih dahulu.
            </p>
            <a
                href="{{ route('events.index') }}"
                class="mt-4 inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
            >
                Kelola Event
            </a>
        </div>
    @elseif (!empty($summary))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $summary['total_warga'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Total Warga</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $summary['sudah_hadir'] }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">Sudah Hadir</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $summary['belum_hadir'] }}</p>
                <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">Belum Hadir</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $summary['total_desa'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Total Desa</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-blue-700 dark:text-blue-400">{{ $summary['self'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Self</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-purple-700 dark:text-purple-400">{{ $summary['operator'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Operator</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-indigo-700 dark:text-indigo-400">{{ $summary['desa_hadir'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Desa dgn Hadir</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-zinc-700 dark:text-zinc-400">{{ $summary['total_desa'] - $summary['desa_hadir'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Desa tanpa Hadir</p>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
        <button
            type="button"
            wire:click="$set('activeTab', 'summary')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $activeTab === 'summary' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
        >
            Ringkasan per Desa
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'list')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $activeTab === 'list' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
        >
            Daftar Kehadiran
        </button>
    </div>

    {{-- Tab: Summary per Desa --}}
    @if ($activeTab === 'summary')
        @if (!empty($desaBreakdown))
            <div class="space-y-3">
                @foreach ($desaBreakdown as $desa)
                    <button
                        type="button"
                        wire:click="selectDesa({{ $desa['desa_id'] }})"
                        class="w-full rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                    >
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
                    </button>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                <p class="text-sm text-zinc-400">Tidak ada data desa.</p>
            </div>
        @endif
    @endif

    {{-- Tab: Daftar Kehadiran --}}
    @if ($activeTab === 'list')
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Daftar Kehadiran
                    @if ($selectedDesaId)
                        @php $selectedDesa = collect($desaBreakdown)->firstWhere('desa_id', $selectedDesaId); @endphp
                        @if ($selectedDesa)
                            &mdash; {{ $selectedDesa['desa_name'] }}
                        @endif
                    @else
                        &mdash; Semua Desa
                    @endif
                </h2>
                @if ($selectedDesaId)
                    <button
                        type="button"
                        wire:click="showAll"
                        class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        Tampilkan Semua
                    </button>
                @endif
            </div>

            <div class="mt-4 space-y-2">
                <flux:input
                    wire:model="listSearch"
                    placeholder="Cari nama..."
                    class="w-full"
                />

                <div class="flex gap-2">
                    <select
                        wire:model="filterStatus"
                        class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="hadir">Hadir</option>
                        <option value="belum">Belum Hadir</option>
                    </select>

                    <select
                        wire:model="filterMethod"
                        class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    >
                        <option value="">Semua Metode</option>
                        <option value="self">Self</option>
                        <option value="operator">Operator</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($attendanceList as $item)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $item['nama'] }}</p>
                            @if ($item['hadir'])
                                <p class="text-xs text-zinc-400">
                                    {{ $item['attended_at'] }}
                                    &middot;
                                    <span class="{{ $item['method'] === 'self' ? 'text-blue-600' : 'text-purple-600' }}">
                                        {{ $item['method'] === 'self' ? 'Self' : 'Operator' }}
                                    </span>
                                </p>
                            @endif
                        </div>
                        @if ($item['hadir'])
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                Hadir
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                Belum Hadir
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 text-center py-4">Tidak ada data.</p>
                @endforelse
            </div>

            @if (count($attendanceList) > 0)
                <div class="mt-2 text-right">
                    <span class="text-xs text-zinc-400">{{ count($attendanceList) }} peserta</span>
                </div>
            @endif
        </div>
    @endif
</div>
