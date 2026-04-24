<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentBehavior;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileBehaviorFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        $schoolId = $this->schoolId($user);
        $studentIds = $this->studentIdsForUser($user, $schoolId);

        $items = StudentBehavior::query()
            ->where('school_id', $schoolId)
            ->where('visible_to_parent', true)
            ->whereIn('student_id', $studentIds)
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'author:id,name'])
            ->latest('date')
            ->limit(30)
            ->get()
            ->map(fn (StudentBehavior $behavior) => [
                'id' => (int) $behavior->id,
                'student_name' => (string) ($behavior->student?->full_name ?? ''),
                'classroom_name' => (string) ($behavior->student?->classroom?->name ?? ''),
                'type' => (string) $behavior->type,
                'description' => (string) $behavior->description,
                'date' => $behavior->date?->toDateString(),
                'date_label' => $behavior->date?->format('d/m/Y') ?? '',
                'author' => (string) ($behavior->author?->name ?? ''),
            ])
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }

    private function studentIdsForUser(User $user, int $schoolId): array
    {
        if ((string) $user->role === User::ROLE_PARENT) {
            return Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', (int) $user->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
