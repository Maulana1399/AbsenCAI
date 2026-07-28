<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Jadwal</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Daftar jadwal perlombaan.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <flux:select wire:model.live="filterVenueId" placeholder="Semua Venue">
            @foreach ($venues as $venue)
                <flux:select.option value="{{ $venue->id }}">{{ $venue->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterStatus" placeholder="Semua Status">
            <flux:select.option value="Scheduled">Scheduled</flux:select.option>
            <flux:select.option value="Ready">Ready</flux:select.option>
            <flux:select.option value="Playing">Playing</flux:select.option>
            <flux:select.option value="Finished">Finished</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Venue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Mulai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Selesai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($schedules as $schedule)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->competitionClass?->competitionCategory?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $schedule->competitionClass?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->venue?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->start_at ? \Carbon\Carbon::parse($schedule->start_at)->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $schedule->end_at ? \Carbon\Carbon::parse($schedule->end_at)->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-zinc-100 text-zinc-800' => $schedule->status === 'Scheduled',
                                'bg-blue-100 text-blue-800' => $schedule->status === 'Ready',
                                'bg-green-100 text-green-800' => $schedule->status === 'Playing',
                                'bg-zinc-800 text-white' => $schedule->status === 'Finished',
                            ])>{{ $schedule->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada jadwal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
