<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            x-data="{ sidebarOpen: false }"
            class="relative flex min-h-screen bg-gray-100"
        >
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"
                @click="sidebarOpen = false"
                style="display: none;"
            ></div>

            @include('layouts.sidebar')

            <div class="flex min-w-0 flex-1 flex-col">
                @include('layouts.topbar')

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-confirm-delete-modal />
    </body>
</html>
