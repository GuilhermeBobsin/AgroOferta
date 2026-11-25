<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Negotiation;
use App\Models\Review;
use App\Services\ReputationService;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Negotiation $negotiation, ReputationService $reputation)
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $negotiation, $reputation, $user): void {
            $review = Review::create($request->validated() + [
                'negotiation_id' => $negotiation->id,
                'reviewer_id' => $user->id,
                'reviewed_id' => $user->id === $negotiation->buyer_id ? $negotiation->seller_id : $negotiation->buyer_id,
            ]);

            $reputation->refresh($review->reviewed);
        });

        return back()->with('status', 'Avaliação enviada. Obrigado!');
    }
}
