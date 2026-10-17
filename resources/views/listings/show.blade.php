<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('home') }}" class="text-sm font-medium text-green-800 hover:underline">&larr; Voltar aos anúncios</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">{{ $listing->title }}</h1>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</div>
        @endif

        @if ($listing->status !== \App\Enums\ListingStatus::Active)
            <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                Este anúncio está <strong>{{ strtolower($listing->status->label()) }}</strong>. Não é possível iniciar uma nova negociação.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-green-50 to-amber-50 shadow-sm">
                @if ($listing->image_path)
                    <img src="{{ asset('storage/'.$listing->image_path) }}" alt="{{ $listing->title }}" class="h-full min-h-72 max-h-[34rem] w-full object-cover">
                @else
                    <div class="flex min-h-72 h-full items-center justify-center text-green-800/40">
                        <x-application-logo class="h-28 w-28" />
                    </div>
                @endif
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                <p class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-900">{{ $listing->category->name }}</p>
                <p class="mt-4 text-3xl font-bold tracking-tight text-green-950">R$ {{ number_format($listing->price, 2, ',', '.') }} <span class="text-base font-medium text-gray-500">/ {{ $listing->unit }}</span></p>

                @if ($listing->quantity !== null)
                    <p class="mt-2 text-sm text-gray-700"><span class="font-semibold">Quantidade:</span> {{ rtrim(rtrim(number_format($listing->quantity, 2, '.', ''), '0'), '.') }} {{ $listing->unit }}</p>
                @endif

                <p class="mt-2 text-sm {{ $listing->negotiable ? 'text-green-800' : 'text-gray-600' }}">{{ $listing->negotiable ? 'O vendedor aceita propostas.' : 'Preço anunciado como fixo.' }}</p>

                @if ($listing->description)
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <h2 class="text-sm font-semibold text-gray-900">Sobre o produto</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $listing->description }}</p>
                    </div>
                @endif

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <h2 class="text-sm font-semibold text-gray-900">Localização</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ $listing->city }} - {{ $listing->state }}</p>
                    <p class="mt-1 text-xs text-gray-400">Endereço exato combinado diretamente entre as partes.</p>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <h2 class="text-sm font-semibold text-gray-900">Anunciado por</h2>
                    <p class="mt-1 text-sm text-gray-700">{{ $seller->name }}</p>
                    @if ($seller->reviews_received_count)
                        <p class="mt-1 text-sm text-amber-700">
                            <span aria-label="Nota média">★ {{ number_format($seller->reviews_received_avg_rating, 1, ',', '') }}</span>
                            <span class="text-gray-500">({{ $seller->reviews_received_count }} {{ $seller->reviews_received_count === 1 ? 'avaliação' : 'avaliações' }})</span>
                        </p>
                    @else
                        <p class="mt-1 text-sm text-gray-500">Ainda sem avaliações</p>
                    @endif
                </div>

                @if ($isOwner)
                    <div class="mt-6 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('listings.edit', $listing) }}" class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Editar anúncio</a>
                        <a href="{{ route('listings.mine') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Meus anúncios</a>
                    </div>
                @elseif ($listing->status === \App\Enums\ListingStatus::Active)
                    @guest
                        <a href="{{ route('login') }}" class="mt-6 block rounded-lg bg-green-800 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-green-900">Entre para negociar com o vendedor</a>
                        <p class="mt-2 text-center text-xs text-gray-500">Ainda não tem conta? <a class="font-medium text-green-800 underline" href="{{ route('register') }}">Cadastre-se</a>.</p>
                    @endguest

                    @can('negotiate', $listing)
                        <div class="mt-6 space-y-2">
                            <form method="POST" action="{{ route('negotiations.store', $listing) }}">
                                @csrf
                                <button class="w-full rounded-lg bg-green-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Iniciar negociação</button>
                            </form>
                            @if ($wa = $listing->whatsappUrl('Olá! Vi seu anúncio "'.$listing->title.'" no AgroOferta.'))
                                <a href="{{ $wa }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg border border-green-700 px-4 py-3 text-center text-sm font-semibold text-green-900 transition hover:bg-green-50">Conversar pelo WhatsApp</a>
                            @endif
                            <p class="text-center text-xs leading-5 text-gray-500">Negocie os detalhes e combine pagamento e retirada diretamente com o vendedor.</p>
                        </div>
                    @endcan
                @endif
            </section>
        </div>

        @if ($reviews->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="reviews-title">
                <h2 id="reviews-title" class="text-lg font-bold text-gray-900">Avaliações de {{ $seller->name }}</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($reviews as $r)
                        <article class="rounded-xl bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-medium text-gray-900">{{ $r->reviewer->name }}</p>
                                <span class="shrink-0 text-amber-700" aria-label="{{ $r->rating }} de 5 estrelas">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $r->created_at->format('d/m/Y') }}</p>
                            @if ($r->comment)
                                <p class="mt-2 text-sm leading-6 text-gray-700">{{ $r->comment }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
