<x-layouts.public>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        <a href="{{ route('public.event', $event) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; {{ $event->name }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 sm:text-3xl">Jadwal Pertandingan</h1>

        <form method="GET" class="mt-6 grid gap-3 sm:grid-cols-3">
            <select name="venue_id" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <option value="">Semua Venue</option>
                @foreach ($venues as $venue)
                    <option value="{{ $venue->id }}" @selected(request('venue_id') == $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
            <select name="class_id" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="Scheduled" @selected(request('status') === 'Scheduled')>Scheduled</option>
                <option value="Ready" @selected(request('status') === 'Ready')>Ready</option>
                <option value="Playing" @selected(request('status') === 'Playing')>Playing</option>
                <option value="Waiting Result" @selected(request('status') === 'Waiting Result')>Waiting Result</option>
                <option value="Finished" @selected(request('status') === 'Finished')>Finished</option>
            </select>
            <div class="sm:col-span-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filter</button>
            </div>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($schedules as $schedule)
                @php
                    $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter();
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-zinc-500">{{ $schedule->competitionClass?->competitionCategory?->name ?? '' }}</div>
                            <div class="font-semibold text-zinc-900">{{ $schedule->competitionClass?->name ?? '-' }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                @if ($schedule->venue)<span>{{ $schedule->venue->name }}</span>@endif
                                @if ($schedule->start_at)<span>{{ \Carbon\Carbon::parse($schedule->start_at)->format('d/m/Y H:i') }}</span>@endif
                            </div>
                            @if ($participants->isNotEmpty())
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    @foreach ($participants as $name)
                                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700">{{ $name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-zinc-100 text-zinc-700' => $schedule->status === 'Scheduled',
                            'bg-blue-100 text-blue-700' => $schedule->status === 'Ready',
                            'bg-green-100 text-green-700' => $schedule->status === 'Playing',
                            'bg-yellow-100 text-yellow-700' => $schedule->status === 'Waiting Result',
                            'bg-zinc-700 text-white' => $schedule->status === 'Finished',
                        ])>{{ $schedule->status }}</span>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-zinc-500">Tidak ada jadwal.</div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    </div>
</x-layouts.public>
