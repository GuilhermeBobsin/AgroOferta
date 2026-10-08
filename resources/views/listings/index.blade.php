<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-10 space-y-8">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                {{ session('status') }}
            </div>
        @endif

        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-green-950 via-green-900 to-green-800 px-6 py-10 text-white shadow-lg sm:px-10 sm:py-14">
            <div class="pointer-events-none absolute -right-14 -top-20 h-72 w-72 rounded-full border-[36px] border-white/5"></div>
            <div class="relative max-w-3xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-green-100">
                    <x-application-logo class="h-4 w-4" /> Marketplace do campo
                </p>
                <h1 class="max-w-2xl text-3xl font-bold leading-tight tracking-tight sm:text-5xl">O que sobra para um produtor pode fazer falta para outro.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-green-100 sm:text-lg">Anuncie insumos disponíveis, encontre o que sua produção precisa e negocie direto com outros produtores.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('listings.create') }}" class="rounded-lg bg-amber-300 px-5 py-3 text-sm font-bold text-green-950 shadow-sm transition hover:bg-amber-200">Anunciar um insumo</a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-lg bg-amber-300 px-5 py-3 text-sm font-bold text-green-950 shadow-sm transition hover:bg-amber-200">Criar minha conta</a>
                        <a href="#anuncios" class="rounded-lg border border-white/30 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Explorar anúncios</a>
                    @endauth
                </div>
            </div>
        </section>

        <section id="anuncios" aria-labelledby="catalog-title" class="scroll-mt-6">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-800">Encontre perto de você</p>
                    <h2 id="catalog-title" class="text-2xl font-bold tracking-tight text-gray-900">Insumos disponíveis</h2>
                </div>
                <p class="text-sm text-gray-500">{{ $listings->total() }} {{ $listings->total() === 1 ? 'anúncio encontrado' : 'anúncios encontrados' }}</p>
            </div>

            <form method="GET" action="{{ route('home') }}" class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="q" class="mb-1 block text-xs font-semibold text-gray-600">O que você procura?</label>
                        <input id="q" name="q" value="{{ request('q') }}" placeholder="Ex.: semente de soja, fertilizante..." class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div>
                        <label for="category" class="mb-1 block text-xs font-semibold text-gray-600">Categoria</label>
                        <select id="category" name="category" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="">Todas</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unit" class="mb-1 block text-xs font-semibold text-gray-600">Unidade</label>
                        <select id="unit" name="unit" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="">Todas</option>
                            @foreach (\App\Models\Listing::UNITS as $u)
                                <option value="{{ $u }}" @selected(request('unit') === $u)>{{ ucfirst($u) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="state" class="mb-1 block text-xs font-semibold text-gray-600">Estado (UF)</label>
                        <input id="state" name="state" value="{{ request('state') }}" maxlength="2" placeholder="RS" class="w-full rounded-lg border-gray-300 text-sm uppercase focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div>
                        <label for="price_min" class="mb-1 block text-xs font-semibold text-gray-600">Preço mínimo (R$)</label>
                        <input id="price_min" name="price_min" type="number" min="0" step="0.01" value="{{ request('price_min') }}" placeholder="0,00" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div>
                        <label for="price_max" class="mb-1 block text-xs font-semibold text-gray-600">Preço máximo (R$)</label>
                        <input id="price_max" name="price_max" type="number" min="0" step="0.01" value="{{ request('price_max') }}" placeholder="Qualquer valor" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-2">
                        <label for="sort" class="mb-1 block text-xs font-semibold text-gray-600">Ordenar por</label>
                        <select id="sort" name="sort" class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="recent" @selected(request('sort', 'recent') === 'recent')>Mais recentes</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Menor preço</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Maior preço</option>
                            @if ($canSortByDistance)
                                <option value="nearest" @selected(request('sort') === 'nearest')>Mais próximos</option>
                            @endif
                        </select>
                    </div>

                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                        <button class="flex-1 rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-900 sm:flex-none">Buscar anúncios</button>
                        <a href="{{ route('home') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-50">Limpar filtros</a>
                    </div>
                </div>
            </form>

            @auth
                @unless ($canSortByDistance)
                    <p class="-mt-2 mb-5 text-sm text-gray-500">Para ordenar por proximidade, <a class="font-medium text-green-800 underline underline-offset-2" href="{{ route('account.edit') }}">adicione sua localização ao perfil</a>.</p>
                @endunless
            @endauth

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($listings as $l)
                    <a href="{{ route('listings.show', $l) }}" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
                        <div class="relative h-48 overflow-hidden bg-gradient-to-br from-green-50 to-amber-50">
                            @if ($l->image_path)
                                <img src="{{ asset('storage/'.$l->image_path) }}" alt="{{ $l->title }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                            @else
                                <div class="flex h-full items-center justify-center text-green-700/40">
                                    <x-application-logo class="h-16 w-16" />
                                </div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-green-900 shadow-sm">{{ $l->category->name }}</span>
                        </div>
                        <div class="p-4">
                            <h3 class="truncate font-semibold text-gray-900 group-hover:text-green-800">{{ $l->title }}</h3>
                            <p class="mt-2 text-xl font-bold tracking-tight text-green-900">R$ {{ number_format($l->price, 2, ',', '.') }} <span class="text-sm font-medium text-gray-500">/ {{ $l->unit }}</span></p>
                            @if ($l->quantity !== null)
                                <p class="mt-1 text-xs text-gray-500">{{ rtrim(rtrim(number_format($l->quantity, 2, '.', ''), '0'), '.') }} {{ $l->unit }} disponíveis</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                                <span class="truncate">{{ $l->city }} - {{ $l->state }}</span>
                                @if (isset($l->distance))
                                    <span class="ms-2 shrink-0 font-medium text-green-800">{{ $l->distance < 1 ? '< 1' : number_format($l->distance, 0, ',', '.') }} km</span>
                                @endif
                            </div>
                            <p class="mt-2 truncate text-xs text-gray-400">Anunciado por {{ $l->user->name }}</p>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-800">
                            <x-application-logo class="h-8 w-8" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">Nenhum anúncio encontrado</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Tente remover alguns filtros ou volte mais tarde. Você também pode ser o primeiro a anunciar um insumo.</p>
                        <div class="mt-5 flex flex-wrap justify-center gap-3">
                            <a href="{{ route('home') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Limpar busca</a>
                            @auth
                                <a href="{{ route('listings.create') }}" class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-900">Anunciar insumo</a>
                            @else
                                <a href="{{ route('register') }}" class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-900">Criar conta</a>
                            @endauth
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($listings->hasPages())
                <div class="mt-7">{{ $listings->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
