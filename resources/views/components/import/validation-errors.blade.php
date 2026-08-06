@props(['errors' => []])

@if (count($errors))
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
        <p class="text-sm font-medium text-red-700 dark:text-red-400">
            {{ count($errors) }} baris memiliki kesalahan validasi:
        </p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600 dark:text-red-400">
            @foreach ($errors as $error)
                <li>
                    Baris {{ $error['row'] }}:
                    @foreach ($error['errors'] as $msg)
                        <span class="ml-1">{{ $msg }}</span>
                    @endforeach
                </li>
            @endforeach
        </ul>
    </div>
@endif
