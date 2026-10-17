<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-green-800">Atualize as informações do produto</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Editar anúncio</h2>
        <p class="mt-2 truncate text-sm text-gray-500">{{ $listing->title }}</p>
    </x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <form method="POST" action="{{ route('listings.update', $listing) }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7 space-y-5">
            @csrf @method('PUT')
            @include('listings._form')
            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-between">
                <a href="{{ route('listings.mine') }}" class="rounded-lg border border-gray-300 px-4 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button class="rounded-lg bg-green-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">Salvar alterações</button>
            </div>
        </form>
    </div>
</x-app-layout>
