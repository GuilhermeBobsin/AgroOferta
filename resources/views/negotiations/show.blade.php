<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('negotiations.index') }}" class="text-sm font-medium text-green-800 hover:underline">&larr; Todas as negociações</a>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                <a class="hover:text-green-800" href="{{ route('listings.show', $negotiation->listing) }}">{{ $negotiation->listing->title }}</a>
            </h1>
            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $negotiation->listing->status->badgeClasses() }}">Anúncio {{ strtolower($negotiation->listing->status->label()) }}</span>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 py-8 space-y-5">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('status') }}</div>
        @endif

        @if ($negotiation->isClosedWithBuyer())
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-950">
                <p class="font-semibold">Venda registrada entre vocês</p>
                <p class="mt-1">Vocês podem avaliar a experiência nesta página. Combine pagamento e retirada diretamente com a outra pessoa.</p>
            </div>
        @elseif ($negotiation->listing->status === \App\Enums\ListingStatus::Sold)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                O vendedor registrou o anúncio como vendido para outra pessoa ou fora do AgroOferta. Esta negociação não habilita avaliações.
            </div>
        @endif

        <section aria-label="Mensagens da negociação" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="max-h-[32rem] min-h-64 space-y-3 overflow-y-auto rounded-xl bg-gray-50 p-3 sm:p-4" aria-live="polite">
                @forelse ($negotiation->messages as $m)
                    @php $isMine = $m->user_id === auth()->id(); @endphp
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <article class="max-w-[88%] rounded-2xl px-4 py-3 text-sm shadow-sm sm:max-w-[78%] {{ $isMine ? 'rounded-br-md bg-green-800 text-white' : 'rounded-bl-md bg-white text-gray-800 ring-1 ring-gray-200' }}">
                            <p class="mb-1 text-xs {{ $isMine ? 'text-green-100' : 'text-gray-500' }}">{{ $isMine ? 'Você' : $m->user->name }} · {{ $m->created_at->format('d/m H:i') }}</p>
                            <p class="whitespace-pre-line break-words leading-6">{{ $m->body }}</p>
                        </article>
                    </div>
                @empty
                    <div class="flex min-h-56 flex-col items-center justify-center px-4 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900"><x-application-logo class="h-7 w-7" /></div>
                        <p class="mt-3 font-semibold text-gray-900">Comece a conversa</p>
                        <p class="mt-1 max-w-sm text-sm leading-5 text-gray-500">Pergunte sobre quantidade, conservação e retirada. Se fizer uma proposta, informe o valor e a unidade.</p>
                    </div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('negotiations.message', $negotiation) }}" class="mt-4">
                @csrf
                <label for="body" class="mb-1 block text-sm font-semibold text-gray-800">Sua mensagem para {{ $other->name }}</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <textarea id="body" name="body" required maxlength="2000" rows="2" placeholder="Escreva sua mensagem..." class="min-h-12 flex-1 resize-y rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('body') }}</textarea>
                    <button class="rounded-lg bg-green-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-900">Enviar mensagem</button>
                </div>
                @error('body') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </form>

            @if ($wa = $other->whatsappUrl('Olá! Sobre o anúncio "'.$negotiation->listing->title.'" no AgroOferta.'))
                <a href="{{ $wa }}" target="_blank" rel="noopener noreferrer" class="mt-3 block rounded-lg border border-green-700 px-4 py-2.5 text-center text-sm font-semibold text-green-900 transition hover:bg-green-50">Continuar no WhatsApp com {{ $other->name }}</a>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="review-title">
            <h2 id="review-title" class="text-lg font-bold text-gray-900">Avaliar {{ $other->name }}</h2>

            @if ($myReview)
                <p class="mt-3 text-sm text-gray-700">
                    Sua avaliação: <span class="text-amber-700" aria-label="{{ $myReview->rating }} de 5 estrelas">{{ str_repeat('★', $myReview->rating) }}{{ str_repeat('☆', 5 - $myReview->rating) }}</span>
                    @if ($myReview->comment) <span> — “{{ $myReview->comment }}”</span> @endif
                </p>
            @elseif (auth()->user()->can('review', $negotiation))
                <p class="mt-1 text-sm text-gray-500">Conte como foi negociar com esta pessoa. Sua avaliação ficará visível no perfil público dela.</p>
                <form method="POST" action="{{ route('reviews.store', $negotiation) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="rating" class="mb-1 block text-sm font-medium text-gray-700">Sua nota</label>
                        <select id="rating" name="rating" required class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <option value="">Selecione uma nota</option>
                            @foreach ([5 => '5 estrelas — Excelente', 4 => '4 estrelas — Bom', 3 => '3 estrelas — Regular', 2 => '2 estrelas — Ruim', 1 => '1 estrela — Péssimo'] as $v => $label)
                                <option value="{{ $v }}" @selected(old('rating') == $v)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('rating') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="comment" class="mb-1 block text-sm font-medium text-gray-700">Comentário (opcional)</label>
                        <textarea id="comment" name="comment" rows="3" maxlength="500" placeholder="Compartilhe um detalhe útil sobre a negociação." class="w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('comment') }}</textarea>
                        @error('comment') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <button class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-900">Enviar avaliação</button>
                </form>
            @elseif ($negotiation->listing->status !== \App\Enums\ListingStatus::Sold)
                <p class="mt-2 text-sm text-gray-500">A avaliação será liberada quando o vendedor marcar este anúncio como vendido para você.</p>
            @else
                <p class="mt-2 text-sm text-gray-500">Esta negociação não foi marcada como a venda concluída, então a avaliação não está disponível.</p>
            @endif
        </section>
    </div>
</x-app-layout>
