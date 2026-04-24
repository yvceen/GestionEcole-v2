<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\EnsuresActiveUser;
use Closure;
use Illuminate\Http\Request;

class TeacherOnly
{
    use EnsuresActiveUser;

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Teacher only.');
        }

        $this->ensureActiveUser($request);

        return $next($request);
    }
}
