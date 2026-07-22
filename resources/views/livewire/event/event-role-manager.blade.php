<div>
    <flux:modal name="manage-event-roles" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Event Role') }}</flux:heading>
                @if ($eventName)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $eventName }}</p>
                @endif
            </div>

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">Role Saat Ini</h3>
                @if ($roles->isEmpty())
                    <p class="text-sm text-zinc-500">Belum ada role.</p>
                @else
                    <div class="max-h-48 space-y-1 overflow-y-auto">
                        @foreach ($roles as $role)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800">
                                <span class="font-medium">{{ $role->name }}</span>
                                @if ($role->code)
                                    <span class="text-xs text-zinc-500">({{ $role->code }})</span>
                                @endif
                                @if (!$role->is_active)
                                    <span class="ml-auto text-xs text-zinc-400">nonaktif</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">Tambah Role</h3>
                <div class="space-y-3">
                    <flux:input wire:model="newName" label="Nama Role" placeholder="Ketua Panitia" />
                    @error('newName') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <flux:input wire:model="newCode" label="Kode (opsional)" placeholder="ketua_panitia" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Deskripsi (opsional)</label>
                        <textarea wire:model="newDescription" rows="2" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                    </div>

                    <flux:input wire:model="newSortOrder" type="number" label="Urutan (opsional)" placeholder="1" />
                </div>
            </div>

            <div class="flex">
                <flux:button variant="primary" wire:click="create" :loading="$processing">
                    Tambah Role
                </flux:button>
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
