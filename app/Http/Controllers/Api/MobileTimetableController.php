<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\StudentPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileTimetableController extends Controller
{
    use InteractsWithParentPortal;
    use InteractsWithStudentPortal {
        InteractsWithParentPortal::schoolIdOrFail insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::visibleCoursesQuery insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::visibleHomeworksQuery insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::unreadNotificationsCount insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::requestedAcademicYearId insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::resolvedAcademicYearId insteadof InteractsWithStudentPortal;
    }

    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->parentIndex($request),
            User::ROLE_STUDENT => $this->studentIndex(),
            default => abort(403),
        };
    }

    private function parentIndex(Request $request): JsonResponse
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $selectedChild = $this->resolveSelectedChild($children, $request->integer('child_id'));
        $schoolId = $this->schoolIdOrFail();
        $academicYearId = $this->resolvedAcademicYearId();
        $placements = app(StudentPlacementService::class)->placementsForStudents($children->pluck('id'), $schoolId, $academicYearId);
        $classroomIds = ($selectedChild ? collect([$selectedChild]) : $children)
            ->map(fn (Student $student) => (int) ($placements->get($student->id)?->classroom_id ?: $student->classroom_id))
            ->filter()
            ->unique()
            ->values();

        $slots = $this->slotsQuery($schoolId, $classroomIds)->get();
        $childByClassroom = $children->keyBy(fn (Student $student) => (int) ($placements->get($student->id)?->classroom_id ?: $student->classroom_id));

        return response()->json([
            'items' => $slots->map(fn (Timetable $slot): array => $this->mapSlot($slot, $childByClassroom))->values()->all(),
            'children' => $children->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => app(StudentPlacementService::class)->classroomNameForStudent($student, $schoolId, $academicYearId),
            ])->values()->all(),
            'selected_child_id' => $selectedChild?->id,
            'selected_academic_year_id' => $academicYearId,
        ]);
    }

    private function studentIndex(): JsonResponse
    {
        $student = $this->currentStudent(['classroom:id,name']);
        $schoolId = $this->schoolIdOrFail();
        $academicYearId = $this->resolvedAcademicYearId();
        $classroomIds = collect([(int) app(StudentPlacementService::class)->classroomIdForStudent($student, $schoolId, $academicYearId)])
            ->filter()
            ->values();

        $slots = $this->slotsQuery($schoolId, $classroomIds)->get();
        $childByClassroom = collect([(int) app(StudentPlacementService::class)->classroomIdForStudent($student, $schoolId, $academicYearId) => $student]);

        return response()->json([
            'items' => $slots->map(fn (Timetable $slot): array => $this->mapSlot($slot, $childByClassroom))->values()->all(),
            'children' => [],
            'selected_child_id' => null,
            'selected_academic_year_id' => $academicYearId,
        ]);
    }

    private function slotsQuery(int $schoolId, Collection $classroomIds)
    {
        return app(AcademicYearService::class)->applyYearScope(
            Timetable::query(),
            $schoolId,
            $this->requestedAcademicYearId(),
        )
            ->where('school_id', $schoolId)
            ->when($classroomIds->isNotEmpty(), fn ($query) => $query->whereIn('classroom_id', $classroomIds))
            ->with(['teacher:id,name', 'classroom:id,name'])
            ->orderBy('day')
            ->orderBy('start_time');
    }

    private function mapSlot(Timetable $slot, Collection $childByClassroom): array
    {
        /** @var Student|null $student */
        $student = $childByClassroom->get((int) $slot->classroom_id);

        return [
            'id' => (int) $slot->id,
            'day' => (int) $slot->day,
            'day_label' => self::DAYS[(int) $slot->day] ?? 'Jour',
            'start_time' => substr((string) $slot->start_time, 0, 5),
            'end_time' => substr((string) $slot->end_time, 0, 5),
            'subject' => (string) $slot->subject,
            'teacher' => (string) ($slot->teacher?->name ?? ''),
            'classroom' => (string) ($slot->classroom?->name ?? ''),
            'room' => (string) ($slot->room ?? ''),
            'student' => $student ? [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => $student->classroom?->name ?? '',
            ] : null,
        ];
    }

    private function resolveSelectedChild(Collection $children, int $childId): ?Student
    {
        return $childId > 0 ? $children->firstWhere('id', $childId) : null;
    }
}
