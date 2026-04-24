<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class MobileCourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId($user);
        $q = trim((string) $request->query('q', ''));

        if ((string) $user->role === User::ROLE_PARENT) {
            $children = $this->parentChildren($user, $schoolId);
            $childId = (int) $request->integer('child_id');
            $selectedChild = $childId > 0 ? $children->firstWhere('id', $childId) : null;
            $classroomIds = $selectedChild && $selectedChild->classroom_id
                ? collect([(int) $selectedChild->classroom_id])
                : $children->pluck('classroom_id')->filter()->unique()->values();

            $items = $classroomIds->isEmpty()
                ? collect()
                : $this->visibleCoursesQuery($schoolId, $classroomIds->all())
                    ->when($q !== '', function (Builder $builder) use ($q): void {
                        $builder->where(function ($nested) use ($q): void {
                            $nested->where('title', 'like', "%{$q}%")
                                ->orWhere('description', 'like', "%{$q}%");
                        });
                    })
                    ->with(['classroom:id,name', 'teacher:id,name', 'attachments:id,course_id,original_name,mime,size'])
                    ->latest()
                    ->limit(60)
                    ->get();

            return response()->json([
                'items' => $items->map(fn (Course $course) => $this->coursePayload($course, $children))->values(),
                'children' => $children->map(fn (Student $student) => $this->studentOptionPayload($student))->values(),
                'selected_child_id' => $selectedChild ? (int) $selectedChild->id : null,
            ]);
        }

        $student = $this->studentRecord($user, $schoolId);
        abort_unless($student && $student->classroom_id, 404, 'Student classroom not found.');

        $items = $this->visibleCoursesQuery($schoolId, [(int) $student->classroom_id])
            ->when($q !== '', function (Builder $builder) use ($q): void {
                $builder->where(function ($nested) use ($q): void {
                    $nested->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->with(['classroom:id,name', 'teacher:id,name', 'attachments:id,course_id,original_name,mime,size'])
            ->latest()
            ->limit(60)
            ->get();

        return response()->json([
            'items' => $items->map(fn (Course $course) => $this->coursePayload($course, collect([$student])))->values(),
            'children' => [],
            'selected_child_id' => null,
        ]);
    }

    public function downloadAttachment(Request $request, CourseAttachment $attachment)
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId($user);

        $attachment->load('course');

        abort_unless($attachment->course instanceof Course, 404);
        abort_unless((int) $attachment->school_id === $schoolId, 403);
        abort_unless((int) $attachment->course->school_id === $schoolId, 404);
        abort_unless($this->canAccessCourse($attachment->course, $user, $schoolId), 403);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->path), 404, 'File not found.');

        return $disk->download($attachment->path, $attachment->original_name);
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

    private function visibleCoursesQuery(int $schoolId, array $classroomIds): Builder
    {
        return Course::query()
            ->where('school_id', $schoolId)
            ->whereIn('classroom_id', $classroomIds)
            ->when(
                Schema::hasTable('courses') && Schema::hasColumn('courses', 'status'),
                fn (Builder $query) => $query->whereIn('status', ['approved', 'confirmed'])
            );
    }

    private function canAccessCourse(Course $course, User $user, int $schoolId): bool
    {
        if ((int) $course->school_id !== $schoolId) {
            return false;
        }

        return match ((string) $user->role) {
            User::ROLE_PARENT => Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', (int) $user->id)
                ->where('classroom_id', (int) $course->classroom_id)
                ->exists(),
            User::ROLE_STUDENT => (int) (Student::query()
                ->active()
                ->where('school_id', $schoolId)
                ->where('user_id', (int) $user->id)
                ->value('classroom_id') ?? 0) === (int) $course->classroom_id,
            default => false,
        };
    }

    private function coursePayload(Course $course, Collection $students): array
    {
        $affectedChildren = $students
            ->where('classroom_id', (int) $course->classroom_id)
            ->pluck('full_name')
            ->map(fn ($name) => (string) $name)
            ->filter()
            ->values()
            ->all();

        return [
            'id' => (int) $course->id,
            'title' => (string) ($course->title ?? 'Course'),
            'description' => (string) ($course->description ?? ''),
            'subject_name' => '',
            'teacher_name' => (string) ($course->teacher?->name ?? ''),
            'classroom_name' => (string) ($course->classroom?->name ?? ''),
            'published_at' => optional($course->published_at)?->toIso8601String(),
            'status' => (string) ($course->status ?? ''),
            'affected_children' => $affectedChildren,
            'attachments' => $course->attachments->map(fn (CourseAttachment $attachment) => [
                'id' => (int) $attachment->id,
                'name' => (string) ($attachment->original_name ?? 'Attachment'),
                'mime' => (string) ($attachment->mime ?? ''),
                'size' => (int) ($attachment->size ?? 0),
                'download_path' => route('api.mobile.courses.attachments.download', ['attachment' => $attachment->id], false),
            ])->values()->all(),
            'created_at' => optional($course->created_at)?->toIso8601String(),
        ];
    }

    private function studentOptionPayload(Student $student): array
    {
        return [
            'id' => (int) $student->id,
            'name' => (string) $student->full_name,
            'classroom' => (string) ($student->classroom?->name ?? ''),
        ];
    }
}
