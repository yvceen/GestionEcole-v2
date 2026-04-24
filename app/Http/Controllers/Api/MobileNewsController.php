<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileNewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user && in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        $schoolId = $this->schoolId($user);
        $classroomIds = $this->classroomIdsForUser($user, $schoolId);

        $items = News::query()
            ->published()
            ->visibleToClassrooms($schoolId, $classroomIds)
            ->with('classroom:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->map(fn (News $news) => $this->newsPayload($news, false))
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }

    public function show(Request $request, News $news): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user && in_array((string) $user->role, [User::ROLE_PARENT, User::ROLE_STUDENT], true), 403);

        $schoolId = $this->schoolId($user);
        $classroomIds = $this->classroomIdsForUser($user, $schoolId);

        $resolved = News::query()
            ->published()
            ->visibleToClassrooms($schoolId, $classroomIds)
            ->with('classroom:id,name')
            ->whereKey($news->id)
            ->first();

        abort_unless($resolved, 404);

        return response()->json(['item' => $this->newsPayload($resolved, true)]);
    }

    private function classroomIdsForUser(User $user, int $schoolId): array
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
            $classroomId = Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('user_id', (int) $user->id)
                ->value('classroom_id');

            return $classroomId ? [(int) $classroomId] : [];
        }

        return [];
    }

    private function newsPayload(News $news, bool $includeBody): array
    {
        return [
            'id' => (int) $news->id,
            'title' => (string) $news->title,
            'summary' => (string) ($news->summary ?? ''),
            'excerpt' => (string) $news->excerpt,
            'body' => $includeBody ? (string) ($news->body ?? '') : null,
            'date' => $news->date?->toDateString(),
            'scope' => (string) ($news->scope ?? 'school'),
            'classroom' => (string) ($news->classroom?->name ?? ''),
            'cover_url' => $news->cover_url,
            'is_pinned' => (bool) ($news->is_pinned ?? false),
        ];
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
