<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</a>
        <span class="mx-1">/</span>
        <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $eventName }}</span>
    </div>

    {{-- Informasi Event --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $event->name }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->typeLabel() }}</p>
                @if ($event->start_date || $event->end_date)
                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $event->start_date?->format('d M Y') ?? '?' }}
                        @if ($event->end_date) – {{ $event->end_date->format('d M Y') }}@endif
                    </p>
                @endif
            </div>
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                {{ ucfirst($event->status) }}
            </span>
        </div>
    </div>

    {{-- Partial per event type (diisi oleh Presenter) --}}
    @include($presenterView, $presenterData)
</div>
