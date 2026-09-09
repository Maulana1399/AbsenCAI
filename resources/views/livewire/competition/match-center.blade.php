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

    {{-- Status Summary --}}
    <div class="grid grid-cols-4 gap-3">
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-950">
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $countPlaying }}</div>
            <div class="text-xs font-medium uppercase tracking-wide text-green-600 dark:text-green-400">Playing</div>
        </div>
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-center dark:border-yellow-800 dark:bg-yellow-950">
            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $countWaiting }}</div>
            <div class="text-xs font-medium uppercase tracking-wide text-yellow-600 dark:text-yellow-400">Waiting</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-800 dark:bg-blue-950">
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $countReady }}</div>
            <div class="text-xs font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Ready</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $countFinished }}</div>
            <div class="text-xs font-medium uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Finished</div>
        </div>
    </div>

    {{-- Type Filter (Semua / Heat / Bracket) --}}
    <div class="flex flex-wrap gap-2">
        <button wire:click="setFilterType(null)"
                @class([
                    'rounded-full px-4 py-2 text-sm font-medium transition',
                    'bg-zinc-600 text-white' => $filterType === null,
                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $filterType !== null,
                ])>Semua</button>
        <button wire:click="setFilterType('heat')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-medium transition',
                    'bg-violet-600 text-white' => $filterType === 'heat',
                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $filterType !== 'heat',
                ])>Heat</button>
        <button wire:click="setFilterType('bracket')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-medium transition',
                    'bg-orange-600 text-white' => $filterType === 'bracket',
                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' => $filterType !== 'bracket',
                ])>Bracket</button>
    </div>

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

    {{-- Playing Section --}}
    @if ($playing->isNotEmpty())
        <div>
            <h2 class="mb-3 text-lg font-bold text-green-800 dark:text-green-200 flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-green-500"></span>
                Playing
            </h2>
            <div class="grid gap-4">
                @foreach ($playing as $schedule)
                    @include('livewire.competition.match-card', ['schedule' => $schedule])
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tidak ada pertandingan berlangsung.</p>
        </div>
    @endif

    {{-- Waiting Result Section --}}
    @if ($waitingResult->isNotEmpty())
        <div>
            <h2 class="mb-3 text-lg font-bold text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-yellow-500"></span>
                Waiting Result ({{ $waitingResult->count() }})
            </h2>
            <div class="grid gap-3">
                @foreach ($waitingResult as $schedule)
                    @include('livewire.competition.match-card', ['schedule' => $schedule])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Ready Queue --}}
    @if ($ready->isNotEmpty())
        <div>
            <h2 class="mb-3 text-lg font-bold text-blue-800 dark:text-blue-200 flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-blue-500"></span>
                Ready Queue ({{ $ready->count() }})
            </h2>
            <div class="grid gap-3">
                @foreach ($ready as $schedule)
                    @include('livewire.competition.match-card', ['schedule' => $schedule])
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tidak ada pertandingan siap dimainkan.</p>
        </div>
    @endif

    {{-- Official Assignment Dialog --}}
    @if ($showOfficialDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Atur Official</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tambah official untuk pertandingan ini.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Official</label>
                        <flux:select wire:model="newOfficialUserId" placeholder="Pilih official">
                            @foreach ($availableOfficials as $official)
                                <flux:select.option value="{{ $official->id }}">{{ $official->name }} ({{ $official->role }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('newOfficialUserId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Peran</label>
                        <flux:select wire:model="newOfficialRole">
                            <flux:select.option value="referee">Referee</flux:select.option>
                            <flux:select.option value="judge">Judge</flux:select.option>
                            <flux:select.option value="scorer">Scorer</flux:select.option>
                            <flux:select.option value="supervisor">Supervisor</flux:select.option>
                        </flux:select>
                        @error('newOfficialRole') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <flux:button wire:click="closeOfficialDialog" variant="ghost">Tutup</flux:button>
                        <flux:button wire:click="assignOfficial" variant="primary">Tambah</flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
