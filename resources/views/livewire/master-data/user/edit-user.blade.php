<div>
    <flux:modal name="edit-user" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Person: {{ $selectedPersonNama ?: '—' }}
                </p>
            </div>

            <flux:input wire:model="name" label="{{ __('Nama') }}" placeholder="{{ __('Masukkan nama lengkap') }}" />

            <flux:input wire:model="email" type="email" label="{{ __('Email') }}" placeholder="{{ __('user@example.com') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Person') }}</label>
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    {{ $selectedPersonNama ?: '—' }}
                </div>
                <p class="mt-1 text-xs text-zinc-500">Person adalah identitas akun dan tidak dapat diubah.</p>
            </div>

            <flux:select wire:model="role" label="{{ __('Platform Role') }}" placeholder="{{ __('Pilih role') }}" :disabled="$roleLocked">
                @if ($roleLocked && $roleLockReason)
                    <p class="mb-2 text-sm text-amber-600 dark:text-amber-400">{{ $roleLockReason }}</p>
                @endif
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="update" wire:loading.attr="disabled" wire:target="update">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
