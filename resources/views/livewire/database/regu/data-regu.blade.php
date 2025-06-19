<div class="overflow-x-auto\">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400\">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400\">
            <tr>
                <th scope="col" class="px-8 py-4 border-b">ID</th>
                <th scope="col" class="px-8 py-4 border-b">Regu</th>
                <th scope="col" class="px-8 py-4 border-b">Edit</th>
            </tr>
        </thead>
        <tbody>
        @foreach($daftarregu as $regu)
            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200 border-b">
                <td class="px-8 py-2 font-medium text-gray-900 dark:text-white\">{{ $regu->id }}</td>
                <td class="px-8 py-2 text-gray-800 dark:text-gray-400\">{{ $regu->regu }}</td>
                <td class="px-8 py-2 space-x-5">
                    <flux:button wire:click="edit({{ $regu->id }})">Edit</flux:button>
                    <flux:button variant="danger" wire:click="delete({{ $regu->id}})">Delete</flux:button>
                </td>
                </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>