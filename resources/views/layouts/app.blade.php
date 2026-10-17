<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Encontre e anuncie insumos agropecuários perto de você no AgroOferta.">
        <meta name="theme-color" content="#166534">

        <title>{{ config('app.name', 'AgroOferta') }} — Insumos para quem produz</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col bg-[#f6f8f5]">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                    <header class="bg-white border-b border-gray-100">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 pb-12">
                {{ $slot }}
            </main>

            <footer class="border-t border-gray-200 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-semibold text-green-800">
                        <x-application-logo class="w-6 h-6" /> AgroOferta
                    </a>
                    <p>Insumos que sobram encontram quem precisa.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
