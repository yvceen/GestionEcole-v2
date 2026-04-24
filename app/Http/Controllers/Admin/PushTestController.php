<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushTestController extends Controller
{
    public function store(Request $request, PushNotificationService $push)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:255'],
        ]);

        $sent = $push->sendToUsers(
            [(int) $user->id],
            $data['title'] ?? 'Test push MyEdu',
            $data['body'] ?? 'Notification test envoyee a cet appareil Android.',
            ['type' => 'push_test'],
            app()->bound('current_school_id') ? (int) app('current_school_id') : null,
        );

        return back()->with('success', $sent > 0
            ? 'Test push envoye a votre appareil.'
            : 'Aucun token Android actif trouve pour ce compte.');
    }
}
