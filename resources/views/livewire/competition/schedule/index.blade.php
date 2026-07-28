<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Jadwal Competition</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Atur jadwal perlombaan per kelas.</p>
        </div>
        <flux:button wire:click="toggleCreateForm" variant="primary">
            {{ $showCreateForm ? 'Batal' : 'Tambah Jadwal' }}
        </flux:button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="grid gap-3 sm:grid-cols-2">
        <flux:select wire:model.live="filterCategoryId" placeholder="Semua Kategori">
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterClassId" placeholder="Semua Kelas">
            @foreach ($filterClasses as $class)
                <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

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
                        <flux:select.option value="NowPlaying">Now Playing</flux:select.option>
                        <flux:select.option value="Finished">Finished</flux:select.option>
                    </flux:select>
                    @error('newStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Venue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Mulai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Selesai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Peserta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Urutan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($schedules as $schedule)
                    @if ($editId === $schedule->id)
                        <tr class="bg-amber-50 dark:bg-amber-950/20">
                            <td class="px-4 py-2">
                                <flux:select wire:model="editCompetitionClassId" size="sm">
                                    @foreach ($allClasses as $class)
                                        <flux:select.option value="{{ $class->id }}">{{ $class->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-4 py-2">
                                <flux:select wire:model="editVenueId" size="sm">
                                    <flux:select.option value="">--</flux:select.option>
                                    @foreach ($venues as $venue)
                                        <flux:select.option value="{{ $venue->id }}">{{ $venue->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-4 py-2"><flux:input wire:model="editStartAt" type="datetime-local" size="sm" /></td>
                            <td class="px-4 py-2"><flux:input wire:model="editEndAt" type="datetime-local" size="sm" /></td>
                            <td class="px-4 py-2">
                                <flux:select wire:model="editStatus" size="sm">
                                    <flux:select.option value="Scheduled">Scheduled</flux:select.option>
                                    <flux:select.option value="Ready">Ready</flux:select.option>
                                    <flux:select.option value="NowPlaying">Now Playing</flux:select.option>
                                    <flux:select.option value="Finished">Finished</flux:select.option>
                                </flux:select>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $schedule->participants_count ?? 0 }}</td>
                            <td class="px-4 py-2"><flux:input wire:model="editSortOrder" type="number" size="sm" /></td>
                            <td class="px-4 py-2">
                                <div class="flex gap-1">
                                    <flux:button wire:click="update" size="sm" variant="primary">Simpan</flux:button>
                                    <flux:button wire:click="cancelEdit" size="sm" variant="ghost">Batal</flux:button>
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3 text-sm">{{ $schedule->competitionClass?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->venue?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->start_at ? \Carbon\Carbon::parse($schedule->start_at)->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->end_at ? \Carbon\Carbon::parse($schedule->end_at)->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $schedule->status === 'Scheduled',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $schedule->status === 'Ready',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $schedule->status === 'NowPlaying',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => $schedule->status === 'Finished',
                                ])>{{ $schedule->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' => ($schedule->participants_count ?? 0) > 0,
                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' => ($schedule->participants_count ?? 0) === 0,
                                ])>
                                    {{ $schedule->participants_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->sort_order ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-1">
                                    <flux:button :href="route('competition.schedule.entries', $schedule->id)" size="sm" icon-trailing="users">Peserta</flux:button>
                                    <flux:button :href="route('competition.schedule.outcomes', $schedule->id)" size="sm" icon-trailing="clipboard-document-list">Outcome</flux:button>
                                    <flux:button wire:click="edit({{ $schedule->id }})" size="sm" icon-trailing="pencil">Edit</flux:button>
                                    <flux:button wire:click="delete({{ $schedule->id }})" size="sm" variant="danger" icon-trailing="trash">Hapus</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada jadwal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
