<div>
    <flux:modal name="manage-committee" class="md:w-[550px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Penugasan Panitia') }}</flux:heading>
                @if ($eventName)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $eventName }}</p>
                @endif
            </div>

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">Panitia Terdaftar</h3>
                @if ($assignments->isEmpty())
                    <p class="text-sm text-zinc-500">Belum ada penugasan.</p>
                @else
                    <div class="max-h-60 space-y-1 overflow-y-auto">
                        @foreach ($assignments as $assignment)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800">
                                <span class="font-medium">{{ $assignment->person?->nama ?? '-' }}</span>
                                <span class="text-xs text-zinc-500">{{ $assignment->eventRole?->name ?? '-' }}</span>
                                <button wire:click="confirmDelete({{ $assignment->id }})" class="ml-auto text-red-500 hover:text-red-700 text-xs"
                                    wire:confirm="Hapus penugasan {{ $assignment->person?->nama ?? '' }}?">
                                    Hapus
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">Tambah Penugasan</h3>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Person</label>
                        @if ($selectedPersonNama)
                            <div class="flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <span>{{ $selectedPersonNama }}</span>
                                <button type="button" wire:click="removePerson" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
                            </div>
                        @else
                            <flux:input wire:model.live="searchPerson" placeholder="Cari Person..." />
                        @endif
                        @if (!empty($personResults) && !$selectedPersonNama)
                            <div class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach ($personResults as $person)
                                    <button type="button" wire:click="selectPerson({{ $person->id }})" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        {{ $person->nama }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('newPersonId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Event Role</label>
                        <select wire:model="newEventRoleId" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Pilih role...</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('newEventRoleId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex">
                <flux:button variant="primary" wire:click="create" :loading="$processing">
                    Tambah Penugasan
                </flux:button>
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
