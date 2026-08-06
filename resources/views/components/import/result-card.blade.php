@props(['result' => []])

@if (isset($result['error']))
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
        <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ $result['error'] }}</p>
    </div>
@else
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <x-import.summary-card label="Berhasil" :value="$result['created'] ?? 0" tone="emerald" />
        <x-import.summary-card label="Duplicate" :value="$result['duplicate'] ?? 0" tone="amber" />
        <x-import.summary-card label="Gagal" :value="$result['failed'] ?? 0" tone="red" />
        <x-import.summary-card label="Warning" :value="$result['warning'] ?? 0" tone="blue" />
        <x-import.summary-card label="Total Baris" :value="$result['total'] ?? 0" tone="zinc" />
    </div>

    @if (! empty($result['errors']))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                {{ count($result['errors']) }} baris gagal:
            </p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600 dark:text-red-400">
                @foreach ($result['errors'] as $error)
                    <li>Baris {{ $error['row'] }}: {{ $error['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
