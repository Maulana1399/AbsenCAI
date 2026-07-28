<div class="space-y-6">
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</a>
        <span class="mx-1">/</span>
        <a href="{{ route('competition.schedule.index') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-300">Jadwal</a>
        <span class="mx-1">/</span>
        <span class="text-zinc-800 dark:text-zinc-200 font-medium">Outcome</span>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $categoryName }} / {{ $className }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Venue: {{ $venueName }} &middot; Input hasil peserta</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">No. Peserta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Desa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Posisi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Skor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($outcomes as $index => $outcome)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $outcome['person_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $outcome['participant_number'] }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $outcome['desa'] }} / {{ $outcome['kelompok'] }}</td>
                        <td class="px-4 py-2">
                            <flux:input wire:model="outcomes.{{ $index }}.position" type="number" min="0" size="sm" placeholder="-" class="w-16" />
                        </td>
                        <td class="px-4 py-2">
                            <flux:select wire:model="outcomes.{{ $index }}.status" size="sm" class="w-28">
                                <flux:select.option value="">--</flux:select.option>
                                <flux:select.option value="Lolos">Lolos</flux:select.option>
                                <flux:select.option value="Gugur">Gugur</flux:select.option>
                                <flux:select.option value="Diskualifikasi">Diskualifikasi</flux:select.option>
                                <flux:select.option value="Tidak Hadir">Tidak Hadir</flux:select.option>
                            </flux:select>
                        </td>
                        <td class="px-4 py-2">
                            <flux:input wire:model="outcomes.{{ $index }}.score" type="number" step="0.01" min="0" size="sm" placeholder="-" class="w-20" />
                        </td>
                        <td class="px-4 py-2">
                            <flux:input wire:model="outcomes.{{ $index }}.remarks" size="sm" placeholder="-" class="w-32" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada peserta terdaftar di kelas ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (!empty($outcomes))
        <div class="flex justify-end">
            <flux:button wire:click="saveOutcomes" variant="primary">
                Simpan Outcome
            </flux:button>
        </div>
    @endif
</div>
