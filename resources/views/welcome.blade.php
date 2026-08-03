<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ config('kjam.name') }} | {{ config('kjam.mvp_name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-900 to-emerald-600 text-white">

<div class="min-h-screen flex flex-col justify-center items-center px-6 py-10">

    <div class="max-w-6xl w-full text-center">

        {{-- Logo --}}
        <div class="mb-6">

            <div class="mx-auto h-24 w-24 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-3xl font-bold shadow-xl">
                K
            </div>

        </div>

        {{-- Judul --}}
        <h1 class="text-5xl md:text-6xl font-extrabold tracking-wide">
            KJA Event Manager
        </h1>

        <h2 class="text-3xl md:text-4xl font-semibold text-emerald-300 mt-2">
            Platform Manajemen Event Multi-Event
        </h2>

        @guest

            {{-- Unauthenticated: Welcome --}}
            <p class="mt-8 text-xl">
                Satu platform untuk seluruh kebutuhan event organisasi Anda.
            </p>

            <p class="mt-3 text-slate-300 max-w-3xl mx-auto">
                Kelola registrasi peserta, absensi QR, laporan kehadiran, dan
                operasional event lainnya dalam satu sistem terintegrasi.
            </p>

            {{-- CTA Buttons --}}
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">

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
                    <a href="{{ route('public.home') }}"
                        class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl border border-white/30 hover:bg-white/10 backdrop-blur transition-all duration-300 font-semibold text-lg shadow-lg hover:scale-105">
                        Absensi Pengajian
                    </a>
                    <span class="mt-2 text-xs text-slate-400 max-w-48 text-center leading-relaxed">
                        Akses cepat untuk operator desa melakukan absensi pengajian.
                    </span>
                </div>

            </div>

            {{-- Feature --}}
            <div class="grid md:grid-cols-3 gap-6 mt-14">

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

    </div>

    {{-- Footer --}}
    <footer class="mt-16 text-center text-sm text-slate-300">

        <div class="font-semibold text-emerald-300">
            Powered by KJA Techno
        </div>

        <div class="mt-1 opacity-70">
            {{ config('kjam.name') }} v1.0.0
        </div>

    </footer>

</div>

</body>
</html>