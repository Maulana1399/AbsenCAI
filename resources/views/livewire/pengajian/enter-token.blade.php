<div class="flex flex-col gap-6 max-w-sm mx-auto">
    <div class="text-center mb-4">
        <div class="mx-auto h-20 w-20 rounded-full bg-emerald-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl">
            KJA
        </div>

        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">
            Pengajian Desa
        </h1>

        <p class="text-emerald-600 text-lg mt-2">
            Akses Dashboard Desa
        </p>

        <p class="text-zinc-500 mt-3 text-sm">
            Masukkan token yang diterima dari Operator Daerah.
        </p>
    </div>

    @if (session('pengajian_logout'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('pengajian_logout') }}
        </div>
    @endif

    @if (session('pengajian_expired'))
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200">
            {{ session('pengajian_expired') }}
        </div>
    @endif

    <form wire:submit="submit" class="flex flex-col gap-4">
        <flux:input
            wire:model="token"
            label="Token Akses"
            type="text"
            required
            autofocus
            autocomplete="off"
            placeholder="Masukkan token akses"
        />

        @error('token')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <flux:button type="submit" variant="primary" class="w-full" :loading="$processing">
            Masuk Dashboard Desa
        </flux:button>
    </form>

    <p class="text-center text-xs text-zinc-400">
        Token dikirim oleh Operator Daerah melalui jalur resmi.
    </p>
</div>
