<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\Payment;
use App\Models\Route as TransportRoute;
use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DesktopAdminController extends Controller
{
    public function workspace(): JsonResponse
    {
        /** @var User|null $user */
        $user = request()->user();
        abort_unless($user, 401);
        abort_unless($this->canUseDesktop($user), 403, 'Desktop administration reservee aux roles autorises.');

        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'Contexte ecole introuvable.');

        return response()->json([
            'students' => $this->students($user, $schoolId),
            'attendance' => $this->attendance($user, $schoolId),
            'homeworks' => $this->homeworks($user, $schoolId),
            'finance' => $this->finance($user, $schoolId),
            'transport' => $this->transport($user, $schoolId),
            'users' => $this->users($user, $schoolId),
        ]);
    }

    public function approveHomework(Homework $homework): JsonResponse
    {
        return $this->updateHomeworkStatus($homework, 'approved');
    }

    public function rejectHomework(Homework $homework): JsonResponse
    {
        return $this->updateHomeworkStatus($homework, 'rejected');
    }

    private function canUseDesktop(User $user): bool
    {
        return in_array((string) $user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_DIRECTOR,
            User::ROLE_SCHOOL_LIFE,
            User::ROLE_TEACHER,
        ], true);
    }

    private function visibleClassroomIds(User $user, int $schoolId): ?array
    {
        if ((string) $user->role !== User::ROLE_TEACHER) {
            return null;
        }

        return $user->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->pluck('classrooms.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function applyClassroomScope(Builder $query, User $user, int $schoolId, string $column = 'classroom_id'): Builder
    {
        $classroomIds = $this->visibleClassroomIds($user, $schoolId);
        if ($classroomIds === null) {
            return $query;
        }

        if ($classroomIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $classroomIds);
    }

    private function students(User $user, int $schoolId): array
    {
        $query = Student::query()
            ->where('school_id', $schoolId)
            ->with(['classroom.level', 'parentUser:id,name,email,phone', 'transportAssignment.route', 'transportAssignment.vehicle'])
            ->latest('id');

        $this->applyClassroomScope($query, $user, $schoolId);

        $activeCount = (clone $query)->whereNull('archived_at')->count();
        $archivedCount = (clone $query)->whereNotNull('archived_at')->count();
        $students = (clone $query)->limit(80)->get();

        return [
            'summary' => [
                'active' => $activeCount,
                'archived' => $archivedCount,
                'shown' => $students->count(),
            ],
            'items' => $students->map(fn (Student $student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
                'level' => (string) ($student->classroom?->level?->name ?? ''),
                'parent_name' => (string) ($student->parentUser?->name ?? ''),
                'parent_phone' => (string) ($student->parentUser?->phone ?? ''),
                'status' => $student->archived_at ? 'archive' : 'actif',
                'transport' => $student->transportAssignment ? [
                    'route' => (string) ($student->transportAssignment->route?->route_name ?? ''),
                    'vehicle' => (string) ($student->transportAssignment->vehicle?->name ?? ''),
                ] : null,
            ])->values()->all(),
        ];
    }

    private function homeworks(User $user, int $schoolId): array
    {
        $query = Homework::query()
            ->where('school_id', $schoolId)
            ->with(['classroom:id,name', 'teacher:id,name', 'subject:id,name'])
            ->latest('created_at');

        if ((string) $user->role === User::ROLE_TEACHER) {
            $query->where('teacher_id', (int) $user->id);
        } else {
            $this->applyClassroomScope($query, $user, $schoolId);
        }

        $pending = (clone $query)->pending()->count();
        $approved = (clone $query)->approved()->count();
        $items = (clone $query)->limit(40)->get();

        return [
            'summary' => [
                'pending' => $pending,
                'approved' => $approved,
                'shown' => $items->count(),
            ],
            'items' => $items->map(fn (Homework $homework) => [
                'id' => (int) $homework->id,
                'title' => (string) $homework->title,
                'description' => (string) ($homework->description ?? ''),
                'classroom' => (string) ($homework->classroom?->name ?? ''),
                'teacher' => (string) ($homework->teacher?->name ?? ''),
                'subject' => (string) ($homework->subject?->name ?? ''),
                'status' => (string) $homework->normalized_status,
                'due_at' => $homework->due_at?->toIso8601String(),
                'created_at' => $homework->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function attendance(User $user, int $schoolId): array
    {
        $query = Attendance::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,full_name,classroom_id', 'classroom:id,name'])
            ->latest('date')
            ->latest('id');

        $this->applyClassroomScope($query, $user, $schoolId);

        $today = now()->toDateString();
        $todayQuery = (clone $query)->whereDate('date', $today);
        $items = (clone $query)->limit(50)->get();

        return [
            'summary' => [
                'today_present' => (clone $todayQuery)->where('status', Attendance::STATUS_PRESENT)->count(),
                'today_absent' => (clone $todayQuery)->where('status', Attendance::STATUS_ABSENT)->count(),
                'today_late' => (clone $todayQuery)->where('status', Attendance::STATUS_LATE)->count(),
                'shown' => $items->count(),
            ],
            'items' => $items->map(fn (Attendance $attendance) => [
                'id' => (int) $attendance->id,
                'student' => (string) ($attendance->student?->full_name ?? ''),
                'classroom' => (string) ($attendance->classroom?->name ?? $attendance->student?->classroom?->name ?? ''),
                'date' => $attendance->date?->toDateString(),
                'status' => (string) $attendance->status,
                'check_in_at' => $attendance->check_in_at?->toIso8601String(),
                'check_out_at' => $attendance->check_out_at?->toIso8601String(),
                'note' => (string) ($attendance->note ?? ''),
            ])->values()->all(),
        ];
    }

    private function updateHomeworkStatus(Homework $homework, string $status): JsonResponse
    {
        /** @var User|null $user */
        $user = request()->user();
        abort_unless($user, 401);
        abort_unless($this->canUseDesktop($user), 403, 'Action non autorisee.');
        abort_if((string) $user->role === User::ROLE_TEACHER, 403, 'Action reservee a l administration.');

        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);

        abort_unless((int) ($homework->school_id ?? 0) === $schoolId, 404);
        abort_unless(in_array($status, ['approved', 'rejected'], true), 422);

        $payload = [];
        if (Schema::hasColumn('homeworks', 'status')) {
            $payload['status'] = $status;
        }
        if ($status === 'approved') {
            if (Schema::hasColumn('homeworks', 'approved_at')) {
                $payload['approved_at'] = now();
            }
            if (Schema::hasColumn('homeworks', 'approved_by')) {
                $payload['approved_by'] = (int) $user->id;
            }
            if (Schema::hasColumn('homeworks', 'rejected_at')) {
                $payload['rejected_at'] = null;
            }
            if (Schema::hasColumn('homeworks', 'rejected_by')) {
                $payload['rejected_by'] = null;
            }
        } else {
            if (Schema::hasColumn('homeworks', 'rejected_at')) {
                $payload['rejected_at'] = now();
            }
            if (Schema::hasColumn('homeworks', 'rejected_by')) {
                $payload['rejected_by'] = (int) $user->id;
            }
            if (Schema::hasColumn('homeworks', 'approved_at')) {
                $payload['approved_at'] = null;
            }
            if (Schema::hasColumn('homeworks', 'approved_by')) {
                $payload['approved_by'] = null;
            }
        }

        if ($payload !== []) {
            $homework->update($payload);
        }

        $this->notifyHomeworkStatus($homework->fresh(), $schoolId, $status);

        return response()->json([
            'message' => $status === 'approved' ? 'Devoir approuve.' : 'Devoir rejete.',
            'homework' => $this->homeworkPayload($homework->fresh(['classroom:id,name', 'teacher:id,name', 'subject:id,name'])),
        ]);
    }

    private function homeworkPayload(?Homework $homework): array
    {
        if (!$homework) {
            return [];
        }

        return [
            'id' => (int) $homework->id,
            'title' => (string) $homework->title,
            'description' => (string) ($homework->description ?? ''),
            'classroom' => (string) ($homework->classroom?->name ?? ''),
            'teacher' => (string) ($homework->teacher?->name ?? ''),
            'subject' => (string) ($homework->subject?->name ?? ''),
            'status' => (string) $homework->normalized_status,
            'due_at' => $homework->due_at?->toIso8601String(),
            'created_at' => $homework->created_at?->toIso8601String(),
        ];
    }

    private function notifyHomeworkStatus(?Homework $homework, int $schoolId, string $status): void
    {
        if (!$homework) {
            return;
        }

        try {
            $service = app(NotificationService::class);
            if ($status === 'approved') {
                $parentIds = $service->parentIdsByClassroom((int) $homework->classroom_id, $schoolId);
                $studentIds = $service->studentUserIdsByClassroom((int) $homework->classroom_id, $schoolId);
                $teacherIds = array_filter([(int) ($homework->teacher_id ?? 0)]);

                $service->notifyUsers(
                    array_values(array_unique(array_merge($parentIds, $studentIds, $teacherIds))),
                    'homework',
                    'Devoir approuve',
                    (string) ($homework->title ?: 'Un devoir a ete valide pour votre classe.'),
                    [
                        'homework_id' => (int) $homework->id,
                        'classroom_id' => (int) $homework->classroom_id,
                    ]
                );

                return;
            }

            $teacherId = (int) ($homework->teacher_id ?? 0);
            if ($teacherId > 0) {
                $service->notifyUsers(
                    [$teacherId],
                    'homework',
                    'Devoir refuse',
                    (string) ($homework->title ?: 'Votre devoir a ete refuse.'),
                    ['homework_id' => (int) $homework->id]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Desktop homework status notification failed', [
                'homework_id' => $homework->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function finance(User $user, int $schoolId): array
    {
        if (!in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            return [
                'allowed' => false,
                'summary' => ['month_total' => 0, 'payments_count' => 0],
                'items' => [],
            ];
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $query = Payment::query()
            ->where('school_id', $schoolId)
            ->whereBetween('paid_at', [$monthStart, $monthEnd]);

        $items = (clone $query)
            ->with(['student.parentUser:id,name,email,phone', 'student.classroom:id,name', 'receipt:id,receipt_number,total_amount'])
            ->latest('paid_at')
            ->limit(40)
            ->get();

        return [
            'allowed' => true,
            'summary' => [
                'month_total' => (float) (clone $query)->sum('amount'),
                'payments_count' => (clone $query)->count(),
                'period' => now()->format('Y-m'),
            ],
            'items' => $items->map(fn (Payment $payment) => [
                'id' => (int) $payment->id,
                'student' => (string) ($payment->student?->full_name ?? ''),
                'classroom' => (string) ($payment->student?->classroom?->name ?? ''),
                'parent' => (string) ($payment->student?->parentUser?->name ?? ''),
                'amount' => (float) $payment->amount,
                'method' => (string) $payment->method,
                'period_month' => $payment->period_month?->format('Y-m'),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'receipt_number' => (string) ($payment->receipt?->receipt_number ?? ''),
            ])->values()->all(),
        ];
    }

    private function transport(User $user, int $schoolId): array
    {
        $routes = TransportRoute::query()
            ->where('school_id', $schoolId)
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('route_name')
            ->limit(40)
            ->get();

        $assignmentsQuery = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->with(['student.classroom:id,name', 'route:id,route_name', 'vehicle:id,name,plate_number']);

        if ((string) $user->role === User::ROLE_TEACHER) {
            $classroomIds = $this->visibleClassroomIds($user, $schoolId) ?? [];
            $assignmentsQuery->whereHas('student', fn (Builder $query) => $query->whereIn('classroom_id', $classroomIds));
        }

        $assignments = (clone $assignmentsQuery)->latest('id')->limit(40)->get();

        return [
            'summary' => [
                'routes' => $routes->count(),
                'active_assignments' => (clone $assignmentsQuery)->count(),
            ],
            'routes' => $routes->map(fn (TransportRoute $route) => [
                'id' => (int) $route->id,
                'name' => (string) $route->route_name,
                'from' => (string) $route->start_point,
                'to' => (string) $route->end_point,
                'vehicle' => (string) ($route->vehicle?->name ?? ''),
                'active' => (bool) $route->is_active,
                'students_count' => (int) ($route->active_assignments_count ?? 0),
            ])->values()->all(),
            'assignments' => $assignments->map(fn (TransportAssignment $assignment) => [
                'id' => (int) $assignment->id,
                'student' => (string) ($assignment->student?->full_name ?? ''),
                'classroom' => (string) ($assignment->student?->classroom?->name ?? ''),
                'route' => (string) ($assignment->route?->route_name ?? ''),
                'vehicle' => (string) ($assignment->vehicle?->name ?? $assignment->vehicle?->plate_number ?? ''),
                'pickup_point' => (string) $assignment->pickup_point,
                'period' => (string) $assignment->period,
            ])->values()->all(),
        ];
    }

    private function users(User $user, int $schoolId): array
    {
        if (!in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            return [
                'allowed' => false,
                'summary' => ['total' => 0, 'active' => 0],
                'items' => [],
            ];
        }

        $query = User::query()
            ->where('school_id', $schoolId)
            ->orderBy('role')
            ->orderBy('name');

        $items = (clone $query)->limit(80)->get();

        return [
            'allowed' => true,
            'summary' => [
                'total' => (clone $query)->count(),
                'active' => Schema::hasColumn('users', 'is_active')
                    ? (clone $query)->where('is_active', true)->count()
                    : (clone $query)->count(),
            ],
            'items' => $items->map(fn (User $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'email' => (string) $item->email,
                'phone' => (string) ($item->phone ?? ''),
                'role' => (string) $item->role,
                'role_label' => User::labelForRole((string) $item->role),
                'is_active' => (bool) ($item->is_active ?? true),
            ])->values()->all(),
        ];
    }
}
