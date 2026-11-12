<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->with('auction')
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless(
            (string) $notification->user_id === (string) $request->user()->user_id,
            403,
        );

        $notification->update(['is_read' => true]);

        if ($notification->auction) {
            return redirect()->route('auctions.show', $notification->auction);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->route('notifications.index');
    }
}
