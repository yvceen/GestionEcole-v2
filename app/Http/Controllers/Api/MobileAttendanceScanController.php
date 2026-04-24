<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileAttendanceScanController extends Controller
{
    public function __construct(
        private readonly AttendanceScanService $scanner,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(
            in_array((string) $user->role, [User::ROLE_TEACHER, User::ROLE_SCHOOL_LIFE], true),
            403,
            'Teacher or school life only.'
        );

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->scanner->process($data['code'], $user, $this->schoolId($user));
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->validator->errors()->first() ?: 'Scan failed.',
            ], 422);
        }

        return response()->json($result);
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);

        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
