<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Encontre e anuncie insumos agropecuários perto de você no AgroOferta.">
        <meta name="theme-color" content="#166534">
        <title>{{ config('app.name', 'AgroOferta') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 bg-gradient-to-br from-green-50 via-white to-amber-50">
            <a href="{{ route('home') }}" class="mb-6 flex items-center gap-3 text-green-800">
                <x-application-logo class="w-12 h-12" />
                <span>
                    <span class="block text-xl font-bold tracking-tight">AgroOferta</span>
                    <span class="block text-xs text-gray-500">Insumos que circulam no campo</span>
                </span>
            </a>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-lg shadow-green-950/5 ring-1 ring-gray-200 rounded-2xl">
                {{ $slot }}
            </div>

            <a href="{{ route('home') }}" class="mt-6 text-sm text-gray-500 hover:text-green-800">Voltar para os anúncios</a>
        </div>
    </body>
</html>
