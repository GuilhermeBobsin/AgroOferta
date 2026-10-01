<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Listing;
use App\Models\Negotiation;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NegotiationController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->user()->id;

        $negotiations = Negotiation::with(['listing', 'buyer', 'seller'])
            ->where(fn ($q) => $q->where('buyer_id', $id)->orWhere('seller_id', $id))
            ->latest('updated_at')->get();

        return view('negotiations.index', compact('negotiations'));
    }

    public function store(Request $request, Listing $listing)
    {
        Gate::authorize('negotiate', $listing);

        $negotiation = Negotiation::firstOrCreate([
            'listing_id' => $listing->id,
            'buyer_id' => $request->user()->id,
        ], ['seller_id' => $listing->user_id]);

        return redirect()->route('negotiations.show', $negotiation);
    }

    public function show(Request $request, Negotiation $negotiation)
    {
        Gate::authorize('view', $negotiation);

        $user = $request->user();
        $negotiation->load(['listing', 'buyer', 'seller', 'messages.user']);

        $myReview = Review::where('negotiation_id', $negotiation->id)->where('reviewer_id', $user->id)->first();
        $other = $user->id === $negotiation->buyer_id ? $negotiation->seller : $negotiation->buyer;

        return view('negotiations.show', compact('negotiation', 'myReview', 'other'));
    }

    public function message(MessageRequest $request, Negotiation $negotiation)
    {
        $negotiation->messages()->create(['user_id' => $request->user()->id, 'body' => $request->validated('body')]);
        $negotiation->touch();

        return back();
    }
}
