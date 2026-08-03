<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Jadwal Competition</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kelola pertandingan yang akan dimainkan.</p>
        </div>
        <flux:button wire:click="toggleCreateForm" variant="primary" class="shrink-0">
            {{ $showCreateForm ? 'Batal' : 'Tambah Jadwal' }}
        </flux:button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="space-y-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kelas atau venue..." icon="magnifying-glass" />
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <flux:select wire:model.live="filterCategoryId">
                <flux:select.option value="">Semua Kategori</flux:select.option>
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterClassId">
                <flux:select.option value="">Semua Kelas</flux:select.option>
                @foreach ($filterClasses as $class)
                    <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterVenueId">
                <flux:select.option value="">Semua Venue</flux:select.option>
                @foreach ($venues as $venue)
                    <flux:select.option value="{{ $venue->id }}">{{ $venue->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterStatus">
                <flux:select.option value="">Semua Status</flux:select.option>
                <flux:select.option value="Scheduled">Scheduled</flux:select.option>
                <flux:select.option value="Ready">Ready</flux:select.option>
                <flux:select.option value="Playing">Playing</flux:select.option>
                <flux:select.option value="Finished">Finished</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Create Form --}}
    @if ($showCreateForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Jadwal Baru</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelas</label>
                    <flux:select wire:model="newCompetitionClassId" placeholder="Pilih kelas">
                        @foreach ($allClasses as $class)
                            <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('newCompetitionClassId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Venue</label>
                    <flux:select wire:model="newVenueId" placeholder="Pilih venue">
                        @foreach ($venues as $venue)
                            <flux:select.option value="{{ $venue->id }}">{{ $venue->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('newVenueId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Mulai</label>
                    <flux:input wire:model="newStartAt" type="datetime-local" />
                    @error('newStartAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Selesai</label>
                    <flux:input wire:model="newEndAt" type="datetime-local" />
                    @error('newEndAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                    <flux:select wire:model="newStatus">
                        <flux:select.option value="Scheduled">Scheduled</flux:select.option>
                        <flux:select.option value="Ready">Ready</flux:select.option>
                        <flux:select.option value="Playing">Playing</flux:select.option>
                        <flux:select.option value="Finished">Finished</flux:select.option>
                    </flux:select>
                    @error('newStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Peserta Dibutuhkan</label>
                    <flux:input wire:model="newRequiredParticipants" type="number" min="1" max="99" />
                    @error('newRequiredParticipants') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Urutan</label>
                    <flux:input wire:model="newSortOrder" type="number" placeholder="Urutan (opsional)" />
                    @error('newSortOrder') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Catatan</label>
                    <textarea wire:model="newNotes" placeholder="Catatan (opsional)" rows="2" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                    @error('newNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button wire:click="create" variant="primary" :loading="$processing">Simpan</flux:button>
            </div>
        </div>
    @endif

    {{-- Schedule Cards --}}
    @if ($schedules->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-200 p-10 text-center dark:border-zinc-700">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada jadwal.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($schedules as $schedule)
                {{-- Inline Edit Card --}}
                @if ($editId === $schedule->id)
                    <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/20">
                        <div class="mb-3 text-sm font-semibold text-amber-700 dark:text-amber-300">Edit Jadwal</div>
                        <div class="space-y-3">
                            <flux:select wire:model="editCompetitionClassId" size="sm" placeholder="Kelas">
                                @foreach ($allClasses as $class)
                                    <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="editVenueId" size="sm">
                                <flux:select.option value="">--</flux:select.option>
                                @foreach ($venues as $venue)
                                    <flux:select.option value="{{ $venue->id }}">{{ $venue->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div class="grid grid-cols-2 gap-2">
                                <flux:input wire:model="editStartAt" type="datetime-local" size="sm" />
                                <flux:input wire:model="editEndAt" type="datetime-local" size="sm" />
                            </div>
                            <div class="text-sm">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Status: </span>
                                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $editStatus }}</span>
                            </div>
                            <flux:input wire:model="editRequiredParticipants" type="number" min="1" max="99" size="sm" label="Peserta Dibutuhkan" />
                            <flux:input wire:model="editSortOrder" type="number" size="sm" label="Urutan" />
                            <textarea wire:model="editNotes" placeholder="Catatan" rows="2" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                            <div class="flex gap-2">
                                <flux:button wire:click="update" size="sm" variant="primary">Simpan</flux:button>
                                <flux:button wire:click="cancelEdit" size="sm" variant="ghost">Batal</flux:button>
                                <flux:button wire:click="delete({{ $schedule->id }})" size="sm" variant="danger" class="ml-auto">Hapus</flux:button>
                            </div>
                        </div>
                    </div>

                {{-- Display Card --}}
                @else
                    @php
                        $participantsCount = $schedule->participants_count ?? 0;
                        $required = $schedule->required_participants ?? 1;
                        $isComplete = $participantsCount >= $required;
                    @endphp
                    <div @class([
                        'group relative flex flex-col overflow-hidden rounded-xl border-2 bg-white p-5 transition hover:shadow-md dark:bg-zinc-950',
                        'border-zinc-200 dark:border-zinc-800' => $schedule->status === 'Scheduled',
                        'border-blue-300 dark:border-blue-700' => $schedule->status === 'Ready',
                        'border-green-400 dark:border-green-600' => $schedule->status === 'Playing',
                        'border-zinc-400 dark:border-zinc-600' => $schedule->status === 'Finished',
                    ])>
                        {{-- Top: Category + Venue + Time --}}
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    {{ $schedule->competitionClass?->competitionCategory?->name ?? '' }}
                                </p>
                                <h3 class="mt-0.5 truncate text-base font-bold text-zinc-900 dark:text-white">
                                    🥋 {{ $schedule->competitionClass?->name ?? '-' }}
                                </h3>
                            </div>
                            <span @class([
                                'inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' => $schedule->status === 'Scheduled',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'Playing',
                                'bg-zinc-800 text-white dark:bg-black dark:text-zinc-300' => $schedule->status === 'Finished',
                            ])>
                                @switch($schedule->status)
                                    @case('Scheduled')⬜ @break
                                    @case('Ready')🟦 @break
                                    @case('Playing')🟢 @break
                                    @case('Finished')⬛ @break
                                @endswitch
                                {{ $schedule->status }}
                            </span>
                        </div>

                        {{-- Venue + Date --}}
                        <div class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($schedule->venue)
                                <span>{{ $schedule->venue->name }}</span>
                            @endif
                            @if ($schedule->start_at)
                                <span class="mx-1">&bull;</span>
                                <span>{{ \Carbon\Carbon::parse($schedule->start_at)->format('d/m/Y') }}</span>
                            @endif
                        </div>

                        {{-- Time Range --}}
                        @if ($schedule->start_at || $schedule->end_at)
                            <div class="mb-3 text-xs text-zinc-400 dark:text-zinc-500">
                                <span>{{ $schedule->start_at ? \Carbon\Carbon::parse($schedule->start_at)->format('H:i') : '?' }}</span>
                                <span> - </span>
                                <span>{{ $schedule->end_at ? \Carbon\Carbon::parse($schedule->end_at)->format('H:i') : '?' }}</span>
                            </div>
                        @endif

                        {{-- Divider --}}
                        <hr class="mb-3 border-zinc-200 dark:border-zinc-800">

                        {{-- Participants --}}
                        <div class="mb-1 flex items-center gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Peserta</span>
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' => $isComplete && $schedule->status !== 'Finished',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' => !$isComplete && $schedule->status !== 'Finished',
                                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $schedule->status === 'Finished',
                                    ])>
                                        {{ $participantsCount }} / {{ $required }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($schedule->status === 'Finished')
                                        Pertandingan selesai
                                    @elseif ($isComplete)
                                        Siap dimainkan
                                    @else
                                        Menunggu peserta
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Notes --}}
                        @if ($schedule->notes)
                            <p class="mb-2 text-xs text-zinc-400 dark:text-zinc-500 italic">{{ $schedule->notes }}</p>
                        @endif

                        {{-- Sort order indicator --}}
                        @if ($schedule->sort_order)
                            <p class="mb-3 text-xs text-zinc-400 dark:text-zinc-500">Pertandingan ke-{{ $schedule->sort_order }}</p>
                        @endif

                        {{-- Actions --}}
                        <div class="mt-auto flex flex-wrap gap-1.5 pt-3">
                            @if ($schedule->status === 'Scheduled')
                                <flux:button :href="route('competition.schedule.entries', ['event' => app(\App\Support\ActiveEventContext::class)->current(), 'schedule' => $schedule->id], absolute: false)" size="xs" icon="users" class="flex-1">Peserta</flux:button>
                                <flux:button wire:click="edit({{ $schedule->id }})" size="xs" icon="pencil" variant="ghost">Edit</flux:button>

                            @elseif ($schedule->status === 'Ready')
                                <flux:button :href="route('competition.schedule.entries', ['event' => app(\App\Support\ActiveEventContext::class)->current(), 'schedule' => $schedule->id], absolute: false)" size="xs" icon="users" class="flex-1">Peserta</flux:button>
                                <flux:button :href="route('competition.match-center', ['event' => app(\App\Support\ActiveEventContext::class)->current()], absolute: false)" size="xs" icon="play" variant="primary" class="flex-1">Match Center</flux:button>
                                <flux:button wire:click="edit({{ $schedule->id }})" size="xs" icon="pencil" variant="ghost">Edit</flux:button>

                            @elseif ($schedule->status === 'Playing')
                                <flux:button :href="route('competition.match-center', ['event' => app(\App\Support\ActiveEventContext::class)->current()], absolute: false)" size="xs" icon="play" variant="primary" class="flex-1">Match Center</flux:button>
                                <flux:button :href="route('competition.schedule.outcomes', ['event' => app(\App\Support\ActiveEventContext::class)->current(), 'schedule' => $schedule->id], absolute: false)" size="xs" icon="clipboard-document-list" class="flex-1">Hasil</flux:button>
                                <flux:button wire:click="edit({{ $schedule->id }})" size="xs" icon="pencil" variant="ghost">Edit</flux:button>

                            @elseif ($schedule->status === 'Finished')
                                <flux:button :href="route('competition.schedule.outcomes', ['event' => app(\App\Support\ActiveEventContext::class)->current(), 'schedule' => $schedule->id], absolute: false)" size="xs" icon="clipboard-document-list" class="flex-1">Hasil</flux:button>
                                <flux:button wire:click="edit({{ $schedule->id }})" size="xs" icon="pencil" variant="ghost">Edit</flux:button>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    @endif
</div>
