<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceReportingService;
use App\Services\TeacherAttendanceRegisterService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
        private readonly TeacherAttendanceRegisterService $registerService,
    ) {
    }

    public function index(Request $request)
    {
        $teacher = auth()->user();
        $schoolId = (int) $teacher->school_id;
        $classroomId = (int) $request->integer('classroom_id');
        $date = (string) $request->get('date', now()->toDateString());

        $registerPayload = $this->registerService->registerPayload($teacher, $schoolId, $classroomId, $date);
        $classrooms = $registerPayload['classrooms'];
        $students = $registerPayload['students'];
        $attendanceByStudentId = $registerPayload['attendanceByStudentId'];
        $selectedClassroom = $registerPayload['selectedClassroom'];

        $sessionHistory = $this->attendanceReporting->teacherSessionHistory(
            $schoolId,
            (int) $teacher->id,
            $classrooms->pluck('id')->all()
        );

        return view('teacher.attendance.index', compact(
            'classrooms',
            'students',
            'classroomId',
            'date',
            'attendanceByStudentId',
            'sessionHistory',
            'selectedClassroom'
        ));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user();
        $schoolId = (int) $teacher->school_id;

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', 'in:' . implode(',', Attendance::statuses())],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->registerService->saveRegister($teacher, $schoolId, $data);
        $classroom = $result['classroom'];

        return redirect()->route('teacher.attendance.index', [
            'classroom_id' => $classroom->id,
            'date' => $data['date'],
        ])->with('success', 'Registre enregistre avec succes.');
    }
}
