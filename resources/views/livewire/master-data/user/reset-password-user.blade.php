<div>
    <flux:modal name="reset-password-user" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Reset Password') }}</flux:heading>
                @if ($userName)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('User: :name', ['name' => $userName]) }}</p>
                @endif
            </div>

            <flux:input wire:model="newPassword" type="password" label="{{ __('Password Baru') }}" placeholder="{{ __('Minimal 8 karakter') }}" />

            <flux:input wire:model="newPasswordConfirmation" type="password" label="{{ __('Konfirmasi Password') }}" placeholder="{{ __('Ulangi password') }}" />

            <div class="flex">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="resetPassword" wire:loading.attr="disabled" wire:target="resetPassword">
                    {{ __('Reset Password') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
