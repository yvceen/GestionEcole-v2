<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithMessages;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MobileMessageController extends Controller
{
    use InteractsWithMessages;

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();

        $threadMessages = $this->threadMessagesForUser($user, $schoolId);
        $summaries = $this->buildThreadSummaries($threadMessages, $user, $schoolId)
            ->map(fn (array $summary) => $this->threadSummaryPayload($summary, $user, $schoolId))
            ->values()
            ->all();

        return response()->json([
            'threads' => $summaries,
            'unread_count' => collect($summaries)->sum('unread_count'),
        ]);
    }

    public function compose(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();
        abort_unless($this->canComposeInMobile($user), 403, 'Compose is not available for this role.');

        return response()->json([
            'role' => (string) $user->role,
            'classrooms' => $this->composeClassrooms($user, $schoolId),
            'parents' => $this->composeParents($user, $schoolId),
            'teachers' => $this->composeTeachers($user, $schoolId),
            'recipients' => $this->composeRecipients($user, $schoolId),
        ]);
    }

    public function show(Request $request, int $thread): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();
        $rootMessage = $this->rootMessageForThread($thread, $schoolId);
        abort_unless($rootMessage, 404);
        abort_unless($this->canUserSeeMessage($rootMessage, $user, $schoolId), 403);

        $messages = $this->visibleThreadMessages($rootMessage, $user, $schoolId);
        abort_unless($messages->isNotEmpty(), 404);
        $this->markThreadAsRead($rootMessage, $user, $schoolId);

        $summary = $this->buildThreadSummaries($messages, $user, $schoolId)->first();
        $replyRecipient = $this->canReplyInMobile($user)
            ? $this->directReplyRecipient($rootMessage, $user, $schoolId)
            : null;

        return response()->json([
            'thread' => $summary ? $this->threadSummaryPayload($summary, $user, $schoolId) : null,
            'can_reply' => $replyRecipient instanceof User,
            'messages' => $messages->map(fn (Message $message) => $this->messagePayload($message, $user))->values()->all(),
        ]);
    }

    public function reply(Request $request, int $thread): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();
        abort_unless($this->canReplyInMobile($user), 403, 'Replies are not available for this role.');

        $rootMessage = $this->rootMessageForThread($thread, $schoolId);
        abort_unless($rootMessage, 404);
        abort_unless($this->canUserSeeMessage($rootMessage, $user, $schoolId), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'body.required' => 'Le message est obligatoire.',
            'body.min' => 'Le message doit contenir au moins 5 caracteres.',
            'body.max' => 'Le message ne doit pas depasser 5000 caracteres.',
        ]);

        $recipient = $this->directReplyRecipient($rootMessage, $user, $schoolId);
        abort_unless($recipient instanceof User, 422, 'This conversation cannot receive a direct reply.');

        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);

        if ((string) $user->role === User::ROLE_TEACHER) {
            $requiresApproval = (string) $recipient->role === User::ROLE_PARENT;
            $status = $requiresApproval ? 'pending' : 'approved';
            $message = $this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $user,
                $schoolId,
                (string) $data['body'],
                $rootMessage->subjectText(),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'user',
                    'id_value' => (int) $recipient->id,
                ],
                $requiresApproval,
                $status,
                $rootMessage,
                $status === 'approved' ? (int) $user->id : null
            ));

            if ($status === 'approved') {
                $this->notifyMessageUsers(
                    [(int) $recipient->id],
                    $message->subjectText(),
                    $message->bodyText(),
                    [
                        'message_id' => (int) $message->id,
                        'url' => $this->messageShowRouteForRole((string) $recipient->role, (int) $message->id),
                    ]
                );
            }
        } else {
            $message = $this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $user,
                $schoolId,
                (string) $data['body'],
                $rootMessage->subjectText(),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'user',
                    'id_value' => (int) $recipient->id,
                ],
                false,
                'approved',
                $rootMessage,
                (int) $user->id
            ));

            $this->notifyMessageUsers(
                [(int) $recipient->id],
                $message->subjectText(),
                $message->bodyText(),
                [
                    'message_id' => (int) $message->id,
                    'url' => $this->messageShowRouteForRole((string) $recipient->role, (int) $message->id),
                ]
            );
        }

        return response()->json([
            'message' => $this->messagePayload($message->load('sender'), $user),
            'thread_id' => (int) $message->thread_key,
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();
        abort_unless($this->canComposeInMobile($user), 403, 'Compose is not available for this role.');

        $message = match ((string) $user->role) {
            User::ROLE_ADMIN => $this->storeAdminMessage($request, $user, $schoolId),
            User::ROLE_TEACHER => $this->storeTeacherMessage($request, $user, $schoolId),
            User::ROLE_PARENT => $this->storeParentMessage($request, $user, $schoolId),
            default => abort(403, 'Compose is not available for this role.'),
        };

        return response()->json([
            'message' => $this->messagePayload($message->load('sender'), $user),
            'thread_id' => (int) $message->thread_key,
        ], 201);
    }

    public function markRead(Request $request, int $thread): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $schoolId = $this->schoolId();
        $rootMessage = $this->rootMessageForThread($thread, $schoolId);
        abort_unless($rootMessage, 404);
        abort_unless($this->canUserSeeMessage($rootMessage, $user, $schoolId), 403);

        $this->markThreadAsRead($rootMessage, $user, $schoolId);

        return response()->json(['ok' => true]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless(
            in_array((string) $user->role, [
                User::ROLE_ADMIN,
                User::ROLE_PARENT,
                User::ROLE_TEACHER,
                User::ROLE_DIRECTOR,
            ], true),
            403,
            'Messaging is not available for this role.'
        );

        return $user;
    }

    private function canReplyInMobile(User $user): bool
    {
        return in_array((string) $user->role, [
            User::ROLE_ADMIN,
            User::ROLE_PARENT,
            User::ROLE_TEACHER,
        ], true);
    }

    private function canComposeInMobile(User $user): bool
    {
        return $this->canReplyInMobile($user);
    }

    private function threadMessagesForUser(User $user, int $schoolId): Collection
    {
        $columns = $this->messageColumns();
        $target = $this->targetColumns($columns);
        $userId = (int) $user->id;

        return match ((string) $user->role) {
            User::ROLE_PARENT => Message::query()
                ->forSchool($schoolId)
                ->with(['sender'])
                ->where(function ($outer) use ($userId, $target, $columns, $user, $schoolId) {
                    $classroomIds = $this->classroomIdsForParent($user, $schoolId);
                    $outer->where('sender_id', $userId);
                    $outer->orWhere(function ($direct) use ($userId, $columns) {
                        if (in_array('status', $columns, true)) {
                            $direct->where('status', 'approved');
                        }
                        $direct->addressedToUser($userId);
                    });

                    if ($classroomIds !== [] && !empty($target)) {
                        $outer->orWhere(function ($classroomQuery) use ($classroomIds, $columns, $target) {
                            if (in_array('status', $columns, true)) {
                                $classroomQuery->where('status', 'approved');
                            }
                            $classroomQuery->where($target['type'], 'classroom')
                                ->whereIn($target['id'], $classroomIds);
                        });
                    }
                })
                ->latest('created_at')
                ->get(),
            User::ROLE_TEACHER => Message::query()
                ->forSchool($schoolId)
                ->with(['sender'])
                ->where(function ($outer) use ($userId, $columns) {
                    $outer->where('sender_id', $userId);
                    $outer->orWhere(function ($inner) use ($userId, $columns) {
                        if (in_array('status', $columns, true)) {
                            $inner->where('status', 'approved');
                        }
                        $inner->addressedToUser($userId);
                    });
                })
                ->latest('created_at')
                ->get(),
            User::ROLE_DIRECTOR => Message::query()
                ->forSchool($schoolId)
                ->with(['sender'])
                ->where(function ($query) use ($userId) {
                    $query->where('sender_id', $userId)
                        ->orWhere(fn ($inner) => $inner->approved()->addressedToUser($userId));
                })
                ->latest('created_at')
                ->get(),
            default => $this->adminThreadMessages($userId, $schoolId),
        };
    }

    private function adminThreadMessages(int $userId, int $schoolId): Collection
    {
        $inboxThreadIds = Message::query()
            ->forSchool($schoolId)
            ->approved()
            ->addressedToUser($userId)
            ->get()
            ->map(fn (Message $message) => (int) $message->thread_key)
            ->unique()
            ->values()
            ->all();

        $sentThreadIds = Message::query()
            ->forSchool($schoolId)
            ->where('sender_id', $userId)
            ->get()
            ->map(fn (Message $message) => (int) $message->thread_key)
            ->unique()
            ->values()
            ->all();

        $threadIds = array_values(array_unique(array_merge($inboxThreadIds, $sentThreadIds)));

        return Message::query()
            ->forSchool($schoolId)
            ->with(['sender'])
            ->when(
                $threadIds !== [],
                fn ($builder) => $builder->whereInThreadKeys($threadIds),
                fn ($builder) => $builder->whereRaw('1 = 0')
            )
            ->latest('created_at')
            ->get();
    }

    private function rootMessageForThread(int $threadId, int $schoolId): ?Message
    {
        return Message::query()
            ->forSchool($schoolId)
            ->whereThreadKey($threadId)
            ->oldest('created_at')
            ->first();
    }

    private function visibleThreadMessages(Message $message, User $user, int $schoolId): Collection
    {
        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->loadVisibleThread(
                $message,
                $user,
                $schoolId,
                $this->classroomIdsForParent($user, $schoolId)
            ),
            User::ROLE_DIRECTOR => Message::query()
                ->forSchool($schoolId)
                ->inThread($message)
                ->with(['sender', 'approver', 'rejecter'])
                ->orderBy('created_at')
                ->get()
                ->filter(function (Message $threadMessage) use ($user): bool {
                    $directorId = (int) $user->id;
                    return (int) ($threadMessage->sender_id ?? 0) === $directorId || $threadMessage->isForUser($directorId);
                })
                ->values(),
            default => $this->loadVisibleThread($message, $user, $schoolId),
        };
    }

    private function canUserSeeMessage(Message $message, User $user, int $schoolId): bool
    {
        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->userCanSeeMessage(
                $message,
                $user,
                $schoolId,
                $this->classroomIdsForParent($user, $schoolId)
            ),
            User::ROLE_DIRECTOR => (int) ($message->school_id ?? 0) === $schoolId
                && (
                    (int) ($message->sender_id ?? 0) === (int) $user->id
                    || $message->isForUser((int) $user->id)
                ),
            default => $this->userCanSeeMessage($message, $user, $schoolId),
        };
    }

    private function threadSummaryPayload(array $summary, User $user, int $schoolId): array
    {
        /** @var Message|null $message */
        $message = $summary['message'] ?? null;
        $rootMessage = $message
            ? $this->rootMessageForThread((int) $summary['thread_id'], $schoolId)
            : null;
        $replyRecipient = $rootMessage && $this->canReplyInMobile($user)
            ? $this->directReplyRecipient($rootMessage, $user, $schoolId)
            : null;

        return [
            'thread_id' => (int) ($summary['thread_id'] ?? 0),
            'message_id' => (int) ($summary['message_id'] ?? 0),
            'subject' => (string) ($summary['subject'] ?? ''),
            'snippet' => (string) ($summary['snippet'] ?? ''),
            'status' => (string) ($summary['status'] ?? 'approved'),
            'participant_label' => (string) ($summary['participant_label'] ?? ''),
            'sender_name' => (string) ($summary['sender_name'] ?? ''),
            'latest_message_at' => optional($summary['created_at'] ?? null)?->toIso8601String(),
            'unread_count' => (int) ($summary['unread_count'] ?? 0),
            'is_latest_mine' => (bool) ($summary['is_latest_mine'] ?? false),
            'can_reply' => $replyRecipient instanceof User,
        ];
    }

    private function messagePayload(Message $message, User $user): array
    {
        return [
            'id' => (int) $message->id,
            'thread_id' => (int) $message->thread_key,
            'sender_name' => (string) ($message->sender?->name ?? 'User'),
            'body' => $message->bodyText(),
            'status' => (string) ($message->status ?? 'approved'),
            'subject' => $message->subjectText(),
            'created_at' => $message->created_at?->toIso8601String(),
            'is_mine' => (int) ($message->sender_id ?? 0) === (int) $user->id,
            'rejection_reason' => (string) ($message->rejection_reason ?? ''),
            'pending_notice' => (string) ($message->status ?? '') === 'pending'
                ? 'En attente de validation admin avant affichage au destinataire.'
                : null,
            'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($message->bodyText()))), 120),
        ];
    }

    private function composeClassrooms(User $user, int $schoolId): array
    {
        if ((string) $user->role === User::ROLE_ADMIN) {
            if (!Schema::hasTable('classrooms')) {
                return [];
            }

            return DB::table('classrooms')
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('school_id', $schoolId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn ($classroom) => [
                    'id' => (int) $classroom->id,
                    'name' => (string) ($classroom->name ?? ('Classroom #' . $classroom->id)),
                    'meta' => '',
                ])
                ->values()
                ->all();
        }

        if ((string) $user->role !== User::ROLE_TEACHER || !Schema::hasTable('classrooms')) {
            return [];
        }

        if (Schema::hasTable('classroom_teacher')) {
            return DB::table('classrooms')
                ->join('classroom_teacher', 'classrooms.id', '=', 'classroom_teacher.classroom_id')
                ->where('classroom_teacher.teacher_id', (int) $user->id)
                ->when(Schema::hasColumn('classroom_teacher', 'school_id'), fn ($query) => $query->where('classroom_teacher.school_id', $schoolId))
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->select('classrooms.id', 'classrooms.name')
                ->orderBy('classrooms.name')
                ->get()
                ->map(fn ($classroom) => [
                    'id' => (int) $classroom->id,
                    'name' => (string) ($classroom->name ?? ('Classroom #' . $classroom->id)),
                    'meta' => '',
                ])
                ->values()
                ->all();
        }

        if (Schema::hasTable('classroom_user')) {
            return DB::table('classrooms')
                ->join('classroom_user', 'classrooms.id', '=', 'classroom_user.classroom_id')
                ->where('classroom_user.user_id', (int) $user->id)
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->select('classrooms.id', 'classrooms.name')
                ->orderBy('classrooms.name')
                ->get()
                ->map(fn ($classroom) => [
                    'id' => (int) $classroom->id,
                    'name' => (string) ($classroom->name ?? ('Classroom #' . $classroom->id)),
                    'meta' => '',
                ])
                ->values()
                ->all();
        }

        return [];
    }

    private function composeParents(User $user, int $schoolId): array
    {
        if (!in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_TEACHER], true)) {
            return [];
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return [];
        }

        $query = DB::table('users')
            ->where('role', User::ROLE_PARENT)
            ->select('id', 'name', 'email')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?? ('Parent #' . $item->id)),
                'meta' => (string) ($item->email ?? ''),
            ])
            ->values()
            ->all();
    }

    private function composeTeachers(User $user, int $schoolId): array
    {
        if ((string) $user->role !== User::ROLE_ADMIN || !Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return [];
        }

        $query = DB::table('users')
            ->whereIn('role', [User::ROLE_TEACHER, User::ROLE_DIRECTOR])
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?? ('User #' . $item->id)),
                'meta' => trim(implode(' - ', array_filter([
                    (string) ($item->role ?? ''),
                    (string) ($item->email ?? ''),
                ]))),
            ])
            ->values()
            ->all();
    }

    private function composeRecipients(User $user, int $schoolId): array
    {
        if ((string) $user->role !== User::ROLE_PARENT || !Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return [];
        }

        $query = DB::table('users')
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_DIRECTOR])
            ->select('id', 'name', 'email', 'role')
            ->orderBy('role')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?? ('User #' . $item->id)),
                'meta' => trim(implode(' - ', array_filter([
                    (string) ($item->role ?? ''),
                    (string) ($item->email ?? ''),
                ]))),
            ])
            ->values()
            ->all();
    }

    private function storeAdminMessage(Request $request, User $user, int $schoolId): Message
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'classroom_id' => ['nullable', 'integer'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer'],
        ]);

        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);
        $classroomId = (int) ($data['classroom_id'] ?? 0);
        $parentIds = array_values(array_unique(array_map('intval', $data['parent_ids'] ?? [])));
        $teacherIds = array_values(array_unique(array_map('intval', $data['teacher_ids'] ?? [])));
        $userIds = array_values(array_unique(array_merge($parentIds, $teacherIds)));

        if ($classroomId > 0 && $userIds !== []) {
            abort(422, 'Choose either one classroom or individual recipients.');
        }

        if ($classroomId <= 0 && $userIds === []) {
            abort(422, 'Choose at least one classroom or one recipient.');
        }

        if ($classroomId > 0 && !$this->classroomExistsInSchool($classroomId, $schoolId)) {
            abort(422, 'The selected classroom is invalid for this school.');
        }

        if ($parentIds !== [] && !$this->usersMatchRolesAndSchool($parentIds, [User::ROLE_PARENT], $schoolId)) {
            abort(422, 'One or more selected parents are invalid for this school.');
        }

        if ($teacherIds !== [] && !$this->usersMatchRolesAndSchool($teacherIds, [User::ROLE_TEACHER, User::ROLE_DIRECTOR], $schoolId)) {
            abort(422, 'One or more selected staff members are invalid for this school.');
        }

        $createdMessages = collect();
        if ($classroomId > 0) {
            $createdMessages->push($this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $user,
                $schoolId,
                (string) $data['body'],
                (string) ($data['subject'] ?? null),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'classroom',
                    'id_value' => $classroomId,
                ],
                false,
                'approved',
                null,
                (int) $user->id
            )));
        } else {
            foreach ($userIds as $recipientId) {
                $createdMessages->push($this->createMessageRecord($this->buildMessagePayload(
                    $columns,
                    $user,
                    $schoolId,
                    (string) $data['body'],
                    (string) ($data['subject'] ?? null),
                    [
                        'type' => $targetColumns['type'] ?? 'recipient_type',
                        'id' => $targetColumns['id'] ?? 'recipient_id',
                        'type_value' => 'user',
                        'id_value' => $recipientId,
                    ],
                    false,
                    'approved',
                    null,
                    (int) $user->id
                )));
            }
        }

        $firstMessage = $createdMessages->first();
        if (!$firstMessage instanceof Message) {
            abort(500, 'Unable to create the message.');
        }

        $notificationUserIds = $classroomId > 0
            ? $this->resolveClassroomNotificationRecipients($classroomId, $schoolId)
            : $userIds;

        $this->notifyMessageUsers(
            $notificationUserIds,
            $firstMessage->subjectText(),
            (string) $data['body'],
            ['message_id' => (int) $firstMessage->id]
        );

        return $firstMessage;
    }

    private function storeTeacherMessage(Request $request, User $user, int $schoolId): Message
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'classroom_id' => ['nullable', 'integer'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer'],
        ]);

        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);
        $classroomId = (int) ($data['classroom_id'] ?? 0);
        $parentIds = collect($data['parent_ids'] ?? [])
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if ($classroomId <= 0 && $parentIds === []) {
            abort(422, 'Choose one classroom or one or more parents.');
        }

        if ($classroomId > 0 && !$this->teacherCanMessageClassroom((int) $user->id, $classroomId, $schoolId)) {
            abort(422, 'The selected classroom is invalid for this teacher.');
        }

        if ($parentIds !== [] && !$this->usersMatchRolesAndSchool($parentIds, [User::ROLE_PARENT], $schoolId)) {
            abort(422, 'One or more selected parents are invalid for this school.');
        }

        if ($classroomId > 0) {
            return $this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $user,
                $schoolId,
                (string) $data['body'],
                (string) ($data['subject'] ?? null),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'classroom',
                    'id_value' => $classroomId,
                    'user_ids' => null,
                ],
                true,
                'pending'
            ));
        }

        $firstMessage = null;
        foreach ($parentIds as $recipientId) {
            $message = $this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $user,
                $schoolId,
                (string) $data['body'],
                (string) ($data['subject'] ?? null),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'user',
                    'id_value' => $recipientId,
                    'user_ids' => null,
                ],
                true,
                'pending'
            ));
            $firstMessage ??= $message;
        }

        if (!$firstMessage instanceof Message) {
            abort(500, 'Unable to create the message.');
        }

        return $firstMessage;
    }

    private function storeParentMessage(Request $request, User $user, int $schoolId): Message
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'recipient_id' => ['required', 'integer'],
        ]);

        $recipient = User::query()
            ->where('school_id', $schoolId)
            ->whereKey((int) $data['recipient_id'])
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_DIRECTOR])
            ->first();
        abort_unless($recipient instanceof User, 422, 'The selected recipient is invalid for this school.');

        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);
        $message = $this->createMessageRecord($this->buildMessagePayload(
            $columns,
            $user,
            $schoolId,
            (string) $data['body'],
            (string) ($data['subject'] ?? null),
            [
                'type' => $targetColumns['type'] ?? 'recipient_type',
                'id' => $targetColumns['id'] ?? 'recipient_id',
                'type_value' => 'user',
                'id_value' => (int) $recipient->id,
            ],
            false,
            'approved',
            null,
            (int) $user->id
        ));

        $this->notifyMessageUsers(
            [(int) $recipient->id],
            $message->subjectText(),
            $message->bodyText(),
            [
                'message_id' => (int) $message->id,
                'url' => $this->messageShowRouteForRole((string) $recipient->role, (int) $message->id),
            ]
        );

        return $message;
    }

    private function classroomExistsInSchool(int $classroomId, int $schoolId): bool
    {
        if ($classroomId <= 0 || !Schema::hasTable('classrooms')) {
            return false;
        }

        $query = DB::table('classrooms')->where('id', $classroomId);
        if (Schema::hasColumn('classrooms', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->exists();
    }

    private function usersMatchRolesAndSchool(array $userIds, array $roles, int $schoolId): bool
    {
        if ($userIds === [] || !Schema::hasTable('users')) {
            return $userIds === [];
        }

        $query = DB::table('users')
            ->whereIn('id', $userIds)
            ->whereIn('role', $roles);

        if (Schema::hasColumn('users', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query->count() === count(array_unique(array_map('intval', $userIds)));
    }

    private function teacherCanMessageClassroom(int $teacherId, int $classroomId, int $schoolId): bool
    {
        if ($classroomId <= 0 || !Schema::hasTable('classrooms')) {
            return false;
        }

        if (Schema::hasTable('classroom_teacher')) {
            return DB::table('classrooms')
                ->join('classroom_teacher', 'classrooms.id', '=', 'classroom_teacher.classroom_id')
                ->where('classrooms.id', $classroomId)
                ->where('classroom_teacher.teacher_id', $teacherId)
                ->when(Schema::hasColumn('classroom_teacher', 'school_id'), fn ($query) => $query->where('classroom_teacher.school_id', $schoolId))
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->exists();
        }

        if (Schema::hasTable('classroom_user')) {
            return DB::table('classrooms')
                ->join('classroom_user', 'classrooms.id', '=', 'classroom_user.classroom_id')
                ->where('classrooms.id', $classroomId)
                ->where('classroom_user.user_id', $teacherId)
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->exists();
        }

        return false;
    }

    private function resolveClassroomNotificationRecipients(int $classroomId, int $schoolId): array
    {
        $parentIds = app(\App\Services\NotificationService::class)->parentIdsByClassroom($classroomId, $schoolId);
        $teacherIds = app(\App\Services\NotificationService::class)->teacherIdsByClassroom($classroomId, $schoolId);

        return array_values(array_unique(array_merge($parentIds, $teacherIds)));
    }
}
