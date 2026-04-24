<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class SetCurrentSchool
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $userSchool = null;
        if ($user && !empty($user->school_id)) {
            $userSchool = School::find((int) $user->school_id);
        }

        $hostSchool = $request->attributes->get('school_from_subdomain');
        $school = $hostSchool ?: $userSchool;

        if ($hostSchool && $user && ($user->role ?? null) !== 'super_admin') {
            if (!empty($user->school_id) && (int) $user->school_id !== (int) $hostSchool->id) {
                abort(403, 'School access mismatch.');
            }
        }

        if (
            !$school
            && $user
            && ($user->role ?? null) !== 'super_admin'
            && $this->routeRequiresSchoolContext($request)
        ) {
            abort(404, 'School context missing.');
        }

        $schoolId = (int) ($school?->id ?? 0) ?: null;

        app()->instance('current_school_id', $schoolId);
        app()->instance('current_school', $school);
        app()->instance('currentSchool', $school);
        View::share('currentSchool', $school);
        View::share('current_school', $school);
        View::share('current_school_id', $schoolId);

        return $next($request);
    }

    private function routeRequiresSchoolContext(Request $request): bool
    {
        $segment = strtolower((string) $request->segment(1));

        return in_array($segment, ['admin', 'teacher', 'parent', 'student', 'director', 'school-life'], true);
    }
}
