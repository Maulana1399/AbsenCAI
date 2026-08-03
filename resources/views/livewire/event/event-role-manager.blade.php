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
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">Role Saat Ini</h3>
                @if ($roles->isEmpty())
                    <p class="text-sm text-zinc-500">Belum ada role.</p>
                @else
                    <div class="max-h-60 space-y-2 overflow-y-auto">
                        @foreach ($roles as $role)
                            <div class="rounded-md bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium">{{ $role->name }}</span>
                                    @if ($role->code)
                                        <span class="text-xs text-zinc-500">({{ $role->code }})</span>
                                    @endif
                                    @if (!$role->is_active)
                                        <span class="text-xs text-zinc-400">nonaktif</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-zinc-500">
                                    @if ($role->committee_assignments_count > 0)
                                        Dipakai oleh {{ $role->committee_assignments_count }} panitia
                                    @else
                                        Belum digunakan
                                    @endif
                                </p>
                                <div class="mt-1 flex gap-1">
                                    <flux:button size="xs" variant="ghost" wire:click="edit({{ $role->id }})">
                                        Edit
                                    </flux:button>
                                    <flux:button size="xs" variant="danger" wire:click="delete({{ $role->id }})"
                                        wire:confirm="Hapus role {{ $role->name }}?">
                                        Hapus
                                    </flux:button>
                                </div>
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

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Template Permission</label>
                        <select wire:model.live="newTemplate" name="newTemplate"
                            class="w-full h-10 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/10 dark:text-zinc-300">
                            <option value="">Pilih template...</option>
                            @foreach ($templateOptions as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newTemplate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Code</label>
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                            {{ $newCode ?: '—' }}
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Code diisi otomatis dari template permission dan tidak dapat diubah.</p>
                    </div>

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

    <flux:modal name="edit-event-role" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Event Role') }}</flux:heading>
            </div>

            <flux:input wire:model="editName" label="Nama Role" placeholder="Ketua Panitia" />
            @error('editName') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Template Permission</label>
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    {{ $templateOptions[$editCode] ?? ($editCode ?: '—') }}
                </div>
                <p class="mt-1 text-xs text-zinc-500">Template tidak dapat diubah setelah role dibuat.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Code</label>
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    {{ $editCode ?: '—' }}
                </div>
                <p class="mt-1 text-xs text-zinc-500">Code adalah identitas Permission Engine dan tidak dapat diubah.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Deskripsi (opsional)</label>
                <textarea wire:model="editDescription" rows="2" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
            </div>

            <div class="flex">
                <flux:button variant="primary" wire:click="update" :loading="$processing">
                    Simpan
                </flux:button>
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
