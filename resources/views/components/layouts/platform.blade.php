<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        <div class="mx-auto min-h-svh w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="flex flex-col">
                <header class="pb-6">
                    <x-app-logo />
                </header>

                <main>
                    {{ $slot }}
                </main>

                <footer class="border-t border-zinc-200 pt-6 text-center text-xs text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">
                    Powered by KJA Techno
                </footer>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
