<div>
    <div class="mb-4 flex gap-2">
        <input type="text" wire:model.defer="search" placeholder="Cari nama atau NIP peserta..." class="border rounded px-3 py-2 w-64" />
        <button wire:click="cari" class="px-3 py-2 bg-blue-500 text-white rounded">Search</button>
    </div>
    {{-- <div>Search: {{ $search }}</div> --}}
    <div class="overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-8 py-4 border-b">Nama</th>
                <th scope="col" class="px-8 py-4 border-b">NIP</th>
                <th scope="col" class="px-8 py-4 border-b">Jenis Kelamin</th>
                <th scope="col" class="px-8 py-4 border-b">Desa</th>
                <th scope="col" class="px-8 py-4 border-b">Kelompok</th>
                <th scope="col" class="px-8 py-4 border-b">Regu</th>
                <th scope="col" class="px-8 py-4 border-b">Detail</th>
            </tr>
        </thead>
        <tbody>
        @if(count($daftarPeserta))
            @foreach($daftarPeserta as $peserta)
                <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200 border-b">
                    <td class="px-8 py-2 font-medium text-gray-900 dark:text-white">{{ $peserta->nama }}</td>
                    <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $peserta->nip }}</td>
                    <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $peserta->jenis_kelamin }}</td>
                    <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $peserta->desa->desa_asal ?? '-'   }}</td>
                    <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $peserta->kelompok->kelompok_asal ?? '-'    }}</td>
                    <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $peserta->regu->regu ?? '-'    }}</td>
                    <td class="px-8 py-2 space-x-5">
                        <flux:button wire:click="edit({{ $peserta->id }})">Edit</flux:button>
                        <flux:button variant="danger" wire:click="delete({{ $peserta->id }})">Delete</flux:button>
                    </td>
                    </tr>
            @endforeach
        @endif
        </tbody>
    </table>
</div>
</div>
