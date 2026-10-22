<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-green-800">Seu espaço no AgroOferta</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Olá, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-gray-600">Acompanhe seus anúncios, conversas e vendas em um só lugar.</p>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid gap-4 sm:grid-cols-3" aria-label="Resumo da conta">
            @foreach ([
                ['label' => 'Anúncios ativos', 'value' => $stats['activeListings'], 'icon' => '📦', 'href' => route('listings.mine')],
                ['label' => 'Negociações', 'value' => $stats['negotiations'], 'icon' => '💬', 'href' => route('negotiations.index')],
                ['label' => 'Vendas registradas', 'value' => $stats['completedSales'], 'icon' => '🤝', 'href' => route('listings.mine')],
            ] as $stat)
                <a href="{{ $stat['href'] }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-green-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-600">{{ $stat['label'] }}</p>
                        <span aria-hidden="true" class="text-xl">{{ $stat['icon'] }}</span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-green-950">{{ $stat['value'] }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-green-800">Ver detalhes <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </section>

        <section class="flex flex-col justify-between gap-4 rounded-2xl bg-gradient-to-r from-green-950 to-green-800 p-6 text-white shadow-sm sm:flex-row sm:items-center sm:p-8">
            <div>
                <h2 class="text-xl font-bold">Tem insumo sobrando ou precisa comprar?</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-green-100">Anuncie o que está disponível ou explore ofertas de outros produtores da sua região.</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ route('listings.create') }}" class="rounded-lg bg-amber-300 px-4 py-2.5 text-sm font-bold text-green-950 hover:bg-amber-200">Criar anúncio</a>
                <a href="{{ route('home') }}" class="rounded-lg border border-white/40 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10">Buscar insumos</a>
            </div>
        </section>

        <div class="grid gap-8 lg:grid-cols-2">
            <section aria-labelledby="dashboard-listings-title">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-green-800">Seus produtos</p>
                        <h2 id="dashboard-listings-title" class="text-xl font-bold text-gray-900">Anúncios recentes</h2>
                    </div>
                    <a href="{{ route('listings.mine') }}" class="text-sm font-semibold text-green-800 hover:underline">Ver todos</a>
                </div>
                @forelse ($recentListings as $listing)
                    <a href="{{ route('listings.show', $listing) }}" class="mb-3 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-green-300">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-800"><x-application-logo class="h-7 w-7" /></div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold text-gray-900">{{ $listing->title }}</h3>
                            <p class="mt-1 truncate text-sm text-gray-500">{{ $listing->category->name }} · {{ $listing->city }} - {{ $listing->state }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $listing->status->badgeClasses() }}">{{ $listing->status->label() }}</span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
                        <p class="font-semibold text-gray-900">Seu primeiro anúncio começa por aqui</p>
                        <p class="mt-1 text-sm text-gray-500">Publique um excedente para que outros produtores possam encontrá-lo.</p>
                        <a href="{{ route('listings.create') }}" class="mt-4 inline-flex rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-900">Anunciar insumo</a>
                    </div>
                @endforelse
            </section>

            <section aria-labelledby="dashboard-negotiations-title">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-green-800">Fale com outros produtores</p>
                        <h2 id="dashboard-negotiations-title" class="text-xl font-bold text-gray-900">Conversas recentes</h2>
                    </div>
                    <a href="{{ route('negotiations.index') }}" class="text-sm font-semibold text-green-800 hover:underline">Ver todas</a>
                </div>
                @forelse ($recentNegotiations as $negotiation)
                    @php $other = auth()->id() === $negotiation->buyer_id ? $negotiation->seller : $negotiation->buyer; @endphp
                    <a href="{{ route('negotiations.show', $negotiation) }}" class="mb-3 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:border-green-300">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-lg" aria-hidden="true">💬</div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold text-gray-900">{{ $negotiation->listing->title }}</h3>
                            <p class="mt-1 truncate text-sm text-gray-500">{{ $other->name }} · {{ $negotiation->updated_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-gray-400" aria-hidden="true">→</span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
                        <p class="font-semibold text-gray-900">Nenhuma conversa por enquanto</p>
                        <p class="mt-1 text-sm text-gray-500">Quando você demonstrar interesse em um anúncio, a negociação aparecerá aqui.</p>
                        <a href="{{ route('home') }}" class="mt-4 inline-flex rounded-lg border border-green-800 px-4 py-2 text-sm font-semibold text-green-900 hover:bg-green-50">Explorar anúncios</a>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
