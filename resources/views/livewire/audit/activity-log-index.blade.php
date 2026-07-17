<div class="space-y-6">
    <div>
        <flux:heading size="xl">Activity Log</flux:heading>
        <flux:subheading>Riwayat aktivitas sistem</flux:subheading>
    </div>

    <flux:separator variant="subtle" />

    <flux:input wire:model.live.debounce="search" placeholder="Cari deskripsi..." />

    <div class="flex flex-wrap gap-3">
        <flux:select wire:model.live="filterModule" placeholder="Semua modul" class="w-48">
            @foreach ($this->modules as $module)
                <flux:select.option value="{{ $module }}">{{ $module }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterAction" placeholder="Semua aksi" class="w-48">
            @foreach ($this->actions as $action)
                <flux:select.option value="{{ $action }}">{{ $action }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="space-y-2">
        @forelse ($logs as $log)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium">{{ $log->user?->name ?? 'Sistem' }}</span>
                            <span class="text-zinc-400">&middot;</span>
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $log->module }}</span>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ $log->action }}</span>
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $log->description }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-400">
                            {{ $log->created_at?->format('d M Y H:i') }}
                        </p>
                    </div>

                    @if ($log->properties && count($log->properties) > 0)
                        <button
                            type="button"
                            wire:click="toggleDetail({{ $log->id }})"
                            class="shrink-0 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                        >
                            {{ $expandedLogId === $log->id ? 'Tutup' : 'Detail' }}
                        </button>
                    @endif
                </div>

                @if ($log->properties && count($log->properties) > 0 && $expandedLogId === $log->id)
                    <div class="mt-2 w-full">
                        <pre class="overflow-auto rounded bg-zinc-100 p-2 text-xs dark:bg-zinc-800">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                Tidak ada activity log.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
