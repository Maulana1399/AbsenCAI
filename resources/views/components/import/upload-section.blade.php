@props([
    'accept' => '.csv,.xlsx,.xls,.txt',
    'templateUrl' => null,
    'file' => null,
    'uploadError' => null,
])

<div class="mt-4 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Format File</p>

    <div class="mt-2 text-xs text-zinc-400">
        {{ $hint }}
    </div>

    @if ($templateUrl)
        <div class="mt-4">
            <a href="{{ $templateUrl }}" download>
                <flux:button type="button" variant="ghost" icon-trailing="arrow-down-tray">
                    Download Template
                </flux:button>
            </a>
        </div>
    @endif

    <div class="mt-4">
        <input
            type="file"
            x-on:livewire-upload-error="$wire.uploadError()"
            accept="{{ $accept }}"
            {{ $attributes->whereStartsWith('wire:model') }}
            class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400"
        />
    </div>

    @if ($file)
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            File terpilih: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $file->getClientOriginalName() }}</span>
        </p>
    @endif

    @if ($uploadError)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $uploadError }}</p>
    @endif

    @error('file')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
