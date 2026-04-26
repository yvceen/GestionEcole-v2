<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MessageController extends Controller
{
    use InteractsWithMessages;

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $admin = auth()->guard('web')->user();
        $adminId = (int) ($admin->id ?? 0);

        $folder = $request->get('folder', 'inbox');
        $qText = trim((string) $request->get('q', ''));
        $selectedThreadId = (int) $request->integer('mid');

        $inboxThreadIds = Message::query()
            ->forSchool($schoolId)
            ->approved()
            ->addressedToUser($adminId)
            ->get()
            ->map(fn (Message $message) => (int) $message->thread_key)
            ->unique()
            ->values();

        $sentThreadIds = Message::query()
            ->forSchool($schoolId)
            ->where('sender_id', $adminId)
            ->get()
            ->map(fn (Message $message) => (int) $message->thread_key)
            ->unique()
            ->values();

        $counts = [
            'inbox' => $inboxThreadIds->count(),
            'pending' => Message::query()->forSchool($schoolId)->pending()->count(),
            'sent' => $sentThreadIds->count(),
        ];

        if ($selectedThreadId > 0 && $folder !== 'sent') {
            $selectedMessage = Message::query()
                ->forSchool($schoolId)
                ->whereThreadKey($selectedThreadId)
                ->oldest('created_at')
                ->first();

            if ($selectedMessage) {
                $this->markThreadAsRead($selectedMessage, $admin, $schoolId);
            }
        }

        $threadIds = ($folder === 'sent' ? $sentThreadIds : $inboxThreadIds)->all();

        $threadMessages = Message::query()
            ->forSchool($schoolId)
            ->with(['sender'])
            ->when($threadIds !== [], fn ($builder) => $builder->whereInThreadKeys($threadIds), fn ($builder) => $builder->whereRaw('1 = 0'))
            ->latest('created_at')
            ->get();

        if ($qText !== '') {
            $matchingThreadIds = $threadMessages
                ->filter(function (Message $message) use ($qText) {
                    return str_contains(mb_strtolower($message->subjectText()), mb_strtolower($qText))
                        || str_contains(mb_strtolower($message->bodyText()), mb_strtolower($qText));
                })
                ->map(fn (Message $message) => (int) $message->thread_key)
                ->unique()
                ->values()
                ->all();

            $threadMessages = $threadMessages
                ->filter(fn (Message $message) => in_array((int) $message->thread_key, $matchingThreadIds, true))
                ->values();
        }

        $messages = $this->paginateCollection(
            $this->buildThreadSummaries($threadMessages, $admin, $schoolId),
            15
        )->appends($request->query());

        return view('admin.messages.index', array_merge(compact('messages', 'counts', 'folder'), [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
            'canCompose' => $this->canCompose(),
            'canModerate' => $this->canModerate(),
        ]));
    }

    public function pending()
    {
        abort_unless($this->canModerate(), 403);
        $schoolId = $this->schoolId();

        $pending = Message::query()
            ->forSchool($schoolId)
            ->pending()
            ->with(['sender', 'replyTo.sender'])
            ->latest('created_at')
            ->paginate(15);

        return view('admin.messages.pending', [
            'pending' => $pending,
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canCompose(), 403);
        $schoolId = $this->schoolId();

        $classrooms = collect();
        if (Schema::hasTable('classrooms')) {
            $classrooms = DB::table('classrooms')
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('school_id', $schoolId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        $parents = collect();
        $teachers = collect();

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            $base = DB::table('users')
                ->when(Schema::hasColumn('users', 'school_id'), fn ($query) => $query->where('school_id', $schoolId))
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name');

            $parents = (clone $base)->where('role', 'parent')->get();
            $teachers = (clone $base)->whereIn('role', ['teacher', 'director'])->get();
        }

        return view('admin.messages.create', [
            'classrooms' => $classrooms,
            'parents' => $parents,
            'teachers' => $teachers,
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ]);
    }

    public function store(StoreMessageRequest $request)
    {
        abort_unless($this->canCompose(), 403);
        $schoolId = $this->schoolId();
        $admin = auth()->guard('web')->user();
        $data = $request->validated();
        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);

        try {
            if (!empty($data['reply_to_id'])) {
                $replyTo = Message::query()->forSchool($schoolId)->findOrFail((int) $data['reply_to_id']);
                $recipient = $this->directReplyRecipient($replyTo, $admin, $schoolId);

                if (!$recipient) {
                    return back()->withInput()->withErrors(['body' => 'Cette conversation ne peut pas recevoir de reponse directe.']);
                }

                $message = $this->createMessageRecord($this->buildMessagePayload(
                    $columns,
                    $admin,
                    $schoolId,
                    (string) $data['body'],
                    (string) ($data['subject'] ?: $replyTo->subjectText()),
                    [
                        'type' => $targetColumns['type'] ?? 'recipient_type',
                        'id' => $targetColumns['id'] ?? 'recipient_id',
                        'type_value' => 'user',
                        'id_value' => (int) $recipient->id,
                    ],
                    false,
                    'approved',
                    $replyTo,
                    (int) $admin->id
                ));

                $this->notifyMessageUsers(
                    [$recipient->id],
                    $message->subjectText(),
                    $message->bodyText(),
                    [
                        'message_id' => (int) $message->id,
                        'url' => $this->messageShowRouteForRole((string) $recipient->role, (int) $message->id),
                    ]
                );

                return redirect()->route($this->routePrefix() . '.show', $message)->with('success', 'Reponse envoyee avec succes.');
            }

            $classroomId = (int) ($data['classroom_id'] ?? 0);
            $parentIds = array_values(array_unique(array_map('intval', $data['parent_ids'] ?? [])));
            $teacherIds = array_values(array_unique(array_map('intval', $data['teacher_ids'] ?? [])));
            $userIds = array_values(array_unique(array_merge($parentIds, $teacherIds)));

            if ($classroomId && $userIds !== []) {
                return back()->withErrors([
                    'classroom_id' => 'Veuillez choisir soit une classe, soit des utilisateurs.',
                ])->withInput();
            }

            if (!$classroomId && $userIds === []) {
                return back()->withErrors([
                    'classroom_id' => 'Veuillez choisir au moins une classe ou un utilisateur.',
                ])->withInput();
            }

            if ($classroomId > 0 && !$this->classroomExistsInSchool($classroomId, $schoolId)) {
                return back()->withErrors([
                    'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
                ])->withInput();
            }

            if ($parentIds !== [] && !$this->usersMatchRolesAndSchool($parentIds, ['parent'], $schoolId)) {
                return back()->withErrors([
                    'parent_ids' => 'Un ou plusieurs parents sont invalides pour cette ecole.',
                ])->withInput();
            }

            if ($teacherIds !== [] && !$this->usersMatchRolesAndSchool($teacherIds, ['teacher', 'director'], $schoolId)) {
                return back()->withErrors([
                    'teacher_ids' => 'Un ou plusieurs membres du personnel sont invalides pour cette ecole.',
                ])->withInput();
            }

            $createdMessages = collect();
            if ($classroomId > 0) {
                $createdMessages->push($this->createMessageRecord($this->buildMessagePayload(
                    $columns,
                    $admin,
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
                    (int) $admin->id
                )));
            } else {
                foreach ($userIds as $userId) {
                    $createdMessages->push($this->createMessageRecord($this->buildMessagePayload(
                        $columns,
                        $admin,
                        $schoolId,
                        (string) $data['body'],
                        (string) ($data['subject'] ?? null),
                        [
                            'type' => $targetColumns['type'] ?? 'recipient_type',
                            'id' => $targetColumns['id'] ?? 'recipient_id',
                            'type_value' => 'user',
                            'id_value' => $userId,
                        ],
                        false,
                        'approved',
                        null,
                        (int) $admin->id
                    )));
                }
            }

            $firstMessage = $createdMessages->first();

            $notificationUserIds = $classroomId > 0
                ? $this->resolveClassroomNotificationRecipients($classroomId, $schoolId)
                : $userIds;

            $this->notifyMessageUsers(
                $notificationUserIds,
                (string) ($data['subject'] ?: 'Nouveau message'),
                (string) $data['body'],
                [
                    'message_id' => (int) ($firstMessage?->id ?? 0),
                ]
            );

            return redirect()
                ->route($this->routePrefix() . '.show', $firstMessage)
                ->with('success', 'Message envoye avec succes.');
        } catch (\Throwable $e) {
            Log::error('Admin message send failed', [
                'school_id' => $schoolId,
                'admin_id' => $admin->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['body' => "Echec de l'envoi du message."]);
        }
    }

    public function show(Message $message)
    {
        $schoolId = $this->schoolId();
        abort_unless((int) $message->school_id === $schoolId, 404);

        $threadMessages = Message::query()
            ->forSchool($schoolId)
            ->inThread($message)
            ->with(['sender', 'approver', 'rejecter'])
            ->orderBy('created_at')
            ->get();
        $this->markThreadAsRead($message, auth()->guard('web')->user(), $schoolId);

        $replyRecipient = $this->directReplyRecipient($message, auth()->guard('web')->user(), $schoolId);

        return view('admin.messages.show', [
            'message' => $message,
            'threadMessages' => $threadMessages,
            'replyRecipient' => $replyRecipient,
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
            'canModerate' => $this->canModerate(),
            'canCompose' => $this->canCompose(),
        ]);
    }

    public function approve(Message $message)
    {
        abort_unless($this->canModerate(), 403);
        $schoolId = $this->schoolId();
        abort_unless((int) $message->school_id === $schoolId, 404);

        if (($message->status ?? null) !== 'pending') {
            return back()->with('success', 'Ce message est deja traite.');
        }

        $admin = auth()->guard('web')->user();
        $columns = collect($this->messageColumns())->flip()->all();
        $payload = [];

        if (isset($columns['status'])) {
            $payload['status'] = 'approved';
        }
        if (isset($columns['approved_by'])) {
            $payload['approved_by'] = $admin->id;
        }
        if (isset($columns['approved_at'])) {
            $payload['approved_at'] = now();
        }
        if (isset($columns['rejected_by'])) {
            $payload['rejected_by'] = null;
        }
        if (isset($columns['rejected_at'])) {
            $payload['rejected_at'] = null;
        }
        if (isset($columns['rejection_reason'])) {
            $payload['rejection_reason'] = null;
        }

        DB::table('messages')->where('id', $message->id)->update($payload);
        $message->refresh();

        try {
            $recipientIds = $this->resolveRecipientUserIdsFromMessage($message, $schoolId);
            $this->notifyMessageUsers(
                $recipientIds,
                $message->subjectText(),
                $message->bodyText(),
                ['message_id' => (int) $message->id]
            );
        } catch (\Throwable $e) {
            Log::warning('Admin message approve notifications failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route($this->routePrefix() . '.pending')->with('success', 'Message approuve.');
    }

    public function reject(ApproveMessageRequest $request, Message $message)
    {
        abort_unless($this->canModerate(), 403);
        $schoolId = $this->schoolId();
        abort_unless((int) $message->school_id === $schoolId, 404);

        if (($message->status ?? null) !== 'pending') {
            return back()->with('success', 'Ce message est deja traite.');
        }

        $admin = auth()->guard('web')->user();
        $data = $request->validated();
        $columns = collect($this->messageColumns())->flip()->all();
        $payload = [];

        if (isset($columns['status'])) {
            $payload['status'] = 'rejected';
        }
        if (isset($columns['rejected_by'])) {
            $payload['rejected_by'] = $admin->id;
        }
        if (isset($columns['rejected_at'])) {
            $payload['rejected_at'] = now();
        }
        if (isset($columns['approved_by'])) {
            $payload['approved_by'] = null;
        }
        if (isset($columns['approved_at'])) {
            $payload['approved_at'] = null;
        }
        if (isset($columns['rejection_reason'])) {
            $payload['rejection_reason'] = $data['reason'] ?? 'Refuse';
        }

        DB::table('messages')->where('id', $message->id)->update($payload);

        try {
            $this->notifyMessageUsers(
                [(int) $message->sender_id],
                $message->subjectText(),
                'Votre message a ete refuse. ' . ($data['reason'] ?? 'Refuse'),
                ['message_id' => (int) $message->id]
            );
        } catch (\Throwable $e) {
            Log::warning('Admin message reject notifications failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route($this->routePrefix() . '.pending')->with('success', 'Message refuse.');
    }

    protected function routePrefix(): string
    {
        return 'admin.messages';
    }

    protected function layoutComponent(): string
    {
        return 'admin-layout';
    }

    protected function canCompose(): bool
    {
        return true;
    }

    protected function canModerate(): bool
    {
        return true;
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

    private function resolveClassroomNotificationRecipients(int $classroomId, int $schoolId): array
    {
        $parentIds = app(\App\Services\NotificationService::class)->parentIdsByClassroom($classroomId, $schoolId);
        $teacherIds = app(\App\Services\NotificationService::class)->teacherIdsByClassroom($classroomId, $schoolId);

        return array_values(array_unique(array_merge($parentIds, $teacherIds)));
    }
}
