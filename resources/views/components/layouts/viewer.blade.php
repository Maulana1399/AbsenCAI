<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-zinc-900 antialiased">
        {{ $slot }}

        @fluxScripts
    </body>
</html>
