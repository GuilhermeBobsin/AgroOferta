<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Meus anúncios</h2>
            <a href="{{ route('listings.create') }}" class="bg-green-700 text-white px-4 py-2 rounded-md text-sm">+ Anunciar</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-4 space-y-3">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @error('buyer_id') <div class="p-3 bg-red-100 text-red-800 rounded text-sm">Comprador inválido para este anúncio.</div> @enderror

        @forelse ($listings as $l)
            <div class="bg-white rounded-lg shadow p-3 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('listings.show', $l) }}" class="font-semibold hover:underline block truncate">{{ $l->title }}</a>
                        <p class="text-sm text-gray-500">
                            R$ {{ number_format($l->price, 2, ',', '.') }} / {{ $l->unit }} · {{ $l->city }}-{{ $l->state }}
                        </p>
                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded {{ $l->status->badgeClasses() }}">{{ $l->status->label() }}</span>
                    </div>

                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="{{ route('listings.edit', $l) }}" class="px-3 py-1 border rounded-md">Editar</a>

                        @if ($l->status !== \App\Enums\ListingStatus::Active)
                            <form method="POST" action="{{ route('listings.status', $l) }}">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="active">
                                <button class="px-3 py-1 border rounded-md text-green-700">Reativar</button>
                            </form>
                        @endif
                        @if ($l->status === \App\Enums\ListingStatus::Active)
                            <form method="POST" action="{{ route('listings.status', $l) }}">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="paused">
                                <button class="px-3 py-1 border rounded-md">Pausar</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('listings.destroy', $l) }}" onsubmit="return confirm('Excluir este anúncio?')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1 border rounded-md text-red-600">Excluir</button>
                        </form>
                    </div>
                </div>

                {{-- Marcar como vendido: informar o comprador libera a avaliação entre as partes --}}
                @if ($l->status !== \App\Enums\ListingStatus::Sold)
                    <form method="POST" action="{{ route('listings.status', $l) }}" class="flex flex-wrap items-center gap-2 text-sm border-t pt-3">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="sold">
                        <span class="text-gray-600">Vendido para:</span>
                        <select name="buyer_id" class="rounded-md border-gray-300 text-sm">
                            <option value="">Fora da plataforma</option>
                            @foreach ($l->negotiations as $n)
                                <option value="{{ $n->buyer_id }}">{{ $n->buyer->name }}</option>
                            @endforeach
                        </select>
                        <button class="px-3 py-1 border rounded-md">Marcar vendido</button>
                    </form>
                @elseif ($l->sold_to_id)
                    <p class="text-xs text-gray-500 border-t pt-2">Venda fechada na plataforma. Avalie o comprador em <a class="underline" href="{{ route('negotiations.index') }}">Negociações</a>.</p>
                @endif
            </div>
        @empty
            <p class="text-gray-500">Você ainda não publicou anúncios.</p>
        @endforelse
    </div>
</x-app-layout>
