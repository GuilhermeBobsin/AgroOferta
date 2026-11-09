<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Http\Requests\ListingIndexRequest;
use App\Http\Requests\ListingRequest;
use App\Http\Requests\ListingStatusRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Support\BrazilLocation;
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
        $origin = $user?->coordinatesForDistance();
        $canSortByDistance = $origin !== null;

        $query = Listing::active()
            ->with(['category', 'user'])
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('title', 'like', "%{$v}%")
                    ->orWhere('description', 'like', "%{$v}%");
            }))
            ->when($request->category, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->unit, fn ($q, $v) => $q->where('unit', $v))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.trim($request->city).'%'))
            ->when($request->state, fn ($q, $v) => $q->where('state', strtoupper($v)))
            ->when(is_numeric($request->price_min), fn ($q) => $q->where('price', '>=', $request->price_min))
            ->when(is_numeric($request->price_max), fn ($q) => $q->where('price', '<=', $request->price_max));

        if (($filters['distance_km'] ?? null) !== null && $canSortByDistance) {
            $query->nearby(
                $origin[0],
                $origin[1],
                (float) $filters['distance_km'],
            );
        }

        match (true) {
            $sort === 'price_asc' => $query->orderBy('price'),
            $sort === 'price_desc' => $query->orderByDesc('price'),
            $sort === 'nearest' && $canSortByDistance && ($filters['distance_km'] ?? null) !== null => $query->orderBy('distance'),
            $sort === 'nearest' && $canSortByDistance => $query->orderByDistance($origin[0], $origin[1]),
            default => $query->latest(),
        };

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
        $salesFilter = $request->input('sales', 'all');
        $salesFilter = in_array($salesFilter, ['all', 'open', 'closed'], true)
            ? $salesFilter
            : 'all';

        $listings = $request->user()
            ->listings()
            ->with(['category', 'negotiations.buyer'])
            ->when($salesFilter === 'open', fn ($query) => $query->whereIn('status', [
                ListingStatus::Active->value,
                ListingStatus::Paused->value,
            ]))
            ->when($salesFilter === 'closed', fn ($query) => $query->where('status', ListingStatus::Sold->value))
            ->latest()
            ->get();

        return view('listings.mine', [
            'listings' => $listings,
            'salesFilter' => $salesFilter,
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
            'location_precision',
            'use_exact_location',
        ]);

        $coordinates = $request->safe()->only([
            'latitude',
            'longitude',
        ]);

        if ($request->boolean('use_exact_location') && isset($coordinates['latitude'], $coordinates['longitude'])) {
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            $data['location_precision'] = 'exact';
        } else {
            $this->useCityCoordinates($data);
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
            'location_precision',
            'use_exact_location',
        ]);

        $coordinates = $request->safe()->only([
            'latitude',
            'longitude',
        ]);

        $moved = mb_strtolower(trim($data['city']))
                !== mb_strtolower(trim($listing->city))
            || strtoupper($data['state']) !== strtoupper($listing->state);

        $sameCoordinates = $listing->latitude !== null && $listing->longitude !== null
            && isset($coordinates['latitude'], $coordinates['longitude'])
            && (float) $coordinates['latitude'] === (float) $listing->latitude
            && (float) $coordinates['longitude'] === (float) $listing->longitude;

        if ($request->boolean('use_exact_location')
            && isset($coordinates['latitude'], $coordinates['longitude'])
            && ! ($moved && $sameCoordinates)) {
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            $data['location_precision'] = 'exact';
        } elseif ($moved || ! isset($coordinates['latitude'], $coordinates['longitude'])) {
            $this->useCityCoordinates($data);
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

    /** @param array<string, mixed> $data */
    private function useCityCoordinates(array &$data): void
    {
        $coordinates = BrazilLocation::coordinates($data['state'], $data['city']);

        $data['latitude'] = $coordinates[0] ?? null;
        $data['longitude'] = $coordinates[1] ?? null;
        $data['location_precision'] = $coordinates ? 'city' : null;
    }
}
