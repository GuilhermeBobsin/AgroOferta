<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar anúncio</h2></x-slot>
    <div class="max-w-2xl mx-auto p-4">
        <form method="POST" action="{{ route('listings.update', $listing) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-4 space-y-4">
            @csrf @method('PUT')
            @include('listings._form')
            <button class="w-full bg-green-700 text-white py-3 rounded-md">Salvar alterações</button>
        </form>
    </div>
</x-app-layout>
