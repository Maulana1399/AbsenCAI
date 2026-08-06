@props([
    'label',
    'value' => 0,
    'tone' => 'zinc',
])

@php
    $tones = [
        'emerald' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950',
        'blue' => 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950',
        'amber' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950',
        'red' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950',
        'purple' => 'border-purple-200 bg-purple-50 dark:border-purple-900 dark:bg-purple-950',
        'zinc' => 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900',
    ];

    $text = [
        'emerald' => 'text-emerald-700 dark:text-emerald-400',
        'blue' => 'text-blue-700 dark:text-blue-400',
        'amber' => 'text-amber-700 dark:text-amber-400',
        'red' => 'text-red-700 dark:text-red-400',
        'purple' => 'text-purple-700 dark:text-purple-400',
        'zinc' => 'text-zinc-700 dark:text-zinc-300',
    ];
@endphp

<div class="rounded-lg border p-3 text-center {{ $tones[$tone] ?? $tones['zinc'] }}">
    <p class="text-xl font-bold {{ $text[$tone] ?? $text['zinc'] }}">{{ $value }}</p>
    <p class="mt-0.5 text-xs {{ $text[$tone] ?? $text['zinc'] }}">{{ $label }}</p>
</div>
