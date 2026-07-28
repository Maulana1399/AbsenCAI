<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Statistik Kompetisi</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Statistik venue, kategori, dan kelas.</p>
    </div>

    {{-- Venue Statistics --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Statistik Venue</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Venue</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Total Jadwal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Finished</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Ready</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Now Playing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($venueStats as $stat)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $stat->venue->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_schedules }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->finished }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->ready }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->now_playing }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada venue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Category Statistics --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Statistik Kategori</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Pendaftaran</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Selesai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($categoryStats as $stat)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $stat->category->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_classes }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_registrations }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->finished_schedules }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Class Statistics --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Statistik Kelas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Pendaftaran</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Jadwal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Selesai</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Hasil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($classStats as $stat)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->category_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $stat->class->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_registrations }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_schedules }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->finished_schedules }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $stat->total_outcomes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
