<x-layouts.public>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <a href="{{ route('public.event', $event) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; {{ $event->name }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 sm:text-3xl">Bracket</h1>

        @if ($brackets->count() > 1)
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($brackets as $b)
                    <a href="{{ route('public.bracket', ['event' => $event, 'bracket' => $b]) }}"
                       @class([
                           'rounded-full px-4 py-2 text-sm font-medium transition',
                           'bg-zinc-600 text-white' => $selectedBracket?->id === $b->id,
                           'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' => $selectedBracket?->id !== $b->id,
                       ])>{{ $b->competitionClass?->name ?? 'Bracket' }}</a>
                @endforeach
            </div>
        @endif

        @if ($selectedBracket && $bracketRounds)
            <div class="mt-6 overflow-x-auto pb-4">
                <div class="flex gap-6 min-w-max">
                    @foreach ($bracketRounds as $roundData)
                        <div class="flex flex-col justify-around gap-4 min-w-[200px]">
                            <h3 class="text-sm font-bold text-zinc-800 text-center uppercase tracking-wider">{{ $roundData['label'] }}</h3>
                            @foreach ($roundData['matches'] as $bm)
                                @php
                                    $schedule = $bm->schedule;
                                    $entries = $schedule?->scheduleEntries ?? collect();
                                    $nameA = $entries->first()?->competitionRegistration?->participation?->person?->nama ?? 'TBD';
                                    $nameB = $entries->skip(1)->first()?->competitionRegistration?->participation?->person?->nama ?? 'TBD';
                                    $isFinished = $schedule && $schedule->status === 'Finished';
                                    $winnerName = $isFinished ? $schedule?->winner?->participation?->person?->nama : null;
                                @endphp
                                <div @class([
                                    'rounded-lg border-2 p-3 text-xs min-w-[180px]',
                                    'bg-white',
                                    'border-green-400' => $schedule && $schedule->status === 'Playing',
                                    'border-yellow-400' => $schedule && $schedule->status === 'Waiting Result',
                                    'border-blue-300' => $schedule && $schedule->status === 'Ready',
                                    'border-zinc-200' => !$schedule || $schedule->status === 'Scheduled',
                                    'border-zinc-500' => $schedule && $schedule->status === 'Finished',
                                ])>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-zinc-500">M{{ $bm->position }}</span>
                                        @if ($schedule)
                                            <span @class([
                                                'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium',
                                                'bg-green-100 text-green-800' => $schedule->status === 'Playing',
                                                'bg-yellow-100 text-yellow-800' => $schedule->status === 'Waiting Result',
                                                'bg-blue-100 text-blue-800' => $schedule->status === 'Ready',
                                                'bg-zinc-100 text-zinc-600' => $schedule->status === 'Scheduled',
                                                'bg-zinc-800 text-white' => $schedule->status === 'Finished',
                                            ])>{{ $schedule->status }}</span>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <div class="rounded bg-red-50 px-2 py-0.5 font-medium text-red-800 {{ $winnerName === $nameA ? 'ring-2 ring-yellow-400' : '' }}">
                                            @if ($nameA === 'TBD')<span class="text-zinc-400 italic">TBD</span>@else{{ $nameA }}@endif
                                        </div>
                                        <div class="rounded bg-blue-50 px-2 py-0.5 font-medium text-blue-800 {{ $winnerName === $nameB ? 'ring-2 ring-yellow-400' : '' }}">
                                            @if ($nameB === 'TBD')<span class="text-zinc-400 italic">TBD</span>@else{{ $nameB }}@endif
                                        </div>
                                    </div>
                                    @if ($winnerName)
                                        <div class="mt-1 text-[10px] font-semibold text-yellow-600">🏆 {{ $winnerName }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-6 rounded-xl border border-dashed border-zinc-200 p-10 text-center">
                <p class="text-sm text-zinc-500">Belum ada bracket.</p>
            </div>
        @endif
    </div>
</x-layouts.public>
