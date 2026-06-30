<div class="flex flex-col gap-6">
    <div class="text-center mb-4">

        <div class="mx-auto h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
            CAI
        </div>

        <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">
            Cinta Alam Indonesia
        </h1>

        <p class="text-blue-500 text-lg mt-2">
            Administrator Login
        </p>

        <p class="text-zinc-500 mt-3">
            Masuk untuk mengakses Dashboard Registrasi & Absensi
        </p>

    </div>
    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Masukkan Password')"
                viewable
            />
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
            </div>

            <div class="text-center mt-5">

        <a
            href="/"
            class="text-blue-500 hover:text-blue-600 text-sm">

            ← Kembali ke Beranda

        </a>

    </div>
    </form>

</div>
