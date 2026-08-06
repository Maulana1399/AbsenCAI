@props([
    'rows' => [],
    'columns' => [],
])

@if (count($rows))
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">#</th>
                    @foreach ($columns as $key => $label)
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-3 py-2 text-zinc-400">{{ $index + 1 }}</td>
                        @foreach ($columns as $key => $label)
                            <td class="px-3 py-2 text-zinc-900 dark:text-white">{{ $row[$key] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
