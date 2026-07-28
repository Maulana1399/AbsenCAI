<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Match Center</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kontrol pertandingan secara langsung.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">{{ session('info') }}</div>
    @endif

    {{-- Venue Filter --}}
    @if ($venues->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            <button wire:click="filterByVenue()"
                    @class([
                        'rounded-full px-4 py-2 text-sm font-medium transition',
                        'bg-zinc-600 text-white' => $filterVenueId === null,
                        'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $filterVenueId !== null,
                    ])>Semua Venue</button>
            @foreach ($venues as $v)
                <button wire:click="filterByVenue({{ $v->id }})"
                        @class([
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            'bg-zinc-600 text-white' => $filterVenueId === (string) $v->id,
                            'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $filterVenueId !== (string) $v->id,
                        ])>{{ $v->name }}</button>
            @endforeach
        </div>
    @endif

    {{-- Match Cards --}}
    @if ($schedules->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-200 p-10 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tidak ada pertandingan Ready atau Playing.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($schedules as $schedule)
                @php
                    $participants = $schedule->scheduleEntries->map(function ($e) {
                        return $e->competitionRegistration?->participation?->person?->nama ?? '?';
                    })->filter()->values();
                    $participantsCount = $schedule->participants_count ?? 0;
                    $required = $schedule->required_participants ?? 1;
                    $participantsComplete = $participantsCount >= $required;
                @endphp
                <div @class([
                    'rounded-xl border-2 bg-white p-5 dark:bg-zinc-950',
                    'border-green-400 dark:border-green-600' => $schedule->status === 'Playing',
                    'border-blue-300 dark:border-blue-700' => $schedule->status === 'Ready',
                ])>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                ])>{{ $schedule->status }}</span>
                                @if ($schedule->venue)
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $schedule->venue->name }}</span>
                                @endif
                            </div>
                            <div class="mt-2">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $schedule->competitionClass?->competitionCategory?->name ?? '' }}</span>
                                <h3 class="text-xl font-bold text-zinc-900 dark:text-white truncate">{{ $schedule->competitionClass?->name ?? '-' }}</h3>
                            </div>
                            @if ($participants->isNotEmpty())
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    @foreach ($participants as $i => $name)
                                        <span class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-semibold"
                                              @class([
                                                  'border-red-300 bg-red-100 text-red-800 dark:border-red-700 dark:bg-red-900/50 dark:text-red-200' => $i === 0,
                                                  'border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-700 dark:bg-blue-900/50 dark:text-blue-200' => $i === 1 && $participants->count() > 1,
                                                  'border-zinc-300 bg-zinc-100 text-zinc-800 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200' => $i > 1,
                                              ])>
                                            @if ($i === 0)🔴 @elseif($i === 1)🔵 @endif
                                            {{ $name }}
                                        </span>
                                        @if ($i === 0 && $participants->count() > 1)
                                            <span class="text-lg font-bold text-zinc-400 dark:text-zinc-500">VS</span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-3 text-sm text-zinc-400 dark:text-zinc-500">Belum ada peserta.</div>
                            @endif
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            @if ($schedule->status === 'Ready' && $participantsComplete)
                                <flux:button wire:click="startMatch({{ $schedule->id }})" variant="primary" class="whitespace-nowrap">
                                    Start Match
                                </flux:button>
                            @endif
                            @if ($schedule->status === 'Playing')
                                <flux:button wire:click="finishMatch({{ $schedule->id }})" variant="danger" class="whitespace-nowrap">
                                    Finish Match
                                </flux:button>
                            @endif
                            <flux:button :href="route('competition.schedule.entries', $schedule->id)"
                                         :variant="$schedule->status === 'Ready' && !$participantsComplete ? 'primary' : 'ghost'"
                                         size="sm" class="whitespace-nowrap">
                                Atur Peserta
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
