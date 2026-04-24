<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function __construct(
        private readonly PushNotificationService $push,
    ) {
    }

    public function notifyUsers(array $userIds, string $type, string $title, string $body, array $data = []): int
    {
        try {
            if (!Schema::hasTable('notifications')) {
                return 0;
            }

            $ids = collect($userIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return 0;
            }

            $recipients = User::query()
                ->whereIn('id', $ids)
                ->select('id', 'role')
                ->get();
            if ($recipients->isEmpty()) {
                return 0;
            }

            $hasRecipientUserId = Schema::hasColumn('notifications', 'recipient_user_id');
            $hasRecipientRole = Schema::hasColumn('notifications', 'recipient_role');
            $userColumn = $hasRecipientUserId ? 'recipient_user_id' : 'user_id';

            $now = now();
            $payload = [];
            foreach ($recipients as $recipient) {
                $row = [
                    $userColumn => (int) $recipient->id,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data ? json_encode($data) : null,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasRecipientUserId && Schema::hasColumn('notifications', 'user_id')) {
                    $row['user_id'] = (int) $recipient->id;
                }
                if ($hasRecipientRole) {
                    $row['recipient_role'] = $recipient->role;
                }

                $payload[] = $row;
            }

            if (empty($payload)) {
                return 0;
            }

            AppNotification::query()->insert($payload);
            $count = count($payload);

            $schoolId = isset($data['school_id']) ? (int) $data['school_id'] : null;
            $this->push->sendToUsers(
                $recipients->pluck('id')->all(),
                $title,
                $body,
                array_merge($data, ['type' => $type]),
                $schoolId && $schoolId > 0 ? $schoolId : null,
            );

            return $count;
        } catch (\Throwable $e) {
            Log::warning('Notification insert failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function notifyParents(array $parentIds, string $type, string $title, string $body, array $data = []): int
    {
        $ids = User::query()
            ->whereIn('id', collect($parentIds)->map(fn ($id) => (int) $id)->filter()->unique()->all())
            ->where('role', User::ROLE_PARENT)
            ->pluck('id')
            ->all();

        return $this->notifyUsers($ids, $type, $title, $body, $data);
    }

    public function parentIdsByClassroom(int $classroomId, ?int $schoolId = null): array
    {
        if ($classroomId <= 0) {
            return [];
        }

        $query = Student::query()
            ->where('classroom_id', $classroomId)
            ->whereNotNull('parent_user_id');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->pluck('parent_user_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    public function parentIdsBySchool(int $schoolId): array
    {
        if ($schoolId <= 0) {
            return [];
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_PARENT)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function studentUserIdsByClassroom(int $classroomId, ?int $schoolId = null): array
    {
        if ($classroomId <= 0) {
            return [];
        }

        $query = Student::query()
            ->where('classroom_id', $classroomId)
            ->whereNotNull('user_id');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->pluck('user_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    public function studentUserIdsBySchool(int $schoolId): array
    {
        if ($schoolId <= 0) {
            return [];
        }

        return Student::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function teacherIdsByClassroom(int $classroomId, ?int $schoolId = null): array
    {
        if ($classroomId <= 0 || !Schema::hasTable('classroom_teacher')) {
            return [];
        }

        $query = DB::table('classroom_teacher')
            ->where('classroom_id', $classroomId);

        if ($schoolId && Schema::hasColumn('classroom_teacher', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function teacherIdsBySchool(int $schoolId): array
    {
        if ($schoolId <= 0) {
            return [];
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_TEACHER)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
