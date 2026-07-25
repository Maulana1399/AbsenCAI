<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        <div class="mx-auto min-h-svh w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="flex flex-col gap-8">
                {{ $slot }}
            </div>
        </div>
        @fluxScripts
    </body>
</html>
