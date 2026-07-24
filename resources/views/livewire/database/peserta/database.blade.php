<div class="space-y-4">
    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Cari nama atau nomor peserta..."
            icon="magnifying-glass"
        />
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">No. Peserta</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Kelamin</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Jenis Peserta</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Status Registrasi</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Regu</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @if(count($daftarPeserta))
                    @foreach($daftarPeserta as $peserta)
                        <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $peserta->nama }}</td>
                            <td class="px-4 py-3">{{ $peserta->participant_number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $peserta->jenis_kelamin }}</td>
                            <td class="px-4 py-3">{{ $peserta->jenis_peserta }}</td>
                            <td class="px-4 py-3">{{ $peserta->status_registrasi_label }}</td>
                            <td class="px-4 py-3">{{ $peserta->desa->desa_asal ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $peserta->kelompok->kelompok_asal ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $peserta->regu->regu ?? '-' }}</td>
                            <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <flux:button wire:click="edit({{ $peserta->id }})">
                                    Edit
                                </flux:button>

                                <flux:button wire:click="ganti({{ $peserta->id }})">
                                    Ganti
                                </flux:button>

                                <flux:button
                                    variant="danger"
                                    wire:click="delete({{ $peserta->id }})"
                                >
                                    Delete
                                </flux:button>
                            </div>
                        </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
