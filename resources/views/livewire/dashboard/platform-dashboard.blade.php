<div class="flex flex-col gap-8">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                Selamat datang, {{ auth()->user()->name }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $activeCount }} event aktif &middot; {{ $userRole }}
            </p>
        </div>

        @can('manage-events')
            <flux:button
                href="{{ route('events.index') }}"
                variant="ghost"
                size="sm"
                wire:navigate
            >
                {{ __('Kelola Event') }}
            </flux:button>
        @endcan
    </div>

    {{-- Event Saya --}}
    <section>
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ __('Event Saya') }}
        </h2>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($events as $event)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center gap-2">
                        <span @class([
                            'inline-flex h-2 w-2 rounded-full',
                            'bg-green-500' => $event->isActive(),
                            'bg-zinc-300' => !$event->isActive(),
                        ])></span>
                        <span class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ ucfirst($event->event_type) }}
                        </span>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">
                            &middot; {{ $event->status }}
                        </span>
                    </div>

                    <h3 class="mt-3 text-base font-bold text-zinc-900 dark:text-white">
                        {{ $event->name }}
                    </h3>

                    <div class="mt-3 space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-1.5">
                            <flux:icon.users class="size-4" />
                            {{ $event->participations_count }} {{ __('Peserta') }}
                        </div>
                        <div class="flex items-center gap-1.5">
                            <flux:icon.shield-check class="size-4" />
                            {{ !empty($roleNamesByEvent[$event->id]) ? $roleLabelForEvent($event) : __('Tidak ada peran') }}
                        </div>
                    </div>

                    <div class="mt-4">
                        <flux:button
                            wire:click="openEvent({{ $event->id }})"
                            variant="primary"
                            size="sm"
                            class="w-full"
                        >
                            {{ __('Masuk Event') }}
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-200 p-10 text-center dark:border-zinc-700">
                    <flux:icon.calendar class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Belum ada event yang tersedia.') }}
                    </p>
                    @can('manage-events')
                        <flux:button
                            href="{{ route('events.index') }}"
                            variant="primary"
                            class="mt-4"
                            wire:navigate
                        >
                            {{ __('Buat Event Baru') }}
                        </flux:button>
                    @endcan
                </div>
            @endforelse
        </div>
    </section>

    {{-- Quick Access --}}
    <section>
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ __('Akses Cepat') }}
        </h2>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <flux:icon.qr-code class="size-5" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ __('Scan QR') }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Absensi via QR Code') }}
                        </p>
                    </div>
                </div>
                @if($activeCount === 1)
                    <flux:button
                        href="{{ route('absensi') }}"
                        variant="ghost"
                        size="sm"
                        class="mt-3 w-full"
                        wire:navigate
                    >
                        {{ __('Buka') }}
                    </flux:button>
                @else
                    <p class="mt-3 text-xs italic text-zinc-400 dark:text-zinc-500">
                        {{ __('TODO: pilih event terlebih dahulu') }}
                    </p>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <flux:icon.user-plus class="size-5" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ __('Registrasi Peserta') }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Tambah peserta baru') }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-xs italic text-zinc-400 dark:text-zinc-500">
                    {{ __('TODO: dialog pemilihan event') }}
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <flux:icon.magnifying-glass class="size-5" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ __('Cari Peserta') }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Temukan peserta dengan cepat') }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-xs italic text-zinc-400 dark:text-zinc-500">
                    {{ __('TODO: dialog pemilihan event') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Informasi --}}
    <section>
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ __('Informasi') }}
        </h2>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Event Diakses') }}
                    </p>
                    <p class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $activeCount }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Peran Aktif') }}
                    </p>
                    <p class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $userRole }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Versi Aplikasi') }}
                    </p>
                    <p class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $version }}
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>
