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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
            'homeworks' => $this->homeworks($user, $schoolId),
            'finance' => $this->finance($user, $schoolId),
            'transport' => $this->transport($user, $schoolId),
        ]);
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
                'classroom' => (string) ($homework->classroom?->name ?? ''),
                'teacher' => (string) ($homework->teacher?->name ?? ''),
                'subject' => (string) ($homework->subject?->name ?? ''),
                'status' => (string) $homework->normalized_status,
                'due_at' => $homework->due_at?->toIso8601String(),
                'created_at' => $homework->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
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
}
