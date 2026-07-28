<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Pendaftaran</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Daftar peserta terdaftar.</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-4">
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
        <flux:select wire:model.live="filterGender" placeholder="Semua Gender">
            <flux:select.option value="L">Laki - Laki</flux:select.option>
            <flux:select.option value="P">Perempuan</flux:select.option>
        </flux:select>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama..." />
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No. Peserta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Gender</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Desa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Kelompok</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Tgl Daftar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($registrations as $reg)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $reg->participation?->participant_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $reg->participation?->person?->nama ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->competitionCategory?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->competitionClass?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->participation?->person?->jenis_kelamin_label ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->participation?->person?->desa?->desa_asal ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->participation?->person?->kelompok?->kelompok_asal ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $reg->created_at?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada pendaftaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
