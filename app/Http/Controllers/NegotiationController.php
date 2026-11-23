<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Listing;
use App\Models\MessageNotification;
use App\Models\Negotiation;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NegotiationController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->user()->id;

        $negotiations = Negotiation::with(['listing', 'buyer', 'seller'])
            ->where(fn ($q) => $q->where('buyer_id', $id)->orWhere('seller_id', $id))
            ->latest('updated_at')->get();

        $unreadCounts = $request->user()->messageNotifications()
            ->whereNull('read_at')
            ->join('messages', 'messages.id', '=', 'message_notifications.message_id')
            ->selectRaw('messages.negotiation_id, COUNT(*) as total')
            ->groupBy('messages.negotiation_id')
            ->pluck('total', 'negotiation_id');

        return view('negotiations.index', compact('negotiations', 'unreadCounts'));
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
        $negotiation->markMessagesRead($user, (int) $negotiation->messages->max('id'));

        $myReview = Review::where('negotiation_id', $negotiation->id)->where('reviewer_id', $user->id)->first();
        $other = $user->id === $negotiation->buyer_id ? $negotiation->seller : $negotiation->buyer;

        return view('negotiations.show', compact('negotiation', 'myReview', 'other'));
    }

    public function message(MessageRequest $request, Negotiation $negotiation)
    {
        DB::transaction(function () use ($request, $negotiation) {
            $senderId = $request->user()->id;
            $message = $negotiation->messages()->create(['user_id' => $senderId, 'body' => $request->validated('body')]);

            MessageNotification::create([
                'user_id' => $senderId === $negotiation->buyer_id ? $negotiation->seller_id : $negotiation->buyer_id,
                'message_id' => $message->id,
            ]);
            $negotiation->touch();
        });

        return back();
    }

    public function messages(Request $request, Negotiation $negotiation)
    {
        Gate::authorize('view', $negotiation);

        $afterId = max(0, (int) $request->integer('after'));
        $userId = $request->user()->id;
        $messages = $negotiation->messages()
            ->with('user:id,name')
            ->where('id', '>', $afterId)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'author' => $message->user_id === $userId ? 'Você' : $message->user->name,
                'is_mine' => $message->user_id === $userId,
                'created_at' => $message->created_at->format('d/m H:i'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function readMessages(Request $request, Negotiation $negotiation)
    {
        Gate::authorize('view', $negotiation);

        $data = $request->validate(['through_id' => ['required', 'integer', 'min:1']]);
        $message = $negotiation->messages()->findOrFail($data['through_id']);
        $negotiation->markMessagesRead($request->user(), $message->id);

        return response()->noContent();
    }
}
