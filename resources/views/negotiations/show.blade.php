<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Negociação: <a class="underline" href="{{ route('listings.show', $negotiation->listing) }}">{{ $negotiation->listing->title }}</a>
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-4 space-y-4">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow p-4 space-y-2 min-h-64">
            @forelse ($negotiation->messages as $m)
                <div class="flex {{ $m->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $m->user_id === auth()->id() ? 'bg-green-100' : 'bg-gray-100' }}">
                        <p class="text-xs text-gray-500">{{ $m->user->name }} · {{ $m->created_at->format('d/m H:i') }}</p>
                        <p class="whitespace-pre-line">{{ $m->body }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Envie a primeira mensagem (ex: proposta de preço e quantidade).</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('negotiations.message', $negotiation) }}" class="flex gap-2">
            @csrf
            <input name="body" required maxlength="2000" placeholder="Escreva sua mensagem..." class="flex-1 rounded-md border-gray-300">
            <button class="bg-green-700 text-white px-4 rounded-md">Enviar</button>
        </form>

        @if ($wa = $other->whatsappUrl('Olá! Sobre o anúncio "'.$negotiation->listing->title.'" no AgroOferta.'))
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="block text-center bg-[#25D366] text-white py-2 rounded-md text-sm">
                Continuar no WhatsApp com {{ $other->name }}
            </a>
        @endif

        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Avaliar {{ $other->name }}</h3>

            @if ($myReview)
                <p class="text-sm text-gray-700">
                    Você avaliou: <span class="text-yellow-600">{{ str_repeat('★', $myReview->rating) }}{{ str_repeat('☆', 5 - $myReview->rating) }}</span>
                    @if ($myReview->comment) — "{{ $myReview->comment }}" @endif
                </p>
            @elseif (auth()->user()->can('review', $negotiation))
                @error('rating') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
                <form method="POST" action="{{ route('reviews.store', $negotiation) }}" class="space-y-2">
                    @csrf
                    <select name="rating" required class="rounded-md border-gray-300 text-sm">
                        <option value="">Nota</option>
                        @foreach ([5 => '★★★★★ Excelente', 4 => '★★★★ Bom', 3 => '★★★ Regular', 2 => '★★ Ruim', 1 => '★ Péssimo'] as $v => $label)
                            <option value="{{ $v }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="comment" rows="2" maxlength="500" placeholder="Comentário (opcional)" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <button class="bg-gray-800 text-white px-4 py-2 rounded-md text-sm">Enviar avaliação</button>
                </form>
            @else
                <p class="text-sm text-gray-500">A avaliação é liberada quando o vendedor marcar o anúncio como vendido para este comprador.</p>
            @endif
        </div>
    </div>
</x-app-layout>
