<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1">
            <flux:heading size="xl" level="1">{{ __('Manajemen User') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Kelola akun pengguna sistem.') }}</flux:subheading>
        </div>
        <div>
            <livewire:master-data.user.create-user />
        </div>
    </div>

    <flux:separator variant="subtle" class="mb-6" />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..." icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="filterRole" placeholder="Semua Role">
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Nama') }}</th>
                    <th class="hidden lg:table-cell px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Role') }}</th>
                    <th class="hidden lg:table-cell px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Dibuat') }}</th>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50" data-testid="user-row-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="hidden lg:table-cell px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->role)
                                <flux:badge color="{{ $user->role === App\Enums\Role::SuperAdmin ? 'red' : ($user->role === App\Enums\Role::Admin ? 'blue' : 'zinc') }}" size="sm">
                                    {{ $user->role->label() }}
                                </flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" class="opacity-60">{{ __('Belum memiliki role') }}</flux:badge>
                            @endif
                        </td>
                        <td class="hidden lg:table-cell px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{-- Desktop: visible action buttons --}}
                            <div class="hidden lg:flex items-center gap-1">
                                <flux:button size="sm" icon="pencil" wire:click="$dispatch('editUser', { id: {{ $user->id }} })">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="sm" icon="lock-closed" wire:click="$dispatch('resetPasswordUser', { id: {{ $user->id }} })">
                                    {{ __('Reset') }}
                                </flux:button>
                                @if ($user->id !== auth()->id())
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="$dispatch('deleteUser', { id: {{ $user->id }} })" data-testid="delete-user-{{ $user->id }}">
                                        {{ __('Hapus') }}
                                    </flux:button>
                                @endif
                            </div>

                            {{-- Mobile/tablet: single Aksi dropdown --}}
                            <div class="flex lg:hidden">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" icon-trailing="chevron-down">{{ __('Aksi') }}</flux:button>

                                    <flux:menu class="w-48">
                                        <flux:menu.item wire:click="$dispatch('editUser', { id: {{ $user->id }} })" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="$dispatch('resetPasswordUser', { id: {{ $user->id }} })" icon="lock-closed">
                                            {{ __('Reset') }}
                                        </flux:menu.item>
                                        @if ($user->id !== auth()->id())
                                            <flux:menu.item wire:click="$dispatch('deleteUser', { id: {{ $user->id }} })" icon="trash" data-testid="delete-user-{{ $user->id }}" class="text-red-600 dark:text-red-400">
                                                {{ __('Hapus') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Belum ada data User.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <livewire:master-data.user.edit-user />
    <livewire:master-data.user.reset-password-user />
    <livewire:master-data.user.delete-user />
</div>
