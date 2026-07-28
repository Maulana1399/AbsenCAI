<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Official Panel</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kirim hasil pertandingan yang ditugaskan kepada Anda.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ session('error') }}</div>
    @endif

    {{-- Waiting Matches --}}
    <div>
        <h2 class="mb-3 text-lg font-bold text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
            <span class="inline-block h-3 w-3 rounded-full bg-yellow-500"></span>
            Menunggu Hasil ({{ $waitingMatches->count() }})
        </h2>

        @if ($waitingMatches->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-700">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Tidak ada pertandingan yang menunggu hasil.</p>
            </div>
        @else
            <div class="grid gap-3">
                @foreach ($waitingMatches as $schedule)
                    @php $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
                    <div class="rounded-xl border-2 border-yellow-300 bg-white p-5 dark:border-yellow-600 dark:bg-zinc-950">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $schedule->competitionClass?->competitionCategory?->name ?? '' }}</div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white truncate">{{ $schedule->competitionClass?->name ?? '-' }}</h3>
                                @if ($schedule->venue)
                                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $schedule->venue->name }}</div>
                                @endif
                                @if ($participants->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        @foreach ($participants as $i => $name)
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-sm font-semibold
                                                {{ $i === 0 ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900/50 dark:text-red-200' : '' }}
                                                {{ $i === 1 ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/50 dark:text-blue-200' : '' }}">
                                                {{ $name }}
                                            </span>
                                            @if ($i === 0 && $participants->count() > 1)
                                                <span class="text-base font-bold text-zinc-400">VS</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <flux:button wire:click="openSubmitDialog({{ $schedule->id }})" variant="primary" class="shrink-0">
                                Kirim Hasil
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Submit Dialog --}}
    @if ($showSubmitDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Kirim Hasil Pertandingan</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tentukan pemenang dan alasan penyelesaian.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pemenang</label>
                        <div class="space-y-2">
                            @foreach ($availableParticipants as $participant)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition
                                    {{ $selectedWinnerId === $participant['id'] ? 'border-blue-500 bg-blue-50 dark:border-blue-600 dark:bg-blue-950' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}">
                                    <input type="radio" name="winner" value="{{ $participant['id'] }}"
                                           wire:model="selectedWinnerId" class="h-4 w-4 text-blue-600">
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $participant['name'] }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">#{{ $participant['number'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedWinnerId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Alasan</label>
                        <flux:select wire:model="finishReason" placeholder="Pilih alasan">
                            <flux:select.option value="">Pilih alasan</flux:select.option>
                            <flux:select.option value="Normal">Normal</flux:select.option>
                            <flux:select.option value="Walk Over (WO)">Walk Over (WO)</flux:select.option>
                            <flux:select.option value="Disqualification (DQ)">Disqualification (DQ)</flux:select.option>
                            <flux:select.option value="Cancel">Cancel</flux:select.option>
                        </flux:select>
                        @error('finishReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Catatan <span class="text-zinc-400">(opsional)</span></label>
                        <textarea wire:model="finishNotes" rows="3" placeholder="Catatan tambahan..."
                                  class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500"></textarea>
                        @error('finishNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <flux:button wire:click="cancelSubmitDialog" variant="ghost">Batal</flux:button>
                    <flux:button wire:click="submitResult" variant="primary">Kirim Hasil</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Finished History --}}
    @if ($finishedMatches->isNotEmpty())
        <div>
            <h2 class="mb-3 text-lg font-bold text-zinc-800 dark:text-zinc-200">Riwayat Hasil</h2>
            <div class="grid gap-2">
                @foreach ($finishedMatches as $schedule)
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $schedule->competitionClass?->name ?? '-' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $schedule->competitionClass?->competitionCategory?->name }} &middot; {{ $schedule->venue?->name ?? '-' }}</div>
                            </div>
                            @if ($schedule->winner)
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">🏆 {{ $schedule->winner?->participation?->person?->nama ?? '-' }}</span>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $schedule->finish_reason }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
