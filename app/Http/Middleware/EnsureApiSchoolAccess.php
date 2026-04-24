<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiSchoolAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((string) $user->role !== 'super_admin') {
            if (!(bool) ($user->is_active ?? false)) {
                return response()->json([
                    'message' => 'Account disabled.',
                ], 403);
            }

            $hostSchool = $request->attributes->get('school_from_subdomain');
            if ($hostSchool && (int) $user->school_id !== (int) $hostSchool->id) {
                return response()->json([
                    'message' => 'School access mismatch.',
                ], 403);
            }

            if (!empty($user->school_id)) {
                $school = School::query()->find((int) $user->school_id);
                if ($school && !$school->is_active) {
                    return response()->json([
                        'message' => 'Your school is inactive.',
                    ], 403);
                }
            }
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
