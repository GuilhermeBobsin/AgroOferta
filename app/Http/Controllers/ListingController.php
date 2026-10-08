<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Http\Requests\ListingRequest;
use App\Http\Requests\ListingStatusRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = $request->get('sort', 'recent');

        $query = Listing::active()
            ->with(['category', 'user'])
            ->when($request->q, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('title', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%");
            }))
            ->when($request->category, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->unit, fn ($q, $v) => $q->where('unit', $v))
            ->when($request->state, fn ($q, $v) => $q->where('state', strtoupper($v)))
            ->when(is_numeric($request->price_min), fn ($q) => $q->where('price', '>=', $request->price_min))
            ->when(is_numeric($request->price_max), fn ($q) => $q->where('price', '<=', $request->price_max));

        $canSortByDistance = $user && $user->latitude !== null && $user->longitude !== null;

        match (true) {
            $sort === 'price_asc' => $query->orderBy('price'),
            $sort === 'price_desc' => $query->orderByDesc('price'),
            $sort === 'nearest' && $canSortByDistance => $query->orderByDistance((float) $user->latitude, (float) $user->longitude),
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
        $seller = $listing->user->loadCount('reviewsReceived')->loadAvg('reviewsReceived', 'rating');
        $reviews = $seller->reviewsReceived()->with('reviewer')->latest()->take(5)->get();
        $isOwner = $request->user()?->id === $listing->user_id;

        return view('listings.show', compact('listing', 'seller', 'reviews', 'isOwner'));
    }

    public function mine(Request $request)
    {
        return view('listings.mine', [
            'listings' => $request->user()->listings()->with(['category', 'negotiations.buyer'])->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('listings.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(ListingRequest $request)
    {
        $user = $request->user();
        $data = $request->safe()->except('image');
        [$data['latitude'], $data['longitude']] = $this->coordinatesFor($user, $data['city'], $data['state']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('listings', 'public');
        }

        $listing = $user->listings()->create($data);

        return redirect()->route('listings.show', $listing)->with('status', 'Anúncio publicado!');
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
        $data = $request->safe()->except('image');

        // Mudou o local: as coordenadas antigas deixam de valer
        $moved = mb_strtolower($data['city']) !== mb_strtolower($listing->city) || $data['state'] !== $listing->state;
        if ($moved) {
            [$data['latitude'], $data['longitude']] = $this->coordinatesFor($request->user(), $data['city'], $data['state']);
        }

        if ($request->hasFile('image')) {
            if ($listing->image_path) {
                Storage::disk('public')->delete($listing->image_path);
            }
            $data['image_path'] = $request->file('image')->store('listings', 'public');
        }

        $listing->update($data);

        return redirect()->route('listings.mine')->with('status', 'Anúncio atualizado.');
    }

    public function updateStatus(ListingStatusRequest $request, Listing $listing)
    {
        $status = ListingStatus::from($request->validated('status'));

        $listing->update([
            'status' => $status,
            'sold_to_id' => $status === ListingStatus::Sold && $request->validated('buyer_id') !== 'outside'
                ? $request->validated('buyer_id')
                : null,
        ]);

        return back()->with('status', 'Status atualizado.');
    }

    public function destroy(Listing $listing)
    {
        Gate::authorize('delete', $listing);
        $listing->delete(); // a foto é removida pelo evento "deleting" do model

        return redirect()->route('listings.mine')->with('status', 'Anúncio removido.');
    }

    /**
     * Só reaproveita a localização do perfil se o anúncio for na mesma cidade/UF do usuário;
     * caso contrário fica sem coordenadas (e vai para o fim na ordenação por proximidade).
     */
    private function coordinatesFor(User $user, string $city, string $state): array
    {
        $sameCity = $user->latitude !== null && $user->longitude !== null
            && mb_strtolower(trim((string) $user->city)) === mb_strtolower(trim($city))
            && strtoupper((string) $user->state) === strtoupper($state);

        return $sameCity ? [$user->latitude, $user->longitude] : [null, null];
    }
}
