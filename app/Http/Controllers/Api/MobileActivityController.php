<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileActivityController extends Controller
{
    use InteractsWithParentPortal;
    use InteractsWithStudentPortal {
        InteractsWithParentPortal::schoolIdOrFail insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::visibleCoursesQuery insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::visibleHomeworksQuery insteadof InteractsWithStudentPortal;
        InteractsWithParentPortal::unreadNotificationsCount insteadof InteractsWithStudentPortal;
    }

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

    public function show(Request $request, Activity $activity): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->parentShow($activity),
            User::ROLE_STUDENT => $this->studentShow($activity),
            default => abort(403),
        };
    }

    private function parentIndex(Request $request): JsonResponse
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $selectedChild = $this->resolveSelectedChild($children, $request->integer('child_id'));
        $schoolId = $this->schoolIdOrFail();
        $childIds = $selectedChild ? collect([$selectedChild->id]) : $children->pluck('id');

        $activities = Activity::query()
            ->where('school_id', $schoolId)
            ->whereHas('participants', fn (Builder $query) => $query->whereIn('student_id', $childIds))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->whereIn('student_id', $childIds)->with('student:id,full_name,classroom_id'),
            ])
            ->orderBy('start_date')
            ->limit(80)
            ->get();

        return response()->json([
            'items' => $activities->map(fn (Activity $activity): array => $this->mapActivity($activity))->values()->all(),
            'children' => $children->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
            ])->values()->all(),
            'selected_child_id' => $selectedChild?->id,
        ]);
    }

    private function studentIndex(): JsonResponse
    {
        $student = $this->currentStudent(['classroom:id,name']);

        $activities = Activity::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereHas('participants', fn (Builder $query) => $query->where('student_id', $student->id))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->where('student_id', $student->id)->with('student:id,full_name,classroom_id'),
            ])
            ->orderBy('start_date')
            ->limit(80)
            ->get();

        return response()->json([
            'items' => $activities->map(fn (Activity $activity): array => $this->mapActivity($activity))->values()->all(),
            'children' => [],
            'selected_child_id' => null,
        ]);
    }

    private function parentShow(Activity $activity): JsonResponse
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $childIds = $children->pluck('id');

        $resolved = Activity::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereKey($activity->id)
            ->whereHas('participants', fn (Builder $query) => $query->whereIn('student_id', $childIds))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->whereIn('student_id', $childIds)->with('student:id,full_name,classroom_id'),
            ])
            ->firstOrFail();

        return response()->json([
            'item' => $this->mapActivity($resolved),
        ]);
    }

    private function studentShow(Activity $activity): JsonResponse
    {
        $student = $this->currentStudent(['classroom:id,name']);

        $resolved = Activity::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereKey($activity->id)
            ->whereHas('participants', fn (Builder $query) => $query->where('student_id', $student->id))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->where('student_id', $student->id)->with('student:id,full_name,classroom_id'),
            ])
            ->firstOrFail();

        return response()->json([
            'item' => $this->mapActivity($resolved),
        ]);
    }

    private function mapActivity(Activity $activity): array
    {
        $participants = $activity->participants->values();
        /** @var ActivityParticipant|null $primaryParticipant */
        $primaryParticipant = $participants->first();

        return [
            'id' => (int) $activity->id,
            'title' => (string) $activity->title,
            'description' => (string) ($activity->description ?? ''),
            'type' => (string) ($activity->type ?? ''),
            'type_label' => Activity::labelForType((string) $activity->type),
            'start_date' => optional($activity->start_date)->toIso8601String(),
            'end_date' => optional($activity->end_date)->toIso8601String(),
            'location' => (string) ($activity->classroom?->name ?? ''),
            'classroom' => (string) ($activity->classroom?->name ?? ''),
            'teacher' => (string) ($activity->teacher?->name ?? ''),
            'status' => (string) ($primaryParticipant?->confirmation_status ?? ActivityParticipant::CONFIRMATION_PENDING),
            'status_label' => $this->statusLabel((string) ($primaryParticipant?->confirmation_status ?? ActivityParticipant::CONFIRMATION_PENDING)),
            'attendance_status' => (string) ($primaryParticipant?->attendance_status ?? ''),
            'participation_note' => (string) ($primaryParticipant?->note ?? ''),
            'participants' => $participants->map(fn (ActivityParticipant $participant): array => [
                'student' => [
                    'id' => (int) ($participant->student?->id ?? 0),
                    'name' => (string) ($participant->student?->full_name ?? ''),
                    'classroom' => (string) ($participant->student?->classroom?->name ?? ''),
                ],
                'confirmation_status' => (string) $participant->confirmation_status,
                'confirmation_label' => $this->statusLabel((string) $participant->confirmation_status),
                'attendance_status' => (string) ($participant->attendance_status ?? ''),
                'note' => (string) ($participant->note ?? ''),
                'confirmed_at' => optional($participant->confirmed_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function resolveSelectedChild(Collection $children, int $childId): ?Student
    {
        return $childId > 0 ? $children->firstWhere('id', $childId) : null;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ActivityParticipant::CONFIRMATION_CONFIRMED => 'Confirmed',
            ActivityParticipant::CONFIRMATION_DECLINED => 'Declined',
            default => 'Pending',
        };
    }
}
