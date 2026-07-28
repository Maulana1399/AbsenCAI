<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Ringkasan</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ringkasan kompetisi.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Kategori</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total_categories }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Kelas</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total_classes }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Pendaftaran</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total_registrations }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Venue</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total_venues }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Jadwal</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $total_schedules }}</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Scheduled</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $scheduled }}</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
            <div class="text-sm text-blue-600 dark:text-blue-400">Ready</div>
            <div class="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $ready }}</div>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <div class="text-sm text-green-600 dark:text-green-400">Playing</div>
            <div class="mt-1 text-2xl font-bold text-green-800 dark:text-green-200">{{ $playing }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Finished</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $finished }}</div>
        </div>
    </div>

    @if ($active_announcements > 0)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200">
            <strong>{{ $active_announcements }}</strong> pengumuman aktif.
        </div>
    @endif
</div>
