<div class="space-y-6">
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</a>
        <span class="mx-1">/</span>
        <span class="text-zinc-800 dark:text-zinc-200 font-medium">Competition &mdash; {{ $eventName }}</span>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Pendaftaran</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalRegistrations }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Kategori</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalCategories }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Kelas</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalClasses }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Venue</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalVenues }}</div>
        </div>
    </div>
</div>
