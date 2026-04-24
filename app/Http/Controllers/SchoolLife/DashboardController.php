<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PickupRequest;
use App\Models\Student;
use App\Services\AttendanceAutoAbsentService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AttendanceAutoAbsentService $attendanceAutoAbsences,
    ) {
    }

    public function index()
    {
        $schoolId = $this->schoolId();
        $this->attendanceAutoAbsences->markDueAbsencesForSchool($schoolId, now());

        $stats = [
            'students' => Student::where('school_id', $schoolId)->active()->count(),
            'today_absent' => Attendance::where('school_id', $schoolId)->whereDate('date', now()->toDateString())->where('status', Attendance::STATUS_ABSENT)->count(),
            'today_late' => Attendance::where('school_id', $schoolId)->whereDate('date', now()->toDateString())->where('status', Attendance::STATUS_LATE)->count(),
            'pickup_pending' => PickupRequest::where('school_id', $schoolId)->where('status', PickupRequest::STATUS_PENDING)->count(),
        ];

        $recentAttendance = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->with(['student.classroom', 'markedBy:id,name'])
            ->orderByDesc('date')
            ->limit(8)
            ->get();

        $pickupRequests = PickupRequest::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [PickupRequest::STATUS_PENDING, PickupRequest::STATUS_APPROVED])
            ->with(['student.classroom', 'parentUser:id,name,phone'])
            ->orderBy('requested_pickup_at')
            ->limit(8)
            ->get();

        return view('school-life.dashboard', compact('stats', 'recentAttendance', 'pickupRequests'));
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
