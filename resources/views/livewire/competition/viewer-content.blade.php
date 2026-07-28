@php
    $firstReady = $ready->first();
    $secondReady = $ready->skip(1)->first();
@endphp

{{-- SEDANG BERLANGSUNG --}}
@if ($playing->isNotEmpty())
    @foreach ($playing as $schedule)
        @php $participants = $schedule->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
        @if ($tvMode)
        <div class="rounded-2xl border-4 border-green-500 bg-green-950 p-8">
            <h2 class="mb-6 text-center text-4xl font-bold text-green-400 uppercase tracking-widest">🔴 Sedang Berlangsung</h2>
            <div class="text-center">
                <div class="text-2xl font-bold text-white">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                <div class="mt-2 text-5xl font-bold text-green-300">{{ $schedule->competitionClass?->name }}</div>
                @if ($schedule->venue)
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-green-600 bg-green-900/50 px-6 py-2 text-2xl text-green-200">
                        {{ $schedule->venue->name }}
                    </div>
                @endif
                @if ($participants->isNotEmpty())
                    <div class="mt-6 flex items-center justify-center gap-6 text-3xl text-white">
                        @foreach ($participants as $i => $name)
                            <span class="rounded-xl border-2 px-6 py-3 font-bold
                                {{ $i === 0 ? 'border-red-500 bg-red-900/50 text-red-200' : '' }}
                                {{ $i === 1 ? 'border-blue-500 bg-blue-900/50 text-blue-200' : '' }}">
                                @if ($i === 0)🔴 @elseif($i === 1)🔵 @endif
                                {{ $name }}
                            </span>
                            @if ($i === 0 && $participants->count() > 1)
                                <span class="text-4xl font-bold text-zinc-500">VS</span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @else
        <div class="mb-4 rounded-xl border-2 border-green-500 bg-green-950 p-4 sm:p-6">
            <h2 class="mb-3 text-center text-lg font-bold text-green-400 uppercase tracking-widest sm:text-2xl">🔴 Sedang Berlangsung</h2>
            <div class="text-center">
                <div class="font-bold text-white sm:text-lg">{{ $schedule->competitionClass?->competitionCategory?->name }}</div>
                <div class="mt-1 text-xl font-bold text-green-300 sm:text-3xl">{{ $schedule->competitionClass?->name }}</div>
                @if ($schedule->venue)
                    <div class="mt-2 text-sm text-zinc-400">{{ $schedule->venue->name }}</div>
                @endif
                @if ($participants->isNotEmpty())
                    <div class="mt-4 flex items-center justify-center gap-3 text-lg sm:text-2xl">
                        @foreach ($participants as $i => $name)
                            <span class="rounded-lg border px-3 py-1.5 font-semibold
                                {{ $i === 0 ? 'border-red-500 bg-red-900/50 text-red-200' : '' }}
                                {{ $i === 1 ? 'border-blue-500 bg-blue-900/50 text-blue-200' : '' }}">
                                {{ $name }}
                            </span>
                            @if ($i === 0 && $participants->count() > 1)
                                <span class="font-bold text-zinc-500">VS</span>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 text-sm text-zinc-400">Belum ada peserta.</div>
                @endif
            </div>
        </div>
        @endif
    @endforeach
