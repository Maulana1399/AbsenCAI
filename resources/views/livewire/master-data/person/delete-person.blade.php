<div>
<flux:modal name="hapus-person" class="min-w-[22rem]">
    <div class="space-y-6">
        @if ($blockReason)
            <div>
                <flux:heading size="lg">Person Tidak Dapat Dihapus</flux:heading>

                <flux:text class="mt-2">
                    <p>{!! $blockReason !!}</p>
                    <p class="mt-2">Hapus data terkait terlebih dahulu sebelum menghapus Person ini.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="primary">Tutup</flux:button>
                </flux:modal.close>
            </div>
        @else
            <div>
                <flux:heading size="lg">Hapus Person?</flux:heading>

                <flux:text class="mt-2">
                    <p>Apakah kamu yakin ingin menghapus <strong>{{ $person_nama }}</strong>?</p>
                    <p>Tidak dapat diurungkan.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="destroy">Hapus Person</flux:button>
            </div>
        @endif
    </div>
</flux:modal>
</div>
