<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? 'KJA Event Manager' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Portal informasi event KJA - jadwal, bracket, dan hasil pertandingan.' }}">

    <meta property="og:title" content="{{ $metaTitle ?? 'KJA Event Manager' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Portal informasi event KJA.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans antialiased text-zinc-900">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('public.home') }}" class="text-xl font-bold text-zinc-900">
                    {{ config('app.name', 'KJA') }}
                </a>
                <nav class="flex items-center gap-4 text-sm text-zinc-600">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-blue-600 hover:text-blue-800">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-800">Login</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-zinc-50 py-6 text-center text-sm text-zinc-500">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                &copy; {{ date('Y') }} {{ config('app.name', 'KJA') }}. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>
