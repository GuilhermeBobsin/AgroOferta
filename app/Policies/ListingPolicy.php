<?php

namespace App\Policies;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ListingPolicy
{
    /** Anúncio pausado só o dono enxerga (404 para os demais). */
    public function view(?User $user, Listing $listing): Response
    {
        return $listing->status !== ListingStatus::Paused || $user?->id === $listing->user_id
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    /** Abrir negociação: não pode ser o dono e o anúncio precisa estar ativo. */
    public function negotiate(User $user, Listing $listing): bool
    {
        return $user->id !== $listing->user_id && $listing->status === ListingStatus::Active;
    }
}
