<flux:modal name="edit-event" class="w-full max-w-2xl md:max-w-3xl">
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Edit Event</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ubah informasi event.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama Event</label>
            <flux:input wire:model="editName" placeholder="Nama event" />
            @error('editName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Deskripsi</label>
            <textarea wire:model="editDescription" placeholder="Deskripsi event (opsional)" rows="3" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-400"></textarea>
            @error('editDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tanggal Mulai</label>
                <flux:input wire:model="editStartDate" type="date" />
                @error('editStartDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tanggal Selesai</label>
                <flux:input wire:model="editEndDate" type="date" />
                @error('editEndDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Batal</flux:button>
            </flux:modal.close>
            <flux:spacer />
            <flux:button wire:click="update" variant="primary" :loading="$processing">
                Simpan
            </flux:button>
        </div>
    </div>
</flux:modal>
