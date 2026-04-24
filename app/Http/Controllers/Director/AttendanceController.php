<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Services\AttendanceReportingService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return view('director.attendance.index', $this->attendanceReporting->buildMonitoringData($schoolId, $request));
    }
}
