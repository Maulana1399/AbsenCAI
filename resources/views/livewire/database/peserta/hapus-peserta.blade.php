<div>
<flux:modal name="hapus-peserta" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Hapus Peserta</flux:heading>

            @if ($peserta)
                <flux:text class="mt-2">
                    <p>Yakin ingin menghapus <strong>{{ $peserta }}</strong>?</p>
                    <p>Tidak dapat diurungkan.</p>
                </flux:text>
            @endif
        </div>

        @if ($blockReason)
            <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                {!! $blockReason !!}
            </div>
        @endif

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>

            @if ($canDelete)
                <flux:button type="submit" variant="danger" wire:click="destroy" wire:loading.attr="disabled" wire:target="destroy">
                    Hapus peserta
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
</div>
