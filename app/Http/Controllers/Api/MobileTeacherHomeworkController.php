<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeacherHomeworkCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MobileTeacherHomeworkController extends Controller
{
    public function __construct(
        private readonly TeacherHomeworkCreationService $creationService,
    ) {
    }

    public function meta(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        $schoolId = $this->schoolId();

        return response()->json([
            'classrooms' => $this->creationService->classroomsForTeacher($teacher, $schoolId)->map(fn ($classroom) => [
                'id' => (int) $classroom->id,
                'name' => (string) $classroom->name,
                'level_name' => (string) ($classroom->level?->name ?? ''),
            ])->values()->all(),
            'subjects' => $this->creationService->subjectsForTeacher($teacher, $schoolId)->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
            ])->values()->all(),
            'attachments_supported' => false,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:6000'],
            'due_at' => ['nullable', 'date'],
        ]);

        try {
            $homework = $this->creationService->create($teacher, $schoolId, $data);
        } catch (HttpException $exception) {
            abort($exception->getStatusCode(), $exception->getMessage());
        }

        return response()->json([
            'message' => 'Homework submitted successfully.',
            'item' => [
                'id' => (int) $homework->id,
                'title' => (string) ($homework->title ?? 'Homework'),
                'description' => (string) ($homework->description ?? ''),
                'due_at' => optional($homework->due_at)?->toIso8601String(),
                'status' => (string) $homework->normalized_status,
                'status_label' => ucfirst((string) $homework->normalized_status),
                'classroom_name' => (string) ($homework->classroom?->name ?? ''),
                'teacher_name' => (string) ($homework->teacher?->name ?? ''),
                'subject_name' => (string) ($homework->subject?->name ?? ''),
                'attachments' => $homework->attachments->map(fn ($attachment) => [
                    'id' => (int) $attachment->id,
                    'name' => (string) ($attachment->original_name ?? 'Attachment'),
                    'mime' => (string) ($attachment->mime ?? ''),
                    'size' => (int) ($attachment->size ?? 0),
                    'download_path' => '/mobile/homeworks/attachments/' . (int) $attachment->id,
                ])->values()->all(),
                'created_at' => optional($homework->created_at)?->toIso8601String(),
                'is_new' => false,
            ],
        ]);
    }

    private function teacher(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_TEACHER, 403, 'Homework creation is only available for teachers.');

        return $user;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
