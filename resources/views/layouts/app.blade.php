<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rumika SaaS') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('rumika-favicon.svg') }}?v=2">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script>
            (() => {
                const savedTheme = localStorage.getItem('rumika-theme');
                document.documentElement.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="rm-app">
            <main>
                {{ $slot }}
            </main>

            @auth
                <livewire:app.rumi-assistant />
            @endauth
        </div>
    </body>
</html>
