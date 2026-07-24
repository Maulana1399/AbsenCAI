<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Kelamin</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($daftarregu as $regu)
                <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3">{{ $regu->regu }}</td>
                    <td class="px-4 py-3">{{ $regu->jenis_kelamin ?? '-' }}</td>
                    <td class="px-4 py-3 space-x-2">
                        <flux:button wire:click="edit({{ $regu->id }})">Edit</flux:button>
                        <flux:button variant="danger" wire:click="delete({{ $regu->id }})">Delete</flux:button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
