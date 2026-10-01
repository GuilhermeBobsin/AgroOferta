<?php

namespace App\Policies;

use App\Models\Negotiation;
use App\Models\Review;
use App\Models\User;

class NegotiationPolicy
{
    public function view(User $user, Negotiation $negotiation): bool
    {
        return $negotiation->hasParticipant($user);
    }

    public function message(User $user, Negotiation $negotiation): bool
    {
        return $negotiation->hasParticipant($user);
    }

    /** Só participantes, só depois da venda fechada com esse comprador, uma vez por pessoa. */
    public function review(User $user, Negotiation $negotiation): bool
    {
        return $negotiation->hasParticipant($user)
            && $negotiation->isClosedWithBuyer()
            && ! Review::where('negotiation_id', $negotiation->id)->where('reviewer_id', $user->id)->exists();
    }
}
