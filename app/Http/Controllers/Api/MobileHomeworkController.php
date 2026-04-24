<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkAttachment;
use App\Models\HomeworkUserView;
use App\Models\Student;
use App\Models\User;
use App\Services\HomeworkAttachmentStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHomeworkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();

        $homeworks = $this->visibleHomeworksQuery($user, $schoolId)
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'subject:id,name,school_id',
                'attachments:id,homework_id,original_name,mime,size',
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('created_at')
            ->limit(50)
            ->get();
        $viewMap = $this->viewMap($user, $schoolId, $homeworks->pluck('id')->all());
        $items = $homeworks->map(fn (Homework $homework) => $this->homeworkPayload(
            $homework,
            $user,
            $schoolId,
            false,
            $this->isUnread($homework, $viewMap[(int) $homework->id] ?? null)
        ))->values();

        return response()->json([
            'items' => $items->all(),
            'unread_count' => $items->where('is_new', true)->count(),
        ]);
    }

    public function show(Request $request, Homework $homework): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();

        abort_unless((int) $homework->school_id === $schoolId, 404);
        abort_unless($this->canUserSeeHomework($homework, $user, $schoolId), 403);

        $homework->load([
            'classroom:id,name',
            'teacher:id,name',
            'subject:id,name,school_id',
            'attachments:id,homework_id,original_name,mime,size',
        ]);
        $view = HomeworkUserView::query()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->where('homework_id', (int) $homework->id)
            ->first();

        return response()->json([
            'item' => $this->homeworkPayload(
                $homework,
                $user,
                $schoolId,
                true,
                $this->isUnread($homework, $view?->viewed_at)
            ),
        ]);
    }

    public function markRead(Request $request, Homework $homework): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();

        abort_unless((int) $homework->school_id === $schoolId, 404);
        abort_unless($this->canUserSeeHomework($homework, $user, $schoolId), 403);

        HomeworkUserView::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'user_id' => (int) $user->id,
                'homework_id' => (int) $homework->id,
            ],
            [
                'viewed_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function downloadAttachment(Request $request, HomeworkAttachment $attachment)
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();

        $attachment->load('homework');

        abort_unless($attachment->homework instanceof Homework, 404);
        abort_unless((int) $attachment->school_id === $schoolId, 403);
        abort_unless((int) $attachment->homework->school_id === $schoolId, 404);
        abort_unless($this->canUserSeeHomework($attachment->homework, $user, $schoolId), 403);

        return app(HomeworkAttachmentStorageService::class)->downloadResponse($attachment);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(in_array((string) $user->role, [
            User::ROLE_PARENT,
            User::ROLE_STUDENT,
            User::ROLE_TEACHER,
        ], true), 403, 'Homework is not available for this role.');

        return $user;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function visibleHomeworksQuery(User $user, int $schoolId): Builder
    {
        $query = Homework::query()->where('school_id', $schoolId);

        return match ((string) $user->role) {
            User::ROLE_PARENT => $query
                ->whereIn('classroom_id', $this->parentClassroomIds($user, $schoolId))
                ->approved(),
            User::ROLE_STUDENT => $query
                ->where('classroom_id', $this->studentClassroomId($user, $schoolId))
                ->approved(),
            User::ROLE_TEACHER => $query
                ->where('teacher_id', (int) $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function canUserSeeHomework(Homework $homework, User $user, int $schoolId): bool
    {
        if ((int) $homework->school_id !== $schoolId) {
            return false;
        }

        return match ((string) $user->role) {
            User::ROLE_PARENT => in_array((int) $homework->classroom_id, $this->parentClassroomIds($user, $schoolId), true)
                && $homework->normalized_status === 'approved',
            User::ROLE_STUDENT => (int) $homework->classroom_id === $this->studentClassroomId($user, $schoolId)
                && $homework->normalized_status === 'approved',
            User::ROLE_TEACHER => (int) ($homework->teacher_id ?? 0) === (int) $user->id,
            default => false,
        };
    }

    private function parentClassroomIds(User $user, int $schoolId): array
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->pluck('classroom_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function studentClassroomId(User $user, int $schoolId): int
    {
        return (int) (Student::query()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->value('classroom_id') ?? 0);
    }

    private function affectedChildren(Homework $homework, User $user, int $schoolId): array
    {
        if ((string) $user->role !== User::ROLE_PARENT) {
            return [];
        }

        return Student::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->where('classroom_id', (int) $homework->classroom_id)
            ->orderBy('full_name')
            ->pluck('full_name')
            ->map(fn ($name) => (string) $name)
            ->filter()
            ->values()
            ->all();
    }

    private function homeworkPayload(
        Homework $homework,
        User $user,
        int $schoolId,
        bool $includeDescription,
        bool $isUnread
    ): array
    {
        return [
            'id' => (int) $homework->id,
            'title' => (string) ($homework->title ?? 'Homework'),
            'description' => $includeDescription ? (string) ($homework->description ?? '') : '',
            'due_at' => optional($homework->due_at)?->toIso8601String(),
            'status' => (string) $homework->normalized_status,
            'status_label' => ucfirst((string) $homework->normalized_status),
            'classroom_name' => (string) ($homework->classroom?->name ?? ''),
            'teacher_name' => (string) ($homework->teacher?->name ?? ''),
            'subject_name' => (string) ($homework->subject?->name ?? ''),
            'affected_children' => $this->affectedChildren($homework, $user, $schoolId),
            'attachments' => $homework->attachments->map(fn ($attachment) => [
                'id' => (int) $attachment->id,
                'name' => (string) ($attachment->original_name ?? 'Attachment'),
                'mime' => (string) ($attachment->mime ?? ''),
                'size' => (int) ($attachment->size ?? 0),
                'download_path' => '/mobile/homeworks/attachments/' . (int) $attachment->id,
            ])->values()->all(),
            'created_at' => optional($homework->created_at)?->toIso8601String(),
            'is_new' => $isUnread,
        ];
    }

    private function viewMap(User $user, int $schoolId, array $homeworkIds): array
    {
        if ($homeworkIds === []) {
            return [];
        }

        return HomeworkUserView::query()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->whereIn('homework_id', $homeworkIds)
            ->get(['homework_id', 'viewed_at'])
            ->mapWithKeys(fn (HomeworkUserView $view) => [
                (int) $view->homework_id => $view->viewed_at,
            ])
            ->all();
    }

    private function isUnread(Homework $homework, $viewedAt): bool
    {
        $reference = $homework->approved_at ?? $homework->updated_at ?? $homework->created_at;
        if ($reference === null) {
            return $viewedAt === null;
        }

        return $viewedAt === null || $viewedAt->lt($reference);
    }
}
