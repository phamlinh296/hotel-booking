<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'message', 'is_read', 'created_at']);

        return response()->json($notifications);
    }

    // PUT /api/notifications/{id}/read
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    // POST /api/notifications (tạo noti thủ công, hoặc khi có sự kiện)
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json($notification, 201);
    }
}
