<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php
            $activeEvent = app(\App\Support\ActiveEventContext::class)->current();
            $isPengajian = $activeEvent?->isPengajian();
            $user = auth()->user();
            $can = fn ($a) => $user?->can($a) ?? false;
        @endphp
        <flux:sidebar sticky stashable @class([
            'border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900',
            'hidden lg:flex' => request()->routeIs('absensi'),
        ])>
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <livewire:event.event-switcher />

            @if ($isPengajian)
                {{-- Pengajian Navigation --}}
                <flux:navlist variant="outline">
                    @can('view-reports')
                    <flux:navlist.group :heading="__('Pengajian')" class="grid">
                        <flux:navlist.item icon="chart-bar" :href="route('pengajian.report')" :current="request()->routeIs('pengajian.report')" wire:navigate>{{ __('Regional Report') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    @can('manage-pengajian')
                    <flux:navlist.group expandable heading="Peserta" class="grid">
                        <flux:navlist.item :href="route('pengajian.admin.manual-entry')" :current="request()->routeIs('pengajian.admin.manual-entry')" wire:navigate>{{ __('Daftar Peserta') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('pengajian.import-massal')" :current="request()->routeIs('pengajian.import-massal')" wire:navigate>{{ __('Import Massal') }}</flux:navlist.item>
                    </flux:navlist.group>

                    <flux:navlist.group expandable heading="Operasional Desa" class="grid">
                        <flux:navlist.item :href="route('pengajian.admin.access')" :current="request()->routeIs('pengajian.admin.access')" wire:navigate>{{ __('Akses Desa') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                </flux:navlist>
            @else
                {{-- CAI / Default Navigation --}}
                <flux:navlist variant="outline">
                    @can('view-dashboard')
                    <flux:navlist.group :heading="__('Platform')" class="grid">
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    @canany(['manage-attendance', 'manage-sessions'])
                    <flux:navlist.group expandable heading="Absensi" class="grid">
                        @can('manage-attendance')
                        <flux:navlist.item icon="home" :href="route('absensi')" :current="request()->routeIs('absensi')" wire:navigate>{{ __('Scan Absensi') }}</flux:navlist.item>
                        @endcan
                        @can('manage-sessions')
                        <flux:navlist.item icon="home" :href="route('sesi.absensi')" :current="request()->routeIs('sesi.absensi')" wire:navigate>{{ __('Sesi Absensi') }}</flux:navlist.item>
                        @endcan
                    </flux:navlist.group>
                    @endcanany

                    @can('manage-registration')
                    <flux:navlist.group expandable heading="Registrasi" class="grid">
                        <flux:navlist.item :href="route('registrasi.peserta')" :current="request()->routeIs('registrasi.peserta')" wire:navigate>{{ __('Registrasi Peserta') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('registrasi.self')" :current="request()->routeIs('registrasi.self')" wire:navigate>{{ __('Self Register') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('registrasi.ulang')" :current="request()->routeIs('registrasi.ulang')" wire:navigate>{{ __('Registrasi Ulang') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    @can('manage-participants')
                    <flux:navlist.group expandable heading="Peserta CAI" class="grid">
                        <flux:navlist.item
                            :href="route('database')"
                            :current="request()->routeIs('database')"
                            wire:navigate
                        >
                            {{ __('Daftar Peserta') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            :href="route('regu')"
                            :current="request()->routeIs('regu')"
                            wire:navigate
                        >
                            {{ __('Regu') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    @can('view-reports')
                    <flux:navlist.group expandable heading="Laporan" class="grid">
                        <flux:navlist.item :href="route('rekap.peserta')" :current="request()->routeIs('rekap.peserta')" wire:navigate>{{ __('Rekap Peserta') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('rekap.absensi')" :current="request()->routeIs('rekap.absensi')" wire:navigate>{{ __('Rekap Absensi') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    @can('manage-qr-labels')
                    <flux:navlist.group expandable heading="QR & Label" class="grid">
                        <flux:navlist.item :href="route('qr-label.index')" :current="request()->routeIs('qr-label.index')" wire:navigate>{{ __('QR & Label') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endcan

                    <flux:navlist.group expandable heading="Event" class="grid">
                        <flux:navlist.item :href="route('events.index')" :current="request()->routeIs('events.index')" wire:navigate>{{ __('Kelola Event') }}</flux:navlist.item>
                    </flux:navlist.group>

                    @canany(['manage-secretariat', 'view-activity-log'])
                    <flux:navlist.group expandable heading="Sekretariat" class="grid">
                        @can('manage-secretariat')
                        <flux:navlist.item :href="route('surat-izin')" :current="request()->routeIs('surat-izin')" wire:navigate>{{ __('Surat Izin') }}</flux:navlist.item>
                        @endcan
                        @can('view-activity-log')
                        <flux:navlist.item :href="route('activity-log.index')" :current="request()->routeIs('activity-log.index')" wire:navigate>{{ __('Activity Log') }}</flux:navlist.item>
                        @endcan
                    </flux:navlist.group>
                    @endcanany
                </flux:navlist>
            @endif


            @can('view-master-data')
            @php
                $isMasterDataActive = request()->routeIs('master-data.index')
                    || request()->routeIs('person.index')
                    || request()->routeIs('desa')
                    || request()->routeIs('kelompok')
                    || request()->routeIs('events.index')
                    || request()->routeIs('correction-requests.index');
            @endphp
            <flux:navlist variant="outline">
                <div x-data="{ expanded: true }" class="block space-y-[2px]">
                    <a href="{{ route('master-data.index') }}" wire:navigate
                       @click="expanded = !expanded"
                       class="w-full h-10 lg:h-8 flex items-center gap-3 rounded-lg px-3 my-px text-sm font-medium leading-none hover:bg-zinc-800/5 dark:hover:bg-white/[7%] text-zinc-500 hover:text-zinc-800 dark:text-white/80 dark:hover:text-white {{ $isMasterDataActive ? 'bg-zinc-800/[4%] dark:bg-white/[7%] text-zinc-800 dark:text-white border border-zinc-200 dark:border-transparent shadow-xs' : '' }}">
                        <flux:icon.folder class="size-4!" />
                        <span class="flex-1">{{ __('Master Data') }}</span>
                        <span @click.prevent.stop="expanded = !expanded" class="flex items-center">
                            <flux:icon.chevron-down x-show="expanded" class="size-3!" />
                            <flux:icon.chevron-right x-show="!expanded" class="size-3!" />
                        </span>
                    </a>
                    <div x-show="expanded" x-cloak class="ps-7 space-y-[2px]">
                        <flux:navlist.item :href="route('person.index')" :current="request()->routeIs('person.index')" wire:navigate>{{ __('Person') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('desa')" :current="request()->routeIs('desa')" wire:navigate>{{ __('Desa') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('kelompok')" :current="request()->routeIs('kelompok')" wire:navigate>{{ __('Kelompok') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('events.index')" :current="request()->routeIs('events.index')" wire:navigate>{{ __('Event') }}</flux:navlist.item>
                        <flux:navlist.item :href="route('correction-requests.index')" :current="request()->routeIs('correction-requests.index')" wire:navigate>{{ __('Permintaan Perubahan') }}</flux:navlist.item>
                    </div>
                </div>
            </flux:navlist>
            @endcan

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header @class(['lg:hidden', 'hidden' => request()->routeIs('absensi')])>
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
