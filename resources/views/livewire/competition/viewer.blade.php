<div class="min-h-screen bg-zinc-900 text-white"
     x-data="{ showAnnouncement: true }"
     wire:poll.5s
>
    {{-- TV Mode --}}
    @if ($tvMode)
        <div class="flex min-h-screen flex-col">
            <div class="flex-shrink-0 border-b border-zinc-700 bg-zinc-950 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold tracking-wide">{{ $event->name }}</h1>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-zinc-300">{{ $selectedVenue?->name ?? 'Semua Venue' }}</div>
                        <div class="text-sm text-zinc-500">{{ $currentTime }} &middot; AUTO</div>
                    </div>
                </div>
            </div>
            @if ($announcement && $announcement->is_active)
                <div x-show="showAnnouncement" class="flex-shrink-0 bg-yellow-600 px-6 py-6 text-center"
                     x-init="setTimeout(() => { showAnnouncement = false; $wire.dismissAnnouncement(); }, 10000)">
                    <p class="text-3xl font-bold text-white">{{ $announcement->message }}</p>
                </div>
            @endif
            <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-6">
                {{-- Venue Filter --}}
                @if ($venues->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="filterByVenue()"
                                @class([
                                    'rounded-full px-4 py-2 text-lg font-medium transition',
                                    'bg-zinc-600 text-white' => $venueId === null,
                                    'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== null,
                                ])>Semua</button>
                        @foreach ($venues as $v)
                            <button wire:click="filterByVenue({{ $v->id }})"
                                    @class([
                                        'rounded-full px-4 py-2 text-lg font-medium transition',
                                        'bg-zinc-600 text-white' => $venueId === (string) $v->id,
                                        'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== (string) $v->id,
                                    ])>{{ $v->name }}</button>
                        @endforeach
                    </div>
                @endif
                @if ($nowPlaying->isNotEmpty())
                    <div class="rounded-2xl border-4 border-green-500 bg-green-950 p-8">
                        <h2 class="mb-6 text-center text-4xl font-bold text-green-400 uppercase tracking-widest">Now Playing</h2>
                        <div class="grid gap-6">
                            @foreach ($nowPlaying as $schedule)
                                <div class="rounded-xl border-2 border-green-700 bg-green-900/50 p-6">
                                    <div class="text-2xl font-bold text-white">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                    <div class="mt-1 text-4xl font-bold text-green-300">{{ $schedule->competitionClass?->name }}</div>
                                    @php $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
                                    @if ($participants->isNotEmpty())
                                        <div class="mt-4 flex flex-wrap items-center gap-4 text-2xl text-white">
                                            @foreach ($participants as $name)
                                                <span class="rounded-lg border border-green-600 bg-green-800/50 px-4 py-1">{{ $name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="mt-4 text-lg text-zinc-400">Belum ada peserta dijadwalkan.</div>
                                    @endif
                                    <div class="mt-4 flex items-center gap-6 text-xl text-zinc-300">
                                        @if ($schedule->venue)<span>{{ $schedule->venue->name }}</span>@endif
                                        @if ($schedule->start_at)<span>{{ \Carbon\Carbon::parse($schedule->start_at)->format('H:i') }} - {{ $schedule->end_at ? \Carbon\Carbon::parse($schedule->end_at)->format('H:i') : '?' }}</span>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($ready->isNotEmpty())
                    <div class="rounded-2xl border-4 border-blue-500 bg-blue-950 p-8">
                        <h2 class="mb-6 text-center text-4xl font-bold text-blue-400 uppercase tracking-widest">Ready</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($ready as $schedule)
                                <div class="rounded-xl border-2 border-blue-700 bg-blue-900/50 p-5">
                                    <div class="text-xl font-bold text-white">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                    <div class="mt-1 text-2xl font-bold text-blue-300">{{ $schedule->competitionClass?->name }}</div>
                                    @if ($schedule->venue)<div class="mt-2 text-lg text-zinc-300">{{ $schedule->venue->name }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($next->isNotEmpty())
                    <div class="rounded-2xl border-2 border-zinc-700 bg-zinc-800 p-6">
                        <h2 class="mb-4 text-center text-2xl font-bold text-zinc-400 uppercase tracking-widest">Next</h2>
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach ($next as $schedule)
                                <div class="rounded-lg border border-zinc-600 bg-zinc-900 p-4">
                                    <div class="text-base font-semibold text-white">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                    <div class="text-lg font-bold text-zinc-300">{{ $schedule->competitionClass?->name }}</div>
                                    @if ($schedule->venue)<div class="mt-1 text-sm text-zinc-500">{{ $schedule->venue->name }}</div>@endif
                                    @if ($schedule->start_at)<div class="text-sm text-zinc-500">{{ \Carbon\Carbon::parse($schedule->start_at)->format('H:i') }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($nowPlaying->isEmpty() && $ready->isEmpty() && $next->isEmpty())
                    <div class="flex flex-1 items-center justify-center"><p class="text-2xl text-zinc-500">Belum ada jadwal.</p></div>
                @endif
            </div>
        </div>
    @else
        {{-- Standard / Mobile / Desktop mode --}}
        <div class="mx-auto max-w-4xl p-3 sm:p-6">
            {{-- Header --}}
            <div class="mb-4 text-center">
                <h1 class="text-xl font-bold tracking-wide sm:text-3xl">{{ $event->name }}</h1>
                <div class="mt-1 flex items-center justify-center gap-3 text-xs text-zinc-500 sm:text-sm">
                    <span>{{ $currentTime }}</span>
                    <span class="inline-block h-2 w-2 rounded-full bg-green-500" title="Auto-refresh 5s"></span>
                    <span>Live</span>
                    @if ($selectedVenue)<span>&middot; {{ $selectedVenue->name }}</span>@endif
                </div>
            </div>

            {{-- Venue Filter --}}
            @if ($venues->isNotEmpty())
                <div class="mb-4 flex flex-wrap justify-center gap-2">
                    <button wire:click="filterByVenue()"
                            @class([
                                'rounded-full px-3 py-1 text-sm font-medium transition',
                                'bg-zinc-600 text-white' => $venueId === null,
                                'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== null,
                            ])>Semua</button>
                    @foreach ($venues as $v)
                        <button wire:click="filterByVenue({{ $v->id }})"
                                @class([
                                    'rounded-full px-3 py-1 text-sm font-medium transition',
                                    'bg-zinc-600 text-white' => $venueId === (string) $v->id,
                                    'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== (string) $v->id,
                                ])>{{ $v->name }}</button>
                    @endforeach
                </div>
            @endif

            {{-- TV Helper (not in TV mode) --}}
            <div class="mb-4 rounded-lg border border-zinc-700 bg-zinc-800 p-3 text-center text-xs text-zinc-400">
                📺 Tambahkan <strong class="text-zinc-200">?display=tv</strong> untuk mode TV &middot; Auto-refresh 5 detik &middot; <a href="{{ request()->fullUrlWithQuery(['display' => 'tv']) }}" class="text-blue-400 hover:text-blue-300 underline">Buka TV Mode</a>
            </div>

            {{-- Announcement --}}
            @if ($announcement && $announcement->is_active)
                <div x-show="showAnnouncement" class="mb-4 rounded-xl border-2 border-yellow-500 bg-yellow-950 p-4 text-center"
                     x-init="setTimeout(() => { showAnnouncement = false; $wire.dismissAnnouncement(); }, 10000)">
                    <p class="text-lg font-bold text-yellow-300">{{ $announcement->message }}</p>
                    <button @click="showAnnouncement = false; $wire.dismissAnnouncement()" class="mt-2 text-sm text-yellow-400 hover:text-yellow-200">Tutup</button>
                </div>
            @endif

            {{-- NOW PLAYING --}}
            @if ($nowPlaying->isNotEmpty())
                <div class="mb-4 rounded-xl border-2 border-green-500 bg-green-950 p-4">
                    <h2 class="mb-3 text-center text-lg font-bold text-green-400 uppercase tracking-widest sm:text-2xl">Now Playing</h2>
                    <div class="grid gap-3">
                        @foreach ($nowPlaying as $schedule)
                            <div class="rounded-lg border border-green-700 bg-green-900/50 p-4">
                                <div class="font-bold text-white sm:text-lg">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                <div class="text-lg font-bold text-green-300 sm:text-2xl">{{ $schedule->competitionClass?->name }}</div>
                                @php $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
                                @if ($participants->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($participants as $name)
                                            <span class="rounded-md border border-green-600 bg-green-800/50 px-2 py-0.5 text-xs font-semibold text-white sm:text-sm">{{ $name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-2 text-xs text-zinc-400">Belum ada peserta dijadwalkan.</div>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-zinc-300 sm:text-sm">
                                    @if ($schedule->venue)<span>{{ $schedule->venue->name }}</span>@endif
                                    @if ($schedule->start_at)<span>{{ \Carbon\Carbon::parse($schedule->start_at)->format('H:i') }} - {{ $schedule->end_at ? \Carbon\Carbon::parse($schedule->end_at)->format('H:i') : '?' }}</span>@endif
                                </div>
                                @if ($schedule->notes)<div class="mt-1 text-xs italic text-zinc-400">{{ $schedule->notes }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- READY --}}
            @if ($ready->isNotEmpty())
                <div class="mb-4 rounded-xl border-2 border-blue-500 bg-blue-950 p-4">
                    <h2 class="mb-3 text-center text-lg font-bold text-blue-400 uppercase tracking-widest sm:text-2xl">Ready</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($ready as $schedule)
                            <div class="rounded-lg border border-blue-700 bg-blue-900/50 p-3">
                                <div class="font-bold text-white">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                <div class="text-base font-bold text-blue-300 sm:text-lg">{{ $schedule->competitionClass?->name }}</div>
                                @if ($schedule->venue)<div class="mt-1 text-xs text-zinc-300">{{ $schedule->venue->name }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- NEXT --}}
            @if ($next->isNotEmpty())
                <div class="rounded-xl border border-zinc-700 bg-zinc-800 p-4">
                    <h2 class="mb-3 text-center text-sm font-bold text-zinc-400 uppercase tracking-widest sm:text-xl">Next</h2>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($next as $schedule)
                            <div class="rounded-lg border border-zinc-600 bg-zinc-900 p-3">
                                <div class="text-xs font-semibold text-white sm:text-sm">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                                <div class="text-sm font-bold text-zinc-300 sm:text-base">{{ $schedule->competitionClass?->name }}</div>
                                @if ($schedule->venue)<div class="text-xs text-zinc-500">{{ $schedule->venue->name }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($nowPlaying->isEmpty() && $ready->isEmpty() && $next->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-700 p-10 text-center">
                    <p class="text-zinc-500">Belum ada jadwal.</p>
                </div>
            @endif
        </div>
    @endif
</div>
