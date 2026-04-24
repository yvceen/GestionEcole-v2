<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\SchoolDomainService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class IdentifySchoolFromSubdomain
{
    public function __construct(
        private readonly SchoolDomainService $schoolDomainService,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $school = null;
        $subdomain = null;

        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'subdomain')) {
            $subdomain = $this->schoolDomainService->extractSubdomainFromHost($request->getHost());

            if ($subdomain !== null) {
                $school = School::query()
                    ->where('subdomain', $subdomain)
                    ->first();

                if (!$school) {
                    abort(404, 'School not found.');
                }
            }
        }

        $this->shareSchool($school);
        app()->instance('school_from_subdomain', $school);
        $request->attributes->set('school_from_subdomain', $school);

        return $next($request);
    }

    private function shareSchool(?School $school): void
    {
        $schoolId = $school?->id;

        app()->instance('currentSchool', $school);
        app()->instance('current_school', $school);
        app()->instance('current_school_id', $schoolId);

        View::share('currentSchool', $school);
        View::share('current_school', $school);
        View::share('current_school_id', $schoolId);
    }
}
