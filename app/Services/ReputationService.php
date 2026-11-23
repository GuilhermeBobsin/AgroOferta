<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReputationService
{
    public function loadSummary(User $user): User
    {
        $user->loadCount('reviewsReceived')
            ->loadAvg('reviewsReceived', 'rating');

        $user->setAttribute('reputacao_nota', $user->reviews_received_avg_rating);

        return $user;
    }

    public function refresh(User $user): void
    {
        $user->forceFill([
            'reputacao_nota' => $user->reviewsReceived()->avg('rating'),
        ])->saveQuietly();
    }

    public function paginateReviews(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, Review> */
    public function latestReviews(User $user, int $limit = 5): Collection
    {
        return $user->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
