<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CAI 2025 | Sistem Registrasi & Absensi</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-950 via-blue-900 to-sky-600 text-white">

<div class="min-h-screen flex flex-col justify-center items-center px-6 py-10">

    <div class="max-w-6xl w-full text-center">

        {{-- Logo --}}
        <div class="mb-6">

            <div class="mx-auto h-24 w-24 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-3xl font-bold shadow-xl">

                CAI

            </div>

        </div>

        {{-- Judul --}}
        <h1 class="text-5xl md:text-6xl font-extrabold tracking-wide">

            CINTA ALAM

        </h1>

        <h2 class="text-3xl md:text-4xl font-semibold text-sky-300 mt-2">

            INDONESIA 2025

        </h2>

        <p class="mt-8 text-xl">

            Platform Registrasi & Absensi Peserta

        </p>

        <p class="mt-3 text-slate-300 max-w-3xl mx-auto">

            Sistem digital untuk membantu proses registrasi,
            absensi peserta, serta monitoring kegiatan secara
            cepat, aman, dan real-time.

        </p>

        {{-- Login Button --}}
        <div class="mt-10">

            @auth

                <a href="{{ url('/dashboard') }}"
                    class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl bg-sky-500 hover:bg-sky-400 transition-all duration-300 font-bold text-lg shadow-2xl hover:scale-105">

                    🚀 Masuk Dashboard

                </a>

            @else

                <a href="{{ route('login') }}"
                    class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl bg-sky-500 hover:bg-sky-400 transition-all duration-300 font-bold text-lg shadow-2xl hover:scale-105">

                    🚀 Login Admin

                </a>

            @endauth

        </div>

        {{-- Feature --}}
        <div class="grid md:grid-cols-3 gap-6 mt-14">

            <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">

                <div class="text-5xl mb-4">
                    👥
                </div>

                <h3 class="text-xl font-bold">
                    Registrasi
                </h3>

                <p class="text-slate-300 mt-3">
                    Kelola data peserta dengan mudah dan cepat.
                </p>

            </div>

            <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">

                <div class="text-5xl mb-4">
                    ✅
                </div>

                <h3 class="text-xl font-bold">
                    Absensi
                </h3>

                <p class="text-slate-300 mt-3">
                    Monitoring kehadiran peserta secara real-time.
                </p>

            </div>

            <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/10 p-6 hover:scale-105 transition duration-300">

                <div class="text-5xl mb-4">
                    📊
                </div>

                <h3 class="text-xl font-bold">
                    Dashboard
                </h3>

                <p class="text-slate-300 mt-3">
                    Statistik peserta dan laporan kegiatan.
                </p>

            </div>

        </div>

    </div>

    {{-- Footer --}}
    <footer class="mt-16 text-center text-sm text-slate-300">

        <div class="font-semibold text-sky-300">

            Powered by KJA Techno

        </div>

        <div class="mt-1">

            Cinta Alam Indonesia 2025

        </div>

        <div class="mt-1 opacity-70">

            Version 1.0.0

        </div>

    </footer>

</div>

</body>
</html>