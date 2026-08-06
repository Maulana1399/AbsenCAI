@props([
    'title',
    'description' => null,
    'step' => 1,
    'steps' => [],
])

<div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
        {{ $title }}
    </h2>

    @if ($description)
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @if (count($steps) > 1)
        <ol class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">
            @foreach ($steps as $index => $label)
                <li class="flex items-center gap-2">
                    <span @class([
                        'inline-flex size-5 items-center justify-center rounded-full text-[10px] font-medium',
                        'bg-emerald-600 text-white' => $index + 1 < $step,
                        'bg-blue-600 text-white' => $index + 1 === $step,
                        'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' => $index + 1 > $step,
                    ])>
                        {{ $index + 1 }}
                    </span>
                    <span @class([
                        'font-medium',
                        'text-zinc-900 dark:text-white' => $index + 1 === $step,
                        'text-zinc-500 dark:text-zinc-400' => $index + 1 !== $step,
                    ])>
                        {{ $label }}
                    </span>
                    @if (! $loop->last)
                        <span class="text-zinc-300 dark:text-zinc-600">›</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    {{ $slot }}
</div>
