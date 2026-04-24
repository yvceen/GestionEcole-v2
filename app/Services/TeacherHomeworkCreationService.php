<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\HomeworkAttachment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TeacherHomeworkCreationService
{
    public function __construct(
        private readonly HomeworkAttachmentStorageService $attachmentStorage,
        private readonly NotificationService $notifications,
    ) {
    }

    public function classroomsForTeacher(User $teacher, int $schoolId): Collection
    {
        return $teacher->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->with('level:id,name,school_id')
            ->orderBy('name')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.level_id']);
    }

    public function subjectsForTeacher(User $teacher, int $schoolId): Collection
    {
        return $teacher->subjects()
            ->where('subjects.school_id', $schoolId)
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name']);
    }

    public function create(User $teacher, int $schoolId, array $data, array $files = []): Homework
    {
        $teacherId = (int) $teacher->id;
        $allowedClassroomIds = $this->classroomsForTeacher($teacher, $schoolId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (!in_array((int) $data['classroom_id'], $allowedClassroomIds, true)) {
            abort(422, 'Classe invalide pour cet enseignant.');
        }

        $subjectId = (int) ($data['subject_id'] ?? 0);
        if ($subjectId > 0) {
            $subjectAllowed = $this->subjectsForTeacher($teacher, $schoolId)
                ->contains(fn (Subject $subject) => (int) $subject->id === $subjectId);

            if (!$subjectAllowed) {
                abort(422, 'Matiere invalide pour cet enseignant.');
            }
        }

        $payload = [
            'school_id' => $schoolId,
            'classroom_id' => (int) $data['classroom_id'],
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId > 0 ? $subjectId : null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ];

        if (Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'status')) {
            $payload['status'] = 'pending';
        }

        /** @var Homework $homework */
        $homework = DB::transaction(function () use ($payload, $files, $schoolId): Homework {
            $homework = Homework::create($payload);

            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $path = $this->attachmentStorage->storeUploadedFile(
                    $file,
                    $schoolId,
                    (int) $homework->id,
                );

                $attachmentPayload = [
                    'homework_id' => (int) $homework->id,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];

                if (Schema::hasTable('homework_attachments') && Schema::hasColumn('homework_attachments', 'school_id')) {
                    $attachmentPayload['school_id'] = $schoolId;
                }

                HomeworkAttachment::create($attachmentPayload);
            }

            return $homework;
        });

        $this->notifyApprovers($homework, $schoolId);

        return $homework->load(['classroom:id,name', 'teacher:id,name', 'subject:id,name,school_id', 'attachments:id,homework_id,original_name,mime,size']);
    }

    private function notifyApprovers(Homework $homework, int $schoolId): void
    {
        try {
            $adminIds = User::query()
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_ADMIN)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $schoolLifeIds = User::query()
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_SCHOOL_LIFE)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->all();

            $this->notifications->notifyUsers(
                array_values(array_unique(array_merge($adminIds, $schoolLifeIds))),
                'homework',
                'Nouveau devoir en attente',
                (string) ($homework->title ?: 'Un devoir enseignant est en attente de validation.'),
                ['homework_id' => (int) $homework->id]
            );
        } catch (\Throwable $e) {
            Log::warning('Teacher homework pending notification failed', [
                'homework_id' => $homework->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
