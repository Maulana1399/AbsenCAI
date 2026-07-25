<div class="mb-4">
    @if ($currentEventName)
        <flux:dropdown position="bottom" align="start">
            <flux:button icon-trailing="chevron-down" variant="ghost" class="w-full justify-start text-sm font-medium">
                <div class="flex items-center gap-2 truncate">
                    <span class="inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                    <span class="truncate">{{ $currentEventName }}</span>
                </div>
            </flux:button>

            <flux:menu class="w-[200px]">
                <flux:menu.radio.group>
                    <div class="px-2 py-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                        Pilih Event
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                @forelse ($events as $event)
                    <flux:menu.item
                        wire:click="switchTo({{ $event->id }})"
                        :active="$event->id === $currentEventId"
                    >
                        <div class="flex items-center gap-2">
                            @if ($event->id === $currentEventId)
                                <span class="inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                            @else
                                <span class="inline-flex h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            @endif
                            <span>{{ $event->name }}</span>
                            @if ($event->event_type === 'pengajian')
                                <span class="ml-auto text-xs text-emerald-600 dark:text-emerald-400">Pengajian</span>
                            @else
                                <span class="ml-auto text-xs text-blue-600 dark:text-blue-400">CAI</span>
                            @endif
                        </div>
                    </flux:menu.item>
                @empty
                    <flux:menu.item disabled>Belum ada event</flux:menu.item>
                @endforelse

                <flux:menu.separator />

                @can('manage-events')
                <flux:menu.item icon="cog" href="{{ route('events.index') }}" wire:navigate>
                    Kelola Event
                </flux:menu.item>
                @endcan
            </flux:menu>
        </flux:dropdown>
    @else
        <flux:dropdown position="bottom" align="start">
            <flux:button icon-trailing="chevron-down" variant="ghost" class="w-full justify-start text-sm">
                <span class="text-zinc-400 dark:text-zinc-500">Pilih Event...</span>
            </flux:button>

            <flux:menu class="w-[200px]">
                @forelse ($events as $event)
                    <flux:menu.item wire:click="switchTo({{ $event->id }})">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            <span>{{ $event->name }}</span>
                            @if ($event->event_type === 'pengajian')
                                <span class="ml-auto text-xs text-emerald-600 dark:text-emerald-400">Pengajian</span>
                            @else
                                <span class="ml-auto text-xs text-blue-600 dark:text-blue-400">CAI</span>
                            @endif
                        </div>
                    </flux:menu.item>
                @empty
                    <flux:menu.item disabled>Belum ada event</flux:menu.item>
                @endforelse

                <flux:menu.separator />

                @can('manage-events')
                <flux:menu.item icon="cog" href="{{ route('events.index') }}" wire:navigate>
                    Kelola Event
                </flux:menu.item>
                @endcan
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
