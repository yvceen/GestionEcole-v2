<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\TransportLog;

class TransportController extends Controller
{
    use InteractsWithStudentPortal;

    public function index()
    {
        $student = $this->currentStudent(['classroom:id,name', 'transportAssignment.route.stops', 'transportAssignment.vehicle.driver']);

        $logs = TransportLog::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->where('student_id', $student->id)
            ->with(['route:id,route_name', 'vehicle:id,name,registration_number'])
            ->latest('logged_at')
            ->limit(20)
            ->get();

        return view('student.transport.index', compact('student', 'logs'));
    }
}
