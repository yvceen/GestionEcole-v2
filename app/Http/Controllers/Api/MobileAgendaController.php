<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);

        $role = (string) $user->role;
        abort_unless(in_array($role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        $classroomIds = $this->resolveClassroomIds($user, $schoolId);
        $today = now()->startOfDay();

        $events = Event::query()
            ->where('school_id', $schoolId)
            ->where('end', '>=', $today)
            ->where(function ($query) use ($classroomIds): void {
                $query->whereNull('classroom_id');
                if ($classroomIds !== []) {
                    $query->orWhereIn('classroom_id', $classroomIds);
                }
            })
            ->with('classroom:id,name')
            ->orderBy('start')
            ->limit(80)
            ->get()
            ->map(function (Event $event): array {
                return [
                    'id' => 'event-' . (int) $event->id,
                    'source' => 'event',
                    'title' => (string) $event->title,
                    'starts_at' => optional($event->start)->toIso8601String(),
                    'ends_at' => optional($event->end)->toIso8601String(),
                    'type' => Event::labelForType((string) $event->type),
                    'type_key' => (string) $event->type,
                    'description' => null,
                    'classroom' => $event->classroom?->name,
                ];
            });

        $activities = Activity::query()
            ->where('school_id', $schoolId)
            ->where('end_date', '>=', $today)
            ->where(function ($query) use ($classroomIds): void {
                $query->whereNull('classroom_id');
                if ($classroomIds !== []) {
                    $query->orWhereIn('classroom_id', $classroomIds);
                }
            })
            ->with('classroom:id,name')
            ->orderBy('start_date')
            ->limit(80)
            ->get()
            ->map(function (Activity $activity): array {
                return [
                    'id' => 'activity-' . (int) $activity->id,
                    'source' => 'activity',
                    'title' => (string) $activity->title,
                    'starts_at' => optional($activity->start_date)->toIso8601String(),
                    'ends_at' => optional($activity->end_date)->toIso8601String(),
                    'type' => 'Activite - ' . Activity::labelForType((string) $activity->type),
                    'type_key' => (string) $activity->type,
                    'description' => $activity->description,
                    'classroom' => $activity->classroom?->name,
                ];
            });

        $items = $events
            ->concat($activities)
            ->sortBy('starts_at')
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    private function resolveClassroomIds(User $user, int $schoolId): array
    {
        if ((string) $user->role === User::ROLE_PARENT) {
            return Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', (int) $user->id)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ((string) $user->role === User::ROLE_STUDENT) {
            $student = Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('user_id', (int) $user->id)
                ->first();

            if (!$student || !$student->classroom_id) {
                return [];
            }

            return [(int) $student->classroom_id];
        }

        return [];
    }
}
