<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use Closure;
use Illuminate\Http\Request;

class DirectorOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'director') {
            abort(403, 'Access denied.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
