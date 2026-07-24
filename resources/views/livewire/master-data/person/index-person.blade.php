<div>
    <div class="space-y-3">
        <flux:input wire:model.live="search" placeholder="Cari nama..." />

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            <select wire:model.live="desaId"
                    class="block w-full sm:w-48 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <option value="">Semua Desa</option>
                @foreach ($desas as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                @endforeach
            </select>

            <select wire:model.live="kelompokId"
                    class="block w-full sm:w-48 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <option value="">Semua Kelompok</option>
                @foreach ($kelompoks as $kelompok)
                    <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                @endforeach
            </select>

            <select wire:model.live="jenisKelamin"
                    class="block w-full sm:w-40 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <option value="">Semua</option>
                <option value="L">Laki-Laki</option>
                <option value="P">Perempuan</option>
            </select>

            <flux:button wire:click="resetFilter" size="sm" variant="ghost">
                Reset Filter
            </flux:button>
        </div>
    </div>

    <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
        {{ $people->total() }} Person{{ $people->total() !== $totalPerson ? ' ditemukan' : '' }}
    </div>

    <div class="mt-3 rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Kelamin</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Tanggal Lahir</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($people as $person)
                    <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $people->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $person->nama }}</td>
                        <td class="px-4 py-3">{{ $person->jenis_kelamin_label }}</td>
                        <td class="px-4 py-3">{{ $person->desa?->desa_asal ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $person->kelompok?->kelompok_asal ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $person->tanggal_lahir?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 space-x-2">
                            <flux:button wire:click="edit({{ $person->id }})">Edit</flux:button>
                            <flux:button variant="danger" wire:click="delete({{ $person->id }})">Hapus</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            Belum ada data Person.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $people->links() }}
    </div>
</div>
