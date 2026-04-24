<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceRegisterService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function classroomsForTeacher(User $teacher, int $schoolId): Collection
    {
        return $teacher->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->with('level:id,name')
            ->orderBy('name')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.level_id']);
    }

    public function resolveClassroom(User $teacher, int $schoolId, int $classroomId): ?Classroom
    {
        if ($classroomId <= 0) {
            return null;
        }

        return $teacher->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->where('classrooms.id', $classroomId)
            ->with('level:id,name')
            ->first(['classrooms.id', 'classrooms.name', 'classrooms.level_id']);
    }

    public function registerPayload(User $teacher, int $schoolId, ?int $classroomId, string $date): array
    {
        $classrooms = $this->classroomsForTeacher($teacher, $schoolId);
        $selectedClassroom = $classrooms->firstWhere('id', $classroomId);
        $students = collect();
        $attendanceByStudentId = [];

        if ($selectedClassroom) {
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->where('classroom_id', (int) $selectedClassroom->id)
                ->active()
                ->orderBy('full_name')
                ->get();

            $attendanceByStudentId = Attendance::query()
                ->where('school_id', $schoolId)
                ->where('classroom_id', (int) $selectedClassroom->id)
                ->whereDate('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id')
                ->all();
        }

        return [
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'students' => $students,
            'attendanceByStudentId' => $attendanceByStudentId,
        ];
    }

    public function saveRegister(User $teacher, int $schoolId, array $data): array
    {
        $classroom = $this->resolveClassroom($teacher, $schoolId, (int) $data['classroom_id']);
        abort_unless($classroom, 404);

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', (int) $classroom->id)
            ->active()
            ->whereIn('id', array_map('intval', array_keys($data['attendance'])))
            ->get(['id', 'full_name', 'parent_user_id']);

        abort_if($students->isEmpty(), 422, 'Aucun eleve valide pour cet appel.');

        $alerts = collect();

        DB::transaction(function () use ($students, $data, $teacher, $schoolId, $classroom, &$alerts): void {
            foreach ($students as $student) {
                $payload = $data['attendance'][$student->id] ?? null;
                if (!$payload) {
                    continue;
                }

                $attendance = Attendance::query()->firstOrNew([
                    'student_id' => (int) $student->id,
                    'date' => $data['date'],
                ]);

                $originalStatus = $attendance->exists ? (string) $attendance->status : null;

                $attendance->fill([
                    'school_id' => $schoolId,
                    'classroom_id' => (int) $classroom->id,
                    'status' => $payload['status'],
                    'note' => trim((string) ($payload['note'] ?? '')) ?: null,
                    'marked_by_user_id' => (int) $teacher->id,
                    'recorded_via' => Attendance::RECORDED_VIA_TEACHER,
                ]);
                $attendance->save();

                if (
                    in_array($attendance->status, [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE], true)
                    && $attendance->status !== $originalStatus
                ) {
                    $alerts->push([
                        'attendance' => [
                            'date' => Carbon::parse($attendance->date)->toDateString(),
                            'status' => (string) $attendance->status,
                            'note' => $attendance->note,
                        ],
                        'student' => $student,
                    ]);
                }
            }
        });

        $this->sendAttendanceAlerts($alerts, $classroom);

        return [
            'classroom' => $classroom,
            'students' => $students,
            'summary' => [
                'total' => $students->count(),
                'present' => $this->countByStatus($data['attendance'], Attendance::STATUS_PRESENT),
                'absent' => $this->countByStatus($data['attendance'], Attendance::STATUS_ABSENT),
                'late' => $this->countByStatus($data['attendance'], Attendance::STATUS_LATE),
            ],
        ];
    }

    private function countByStatus(array $attendance, string $status): int
    {
        return collect($attendance)
            ->filter(fn ($row) => (string) data_get($row, 'status') === $status)
            ->count();
    }

    private function sendAttendanceAlerts(Collection $alerts, Classroom $classroom): void
    {
        $alerts->each(function (array $item) use ($classroom): void {
            $student = $item['student'];
            $attendance = $item['attendance'];

            if (!$student->parent_user_id) {
                return;
            }

            $statusLabel = $attendance['status'] === Attendance::STATUS_LATE ? 'en retard' : 'absent';

            $this->notifications->notifyUsers(
                [(int) $student->parent_user_id],
                'attendance',
                'Nouvelle alerte de presence',
                sprintf(
                    '%s a ete marque %s le %s%s.',
                    $student->full_name,
                    $statusLabel,
                    Carbon::parse($attendance['date'])->format('d/m/Y'),
                    $attendance['note'] ? ' (' . $attendance['note'] . ')' : ''
                ),
                [
                    'student_id' => (int) $student->id,
                    'classroom_id' => (int) $classroom->id,
                    'date' => $attendance['date'],
                    'status' => $attendance['status'],
                    'route' => route('parent.attendance.index', absolute: false),
                ]
            );
        });
    }
}
