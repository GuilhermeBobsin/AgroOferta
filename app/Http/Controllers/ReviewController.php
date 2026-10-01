<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Negotiation;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Negotiation $negotiation)
    {
        $user = $request->user();

        Review::create($request->validated() + [
            'negotiation_id' => $negotiation->id,
            'reviewer_id' => $user->id,
            'reviewed_id' => $user->id === $negotiation->buyer_id ? $negotiation->seller_id : $negotiation->buyer_id,
        ]);

        return back()->with('status', 'Avaliação enviada. Obrigado!');
    }
}
