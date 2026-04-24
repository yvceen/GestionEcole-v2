<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'student') {
            abort(403, 'Student only.');
        }

        if (!(bool) ($user->is_active ?? false)) {
            auth()->logout();
            abort(403, 'Inactive student account.');
        }

        return $next($request);
    }
}
