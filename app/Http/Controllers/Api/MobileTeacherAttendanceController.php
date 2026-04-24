<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Services\TeacherAttendanceRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileTeacherAttendanceController extends Controller
{
    public function __construct(
        private readonly TeacherAttendanceRegisterService $registerService,
    ) {
    }

    public function meta(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        $schoolId = $this->schoolId();
        $classrooms = $this->registerService->classroomsForTeacher($teacher, $schoolId);

        return response()->json([
            'classrooms' => $classrooms->map(fn ($classroom) => [
                'id' => (int) $classroom->id,
                'name' => (string) $classroom->name,
                'level_name' => (string) ($classroom->level?->name ?? ''),
            ])->values()->all(),
            'statuses' => [
                ['value' => Attendance::STATUS_PRESENT, 'label' => 'Present'],
                ['value' => Attendance::STATUS_ABSENT, 'label' => 'Absent'],
                ['value' => Attendance::STATUS_LATE, 'label' => 'Late'],
            ],
            'default_date' => now()->toDateString(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        $schoolId = $this->schoolId();
        $classroomId = (int) $request->integer('classroom_id');
        $date = (string) $request->get('date', now()->toDateString());

        abort_if($classroomId <= 0, 422, 'Classroom is required.');

        $payload = $this->registerService->registerPayload($teacher, $schoolId, $classroomId, $date);
        $selectedClassroom = $payload['selectedClassroom'];
        abort_unless($selectedClassroom, 404);

        $students = $payload['students'];
        $attendanceByStudentId = $payload['attendanceByStudentId'];

        return response()->json([
            'classroom' => [
                'id' => (int) $selectedClassroom->id,
                'name' => (string) $selectedClassroom->name,
                'level_name' => (string) ($selectedClassroom->level?->name ?? ''),
            ],
            'date' => $date,
            'has_existing_records' => !empty($attendanceByStudentId),
            'students' => $students->map(function ($student) use ($attendanceByStudentId) {
                $row = $attendanceByStudentId[$student->id] ?? null;

                return [
                    'id' => (int) $student->id,
                    'name' => (string) $student->full_name,
                    'status' => (string) ($row?->status ?? Attendance::STATUS_PRESENT),
                    'note' => (string) ($row?->note ?? ''),
                    'updated_at' => optional($row?->updated_at ?? $row?->created_at)?->toIso8601String(),
                ];
            })->values()->all(),
            'summary' => [
                'total' => $students->count(),
                'present' => $students->filter(fn ($student) => (string) (($attendanceByStudentId[$student->id] ?? null)?->status ?? Attendance::STATUS_PRESENT) === Attendance::STATUS_PRESENT)->count(),
                'absent' => $students->filter(fn ($student) => (string) (($attendanceByStudentId[$student->id] ?? null)?->status ?? '') === Attendance::STATUS_ABSENT)->count(),
                'late' => $students->filter(fn ($student) => (string) (($attendanceByStudentId[$student->id] ?? null)?->status ?? '') === Attendance::STATUS_LATE)->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', 'in:' . implode(',', Attendance::statuses())],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->registerService->saveRegister($teacher, $schoolId, $data);
        $classroom = $result['classroom'];

        return response()->json([
            'message' => 'Attendance register saved successfully.',
            'classroom' => [
                'id' => (int) $classroom->id,
                'name' => (string) $classroom->name,
            ],
            'date' => (string) $data['date'],
            'summary' => $result['summary'],
        ]);
    }

    private function teacher(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_TEACHER, 403, 'Attendance is only available for teachers.');

        return $user;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
