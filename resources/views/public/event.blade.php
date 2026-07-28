<x-layouts.public>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <a href="{{ route('public.home') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Semua Event</a>

        <h1 class="mt-4 text-3xl font-bold text-zinc-900 sm:text-4xl">{{ $event->name }}</h1>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('public.schedule', $event) }}" class="rounded-xl border border-zinc-200 bg-white p-5 text-center transition hover:shadow-md">
                <div class="text-2xl font-bold text-blue-600">{{ $scheduleCount }}</div>
                <div class="mt-1 text-sm text-zinc-500">Jadwal</div>
            </a>
            @if ($bracketCount > 0)
            <a href="{{ route('public.bracket', $event) }}" class="rounded-xl border border-zinc-200 bg-white p-5 text-center transition hover:shadow-md">
                <div class="text-2xl font-bold text-indigo-600">{{ $bracketCount }}</div>
                <div class="mt-1 text-sm text-zinc-500">Bracket</div>
            </a>
            @endif
            @if ($announcementCount > 0)
            <a href="{{ route('public.announcements', $event) }}" class="rounded-xl border border-zinc-200 bg-white p-5 text-center transition hover:shadow-md">
                <div class="text-2xl font-bold text-amber-600">{{ $announcementCount }}</div>
                <div class="mt-1 text-sm text-zinc-500">Pengumuman</div>
            </a>
            @endif
        </div>

        @if ($event->description)
            <div class="mt-8 prose prose-zinc max-w-none">
                <p>{{ $event->description }}</p>
            </div>
        @endif
    </div>
</x-layouts.public>
