<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-green-800">Gerencie seus produtos anunciados</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Meus anúncios</h1>
            </div>
            <a href="{{ route('listings.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">+ Anunciar insumo</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 py-8 space-y-4">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</div>
        @endif
        @error('buyer_id')
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Selecione um comprador que negociou este anúncio ou informe que a venda ocorreu fora do AgroOferta.</div>
        @enderror

        @forelse ($listings as $l)
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5">
                    <div class="h-24 w-full shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-green-50 to-amber-50 sm:h-24 sm:w-32">
                        @if ($l->image_path)
                            <img src="{{ asset('storage/'.$l->image_path) }}" alt="{{ $l->title }}" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-green-800/40"><x-application-logo class="h-10 w-10" /></div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('listings.show', $l) }}" class="block truncate text-lg font-semibold text-gray-900 hover:text-green-800 hover:underline">{{ $l->title }}</a>
                        <p class="mt-1 text-sm font-semibold text-green-900">R$ {{ number_format($l->price, 2, ',', '.') }} / {{ $l->unit }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $l->city }} - {{ $l->state }} · {{ $l->category->name }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $l->status->badgeClasses() }}">{{ $l->status->label() }}</span>
                            <span class="text-xs text-gray-500">{{ $l->negotiations->count() }} {{ $l->negotiations->count() === 1 ? 'negociação' : 'negociações' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <a href="{{ route('listings.edit', $l) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Editar</a>

                        @if ($l->status !== \App\Enums\ListingStatus::Active)
                            <form method="POST" action="{{ route('listings.status', $l) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="active">
                                <button class="rounded-lg border border-green-700 px-3 py-2 text-sm font-semibold text-green-900 hover:bg-green-50">Reativar</button>
                            </form>
                        @endif
                        @if ($l->status === \App\Enums\ListingStatus::Active)
                            <form method="POST" action="{{ route('listings.status', $l) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="paused">
                                <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Pausar</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('listings.destroy', $l) }}" onsubmit="return confirm('Excluir este anúncio e sua foto?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Excluir</button>
                        </form>
                    </div>
                </div>

                @if ($l->status !== \App\Enums\ListingStatus::Sold)
                    <form method="POST" action="{{ route('listings.status', $l) }}" class="border-t border-gray-100 bg-gray-50/70 p-4 sm:px-5">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="sold">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="buyer_id_{{ $l->id }}" class="mb-1 block text-sm font-semibold text-gray-800">Concluir venda</label>
                                <select id="buyer_id_{{ $l->id }}" name="buyer_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                    <option value="" disabled selected>Selecione onde a venda aconteceu</option>
                                    @foreach ($l->negotiations as $n)
                                        <option value="{{ $n->buyer_id }}">Negociação com {{ $n->buyer->name }}</option>
                                    @endforeach
                                    <option value="outside">Venda fora do AgroOferta</option>
                                </select>
                            </div>
                            <button class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-900">Marcar como vendido</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500">Escolha a negociação para liberar a avaliação entre vocês. Vendas feitas fora do AgroOferta não habilitam avaliações pela plataforma.</p>
                    </form>
                @elseif ($l->sold_to_id)
                    <div class="border-t border-gray-100 bg-green-50/60 px-4 py-3 text-sm text-gray-700 sm:px-5">
                        Venda registrada pelo AgroOferta. <a class="font-semibold text-green-900 underline underline-offset-2" href="{{ route('negotiations.index') }}">Abra as negociações para avaliar o comprador.</a>
                    </div>
                @else
                    <div class="border-t border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600 sm:px-5">Venda registrada fora do AgroOferta; avaliações pela plataforma não estão disponíveis.</div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-800"><x-application-logo class="h-8 w-8" /></div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Você ainda não publicou anúncios</h2>
                <p class="mt-2 text-sm text-gray-500">Anuncie um insumo disponível para que outros produtores possam encontrá-lo.</p>
                <a href="{{ route('listings.create') }}" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Criar meu primeiro anúncio</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
