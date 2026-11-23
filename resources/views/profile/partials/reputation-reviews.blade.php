<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="reputation-title">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 id="reputation-title" class="text-lg font-bold text-gray-900">Reputação e avaliações recebidas</h2>
            @if ($profileUser->reviews_received_count > 0)
                <p class="mt-1 text-sm text-amber-700">
                    <span aria-label="Nota média">★ {{ number_format((float) $profileUser->reputacao_nota, 1, ',', '') }} de 5</span>
                    <span class="text-gray-500">({{ $profileUser->reviews_received_count }} {{ $profileUser->reviews_received_count === 1 ? 'avaliação' : 'avaliações' }})</span>
                </p>
            @else
                <p class="mt-1 text-sm text-gray-500">Sem avaliações (0 avaliações)</p>
            @endif
        </div>
    </div>

    @if ($reviews->count() > 0)
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach ($reviews as $review)
                <article class="rounded-xl bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-gray-900">{{ $review->reviewer?->name ?? 'Usuário' }}</p>
                        <span class="shrink-0 text-amber-700" aria-label="{{ $review->rating }} de 5 estrelas">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $review->created_at->format('d/m/Y') }}</p>
                    @if ($review->comment)
                        <p class="mt-2 text-sm leading-6 text-gray-700">{{ $review->comment }}</p>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-5">{{ $reviews->links() }}</div>
    @endif
</section>
