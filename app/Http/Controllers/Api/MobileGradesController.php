<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileGradesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId($user);

        if ((string) $user->role === User::ROLE_PARENT) {
            $children = $this->parentChildren($user, $schoolId);
            $childId = (int) $request->integer('child_id');
            $selectedChild = $childId > 0 ? $children->firstWhere('id', $childId) : null;

            $query = Grade::query()
                ->where('school_id', $schoolId)
                ->whereIn('student_id', $children->pluck('id'))
                ->with([
                    'student:id,full_name,classroom_id',
                    'student.classroom:id,name',
                    'subject:id,name',
                    'assessment:id,title,date',
                    'teacher:id,name',
                ])
                ->when($selectedChild, fn ($builder) => $builder->where('student_id', (int) $selectedChild->id))
                ->latest('id');

            $rows = $query->limit(100)->get();

            return response()->json([
                'items' => $rows->map(fn (Grade $grade) => $this->gradePayload($grade))->values(),
                'children' => $children->map(fn (Student $student) => $this->studentOptionPayload($student))->values(),
                'selected_child_id' => $selectedChild ? (int) $selectedChild->id : null,
                'overall_average' => $this->overallAverage($rows),
            ]);
        }

        $student = $this->studentRecord($user, $schoolId);
        abort_unless($student, 404, 'Student profile not found.');

        $rows = Grade::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with([
                'student:id,full_name,classroom_id',
                'student.classroom:id,name',
                'subject:id,name',
                'assessment:id,title,date',
                'teacher:id,name',
            ])
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'items' => $rows->map(fn (Grade $grade) => $this->gradePayload($grade))->values(),
            'children' => [],
            'selected_child_id' => null,
            'overall_average' => $this->overallAverage($rows),
        ]);
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

    private function studentOptionPayload(Student $student): array
    {
        return [
            'id' => (int) $student->id,
            'name' => (string) $student->full_name,
            'classroom' => (string) ($student->classroom?->name ?? ''),
        ];
    }

    private function gradePayload(Grade $grade): array
    {
        $maxScore = max(1, (int) ($grade->max_score ?? 0));

        return [
            'id' => (int) $grade->id,
            'score' => (float) $grade->score,
            'max_score' => (int) ($grade->max_score ?? 0),
            'percentage' => round((((float) $grade->score) / $maxScore) * 100, 2),
            'comment' => (string) ($grade->comment ?? ''),
            'date' => $grade->assessment?->date?->toDateString() ?? optional($grade->created_at)?->toDateString(),
            'student' => [
                'id' => (int) ($grade->student?->id ?? 0),
                'name' => (string) ($grade->student?->full_name ?? ''),
                'classroom' => (string) ($grade->student?->classroom?->name ?? ''),
            ],
            'subject' => (string) ($grade->subject?->name ?? ''),
            'teacher' => (string) ($grade->teacher?->name ?? ''),
            'assessment_title' => (string) ($grade->assessment?->title ?? ''),
            'created_at' => optional($grade->created_at)?->toIso8601String(),
        ];
    }

    private function overallAverage(Collection $rows): float
    {
        return round($rows->avg(function (Grade $grade) {
            $maxScore = max(1, (int) ($grade->max_score ?? 0));

            return (((float) $grade->score) / $maxScore) * 100;
        }) ?? 0, 2);
    }
}
