<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceEntryScanService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceScanController extends Controller
{
    public function __construct(
        private readonly AttendanceEntryScanService $scanner,
    ) {
    }

    public function index()
    {
        $user = $this->authorizedOperator();
        $schoolId = $this->schoolId();

        $recentScans = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('check_in_at')
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name'])
            ->orderByDesc('check_in_at')
            ->limit(12)
            ->get();

        return view('shared.attendance-scan.index', compact('user', 'recentScans'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizedOperator();
        $data = $request->validate([
            'qr_token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $payload = $this->scanner->scan($data['qr_token'], $user, $this->schoolId());
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->errors()['qr_token'][0] ?? 'Scan impossible.',
            ], 422);
        }

        return response()->json($payload);
    }

    private function authorizedOperator(): User
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array((string) $user->role, [User::ROLE_TEACHER, User::ROLE_SCHOOL_LIFE], true),
            403,
            'Teacher or school life only.'
        );

        return $user;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
