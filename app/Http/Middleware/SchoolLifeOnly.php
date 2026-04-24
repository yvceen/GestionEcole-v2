<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class SchoolLifeOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== User::ROLE_SCHOOL_LIFE) {
            abort(403, 'School life staff only.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
