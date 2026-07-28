<x-layouts.public>
    {{-- Hero --}}
    <section class="bg-gradient-to-br from-blue-600 to-indigo-800 text-white">
        <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6 sm:py-24">
            <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">Selamat Datang di Event KJA</h1>
            <p class="mt-4 text-lg text-blue-100 sm:text-xl">Portal informasi resmi untuk seluruh event dan kompetisi KJA.</p>
        </div>
    </section>

    {{-- Search --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 -mt-6">
        <form action="{{ route('public.home') }}" method="GET" class="mx-auto max-w-xl">
            <input type="text" name="search" placeholder="Cari event..." value="{{ request('search') }}"
                   class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-lg focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </form>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        {{-- Running Events --}}
        @if ($running->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-zinc-900">Sedang Berlangsung</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($running as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-green-200 bg-green-50 p-5 transition hover:shadow-md">
                            <span class="inline-flex items-center rounded-full bg-green-200 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                            <h3 class="mt-2 text-lg font-bold text-zinc-900">{{ $event->name }}</h3>
                            @if ($event->venue)<p class="mt-1 text-sm text-zinc-500">{{ $event->venue }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Upcoming Events --}}
        @if ($upcoming->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-zinc-900">Akan Datang</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-zinc-200 bg-white p-5 transition hover:shadow-md">
                            <h3 class="text-lg font-bold text-zinc-900">{{ $event->name }}</h3>
                            @if ($event->start_date)<p class="mt-1 text-sm text-zinc-500">{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Finished Events --}}
        @if ($finished->isNotEmpty())
            <section>
                <h2 class="mb-6 text-2xl font-bold text-zinc-900">Event Sebelumnya</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($finished as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-zinc-200 bg-white p-5 transition hover:shadow-md">
                            <span class="inline-flex items-center rounded-full bg-zinc-200 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">Selesai</span>
                            <h3 class="mt-2 text-lg font-bold text-zinc-900">{{ $event->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($running->isEmpty() && $upcoming->isEmpty() && $finished->isEmpty())
            <div class="py-16 text-center">
                <p class="text-zinc-500">Belum ada event.</p>
            </div>
        @endif
    </div>
</x-layouts.public>
