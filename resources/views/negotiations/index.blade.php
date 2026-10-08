<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-green-800">Converse diretamente com outros produtores</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Minhas negociações</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        @if ($negotiations->isNotEmpty())
            <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-sm text-gray-500">{{ $negotiations->count() }} {{ $negotiations->count() === 1 ? 'conversa' : 'conversas' }}</p>
                <a href="{{ route('home') }}" class="text-sm font-semibold text-green-800 hover:underline">Encontrar mais insumos</a>
            </div>

            <div class="space-y-3">
                @foreach ($negotiations as $n)
                    @php
                        $isBuyer = auth()->id() === $n->buyer_id;
                        $other = $isBuyer ? $n->seller : $n->buyer;
                    @endphp
                    <a href="{{ route('negotiations.show', $n) }}" class="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-green-300 hover:shadow-md sm:p-5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-900">
                            <x-application-logo class="h-7 w-7" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                                <h2 class="truncate font-semibold text-gray-900 group-hover:text-green-800">{{ $n->listing->title }}</h2>
                                <span class="shrink-0 text-xs text-gray-500">{{ $n->updated_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">{{ $isBuyer ? 'Vendedor' : 'Comprador' }}: {{ $other->name }}</p>
                            <p class="mt-1 text-sm font-medium text-green-900">R$ {{ number_format($n->listing->price, 2, ',', '.') }} / {{ $n->listing->unit }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full px-2.5 py-1 font-semibold {{ $n->listing->status->badgeClasses() }}">Anúncio {{ strtolower($n->listing->status->label()) }}</span>
                                @if ($n->isClosedWithBuyer())
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 font-semibold text-green-900">Venda confirmada entre vocês</span>
                                @elseif ($n->listing->status === \App\Enums\ListingStatus::Sold)
                                    <span class="text-gray-500">Venda registrada para outra pessoa ou fora da plataforma</span>
                                @endif
                            </div>
                        </div>
                        <span aria-hidden="true" class="mt-2 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-green-800">&#8594;</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-800"><x-application-logo class="h-8 w-8" /></div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Suas conversas aparecerão aqui</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">Quando você iniciar uma negociação ou alguém demonstrar interesse em um dos seus anúncios, poderá acompanhar a conversa nesta página.</p>
                <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-900">Explorar insumos</a>
            </div>
        @endif
    </div>
</x-app-layout>
