<div>
    <div class="mb-4">
        <flux:input wire:model.live="search" placeholder="Cari nama atau NIP..." />
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Kelamin</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">NIP</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Tanggal Lahir</th>
                        <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($people as $person)
                    <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-6 py-2 font-medium text-zinc-900 dark:text-white">{{ $people->firstItem() + $loop->index }}</td>
                        <td class="px-6 py-2 font-medium text-zinc-900 dark:text-white">{{ $person->nama }}</td>
                        <td class="px-6 py-2">{{ $person->jenis_kelamin_label }}</td>
                        <td class="px-6 py-2">{{ $person->desa?->desa_asal ?? '-' }}</td>
                        <td class="px-6 py-2">{{ $person->kelompok?->kelompok_asal ?? '-' }}</td>
                        <td class="px-6 py-2">{{ $person->nip ?? '-' }}</td>
                        <td class="px-6 py-2">{{ $person->tanggal_lahir?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-6 py-2 space-x-2">
                            <flux:button wire:click="edit({{ $person->id }})">Edit</flux:button>
                            <flux:button variant="danger" wire:click="delete({{ $person->id }})">Hapus</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
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
