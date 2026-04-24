<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(fn (AppNotification $notification) => [
                'id' => (int) $notification->id,
                'type' => (string) $notification->type,
                'title' => (string) $notification->title,
                'body' => (string) $notification->body,
                'data' => $notification->data ?? [],
                'read_at' => optional($notification->read_at)?->toIso8601String(),
                'created_at' => optional($notification->created_at)?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'items' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->recipient_id === (int) $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
