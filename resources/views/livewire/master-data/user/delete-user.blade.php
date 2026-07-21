<div>
    <flux:modal name="hapus-user" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Hapus User') }}</flux:heading>
                @if ($userName)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Yakin ingin menghapus user :name?', ['name' => $userName]) }}</p>
                @endif
            </div>

            @if ($blockReason)
                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                    {!! $blockReason !!}
                </div>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$modal('hapus-user').close()">{{ __('Batal') }}</flux:button>
                @if ($canDelete)
                    <flux:button variant="danger" wire:click="destroy" wire:loading.attr="disabled" wire:target="destroy">
                        {{ __('Hapus') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
