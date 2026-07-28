<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Hasil</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Hasil perlombaan peserta.</p>
    </div>

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

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Peserta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Posisi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Skor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($outcomes as $outcome)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $outcome->competitionRegistration?->competitionCategory?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $outcome->competitionRegistration?->competitionClass?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $outcome->competitionRegistration?->participation?->person?->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $outcome->position ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $outcome->score ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($outcome->status)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ $outcome->status }}</span>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $outcome->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada hasil.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
