<div>
    <flux:modal.trigger name="tambah-user">
        <flux:button variant="primary">{{ __('Tambah User') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="tambah-user" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Tambah User') }}</flux:heading>
            </div>

            <flux:input wire:model="name" label="{{ __('Nama') }}" placeholder="{{ __('Masukkan nama lengkap') }}" />

            <flux:input wire:model="email" type="email" label="{{ __('Email') }}" placeholder="{{ __('user@example.com') }}" />

            <flux:input wire:model="password" type="password" label="{{ __('Password') }}" placeholder="{{ __('Minimal 8 karakter') }}" />

            <flux:input wire:model="passwordConfirmation" type="password" label="{{ __('Konfirmasi Password') }}" placeholder="{{ __('Ulangi password') }}" />

            <flux:select wire:model="role" label="{{ __('Platform Role') }}" placeholder="{{ __('Pilih role') }}">
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Person (opsional)</label>
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
                @error('person_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
