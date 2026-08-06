@props([
    'processing' => false,
    'message' => 'Memproses…',
])

@if ($processing)
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-400">
        <span class="inline-block size-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span>
        {{ $message }}
    </div>
@endif
