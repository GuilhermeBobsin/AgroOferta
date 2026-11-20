<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-green-800">Itens salvos para consultar depois</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Meus favoritos</h1>
            </div>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-green-800 hover:underline">Explorar anúncios</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($listings as $listing)
                <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <a href="{{ route('listings.show', $listing) }}" class="block hover:bg-green-50/40">
                        <div class="h-44 overflow-hidden bg-gradient-to-br from-green-50 to-amber-50">
                            @if ($listing->image_path)
                                <img src="{{ asset('storage/'.$listing->image_path) }}" alt="{{ $listing->title }}"
                                    loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-green-800/40">
                                    <x-application-logo class="h-14 w-14" />
                                </div>
                            @endif
                        </div>

                        <div class="p-5">
                            <p class="text-xs font-semibold text-green-800">{{ $listing->category->name }}</p>
                            <h2 class="mt-1 truncate font-semibold text-gray-900">{{ $listing->title }}</h2>
                            <p class="mt-2 text-lg font-bold text-green-900">R$ {{ number_format($listing->price, 2, ',', '.') }} <span class="text-sm font-medium text-gray-500">/ {{ $listing->unit }}</span></p>
                            @if (isset($listing->distance))
                                <p class="mt-2 text-sm font-medium text-green-800">{{ $listing->distance < 1 ? '< 1' : number_format($listing->distance, 0, ',', '.') }} km de você</p>
                            @endif
                        </div>
                    </a>
                    <form method="POST" action="{{ route('favorites.destroy', $listing) }}" class="border-t border-gray-100 px-5 py-3">
                        @csrf @method('DELETE')
                        <button class="text-sm font-semibold text-red-700 hover:underline">Remover dos favoritos</button>
                    </form>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h2 class="text-lg font-semibold text-gray-900">Nenhum favorito salvo</h2>
                    <p class="mt-2 text-sm text-gray-500">Use o coração nos anúncios para encontrá-los novamente aqui.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Explorar anúncios</a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $listings->links() }}</div>
    </div>
</x-app-layout>
