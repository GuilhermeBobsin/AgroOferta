<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Http\Requests\ListingIndexRequest;
use App\Http\Requests\ListingRequest;
use App\Http\Requests\ListingStatusRequest;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function index(ListingIndexRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'recent';

        $query = Listing::active()
            ->with(['category', 'user'])
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('title', 'like', "%{$v}%")
                    ->orWhere('description', 'like', "%{$v}%");
            }))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['unit'] ?? null, fn ($q, $v) => $q->where('unit', $v))
            ->when($filters['state'] ?? null, fn ($q, $v) => $q->where('state', strtoupper($v)))
            ->when(isset($filters['price_min']), fn ($q) => $q->where('price', '>=', $filters['price_min']))
            ->when(isset($filters['price_max']), fn ($q) => $q->where('price', '<=', $filters['price_max']));

        $canSortByDistance = $user
            && $user->latitude !== null
            && $user->longitude !== null;

        $distanceKm = $filters['distance_km'] ?? null;

        if ($distanceKm !== null && $distanceKm !== '' && $canSortByDistance) {
            $query->nearby(
                (float) $user->latitude,
                (float) $user->longitude,
                (float) $distanceKm
            );
        } else {
            match (true) {
                $sort === 'price_asc' => $query->orderBy('price'),
                $sort === 'price_desc' => $query->orderByDesc('price'),
                $sort === 'nearest' && $canSortByDistance => $query->orderByDistance(
                    (float) $user->latitude,
                    (float) $user->longitude
                ),
                default => $query->latest(),
            };
        }

        return view('listings.index', [
            'listings' => $query->paginate(12)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
            'canSortByDistance' => $canSortByDistance,
        ]);
    }

    public function show(Request $request, Listing $listing)
    {
        Gate::authorize('view', $listing);

        $listing->load(['category', 'user']);
        $seller = $listing->user->loadCount('reviewsReceived')
            ->loadAvg('reviewsReceived', 'rating');
        $reviews = $seller->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->take(5)
            ->get();
        $isOwner = $request->user()?->id === $listing->user_id;

        return view('listings.show', compact('listing', 'seller', 'reviews', 'isOwner'));
    }

    public function mine(Request $request)
    {
        return view('listings.mine', [
            'listings' => $request->user()
                ->listings()
                ->with(['category', 'negotiations.buyer'])
                ->latest()
                ->get(),
        ]);
    }

    public function create()
    {
        return view('listings.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ListingRequest $request)
    {
        $user = $request->user();

        $data = $request->safe()->except([
            'image',
            'latitude',
            'longitude',
        ]);

        $coordinates = $request->safe()->only([
            'latitude',
            'longitude',
        ]);

        $sameCity = mb_strtolower(trim((string) $user->city))
                === mb_strtolower(trim($data['city']))
            && strtoupper((string) $user->state) === strtoupper($data['state']);

        if (isset($coordinates['latitude'], $coordinates['longitude'])) {
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
        } elseif (
            $sameCity
            && $user->latitude !== null
            && $user->longitude !== null
        ) {
            $data['latitude'] = $user->latitude;
            $data['longitude'] = $user->longitude;
        } else {
            $data['latitude'] = null;
            $data['longitude'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('listings', 'public');
        }

        $listing = $user->listings()->create($data);

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Anúncio publicado!');
    }

    public function edit(Listing $listing)
    {
        Gate::authorize('update', $listing);

        return view('listings.edit', [
            'listing' => $listing,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        $data = $request->safe()->except([
            'image',
            'latitude',
            'longitude',
        ]);

        $coordinates = $request->safe()->only([
            'latitude',
            'longitude',
        ]);

        $moved = mb_strtolower(trim($data['city']))
                !== mb_strtolower(trim($listing->city))
            || strtoupper($data['state']) !== strtoupper($listing->state);

        if (isset($coordinates['latitude'], $coordinates['longitude'])) {
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
        } elseif ($moved) {
            $data['latitude'] = null;
            $data['longitude'] = null;
        }

        if ($request->hasFile('image')) {
            if ($listing->image_path) {
                Storage::disk('public')->delete($listing->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('listings', 'public');
        }

        $listing->update($data);

        return redirect()
            ->route('listings.mine')
            ->with('status', 'Anúncio atualizado.');
    }

    public function updateStatus(ListingStatusRequest $request, Listing $listing)
    {
        Gate::authorize('update', $listing);

        $status = ListingStatus::from($request->validated('status'));
        $buyerId = $request->validated('buyer_id');

        $listing->update([
            'status' => $status,
            'sold_to_id' => $status === ListingStatus::Sold
                && $buyerId !== 'outside'
                ? $buyerId
                : null,
        ]);

        return back()->with('status', 'Status atualizado.');
    }

    public function destroy(Listing $listing)
    {
        Gate::authorize('delete', $listing);

        $listing->delete();

        return redirect()
            ->route('listings.mine')
            ->with('status', 'Anúncio removido.');
    }
}