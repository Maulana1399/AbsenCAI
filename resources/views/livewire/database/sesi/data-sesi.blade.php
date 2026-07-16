<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
            <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                <tr>
                    <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">No</th>
                    <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama Sesi</th>
                    <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Tanggal</th>
                    <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Aktif</th>
                    <th class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($daftarSesi as $sesi)
                <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                    <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">{{ $loop->iteration }}</td>
                    <td class="px-6 py-3">{{ $sesi->nama_sesi }}</td>
                    <td class="px-6 py-3">{{ $sesi->tanggal }}</td>
                    <td class="px-6 py-3">
                        @if($sesi->aktif)
                            <span class="rounded-lg bg-green-100 dark:bg-green-900/40 px-2 py-1 text-xs text-green-700 dark:text-green-300">Ya</span>
                        @else
                            <span class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-2 py-1 text-xs text-zinc-500 dark:text-zinc-400">Tidak</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 space-x-2">
                        <flux:button wire:click="edit({{ $sesi->id }})">Edit</flux:button>
                        <flux:button variant="danger" wire:click="delete({{ $sesi->id }})">Hapus</flux:button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
