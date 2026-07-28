@php
    \Carbon\Carbon::setLocale('id');
@endphp

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</a>
        <span class="mx-1">/</span>
        <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $eventName }}</span>
    </div>

    @if ($event->isCompetition() && $overview)
        {{-- Overview Cards --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                <div class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Peserta</div>
                <div class="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $overview['participants'] }}</div>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-950">
                <div class="text-xs font-medium uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Kelas</div>
                <div class="mt-1 text-2xl font-bold text-indigo-800 dark:text-indigo-200">{{ $overview['classes'] }}</div>
            </div>
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-950">
                <div class="text-xs font-medium uppercase tracking-wide text-purple-600 dark:text-purple-400">Venue</div>
                <div class="mt-1 text-2xl font-bold text-purple-800 dark:text-purple-200">{{ $overview['venues'] }}</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                <div class="text-xs font-medium uppercase tracking-wide text-amber-600 dark:text-amber-400">Hari Ini</div>
                <div class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">{{ $overview['today_matches'] }}</div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                <div class="text-xs font-medium uppercase tracking-wide text-green-600 dark:text-green-400">Berlangsung</div>
                <div class="mt-1 text-2xl font-bold text-green-800 dark:text-green-200">{{ $overview['running'] }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Selesai</div>
                <div class="mt-1 text-2xl font-bold text-zinc-800 dark:text-zinc-200">{{ $overview['finished'] }}</div>
            </div>
            @if ($overview['pending'] > 0)
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                <div class="text-xs font-medium uppercase tracking-wide text-yellow-600 dark:text-yellow-400">Belum Dijadwalkan</div>
                <div class="mt-1 text-2xl font-bold text-yellow-800 dark:text-yellow-200">{{ $overview['pending'] }}</div>
            </div>
            @endif
        </div>
    @else
        {{-- Original CAI stat cards --}}
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
                    <flux:button size="sm" variant="primary" class="mt-3">Ganti Sesi</flux:button>
                </flux:modal.trigger>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Hadir</div>
                <div class="mt-1 text-2xl font-bold text-green-600">{{ $sudahAbsenCount }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Izin</div>
                <div class="mt-1 text-2xl font-bold text-amber-600">{{ $izinCount }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Belum Absen</div>
                <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $belumAbsenCount }}</div>
            </div>
        </div>

        @if ($pesertaBelumAbsen->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Peserta Belum Absen</h3>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($pesertaBelumAbsen as $p)
                        <div class="px-4 py-2.5 text-sm text-zinc-900 dark:text-white">{{ $p->person?->nama ?? '-' }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Event Information --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $event->name }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->typeLabel() }}</p>
                @if ($event->start_date || $event->end_date)
                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $event->start_date?->format('d M Y') ?? '?' }}
                        @if ($event->end_date) – {{ $event->end_date->format('d M Y') }}@endif
                    </p>
                @endif
            </div>
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                {{ ucfirst($event->status) }}
            </span>
        </div>
    </div>

    @if ($event->isCompetition())
        {{-- Live Competition --}}
        @if ($liveMatches->isNotEmpty())
            <div>
                <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-white">Live Pertandingan</h2>
                @foreach ($liveMatches as $venueName => $matches)
                    <div class="mb-4">
                        <h3 class="mb-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ $venueName }}</h3>
                        <div class="space-y-2">
                            @foreach ($matches as $schedule)
                                @php
                                    $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter();
                                @endphp
                                <a href="{{ route('competition.match-center') }}" 
                                   class="flex items-center justify-between rounded-lg border bg-white p-3 transition hover:shadow dark:bg-zinc-950
                                          {{ $schedule->status === 'Playing' ? 'border-green-300 dark:border-green-700' : '' }}
                                          {{ $schedule->status === 'Waiting Result' ? 'border-yellow-300 dark:border-yellow-700' : '' }}
                                          {{ $schedule->status === 'Ready' ? 'border-blue-300 dark:border-blue-700' : '' }}">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ $schedule->competitionClass?->name ?? '-' }}</div>
                                        @if ($participants->isNotEmpty())
                                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $participants->implode(' vs ') }}</div>
                                        @endif
                                    </div>
                                    <span @class([
                                        'ml-2 inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase',
                                        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' => $schedule->status === 'Waiting Result',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                    ])>{{ $schedule->status }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Today's Schedule --}}
        @if ($todaySchedules->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Jadwal Hari Ini</h2>
                    <a href="{{ route('competition.schedule.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Lihat Semua</a>
                </div>
                <div class="space-y-2">
                    @foreach ($todaySchedules as $schedule)
                        @php $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0">{{ $schedule->start_at?->format('H:i') ?? '-' }}</span>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ $schedule->competitionClass?->name ?? '-' }}</span>
                                </div>
                                @if ($schedule->venue)<div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $schedule->venue->name }}</div>@endif
                                @if ($participants->isNotEmpty())
                                    <div class="mt-0.5 flex flex-wrap gap-1">
                                        @foreach ($participants as $name)
                                            <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ $name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <span @class([
                                'ml-2 inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-medium',
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' => $schedule->status === 'Scheduled',
                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' => $schedule->status === 'Waiting Result',
                                'bg-zinc-700 text-white' => $schedule->status === 'Finished',
                            ])>{{ $schedule->status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Recent Activity --}}
        <div class="grid gap-6 sm:grid-cols-2">
            @if ($recentRegistrations->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-white">Registrasi Terbaru</h2>
                    <div class="space-y-2">
                        @foreach ($recentRegistrations as $reg)
                            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $reg->participation?->person?->nama ?? '-' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reg->competitionClass?->name ?? '-' }} &middot; {{ $reg->created_at->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($recentResults->isNotEmpty())
                <div>
                    <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-white">Hasil Terbaru</h2>
                    <div class="space-y-2">
                        @foreach ($recentResults as $result)
                            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $result->competitionClass?->name ?? '-' }}</div>
                                @if ($result->winner)
                                    <div class="text-xs text-yellow-600 dark:text-yellow-400">🏆 {{ $result->winner->participation?->person?->nama ?? '-' }}</div>
                                @endif
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $result->finished_at?->diffForHumans() ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Quick Actions --}}
    <div>
        <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-white">Aksi Cepat</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @can('manage-registration')
            <a href="{{ route('competition.registration') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Pendaftaran</div>
            </a>
            @endcan
            <a href="{{ route('competition.participants') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Peserta</div>
            </a>
            @can('manage-events')
            <a href="{{ route('competition.schedule.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Jadwal</div>
            </a>
            @endcan
            @can('manage-matches')
            <a href="{{ route('competition.match-center') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Match Center</div>
            </a>
            @endcan
            @can('manage-events')
            <a href="{{ route('competition.bracket-manager') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Bracket</div>
            </a>
            @endcan
            @can('manage-events')
            <a href="{{ route('competition.category.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 text-center transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-lg font-semibold text-zinc-900 dark:text-white">Konfigurasi</div>
            </a>
            @endcan
        </div>
    </div>
</div>
