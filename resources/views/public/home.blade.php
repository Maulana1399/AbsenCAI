<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} | KJA Event Manager</title>
    <meta name="description" content="Platform Manajemen Event Multi-Event — Kelola registrasi peserta, absensi QR, laporan kehadiran, dan operasional event lainnya dalam satu sistem terintegrasi.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans antialiased">

    {{-- Hero Section (no header bar) --}}
    <section class="relative bg-gradient-to-b from-slate-950 via-emerald-900 to-emerald-600 text-white overflow-hidden">

        {{-- Login — top-right of hero --}}
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-10">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-white/20 hover:bg-white/10 text-sm font-medium text-white/90 hover:text-white transition">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-white/20 hover:bg-white/10 text-sm font-medium text-white/90 hover:text-white transition">
                    Login
                </a>
            @endauth
        </div>

        {{-- Hero Content --}}
        <div class="mx-auto max-w-6xl px-4 pt-16 pb-24 sm:px-6 sm:pt-20 sm:pb-32 text-center">

            <div class="mx-auto mb-6 h-20 w-20 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-3xl font-bold shadow-xl">
                K
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl">
                KJA Event Manager
            </h1>
            <p class="mt-2 text-2xl font-semibold text-emerald-300 md:text-3xl">
                Platform Manajemen Event Multi-Event
            </p>

            @guest
                <p class="mt-6 text-lg text-slate-300 max-w-3xl mx-auto">
                    Satu platform untuk seluruh kebutuhan event organisasi Anda.
                    Kelola registrasi peserta, absensi QR, laporan kehadiran, dan
                    operasional event lainnya dalam satu sistem terintegrasi.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
                    <div class="flex flex-col items-center">
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl bg-white/20 hover:bg-white/30 backdrop-blur transition-all duration-300 font-bold text-lg shadow-2xl hover:scale-105">
                            Masuk Admin
                        </a>
                        <span class="mt-2 text-xs text-slate-400 max-w-48 text-center leading-relaxed">
                            Kelola seluruh event dan pengaturan platform.
                        </span>
                    </div>
                    <div class="flex flex-col items-center">
                        <a href="{{ $pengajianEvent ? route('pengajian.enter-token', ['event' => $pengajianEvent]) : route('public.home') }}"
                            class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl border border-white/30 hover:bg-white/10 backdrop-blur transition-all duration-300 font-semibold text-lg shadow-lg hover:scale-105">
                            Absensi Pengajian
                        </a>
                        <span class="mt-2 text-xs text-slate-400 max-w-48 text-center leading-relaxed">
                            Akses cepat untuk operator desa melakukan absensi pengajian.
                        </span>
                    </div>
                </div>

                {{-- Feature Cards --}}
                <div class="grid md:grid-cols-3 gap-6 mt-12">
                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">
                        <div class="text-5xl mb-4">👥</div>
                        <h3 class="text-xl font-bold">Registrasi</h3>
                        <p class="text-slate-300 mt-3">Kelola data peserta dengan mudah dan cepat.</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">
                        <div class="text-5xl mb-4">✅</div>
                        <h3 class="text-xl font-bold">Absensi QR</h3>
                        <p class="text-slate-300 mt-3">Monitoring kehadiran peserta secara real-time.</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">
                        <div class="text-5xl mb-4">📊</div>
                        <h3 class="text-xl font-bold">Laporan</h3>
                        <p class="text-slate-300 mt-3">Statistik peserta dan laporan kegiatan.</p>
                    </div>
                </div>
            @endguest

            {{-- Search --}}
            <div class="mt-12 max-w-xl mx-auto">
                <form action="{{ route('public.home') }}" method="GET">
                    <input type="text" name="search" placeholder="Cari event..." value="{{ request('search') }}"
                           class="w-full rounded-xl border border-white/20 bg-white/10 backdrop-blur px-4 py-3 text-sm text-white placeholder-white/60 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                </form>
            </div>
        </div>

        {{-- Smooth fade to body background --}}
        <div class="h-16 sm:h-24 bg-gradient-to-b from-emerald-600 to-slate-950"></div>
    </section>

    {{-- Event Listing --}}
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
        @if ($running->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-white">Sedang Berlangsung</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($running as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-white/10 bg-white/5 backdrop-blur p-5 transition hover:bg-white/10">
                            <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-300">Aktif</span>
                            <h3 class="mt-2 text-lg font-bold text-white">{{ $event->name }}</h3>
                            @if ($event->start_date)<p class="mt-1 text-sm text-slate-400">{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($upcoming->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-white">Akan Datang</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-white/10 bg-white/5 backdrop-blur p-5 transition hover:bg-white/10">
                            <h3 class="text-lg font-bold text-white">{{ $event->name }}</h3>
                            @if ($event->start_date)<p class="mt-1 text-sm text-slate-400">{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($finished->isNotEmpty())
            <section>
                <h2 class="mb-6 text-2xl font-bold text-white">Event Sebelumnya</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($finished as $event)
                        <a href="{{ route('public.event', $event) }}" class="block rounded-xl border border-white/10 bg-white/5 backdrop-blur p-5 transition hover:bg-white/10">
                            <span class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-slate-300">Selesai</span>
                            <h3 class="mt-2 text-lg font-bold text-white">{{ $event->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($running->isEmpty() && $upcoming->isEmpty() && $finished->isEmpty())
            <div class="py-16 text-center">
                <p class="text-slate-400">Belum ada event.</p>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-8 text-center text-sm text-slate-400">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="font-semibold text-emerald-400">Powered by KJA Techno</div>
            <div class="mt-1 opacity-70">{{ config('app.name') }} v1.0.0</div>
            <div class="mt-2">&copy; {{ date('Y') }} {{ config('app.name', 'KJA') }}. All rights reserved.</div>
        </div>
    </footer>

</body>
</html>