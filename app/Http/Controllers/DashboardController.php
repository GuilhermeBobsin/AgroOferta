<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Negotiation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $listings = $user->listings();
        $negotiations = Negotiation::query()
            ->where(fn ($query) => $query->where('buyer_id', $user->id)->orWhere('seller_id', $user->id));

        return view('dashboard', [
            'stats' => [
                'activeListings' => (clone $listings)->where('status', ListingStatus::Active->value)->count(),
                'negotiations' => (clone $negotiations)->count(),
                'completedSales' => (clone $listings)->where('status', ListingStatus::Sold->value)->count(),
            ],
            'recentListings' => (clone $listings)->with('category')->latest()->take(3)->get(),
            'recentNegotiations' => (clone $negotiations)->with(['listing', 'buyer', 'seller'])->latest('updated_at')->take(5)->get(),
        ]);
    }
}
