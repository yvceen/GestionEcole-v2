<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index()
    {
        $userColumn = Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'recipient_user_id')
            ? 'recipient_user_id'
            : 'user_id';

        $notifications = ParentNotification::query()
            ->where($userColumn, auth()->id())
            ->latest('id')
            ->paginate(20);

        $view = 'notifications.index';
        Log::debug('ParentNotification@index rendering view', [
            'view' => $view,
            'user_id' => auth()->id(),
            'count' => $notifications->count(),
            'total' => method_exists($notifications, 'total') ? $notifications->total() : null,
            'user_column' => $userColumn,
        ]);

        return view($view, [
            'notifications' => $notifications,
            'role' => 'parent',
        ]);
    }

    public function open(ParentNotification $notification): RedirectResponse
    {
        $recipientId = (int) ($notification->recipient_user_id ?? $notification->user_id ?? 0);
        abort_unless($recipientId === (int) auth()->id(), 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $target = $this->resolveTargetUrl($notification);
        return redirect()->to($target);
    }

    public function markAsRead(ParentNotification $notification): RedirectResponse
    {
        $recipientId = (int) ($notification->recipient_user_id ?? $notification->user_id ?? 0);
        abort_unless($recipientId === (int) auth()->id(), 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return back();
    }

    private function resolveTargetUrl(ParentNotification $notification): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        if (!empty($data['url']) && is_string($data['url'])) {
            return $data['url'];
        }

        if (!empty($data['message_id']) && is_numeric($data['message_id'])) {
            return route('parent.messages.show', (int) $data['message_id']);
        }

        return match ($notification->type) {
            'appointment' => route('parent.appointments.create'),
            'message' => route('parent.messages.index'),
            'homework' => route('parent.homeworks.index'),
            'course' => route('parent.courses.index'),
            'news' => route('parent.dashboard'),
            default => route('parent.dashboard'),
        };
    }
}
