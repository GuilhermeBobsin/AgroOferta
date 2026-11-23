<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('home') }}" class="text-sm font-medium text-green-800 hover:underline">&larr; Voltar aos anúncios</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Perfil de {{ $user->name }}</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @include('profile.partials.reputation-reviews', ['profileUser' => $user, 'reviews' => $reviews])
    </div>
</x-app-layout>
