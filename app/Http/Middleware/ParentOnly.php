<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use Closure;
use Illuminate\Http\Request;

class ParentOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'parent') {
            abort(403, 'Access denied.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
