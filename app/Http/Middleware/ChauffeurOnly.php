<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ChauffeurOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== User::ROLE_CHAUFFEUR) {
            abort(403, 'Chauffeur only.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
