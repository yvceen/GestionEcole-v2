<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSchoolActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || ($user->role ?? null) === 'super_admin') {
            return $next($request);
        }

        $schoolId = $user->school_id ?? null;
        if ($schoolId) {
            $school = School::find($schoolId);
            if ($school && !$school->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('school.inactive');
            }
        }

        return $next($request);
    }
}
