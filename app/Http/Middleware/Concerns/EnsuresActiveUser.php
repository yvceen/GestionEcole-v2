<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;

trait EnsuresActiveUser
{
    protected function ensureActiveUser(Request $request): void
    {
        $user = $request->user() ?? auth()->user();

        if ($user && !(bool) ($user->is_active ?? false)) {
            abort(403, 'Account disabled');
        }
    }
}