@elseif ($firstReady)
    {{-- No Playing: show first Ready as featured --}}
    @php $participants = $firstReady->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
    @if ($tvMode)
    <div class="rounded-2xl border-4 border-blue-500 bg-blue-950 p-8">
        <h2 class="mb-6 text-center text-4xl font-bold text-blue-400 uppercase tracking-widest">🟡 Selanjutnya</h2>
        <div class="text-center">
            <div class="text-2xl font-bold text-white">{{ $firstReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-2 text-5xl font-bold text-blue-300">{{ $firstReady->competitionClass?->name }}</div>
            @if ($firstReady->venue)
                <div class="mt-4 text-2xl text-zinc-300">{{ $firstReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-6 flex items-center justify-center gap-6 text-3xl text-white">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-xl border-2 px-6 py-3 font-bold
                            {{ $i === 0 ? 'border-red-500 bg-red-900/50 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-500 bg-blue-900/50 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="text-4xl font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="mb-4 rounded-xl border-2 border-blue-500 bg-blue-950 p-4 sm:p-6">
        <h2 class="mb-3 text-center text-lg font-bold text-blue-400 uppercase tracking-widest sm:text-2xl">🟡 Selanjutnya</h2>
        <div class="text-center">
            <div class="font-bold text-white sm:text-lg">{{ $firstReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-1 text-xl font-bold text-blue-300 sm:text-3xl">{{ $firstReady->competitionClass?->name }}</div>
            @if ($firstReady->venue)
                <div class="mt-2 text-sm text-zinc-400">{{ $firstReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-4 flex items-center justify-center gap-3 text-lg sm:text-2xl">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-lg border px-3 py-1.5 font-semibold
                            {{ $i === 0 ? 'border-red-500 bg-red-900/50 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-500 bg-blue-900/50 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif
@endif

{{-- SELANJUTNYA (Extra Ready matches beyond the featured one) --}}
@if ($playing->isNotEmpty() && $firstReady)
    {{-- Playing exists: show first Ready as "Selanjutnya" --}}
    @php $participants = $firstReady->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
    @if ($tvMode)
    <div class="rounded-2xl border-2 border-zinc-700 bg-zinc-800 p-6">
        <h2 class="mb-4 text-center text-2xl font-bold text-zinc-400 uppercase tracking-widest">🟡 Selanjutnya</h2>
        <div class="text-center">
            <div class="text-xl font-bold text-white">{{ $firstReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-1 text-3xl font-bold text-blue-300">{{ $firstReady->competitionClass?->name }}</div>
            @if ($firstReady->venue)
                <div class="mt-2 text-xl text-zinc-400">{{ $firstReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-4 flex items-center justify-center gap-4 text-2xl text-white">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-lg border px-4 py-1 font-semibold
                            {{ $i === 0 ? 'border-red-600 bg-red-900/30 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-600 bg-blue-900/30 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="text-2xl font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="rounded-xl border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-3 text-center text-sm font-bold text-zinc-400 uppercase tracking-widest sm:text-xl">🟡 Selanjutnya</h2>
        <div class="rounded-lg border border-zinc-700 bg-zinc-900 p-4 text-center">
            <div class="font-bold text-white">{{ $firstReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-1 text-lg font-bold text-blue-300 sm:text-2xl">{{ $firstReady->competitionClass?->name }}</div>
            @if ($firstReady->venue)
                <div class="mt-1 text-sm text-zinc-400">{{ $firstReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-3 flex items-center justify-center gap-2 text-base sm:text-xl">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-lg border px-2 py-1 font-semibold
                            {{ $i === 0 ? 'border-red-600 bg-red-900/30 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-600 bg-blue-900/30 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif
@elseif (!$playing->isNotEmpty() && $secondReady)
    {{-- No Playing: show second Ready as "Selanjutnya" --}}
    @php $participants = $secondReady->scheduleEntries->map(fn($e) => $e->competitionRegistration?->participation?->person?->nama)->filter(); @endphp
    @if ($tvMode)
    <div class="rounded-2xl border-2 border-zinc-700 bg-zinc-800 p-6">
        <h2 class="mb-4 text-center text-2xl font-bold text-zinc-400 uppercase tracking-widest">🟡 Selanjutnya</h2>
        <div class="text-center">
            <div class="text-xl font-bold text-white">{{ $secondReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-1 text-3xl font-bold text-blue-300">{{ $secondReady->competitionClass?->name }}</div>
            @if ($secondReady->venue)
                <div class="mt-2 text-xl text-zinc-400">{{ $secondReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-4 flex items-center justify-center gap-4 text-2xl text-white">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-lg border px-4 py-1 font-semibold
                            {{ $i === 0 ? 'border-red-600 bg-red-900/30 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-600 bg-blue-900/30 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="text-2xl font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="rounded-xl border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-3 text-center text-sm font-bold text-zinc-400 uppercase tracking-widest sm:text-xl">🟡 Selanjutnya</h2>
        <div class="rounded-lg border border-zinc-700 bg-zinc-900 p-4 text-center">
            <div class="font-bold text-white">{{ $secondReady->competitionClass?->competitionCategory?->name }}</div>
            <div class="mt-1 text-lg font-bold text-blue-300 sm:text-2xl">{{ $secondReady->competitionClass?->name }}</div>
            @if ($secondReady->venue)
                <div class="mt-1 text-sm text-zinc-400">{{ $secondReady->venue->name }}</div>
            @endif
            @if ($participants->isNotEmpty())
                <div class="mt-3 flex items-center justify-center gap-2 text-base sm:text-xl">
                    @foreach ($participants as $i => $name)
                        <span class="rounded-lg border px-2 py-1 font-semibold
                            {{ $i === 0 ? 'border-red-600 bg-red-900/30 text-red-200' : '' }}
                            {{ $i === 1 ? 'border-blue-600 bg-blue-900/30 text-blue-200' : '' }}">
                            {{ $name }}
                        </span>
                        @if ($i === 0 && $participants->count() > 1)
                            <span class="font-bold text-zinc-500">VS</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif
@endif

{{-- Empty State --}}
@if ($playing->isEmpty() && $ready->isEmpty())
    @if ($tvMode)
    <div class="flex flex-1 items-center justify-center">
        <p class="text-2xl text-zinc-500">Belum ada pertandingan.</p>
    </div>
    @else
    <div class="rounded-xl border border-dashed border-zinc-700 p-10 text-center">
        <p class="text-zinc-500">Belum ada pertandingan.</p>
    </div>
    @endif
@endif
