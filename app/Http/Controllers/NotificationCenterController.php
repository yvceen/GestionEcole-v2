<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class NotificationCenterController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userColumn = $this->userColumn();

        $notifications = AppNotification::query()
            ->where($userColumn, $user->id)
            ->latest('id')
            ->paginate(20);

        $view = 'notifications.index';
        Log::debug('NotificationCenter@index rendering view', [
            'view' => $view,
            'user_id' => $user?->id,
            'role' => (string) $user?->role,
            'count' => $notifications->count(),
            'total' => method_exists($notifications, 'total') ? $notifications->total() : null,
            'user_column' => $userColumn,
        ]);

        return view($view, [
            'notifications' => $notifications,
            'role' => (string) $user->role,
        ]);
    }

    public function open(AppNotification $notification): RedirectResponse
    {
        $this->assertOwnership($notification);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return redirect()->to($this->resolveTargetUrl($notification));
    }

    public function markAllRead(): RedirectResponse
    {
        AppNotification::query()
            ->where($this->userColumn(), auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Notifications marquees comme lues.');
    }

    private function assertOwnership(AppNotification $notification): void
    {
        $column = $this->userColumn();
        abort_unless((int) ($notification->{$column} ?? 0) === (int) auth()->id(), 403);
    }

    private function userColumn(): string
    {
        return Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'recipient_user_id')
            ? 'recipient_user_id'
            : 'user_id';
    }

    private function resolveTargetUrl(AppNotification $notification): string
    {
        $role = (string) auth()->user()?->role;
        $data = is_array($notification->data) ? $notification->data : [];
        if (!empty($data['url']) && is_string($data['url'])) {
            return $data['url'];
        }

        if (!empty($data['route']) && is_string($data['route'])) {
            return $data['route'];
        }

        if (!empty($data['message_id']) && is_numeric($data['message_id'])) {
            $route = $this->routePrefix($role) . ".messages.show";
            if (Route::has($route)) {
                return route($route, (int) $data['message_id']);
            }
        }

        return match ($notification->type) {
            'appointment' => $this->safeRoleRoute($role, 'appointments'),
            'message' => $this->safeRoleRoute($role, 'messages'),
            'homework' => $this->safeRoleRoute($role, 'homeworks'),
            'course' => $this->safeRoleRoute($role, 'courses'),
            'pickup_request' => $this->safeRoleRoute($role, 'pickup_requests'),
            'agenda' => $this->safeRoleRoute($role, 'agenda'),
            'news' => $this->safeRoleRoute($role, 'dashboard'),
            default => $this->safeRoleRoute($role, 'dashboard'),
        };
    }

    private function safeRoleRoute(string $role, string $target): string
    {
        $prefix = $this->routePrefix($role);
        $candidates = match ($target) {
            'appointments' => [
                "{$prefix}.appointments.index",
                "{$prefix}.appointments.create",
                "{$prefix}.dashboard",
            ],
            'messages' => ["{$prefix}.messages.index", "{$prefix}.dashboard"],
            'homeworks' => ["{$prefix}.homeworks.index", "{$prefix}.dashboard"],
            'courses' => ["{$prefix}.courses.index", "{$prefix}.dashboard"],
            'pickup_requests' => ["{$prefix}.pickup-requests.index", "{$prefix}.dashboard"],
            'agenda' => ["{$prefix}.events.index", "{$prefix}.dashboard"],
            default => ["{$prefix}.dashboard", 'dashboard'],
        };

        foreach ($candidates as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }

        return route('dashboard');
    }

    private function routePrefix(string $role): string
    {
        return $role === 'school_life' ? 'school-life' : $role;
    }
}
