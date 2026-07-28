<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Bracket Manager</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Generate and view single elimination brackets.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ session('error') }}</div>
    @endif

    {{-- Generate Section --}}
    @can('manage-events')
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Generate Bracket</h2>
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelas</label>
                <flux:select wire:model="filterClassId" placeholder="Pilih kelas">
                    @foreach ($classes as $class)
                        <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Peserta</label>
                <flux:select wire:model="newParticipantCount">
                    <flux:select.option value="4">4</flux:select.option>
                    <flux:select.option value="8">8</flux:select.option>
                    <flux:select.option value="16">16</flux:select.option>
                    <flux:select.option value="32">32</flux:select.option>
                </flux:select>
            </div>
            <flux:button wire:click="generate({{ $filterClassId }})" variant="primary" :disabled="!$filterClassId">
                Generate
            </flux:button>
        </div>
    </div>
    @endcan

    {{-- Existing Brackets --}}
    @if ($brackets->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach ($brackets as $b)
                <button wire:click="selectBracket({{ $b->id }})"
                        @class([
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            'bg-zinc-600 text-white' => $selectedBracketId === $b->id,
                            'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $selectedBracketId !== $b->id,
                        ])>
                    {{ $b->name }} ({{ $b->participant_count }})
                </button>
            @endforeach
        </div>
    @endif

    {{-- Bracket Display --}}
    @if ($selectedBracket && $bracketRounds)
        <div class="overflow-x-auto pb-4">
            <div class="flex gap-6 min-w-max">
                @foreach ($bracketRounds as $roundData)
                    <div class="flex flex-col justify-around gap-4 min-w-[200px]">
                        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 text-center uppercase tracking-wider">
                            {{ $roundData['label'] }}
                        </h3>
                        @foreach ($roundData['matches'] as $bm)
                            @php
                                $schedule = $bm->schedule;
                                $entries = $schedule?->scheduleEntries ?? collect();
                                $participantA = $entries->first();
                                $participantB = $entries->skip(1)->first();
                                $nameA = $participantA?->competitionRegistration?->participation?->person?->nama ?? 'TBD';
                                $nameB = $participantB?->competitionRegistration?->participation?->person?->nama ?? 'TBD';
                                $winner = $schedule?->winner;
                                $winnerName = $winner?->participation?->person?->nama ?? null;
                            @endphp
                            <div @class([
                                'rounded-lg border-2 p-3 text-xs min-w-[180px]',
                                'bg-white dark:bg-zinc-950',
                                'border-green-400 dark:border-green-600' => $schedule && $schedule->status === 'Playing',
                                'border-yellow-400 dark:border-yellow-600' => $schedule && $schedule->status === 'Waiting Result',
                                'border-blue-300 dark:border-blue-700' => $schedule && $schedule->status === 'Ready',
                                'border-zinc-200 dark:border-zinc-700' => !$schedule || $schedule->status === 'Scheduled',
                                'border-zinc-500 dark:border-zinc-500' => $schedule && $schedule->status === 'Finished',
                            ])>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold text-zinc-500 dark:text-zinc-400">M{{ $bm->position }}</span>
                                    @if ($schedule)
                                        <span @class([
                                            'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium',
                                            'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' => $schedule->status === 'Waiting Result',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $schedule->status === 'Scheduled',
                                            'bg-zinc-700 text-white dark:bg-zinc-600' => $schedule->status === 'Finished',
                                        ])>{{ $schedule->status }}</span>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    <div @class([
                                        'rounded px-2 py-0.5 font-medium',
                                        'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200' => true,
                                        'ring-2 ring-yellow-400' => $winnerName === $nameA,
                                    ])>
                                        @if ($nameA === 'TBD')
                                            <span class="text-zinc-400 italic">TBD</span>
                                        @else
                                            {{ $nameA }}
                                        @endif
                                    </div>
                                    <div @class([
                                        'rounded px-2 py-0.5 font-medium',
                                        'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' => true,
                                        'ring-2 ring-yellow-400' => $winnerName === $nameB,
                                    ])>
                                        @if ($nameB === 'TBD')
                                            <span class="text-zinc-400 italic">TBD</span>
                                        @else
                                            {{ $nameB }}
                                        @endif
                                    </div>
                                </div>
                                @if ($winnerName)
                                    <div class="mt-1 text-[10px] font-semibold text-yellow-600 dark:text-yellow-400">
                                        🏆 {{ $winnerName }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @elseif ($selectedBracketId)
        <div class="rounded-xl border border-dashed border-zinc-200 p-10 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Bracket not found.</p>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-200 p-10 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Select or generate a bracket to view.</p>
        </div>
    @endif
</div>
