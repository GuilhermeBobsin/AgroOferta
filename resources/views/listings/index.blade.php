<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Insumos disponíveis</h2>
            <a href="{{ route('listings.create') }}" class="bg-green-700 text-white px-4 py-2 rounded-md text-sm">+ Anunciar</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-4">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        <form method="GET" class="bg-white rounded-lg shadow p-3 mb-6 grid grid-cols-2 lg:grid-cols-6 gap-2 text-sm">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar produto..." class="col-span-2 rounded-md border-gray-300">

            <select name="category" class="rounded-md border-gray-300">
                <option value="">Categoria</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <select name="unit" class="rounded-md border-gray-300">
                <option value="">Unidade</option>
                @foreach (\App\Models\Listing::UNITS as $u)
                    <option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>
                @endforeach
            </select>

            <input name="state" value="{{ request('state') }}" maxlength="2" placeholder="UF" class="rounded-md border-gray-300 uppercase">
            <input name="price_min" type="number" step="0.01" value="{{ request('price_min') }}" placeholder="Preço mín." class="rounded-md border-gray-300">
            <input name="price_max" type="number" step="0.01" value="{{ request('price_max') }}" placeholder="Preço máx." class="rounded-md border-gray-300">

            <select name="sort" class="rounded-md border-gray-300 col-span-2">
                <option value="recent" @selected(request('sort', 'recent') === 'recent')>Mais recentes</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Menor preço</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Maior preço</option>
                @if ($canSortByDistance)
                    <option value="nearest" @selected(request('sort') === 'nearest')>Mais próximos</option>
                @endif
            </select>

            <button class="bg-gray-800 text-white rounded-md px-4 py-2 col-span-2 lg:col-span-1">Filtrar</button>
            <a href="{{ route('home') }}" class="text-center text-gray-500 py-2 underline">Limpar</a>
        </form>

        @auth
            @unless ($canSortByDistance)
                <p class="text-xs text-gray-500 mb-3">Quer ordenar por proximidade? <a class="underline" href="{{ route('account.edit') }}">Informe sua localização</a>.</p>
            @endunless
        @endauth

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse ($listings as $l)
                <a href="{{ route('listings.show', $l) }}" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-md transition">
                    <div class="h-40 bg-gray-200">
                        @if ($l->image_path)
                            <img src="{{ asset('storage/'.$l->image_path) }}" alt="{{ $l->title }}" class="w-full h-40 object-cover">
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="text-xs text-green-700">{{ $l->category->name }}</p>
                        <h3 class="font-semibold truncate">{{ $l->title }}</h3>
                        <p class="text-lg font-bold">R$ {{ number_format($l->price, 2, ',', '.') }} <span class="text-sm font-normal text-gray-500">/ {{ $l->unit }}</span></p>
                        <p class="text-xs text-gray-500">
                            {{ $l->city }} - {{ $l->state }}
                            @if (isset($l->distance)) · a {{ number_format($l->distance, 0) }} km @endif
                        </p>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-gray-500">Nenhum anúncio encontrado.</p>
            @endforelse
        </div>

        <div class="mt-6">{{ $listings->links() }}</div>
    </div>
</x-app-layout>
