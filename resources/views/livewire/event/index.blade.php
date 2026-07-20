<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Event</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kelola event dalam sistem.</p>
        </div>
        <flux:button wire:click="toggleCreateForm" variant="primary">
            {{ $showCreateForm ? 'Batal' : 'Tambah Event' }}
        </flux:button>
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

    @if ($showCreateForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Event Baru</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nama Event</label>
                    <flux:input wire:model="newName" placeholder="Nama event" />
                    @error('newName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                    <flux:input wire:model="newSlug" placeholder="url-friendly-slug" />
                    @error('newSlug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tipe Event</label>
                    <select wire:model="newEventType" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                        <option value="cai">CAI</option>
                        <option value="pengajian">Pengajian Desa</option>
                    </select>
                    @error('newEventType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Deskripsi</label>
                    <textarea wire:model="newDescription" placeholder="Deskripsi event (opsional)" rows="3" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-400"></textarea>
                    @error('newDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tanggal Mulai</label>
                    <flux:input wire:model="newStartDate" type="date" />
                    @error('newStartDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tanggal Selesai</label>
                    <flux:input wire:model="newEndDate" type="date" />
                    @error('newEndDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <flux:button wire:click="create" variant="primary" :loading="$processing">
                    Simpan
                </flux:button>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Nama</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tipe</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tanggal Mulai</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tanggal Selesai</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($events as $event)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            @if ($event->isPengajian())
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Pengajian</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">CAI</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            @if ($event->isActive())
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">Archived</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->end_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <div class="flex items-center gap-1">
                                <flux:button wire:click="edit({{ $event->id }})" size="sm" icon-trailing="pencil">Edit</flux:button>
                                @if ($event->isActive())
                                    <flux:button wire:click="archive({{ $event->id }})" size="sm" icon-trailing="archive-box" variant="danger">Arsipkan</flux:button>
                                @else
                                    <flux:button wire:click="activate({{ $event->id }})" size="sm" icon-trailing="arrow-uturn-left" variant="primary">Aktifkan</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Belum ada event.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <livewire:event.edit-status />
</div>
