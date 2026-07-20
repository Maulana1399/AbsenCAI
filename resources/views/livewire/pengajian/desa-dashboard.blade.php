<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="text-center mb-2">
        <div class="mx-auto h-20 w-20 rounded-full bg-emerald-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
            KJA
        </div>

        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">
            Dashboard Desa
        </h1>

        <p class="text-emerald-600 text-lg mt-1">
            {{ $eventName }}
        </p>

        <p class="text-zinc-500 text-sm mt-1">
            {{ $desaName }}
        </p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
        <button
            type="button"
            wire:click="$set('activeTab', 'attendance')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $activeTab === 'attendance' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
        >
            Absen Peserta
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'list')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $activeTab === 'list' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
        >
            Daftar Kehadiran
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'qr')"
            class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $activeTab === 'qr' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
        >
            QR Absensi
        </button>
    </div>

    {{-- Summary Cards --}}
    @if (!empty($summary))
        <div class="grid grid-cols-3 gap-3">
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
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-blue-700 dark:text-blue-400">{{ $summary['self'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Self</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-lg font-bold text-purple-700 dark:text-purple-400">{{ $summary['operator'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Via Operator</p>
            </div>
        </div>
    @endif

    {{-- Tab: Absensi oleh Operator --}}
    @if ($activeTab === 'attendance')
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Absensi oleh Operator
            </h2>

            <p class="mt-1 text-xs text-zinc-400">
                Cari peserta dari desa ini untuk mencatat kehadiran secara manual.
            </p>

            @if ($successMessage)
                <div class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                    {{ $successMessage }}
                </div>
            @endif

            @if ($errorMessage)
                <div class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-400">
                    {{ $errorMessage }}
                </div>
            @endif

            @if ($showingConfirmation && $selectedPersonId !== null)
                {{-- Selected person confirmation --}}
                <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Peserta dipilih:</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedPersonName }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $desaName }}</p>
                </div>

                <div class="mt-4 flex gap-2">
                    <flux:button
                        wire:click="confirmOperatorAttendance"
                        :loading="$processing"
                    >
                        Tandai Hadir
                    </flux:button>

                    <flux:button
                        wire:click="resetSelection"
                        variant="ghost"
                    >
                        Batal
                    </flux:button>
                </div>
            @else
                {{-- Search --}}
                <div class="mt-4">
                    <flux:input
                        wire:model="query"
                        placeholder="Cari nama peserta (min. 3 karakter)..."
                        class="w-full"
                    />

                    <div class="mt-2 flex gap-2">
                        <flux:button
                            wire:click="searchPersons"
                            :loading="$searching"
                            size="sm"
                        >
                            Cari
                        </flux:button>
                    </div>
                </div>

                {{-- Results --}}
                @if (count($searchResults) > 0)
                    <div class="mt-4 space-y-2">
                        @foreach ($searchResults as $result)
                            <button
                                type="button"
                                wire:click="selectPerson({{ $result['id'] }})"
                                class="w-full rounded-lg border border-zinc-200 px-4 py-3 text-left text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            >
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $result['nama'] }}</span>
                                @if ($result['has_birth_date'])
                                    <span class="text-zinc-400 ml-2">({{ $result['birth_date_masked'] }})</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @elseif (mb_strlen(trim($query)) >= 3 && !$searching)
                    <p class="mt-3 text-sm text-zinc-400">Tidak ditemukan. Pastikan nama minimal 3 karakter dan sesuai dengan data peserta.</p>
                @endif
            @endif
        </div>
    @endif

    {{-- Tab: Daftar Kehadiran --}}
    @if ($activeTab === 'list')
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Daftar Kehadiran
            </h2>

            {{-- Filters --}}
            <div class="mt-4 space-y-2">
                <input
                    type="text"
                    wire:input="searchList($event.target.value)"
                    placeholder="Cari nama..."
                    value="{{ $listSearch }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500"
                />

                <div class="flex gap-2">
                    <select
                        wire:change="setFilterStatus($event.target.value)"
                        class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="hadir">Hadir</option>
                        <option value="belum">Belum Hadir</option>
                    </select>

                    <select
                        wire:change="setFilterMethod($event.target.value)"
                        class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    >
                        <option value="">Semua Metode</option>
                        <option value="self">Self</option>
                        <option value="operator">Operator</option>
                    </select>
                </div>
            </div>

            {{-- List --}}
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

    {{-- Tab: QR Absensi --}}
    @if ($activeTab === 'qr')
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                QR Akses
            </h2>

            <div class="mt-4 flex flex-col items-center gap-3">
                @if ($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}"
                         alt="QR Absen"
                         class="h-56 w-56">
                @else
                    <div class="flex h-56 w-56 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <p class="text-xs text-zinc-400">QR tidak tersedia</p>
                    </div>
                @endif

                <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center">
                    Scan QR untuk melakukan absensi mandiri
                </p>

                <div class="flex gap-2">
                    <flux:button
                        wire:click="refreshNonce"
                        variant="ghost"
                        size="sm"
                        :loading="$processing"
                    >
                        Segarkan QR
                    </flux:button>

                    <flux:button
                        onclick="window.open('{{ route('pengajian.qr-print', absolute: false) }}', 'print', 'width=600,height=800')"
                        variant="ghost"
                        size="sm"
                    >
                        Cetak QR
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-900/50">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                URL Absensi
            </h3>
            @if ($qrUrl)
                <p class="mt-2 text-xs text-zinc-400 break-all font-mono">
                    {{ url($qrUrl) }}
                </p>
            @endif
        </div>
    @endif

    {{-- Logout --}}
    <div class="flex flex-col gap-3">
        <button
            type="button"
            wire:click="logout"
            wire:loading.attr="disabled"
            class="w-full rounded-xl border border-red-300 px-4 py-3 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950"
        >
            Keluar dari Dashboard Desa
        </button>
    </div>
</div>
