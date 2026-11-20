<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $origin = $user->coordinatesForDistance();
        $listings = $user->favoriteListings()
            ->with(['category', 'user'])
            ->where('status', ListingStatus::Active->value)
            ->when($origin, fn ($query) => $query->withDistance($origin[0], $origin[1]))
            ->latest('favorites.created_at')
            ->paginate(12);

        return view('favorites.index', compact('listings'));
    }

    public function store(Request $request, Listing $listing)
    {
        abort_unless(
            $listing->status === ListingStatus::Active && $listing->user_id !== $request->user()->id,
            404,
        );

        $request->user()->favoriteListings()->syncWithoutDetaching([$listing->id]);

        return back()->with('status', 'Anúncio salvo nos favoritos.');
    }

    public function destroy(Request $request, Listing $listing)
    {
        $request->user()->favoriteListings()->detach($listing->id);

        return back()->with('status', 'Anúncio removido dos favoritos.');
    }
}
