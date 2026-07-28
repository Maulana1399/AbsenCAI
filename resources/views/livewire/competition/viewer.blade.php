<div class="min-h-screen bg-zinc-900 text-white"
     x-data="{ showAnnouncement: true }"
     wire:poll.5s
>
    {{-- TV Mode --}}
    @if ($tvMode)
        <div class="flex min-h-screen flex-col">
            <div class="flex-shrink-0 border-b border-zinc-700 bg-zinc-950 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold tracking-wide">{{ $event->name }}</h1>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-zinc-300">{{ $selectedVenue?->name ?? 'Semua Venue' }}</div>
                        <div class="text-sm text-zinc-500">{{ $currentTime }}</div>
                    </div>
                </div>
            </div>
            @if ($announcement && $announcement->is_active)
                <div x-show="showAnnouncement" class="flex-shrink-0 bg-yellow-600 px-6 py-6 text-center"
                     x-init="setTimeout(() => { showAnnouncement = false; $wire.dismissAnnouncement(); }, 10000)">
                    <p class="text-3xl font-bold text-white">{{ $announcement->message }}</p>
                </div>
            @endif
            <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-6">
                @include('livewire.competition.viewer-content')
            </div>
        </div>
    @else
        {{-- Standard / Mobile / Desktop mode --}}
        <div class="mx-auto max-w-4xl p-3 sm:p-6">
            <div class="mb-4 text-center">
                <h1 class="text-xl font-bold tracking-wide sm:text-3xl">{{ $event->name }}</h1>
                <div class="mt-1 flex items-center justify-center gap-3 text-xs text-zinc-500 sm:text-sm">
                    <span>{{ $currentTime }}</span>
                    <span class="inline-block h-2 w-2 rounded-full bg-green-500" title="Auto-refresh 5s"></span>
                    <span>Live</span>
                    @if ($selectedVenue)<span>&middot; {{ $selectedVenue->name }}</span>@endif
                </div>
            </div>

            {{-- Venue Filter --}}
            @if ($venues->isNotEmpty())
                <div class="mb-4 flex flex-wrap justify-center gap-2">
                    <button wire:click="filterByVenue()"
                            @class([
                                'rounded-full px-3 py-1 text-sm font-medium transition',
                                'bg-zinc-600 text-white' => $venueId === null,
                                'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== null,
                            ])>Semua</button>
                    @foreach ($venues as $v)
                        <button wire:click="filterByVenue({{ $v->id }})"
                                @class([
                                    'rounded-full px-3 py-1 text-sm font-medium transition',
                                    'bg-zinc-600 text-white' => $venueId === (string) $v->id,
                                    'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' => $venueId !== (string) $v->id,
                                ])>{{ $v->name }}</button>
                    @endforeach
                </div>
            @endif

            {{-- TV Helper --}}
            <div class="mb-4 rounded-lg border border-zinc-700 bg-zinc-800 p-3 text-center text-xs text-zinc-400">
                📺 Tambahkan <strong class="text-zinc-200">?display=tv</strong> untuk mode TV &middot; Auto-refresh 5 detik &middot; <a href="{{ request()->fullUrlWithQuery(['display' => 'tv']) }}" class="text-blue-400 hover:text-blue-300 underline">Buka TV Mode</a>
            </div>

            {{-- Announcement --}}
            @if ($announcement && $announcement->is_active)
                <div x-show="showAnnouncement" class="mb-4 rounded-xl border-2 border-yellow-500 bg-yellow-950 p-4 text-center"
                     x-init="setTimeout(() => { showAnnouncement = false; $wire.dismissAnnouncement(); }, 10000)">
                    <p class="text-lg font-bold text-yellow-300">{{ $announcement->message }}</p>
                    <button @click="showAnnouncement = false; $wire.dismissAnnouncement()" class="mt-2 text-sm text-yellow-400 hover:text-yellow-200">Tutup</button>
                </div>
            @endif

            @include('livewire.competition.viewer-content')
        </div>
    @endif
</div>
