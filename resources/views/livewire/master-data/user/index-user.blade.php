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
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Role') }}</th>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Dibuat') }}</th>
                    <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->role)
                                <flux:badge color="{{ $user->role === App\Enums\Role::SuperAdmin ? 'red' : ($user->role === App\Enums\Role::Admin ? 'blue' : 'zinc') }}" size="sm">
                                    {{ $user->role->label() }}
                                </flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" class="opacity-60">{{ __('Belum memiliki role') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="$dispatch('editUser', { id: {{ $user->id }} })" title="{{ __('Edit') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="$dispatch('resetPasswordUser', { id: {{ $user->id }} })" title="{{ __('Reset Password') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </flux:button>
                                @if ($user->id !== auth()->id())
                                    <flux:button size="sm" variant="ghost" wire:click="$dispatch('deleteUser', { id: {{ $user->id }} })" title="{{ __('Hapus') }}" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </flux:button>
                                @endif
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
