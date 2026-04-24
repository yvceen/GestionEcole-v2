<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AppNotification;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait InteractsWithMessages
{
    protected function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    protected function messageColumns(): array
    {
        return Message::columns();
    }

    protected function targetColumns(array $columns): array
    {
        $hasTarget = in_array('target_type', $columns, true) && in_array('target_id', $columns, true);
        $hasRecipient = in_array('recipient_type', $columns, true) && in_array('recipient_id', $columns, true);

        if ($hasTarget) {
            return ['type' => 'target_type', 'id' => 'target_id'];
        }

        if ($hasRecipient) {
            return ['type' => 'recipient_type', 'id' => 'recipient_id'];
        }

        return [];
    }

    protected function directReplyRecipient(Message $message, User $sender, int $schoolId): ?User
    {
        $columns = $this->messageColumns();
        $target = $this->targetColumns($columns);

        if ((int) ($message->sender_id ?? 0) !== (int) $sender->id) {
            return User::query()
                ->where('school_id', $schoolId)
                ->whereKey((int) $message->sender_id)
                ->first();
        }

        if (empty($target)) {
            return null;
        }

        $typeColumn = $target['type'];
        $idColumn = $target['id'];
        $typeValue = (string) ($message->{$typeColumn} ?? '');
        $idValue = (int) ($message->{$idColumn} ?? 0);

        if ($typeValue === 'user' && $idValue > 0) {
            return User::query()
                ->where('school_id', $schoolId)
                ->whereKey($idValue)
                ->first();
        }

        return null;
    }

    protected function buildMessagePayload(
        array $columns,
        User $sender,
        int $schoolId,
        string $body,
        ?string $subject,
        array $target,
        bool $approvalRequired,
        string $status,
        ?Message $replyTo = null,
        ?int $approvedBy = null
    ): array {
        $now = now();
        $payload = [];

        if (in_array('school_id', $columns, true)) {
            $payload['school_id'] = $schoolId;
        }
        if (in_array('sender_id', $columns, true)) {
            $payload['sender_id'] = (int) $sender->id;
        } elseif (in_array('sender_user_id', $columns, true)) {
            $payload['sender_user_id'] = (int) $sender->id;
        }
        if (in_array('sender_role', $columns, true)) {
            $payload['sender_role'] = $sender->role;
        }
        if (in_array('subject', $columns, true)) {
            $payload['subject'] = $subject;
        } elseif (in_array('title', $columns, true)) {
            $payload['title'] = $subject;
        }
        if (in_array('body', $columns, true)) {
            $payload['body'] = $body;
        } elseif (in_array('message', $columns, true)) {
            $payload['message'] = $body;
        } elseif (in_array('content', $columns, true)) {
            $payload['content'] = $body;
        }
        if (in_array('approval_required', $columns, true)) {
            $payload['approval_required'] = $approvalRequired;
        }
        if (in_array('status', $columns, true)) {
            $payload['status'] = $status;
        }
        if (in_array('thread_id', $columns, true)) {
            $payload['thread_id'] = $replyTo?->thread_key;
        }
        if (in_array('reply_to_id', $columns, true)) {
            $payload['reply_to_id'] = $replyTo?->id;
        }
        if (in_array('approved_by', $columns, true)) {
            $payload['approved_by'] = $status === 'approved' ? $approvedBy : null;
        }
        if (in_array('approved_at', $columns, true)) {
            $payload['approved_at'] = $status === 'approved' ? $now : null;
        }
        if (in_array('rejected_by', $columns, true)) {
            $payload['rejected_by'] = null;
        }
        if (in_array('rejected_at', $columns, true)) {
            $payload['rejected_at'] = null;
        }
        if (in_array('rejection_reason', $columns, true)) {
            $payload['rejection_reason'] = null;
        }
        if (in_array('created_at', $columns, true)) {
            $payload['created_at'] = $now;
        }
        if (in_array('updated_at', $columns, true)) {
            $payload['updated_at'] = $now;
        }

        if (isset($target['type'], $target['id'])) {
            $payload[$target['type']] = $target['type_value'];
            $payload[$target['id']] = $target['id_value'];
        }

        if (in_array('target_user_ids', $columns, true)) {
            $payload['target_user_ids'] = $target['user_ids'] ?? null;
        }

        return $payload;
    }

    protected function createMessageRecord(array $payload): Message
    {
        $id = DB::table('messages')->insertGetId($payload);

        if (!empty($payload['thread_id']) || !Schema::hasColumn('messages', 'thread_id')) {
            return Message::query()->findOrFail($id);
        }

        DB::table('messages')->where('id', $id)->update(['thread_id' => $id]);

        return Message::query()->findOrFail($id);
    }

    protected function notifyMessageUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        if ($userIds === []) {
            return;
        }

        if (
            !array_key_exists('thread_id', $data)
            && !empty($data['message_id'])
            && is_numeric($data['message_id'])
        ) {
            $message = Message::query()
                ->select(Message::hasThreadIdColumn() ? ['id', 'thread_id'] : ['id'])
                ->find((int) $data['message_id']);

            if ($message) {
                $data['thread_id'] = (int) $message->thread_key;
            }
        }

        if (!empty($data['message_id']) && is_numeric($data['message_id'])) {
            $message = Message::query()
                ->with('sender:id,name')
                ->find((int) $data['message_id']);

            if ($message) {
                $data['sender_name'] = $message->sender?->name;
                $data['message_body'] = $message->bodyText();
            }
        }

        app(NotificationService::class)->notifyUsers(
            $userIds,
            'message',
            $title,
            mb_substr($body, 0, 180),
            $data
        );
    }

    protected function resolveRecipientUserIdsFromMessage(Message $message, int $schoolId): array
    {
        $columns = $this->messageColumns();
        $target = $this->targetColumns($columns);

        if (empty($target)) {
            return [];
        }

        $typeColumn = $target['type'];
        $idColumn = $target['id'];
        $typeValue = (string) ($message->{$typeColumn} ?? '');
        $idValue = (int) ($message->{$idColumn} ?? 0);

        if ($typeValue === 'classroom' && $idValue > 0) {
            return app(NotificationService::class)->parentIdsByClassroom($idValue, $schoolId);
        }

        $recipients = [];
        if ($typeValue === 'user' && $idValue > 0) {
            $recipients[] = $idValue;
        }

        if (
            in_array('target_user_ids', $columns, true)
            && is_array($message->target_user_ids)
        ) {
            $recipients = array_merge($recipients, array_map('intval', $message->target_user_ids));
        }

        return array_values(array_unique(array_filter($recipients)));
    }

    protected function userCanSeeMessage(Message $message, User $user, int $schoolId, array $classroomIds = []): bool
    {
        if ((int) ($message->school_id ?? 0) !== $schoolId) {
            return false;
        }

        if ((int) ($message->sender_id ?? 0) === (int) $user->id) {
            return true;
        }

        if (($message->status ?? 'approved') !== 'approved') {
            return false;
        }

        $columns = $this->messageColumns();
        $target = $this->targetColumns($columns);
        if (empty($target)) {
            return false;
        }

        $typeValue = (string) ($message->{$target['type']} ?? '');
        $idValue = (int) ($message->{$target['id']} ?? 0);

        if ($typeValue === 'user' && $idValue === (int) $user->id) {
            return true;
        }

        if (
            $target['type'] === 'target_type'
            && $typeValue === 'user'
            && is_array($message->target_user_ids)
            && in_array((int) $user->id, array_map('intval', $message->target_user_ids), true)
        ) {
            return true;
        }

        return $typeValue === 'classroom'
            && $classroomIds !== []
            && in_array($idValue, array_map('intval', $classroomIds), true);
    }

    protected function loadVisibleThread(Message $message, User $user, int $schoolId, array $classroomIds = []): Collection
    {
        return Message::query()
            ->forSchool($schoolId)
            ->inThread($message)
            ->with(['sender', 'approver', 'rejecter'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Message $threadMessage) => $this->userCanSeeMessage($threadMessage, $user, $schoolId, $classroomIds))
            ->values();
    }

    protected function buildThreadSummaries(Collection $messages, User $user, int $schoolId): Collection
    {
        $unreadCounts = $this->unreadMessageCountsByThreadForUser($user, $schoolId);

        return $messages
            ->groupBy(fn (Message $message) => (int) $message->thread_key)
            ->map(function (Collection $threadMessages) use ($user, $unreadCounts) {
                $ordered = $threadMessages
                    ->sortByDesc(fn (Message $message) => optional($message->created_at)?->getTimestamp() ?? 0)
                    ->values();

                /** @var Message|null $latest */
                $latest = $ordered->first();
                if (!$latest) {
                    return null;
                }

                $subjectSource = $threadMessages->first(function (Message $message) {
                    $subject = trim($message->subjectText());

                    return $subject !== '' && $subject !== '(Sans sujet)';
                }) ?: $latest;

                $participants = $threadMessages
                    ->map(function (Message $message) use ($user) {
                        if ((int) ($message->sender_id ?? 0) === (int) $user->id) {
                            return null;
                        }

                        return $message->sender?->name ?: ('Utilisateur #' . (int) ($message->sender_id ?? 0));
                    })
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'thread_id' => (int) $latest->thread_key,
                    'message_id' => (int) $latest->id,
                    'message' => $latest,
                    'latest_message' => $latest,
                    'subject' => $subjectSource->subjectText(),
                    'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($latest->bodyText()))), 120),
                    'status' => (string) ($latest->status ?? 'approved'),
                    'created_at' => $latest->created_at,
                    'sender_name' => $latest->sender?->name ?: ('Utilisateur #' . (int) ($latest->sender_id ?? 0)),
                    'participant_label' => $participants->take(2)->implode(', ') ?: 'Vous',
                    'is_latest_mine' => (int) ($latest->sender_id ?? 0) === (int) $user->id,
                    'unread_count' => (int) ($unreadCounts[(int) $latest->thread_key] ?? 0),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $summary) => optional($summary['created_at'] ?? null)?->getTimestamp() ?? 0)
            ->values();
    }

    protected function classroomIdsForParent(User $parent, int $schoolId): array
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'parent_user_id') || !Schema::hasColumn('students', 'classroom_id')) {
            return [];
        }

        return DB::table('students')
            ->where('parent_user_id', $parent->id)
            ->when(Schema::hasColumn('students', 'school_id'), fn ($query) => $query->where('school_id', $schoolId))
            ->whereNotNull('classroom_id')
            ->pluck('classroom_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function paginateCollection(Collection $items, int $perPage = 15, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    protected function messageShowRouteForRole(string $role, int $messageId): ?string
    {
        $route = "{$role}.messages.show";

        return Route::has($route) ? route($route, $messageId) : null;
    }

    protected function markThreadAsRead(Message $message, User $user, int $schoolId): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $userColumn = $this->notificationUserColumn();
        if (!Schema::hasColumn('notifications', $userColumn)) {
            return;
        }

        $threadMessageIds = Message::query()
            ->forSchool($schoolId)
            ->inThread($message)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($threadMessageIds === []) {
            return;
        }

        $messageIdSet = array_fill_keys($threadMessageIds, true);
        $notificationIds = AppNotification::query()
            ->where($userColumn, (int) $user->id)
            ->where('type', 'message')
            ->whereNull('read_at')
            ->get(['id', 'data'])
            ->filter(function (AppNotification $notification) use ($messageIdSet) {
                $messageId = (int) data_get($notification->data, 'message_id');

                return $messageId > 0 && isset($messageIdSet[$messageId]);
            })
            ->pluck('id')
            ->all();

        if ($notificationIds === []) {
            return;
        }

        AppNotification::query()
            ->whereIn('id', $notificationIds)
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function unreadMessageCountsByThreadForUser(User $user, int $schoolId): array
    {
        if (!Schema::hasTable('notifications')) {
            return [];
        }

        $userColumn = $this->notificationUserColumn();
        if (!Schema::hasColumn('notifications', $userColumn)) {
            return [];
        }

        $notifications = AppNotification::query()
            ->where($userColumn, (int) $user->id)
            ->where('type', 'message')
            ->whereNull('read_at')
            ->get(['data']);

        $messageIds = $notifications
            ->map(fn (AppNotification $notification) => (int) data_get($notification->data, 'message_id'))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($messageIds->isEmpty()) {
            return [];
        }

        $threadMap = Message::query()
            ->forSchool($schoolId)
            ->whereIn('id', $messageIds->all())
            ->get(Message::hasThreadIdColumn() ? ['id', 'thread_id'] : ['id'])
            ->mapWithKeys(fn (Message $message) => [(int) $message->id => (int) $message->thread_key]);

        $counts = [];
        foreach ($notifications as $notification) {
            $messageId = (int) data_get($notification->data, 'message_id');
            $threadId = (int) ($threadMap[$messageId] ?? 0);

            if ($threadId <= 0) {
                continue;
            }

            $counts[$threadId] = ($counts[$threadId] ?? 0) + 1;
        }

        return $counts;
    }

    protected function notificationUserColumn(): string
    {
        return Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'recipient_user_id')
            ? 'recipient_user_id'
            : 'user_id';
    }
}
