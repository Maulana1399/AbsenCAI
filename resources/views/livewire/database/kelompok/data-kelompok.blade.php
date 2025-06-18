<div class="overflow-x-auto">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-8 py-4 border-b">ID</th>
                <th scope="col" class="px-8 py-4 border-b">Kelompok</th>
                <th scope="col" class="px-8 py-4 border-b">Desa</th>
                <th scope="col" class="px-8 py-4 border-b">Edit</th>
            </tr>
        </thead>
        <tbody>
        @foreach($daftarkelompok as $kelompok)
            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200 border-b">
                <td class="px-8 py-2 font-medium text-gray-900 dark:text-white">{{ $kelompok->id }}</td>
                <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $kelompok->kelompok_asal }}</td>
                <td class="px-8 py-2 text-gray-800 dark:text-gray-400">{{ $kelompok->desa->desa_asal ?? '-'  }}</td>
                <td class="px-8 py-2 space-x-5">
                    <button class="cursor-pointer px-4 py-2 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-400 dark:bg-blue-800 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Edit
                    </button>
                    <button class="cursor-pointer px-4 py-2 text-xs font-medium text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-400 dark:bg-red-800 dark:hover:bg-red-700 dark:focus:ring-red-800 ml-2">
                        Delete
                    </button>
                </td>
                </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>