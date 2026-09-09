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
                <flux:select wire:model.live="filterClassId" placeholder="Pilih kelas">
                    @foreach ($classes->whereNotIn('id', $classIdsWithBrackets) as $class)
                        <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($classes->every(fn ($c) => in_array($c->id, $classIdsWithBrackets)))
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Semua kelas sudah memiliki bracket.</p>
                @endif
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
            <div class="flex items-center gap-2 pb-2">
                <flux:checkbox wire:model="thirdPlaceMatch" id="thirdPlaceMatch" />
                <label for="thirdPlaceMatch" class="text-sm text-zinc-700 dark:text-zinc-300">Perebutan Juara 3</label>
            </div>
            @php
                $selectedClass = $filterClassId ? $classes->firstWhere('id', (int) $filterClassId) : null;
                $regCount = $selectedClass ? \App\Models\CompetitionRegistration::where('competition_class_id', $selectedClass->id)->count() : 0;
                $suggestedSize = $regCount <= 4 ? 4 : ($regCount <= 8 ? 8 : ($regCount <= 16 ? 16 : 32));
            @endphp
            <flux:button wire:click="generate({{ $filterClassId }})" variant="primary" :disabled="!$filterClassId">
                Generate
            </flux:button>
        </div>
        @if ($selectedClass && $regCount > 0)
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $regCount }} peserta terdaftar — ukuran bracket disarankan: {{ $suggestedSize }}
            </p>
        @endif
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

    {{-- Bracket Actions --}}
    @can('manage-events')
    @if ($selectedBracket)
        @php
            $scheduleIds = $selectedBracket->bracketMatches->pluck('competition_schedule_id');
            $hasPlayed = \App\Models\CompetitionSchedule::whereIn('id', $scheduleIds)
                ->where('status', '!=', 'Scheduled')
                ->exists();
        @endphp
        <div class="flex flex-wrap items-center gap-3">
            @if ($hasPlayed)
                <span class="inline-flex items-center rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 cursor-not-allowed" title="Tidak dapat menghapus karena sudah ada pertandingan yang dimainkan">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Hapus
                </span>
                <span class="inline-flex items-center rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 cursor-not-allowed" title="Tidak dapat membuat ulang karena sudah ada pertandingan yang dimainkan">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Buat Ulang
                </span>
                <p class="text-xs text-zinc-400 dark:text-zinc-500">Bracket sudah memiliki pertandingan yang dimainkan dan tidak dapat dihapus.</p>
            @else
                <flux:button wire:click="deleteBracket({{ $selectedBracket->id }})" variant="danger" size="sm" wire:confirm="Hapus bracket ini?">
                    Hapus
                </flux:button>
                <flux:button wire:click="regenerateBracket({{ $selectedBracket->id }})" variant="primary" size="sm">
                    Buat Ulang
                </flux:button>
            @endif
        </div>
    @endif
    @endcan

    {{-- Podium Final (Juara 1/2/3, atau 1/2/3/4 jika Perebutan Juara 3) --}}
    @if ($selectedBracket && ! empty($podium))
        <div @class(['grid gap-3', $selectedBracket->third_place_match ? 'sm:grid-cols-4' : 'sm:grid-cols-3'])>
            @foreach ($podium as $entry)
                <div class="rounded-xl border p-4 text-center {{ $entry['position'] === 1 ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40' : ($entry['position'] === 2 ? 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900' : 'border-orange-200 bg-orange-50/60 dark:border-orange-800 dark:bg-orange-950/30') }}">
                    <p class="text-xs font-medium uppercase tracking-wide {{ $entry['position'] === 1 ? 'text-amber-600 dark:text-amber-400' : ($entry['position'] === 2 ? 'text-zinc-500' : 'text-orange-600 dark:text-orange-400') }}">
                        Juara {{ $entry['position'] }}
                    </p>
                    <p class="mt-1 truncate font-semibold text-zinc-900 dark:text-white">{{ $entry['person_name'] ?? $entry['team_name'] ?? '-' }}</p>
                    @if (! empty($entry['participant_number'] ?? null))
                        <p class="text-xs text-zinc-500">{{ $entry['participant_number'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($selectedBracket->third_place_match)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Juara 1 = pemenang Final &middot; Juara 2 = runner-up Final &middot; Juara 3 = pemenang <strong>Perebutan Juara 3</strong> (semifinal loser) &middot; Juara 4 = runner-up Perebutan Juara 3.
            </p>
        @else
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Juara 1 = pemenang Final &middot; Juara 2 = runner-up Final &middot; Juara 3 ditentukan otomatis dari <strong>semifinal losers</strong> (seri peringkat 3, tanpa perebutan Juara 3).
            </p>
        @endif
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
                                $nameA = $participantA?->competitionRegistration?->participation?->person?->nama ?? $participantA?->team?->name ?? 'TBD';
                                $nameB = $participantB?->competitionRegistration?->participation?->person?->nama ?? $participantB?->team?->name ?? 'TBD';
                                $isFinished = $schedule && $schedule->status === 'Finished';
                                $winner = $isFinished ? $schedule?->winner : null;
                                $winnerName = $winner?->participation?->person?->nama ?? $schedule?->winnerTeam?->name ?? null;
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
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $schedule->status === 'Waiting Result',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $schedule->status === 'Scheduled',
                                            'bg-zinc-800 text-white dark:bg-black dark:text-zinc-300' => $schedule->status === 'Finished',
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
