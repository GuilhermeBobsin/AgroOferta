<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $listing->title }}</h2></x-slot>

    <div class="max-w-4xl mx-auto p-4 space-y-6">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        @if ($listing->status !== \App\Enums\ListingStatus::Active)
            <div class="p-3 bg-yellow-100 text-yellow-800 rounded">
                Este anúncio está <strong>{{ strtolower($listing->status->label()) }}</strong>.
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-gray-200 rounded-lg overflow-hidden min-h-48">
                @if ($listing->image_path)
                    <img src="{{ asset('storage/'.$listing->image_path) }}" alt="{{ $listing->title }}" class="w-full object-cover">
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-4 space-y-3">
                <p class="text-sm text-green-700">{{ $listing->category->name }}</p>
                <p class="text-3xl font-bold">R$ {{ number_format($listing->price, 2, ',', '.') }} <span class="text-base font-normal text-gray-500">/ {{ $listing->unit }}</span></p>
                @if ($listing->quantity) <p>Disponível: {{ rtrim(rtrim($listing->quantity, '0'), '.') }} {{ $listing->unit }}</p> @endif
                @if ($listing->negotiable) <p class="text-sm text-green-700">Aceita negociação</p> @endif
                <p class="text-gray-700 whitespace-pre-line">{{ $listing->description }}</p>
                <p class="text-sm text-gray-500">{{ $listing->city }} - {{ $listing->state }}</p>

                <div class="text-sm border-t pt-3">
                    <p>Vendedor: <strong>{{ $seller->name }}</strong></p>
                    @if ($seller->reviews_received_count)
                        <p class="text-yellow-600">
                            ★ {{ number_format($seller->reviews_received_avg_rating, 1, ',', '') }}
                            <span class="text-gray-500">({{ $seller->reviews_received_count }} avaliações)</span>
                        </p>
                    @else
                        <p class="text-gray-500">Ainda sem avaliações</p>
                    @endif
                </div>

                @if ($isOwner)
                    <div class="flex gap-2 text-sm">
                        <a href="{{ route('listings.edit', $listing) }}" class="px-3 py-2 border rounded-md">Editar</a>
                        <a href="{{ route('listings.mine') }}" class="px-3 py-2 border rounded-md">Meus anúncios</a>
                    </div>
                @endif

                @guest
                    <a href="{{ route('login') }}" class="block text-center bg-green-700 text-white py-3 rounded-md">Entre para negociar ou ver o WhatsApp</a>
                @endguest

                @can('negotiate', $listing)
                    <form method="POST" action="{{ route('negotiations.store', $listing) }}">
                        @csrf
                        <button class="w-full bg-green-700 text-white py-3 rounded-md">Negociar / Comprar</button>
                    </form>

                    {{-- O telefone só é exposto para usuários logados --}}
                    @if ($wa = $listing->whatsappUrl())
                        <a href="{{ $wa }}" target="_blank" rel="noopener"
                           class="block text-center bg-[#25D366] text-white py-3 rounded-md font-medium">Falar no WhatsApp</a>
                    @endif
                @endcan
            </div>
        </div>

        @if ($reviews->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Avaliações do vendedor</h3>
                <div class="space-y-3">
                    @foreach ($reviews as $r)
                        <div class="text-sm border-b last:border-0 pb-2">
                            <p><span class="text-yellow-600">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                               <span class="text-gray-500">— {{ $r->reviewer->name }}, {{ $r->created_at->format('d/m/Y') }}</span></p>
                            @if ($r->comment) <p class="text-gray-700">{{ $r->comment }}</p> @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
