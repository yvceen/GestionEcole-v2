<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\StudentPlacementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId($user);
        $status = trim((string) $request->query('status', ''));
        $dateFrom = $this->parseDate((string) $request->query('date_from', ''));
        $dateTo = $this->parseDate((string) $request->query('date_to', ''), true);

        if ((string) $user->role === User::ROLE_PARENT) {
            $children = $this->parentChildren($user, $schoolId);
            $childId = (int) $request->integer('child_id');
            $selectedChild = $childId > 0 ? $children->firstWhere('id', $childId) : null;
            $academicYearId = $this->resolvedAcademicYearId($schoolId, $request);

            $query = app(AcademicYearService::class)->applyYearScope(
                Attendance::query(),
                $schoolId,
                $this->requestedAcademicYearId($request),
            )
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $children->pluck('id'))
                ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'markedBy:id,name'])
                ->when($selectedChild, fn ($builder) => $builder->where('student_id', (int) $selectedChild->id))
                ->when($status !== '', fn ($builder) => $builder->where('status', $status))
                ->when($dateFrom, fn ($builder) => $builder->where('date', '>=', $dateFrom))
                ->when($dateTo, fn ($builder) => $builder->where('date', '<=', $dateTo))
                ->orderByDesc('date')
                ->orderByDesc('id');

            return response()->json([
                'items' => $query->limit(100)->get()->map(fn (Attendance $attendance) => $this->attendancePayload($attendance))->values(),
                'children' => $children->map(fn (Student $student) => $this->studentOptionPayload($student, $schoolId, $academicYearId))->values(),
                'summary' => $this->summaryPayload((clone $query)->get()),
                'selected_child_id' => $selectedChild ? (int) $selectedChild->id : null,
                'selected_academic_year_id' => $academicYearId,
            ]);
        }

        $student = $this->studentRecord($user, $schoolId);
        abort_unless($student, 404, 'Student profile not found.');
        $academicYearId = $this->resolvedAcademicYearId($schoolId, $request);

        $query = app(AcademicYearService::class)->applyYearScope(
            Attendance::query(),
            $schoolId,
            $this->requestedAcademicYearId($request),
        )
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'markedBy:id,name'])
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->when($dateFrom, fn ($builder) => $builder->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->where('date', '<=', $dateTo))
            ->orderByDesc('date')
            ->orderByDesc('id');

        return response()->json([
            'items' => $query->limit(100)->get()->map(fn (Attendance $attendance) => $this->attendancePayload($attendance))->values(),
            'children' => [],
            'summary' => $this->summaryPayload((clone $query)->get()),
            'selected_child_id' => null,
            'selected_academic_year_id' => $academicYearId,
        ]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        return $user;
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function parentChildren(User $user, int $schoolId): Collection
    {
        return Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->with('classroom:id,name')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'classroom_id']);
    }

    private function studentRecord(User $user, int $schoolId): ?Student
    {
        return Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->with('classroom:id,name')
            ->first(['id', 'full_name', 'classroom_id']);
    }

    private function studentOptionPayload(Student $student, int $schoolId, ?int $academicYearId): array
    {
        return [
            'id' => (int) $student->id,
            'name' => (string) $student->full_name,
            'classroom' => app(StudentPlacementService::class)->classroomNameForStudent($student, $schoolId, $academicYearId),
        ];
    }

    private function attendancePayload(Attendance $attendance): array
    {
        return [
            'id' => (int) $attendance->id,
            'date' => $attendance->date?->toDateString(),
            'status' => (string) $attendance->status,
            'status_label' => $this->statusLabel((string) $attendance->status),
            'note' => (string) ($attendance->note ?? ''),
            'check_in_at' => optional($attendance->check_in_at)?->toIso8601String(),
            'check_out_at' => optional($attendance->check_out_at)?->toIso8601String(),
            'recorded_via' => (string) ($attendance->recorded_via ?? ''),
            'marked_by' => (string) ($attendance->markedBy?->name ?? ''),
            'student' => [
                'id' => (int) ($attendance->student?->id ?? 0),
                'name' => (string) ($attendance->student?->full_name ?? ''),
                'classroom' => (string) ($attendance->student?->classroom?->name ?? ''),
            ],
            'created_at' => optional($attendance->created_at)?->toIso8601String(),
        ];
    }

    private function summaryPayload(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'present' => $rows->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $rows->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $rows->where('status', Attendance::STATUS_LATE)->count(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => 'Present',
            Attendance::STATUS_ABSENT => 'Absent',
            Attendance::STATUS_LATE => 'Late',
            default => ucfirst($status),
        };
    }

    private function parseDate(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function requestedAcademicYearId(Request $request): ?int
    {
        $value = (int) $request->integer('academic_year_id');

        return $value > 0 ? $value : null;
    }

    private function resolvedAcademicYearId(int $schoolId, Request $request): int
    {
        return app(AcademicYearService::class)
            ->resolveYearForSchool($schoolId, $this->requestedAcademicYearId($request))
            ->id;
    }
}
