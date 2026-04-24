<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // admin + super_admin فقط
        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            abort(403, 'Access denied.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
