<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->messageNotifications()
            ->with(['message.user', 'message.negotiation.listing'])
            ->latest('id')->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function feed(Request $request)
    {
        $unreadCount = $request->user()->messageNotifications()->whereNull('read_at')->count();

        // With the bell closed, only run an indexed count; no messages or users are loaded.
        if ($request->boolean('summary')) {
            return response()->json(['unread_count' => $unreadCount])
                ->header('Cache-Control', 'no-store, private');
        }

        $notifications = $request->user()->messageNotifications()
            ->with(['message.user', 'message.negotiation.listing'])
            ->latest('id')->limit(8)->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'sender' => $notification->message->user->name,
                'listing' => $notification->message->negotiation->listing->title,
                'preview' => Str::limit($notification->message->body, 120),
                'time' => $notification->created_at->diffForHumans(),
                'unread' => $notification->read_at === null,
                'url' => route('negotiations.show', $notification->message->negotiation_id),
            ]),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function readAll(Request $request)
    {
        $request->user()->messageNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->noContent()
            : back()->with('status', 'Todas as notificações foram marcadas como lidas.');
    }
}
