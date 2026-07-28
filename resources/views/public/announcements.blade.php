<x-layouts.public>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
        <a href="{{ route('public.event', $event) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; {{ $event->name }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 sm:text-3xl">Pengumuman</h1>

        <div class="mt-6 space-y-4">
            @forelse ($announcements as $announcement)
                <div class="rounded-xl border border-zinc-200 bg-white p-5">
                    <p class="text-zinc-900">{{ $announcement->message }}</p>
                    <p class="mt-2 text-xs text-zinc-500">{{ $announcement->created_at->format('d M Y H:i') }}</p>
                </div>
            @empty
                <div class="py-12 text-center text-zinc-500">Belum ada pengumuman.</div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $announcements->links() }}
        </div>
    </div>
</x-layouts.public>
