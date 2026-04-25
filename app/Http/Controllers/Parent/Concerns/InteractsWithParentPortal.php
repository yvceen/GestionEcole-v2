<?php

namespace App\Http\Controllers\Parent\Concerns;

use App\Models\AppNotification;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\StudentPlacementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait InteractsWithParentPortal
{
    private function schoolIdOrFail(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    private function currentParent(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->role === User::ROLE_PARENT, 403);

        return $user;
    }

    private function ownedChildrenQuery(array $with = []): Builder
    {
        return Student::query()
            ->with($with)
            ->where('school_id', $this->schoolIdOrFail())
            ->where('parent_user_id', $this->currentParent()->id);
    }

    private function ownedChildren(array $with = []): Collection
    {
        return $this->ownedChildrenQuery($with)
            ->orderBy('full_name')
            ->get();
    }

    private function resolveOwnedStudent(Student $student, array $with = []): Student
    {
        $resolved = $this->ownedChildrenQuery($with)->find($student->id);
        abort_unless($resolved, 404);

        return $resolved;
    }

    private function ownedClassroomIds(): Collection
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $schoolId = $this->schoolIdOrFail();
        $academicYearId = $this->resolvedAcademicYearId();
        $placements = app(StudentPlacementService::class)->placementsForStudents(
            $children->pluck('id'),
            $schoolId,
            $academicYearId,
        );

        return $children
            ->map(fn (Student $student) => (int) ($placements->get($student->id)?->classroom_id ?: $student->classroom_id))
            ->filter()
            ->unique()
            ->values();
    }

    private function visibleCoursesQuery(Collection $classroomIds): Builder
    {
        $query = Course::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('classroom_id', $classroomIds)
            ->when(
                Schema::hasTable('courses') && Schema::hasColumn('courses', 'status'),
                fn (Builder $query) => $query->whereIn('status', ['approved', 'confirmed'])
            );

        return app(AcademicYearService::class)->applyYearScope(
            $query,
            $this->schoolIdOrFail(),
            $this->requestedAcademicYearId(),
        );
    }

    private function visibleHomeworksQuery(Collection $classroomIds): Builder
    {
        $query = Homework::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('classroom_id', $classroomIds)
            ->when(
                Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'status'),
                fn (Builder $query) => $query->whereIn('status', ['approved', 'confirmed'])
            );

        return app(AcademicYearService::class)->applyYearScope(
            $query,
            $this->schoolIdOrFail(),
            $this->requestedAcademicYearId(),
        );
    }

    private function unreadNotificationsCount(): int
    {
        $userId = (int) auth()->id();
        if ($userId <= 0 || !Schema::hasTable('notifications')) {
            return 0;
        }

        $userColumn = Schema::hasColumn('notifications', 'recipient_user_id')
            ? 'recipient_user_id'
            : 'user_id';

        return AppNotification::query()
            ->where($userColumn, $userId)
            ->whereNull('read_at')
            ->count();
    }

    private function ownedPaymentsQuery(): Builder
    {
        $query = Payment::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('student_id', $this->ownedChildren()->pluck('id'));

        return app(AcademicYearService::class)->applyYearScope(
            $query,
            $this->schoolIdOrFail(),
            $this->requestedAcademicYearId(),
        );
    }

    private function ownedReceiptsQuery(): Builder
    {
        $schoolId = $this->schoolIdOrFail();
        $childIds = $this->ownedChildren()->pluck('id');
        $parentId = $this->currentParent()->id;
        $requestedAcademicYearId = $this->requestedAcademicYearId();

        return Receipt::query()
            ->where('school_id', $schoolId)
            ->where(function (Builder $query) use ($childIds, $schoolId, $parentId) {
                $query->where('parent_id', $parentId)
                    ->orWhereHas('payments', fn (Builder $payments) => $payments
                        ->where('school_id', $schoolId)
                        ->whereIn('student_id', $childIds));
            })
            ->when($requestedAcademicYearId, function (Builder $query) use ($schoolId, $requestedAcademicYearId, $childIds): void {
                $query->whereHas('payments', fn (Builder $payments) => app(AcademicYearService::class)
                    ->applyYearScope($payments, $schoolId, $requestedAcademicYearId, 'payments', false)
                    ->where('school_id', $schoolId)
                    ->whereIn('student_id', $childIds));
            });
    }

    private function nextTimetableSlotForChildren(Collection $children): ?array
    {
        $schoolId = $this->schoolIdOrFail();
        $academicYearId = $this->resolvedAcademicYearId();
        $placements = app(StudentPlacementService::class)->placementsForStudents(
            $children->pluck('id'),
            $schoolId,
            $academicYearId,
        );
        $classroomIds = $children
            ->map(fn (Student $student) => (int) ($placements->get($student->id)?->classroom_id ?: $student->classroom_id))
            ->filter()
            ->unique()
            ->values();

        if ($classroomIds->isEmpty()) {
            return null;
        }

        $query = Timetable::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('classroom_id', $classroomIds)
            ->with(['teacher:id,name', 'classroom:id,name'])
            ->orderBy('day')
            ->orderBy('start_time');

        $slots = app(AcademicYearService::class)->applyYearScope(
            $query,
            $schoolId,
            $this->requestedAcademicYearId(),
        )->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $now = now();
        $currentDay = (int) $now->dayOfWeekIso;
        $currentTime = $now->format('H:i:s');

        $slot = $slots->first(function (Timetable $candidate) use ($currentDay, $currentTime) {
            return (int) $candidate->day > $currentDay
                || ((int) $candidate->day === $currentDay && (string) $candidate->start_time >= $currentTime);
        }) ?? $slots->first();

        if (!$slot) {
            return null;
        }

        $child = $children->first(function (Student $student) use ($placements, $slot) {
            return (int) ($placements->get($student->id)?->classroom_id ?: $student->classroom_id) === (int) $slot->classroom_id;
        });

        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        return [
            'child' => $child?->full_name,
            'classroom' => $slot->classroom?->name,
            'subject' => $slot->subject,
            'teacher' => $slot->teacher?->name,
            'day' => $days[(int) $slot->day] ?? 'Prochain cours',
            'time' => substr((string) $slot->start_time, 0, 5) . ' - ' . substr((string) $slot->end_time, 0, 5),
            'room' => $slot->room,
        ];
    }

    private function requestedAcademicYearId(): ?int
    {
        $request = request();
        if (!$request) {
            return null;
        }

        $value = (int) $request->integer('academic_year_id');

        return $value > 0 ? $value : null;
    }

    private function resolvedAcademicYearId(): ?int
    {
        return app(AcademicYearService::class)
            ->resolveYearForSchool($this->schoolIdOrFail(), $this->requestedAcademicYearId())
            ->id;
    }

    private function classroomIdForChild(Student $student): ?int
    {
        return app(StudentPlacementService::class)->classroomIdForStudent(
            $student,
            $this->schoolIdOrFail(),
            $this->resolvedAcademicYearId(),
        );
    }

    private function classroomNameForChild(Student $student): string
    {
        return app(StudentPlacementService::class)->classroomNameForStudent(
            $student,
            $this->schoolIdOrFail(),
            $this->resolvedAcademicYearId(),
        );
    }
}
