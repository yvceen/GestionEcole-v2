<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\InteractsWithMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MessageController extends Controller
{
    use InteractsWithMessages;

    public function index(Request $request)
    {
        try {
            $schoolId = $this->schoolId();
            $teacher = Auth::user();
            $teacherId = (int) $teacher->id;
            $columns = $this->messageColumns();
            $selectedThreadId = (int) $request->integer('mid');

            if ($selectedThreadId > 0) {
                $selectedMessage = Message::query()
                    ->forSchool($schoolId)
                    ->whereThreadKey($selectedThreadId)
                    ->oldest('created_at')
                    ->first();

                if ($selectedMessage && $this->userCanSeeMessage($selectedMessage, $teacher, $schoolId)) {
                    $this->markThreadAsRead($selectedMessage, $teacher, $schoolId);
                }
            }

            $threadMessages = Message::query()
                ->forSchool($schoolId)
                ->with(['sender'])
                ->where(function ($outer) use ($teacherId, $columns) {
                    $outer->where('sender_id', $teacherId);

                    $outer->orWhere(function ($inner) use ($teacherId, $columns) {
                        if (in_array('status', $columns, true)) {
                            $inner->where('status', 'approved');
                        }

                        $inner->addressedToUser($teacherId);
                    });
                })
                ->latest('created_at')
                ->get();

            $messages = $this->paginateCollection(
                $this->buildThreadSummaries($threadMessages, $teacher, $schoolId),
                15
            )->appends($request->query());

            return view('teacher.messages.index', compact('messages'));
        } catch (\Throwable $e) {
            Log::error($e);

            return back()->withErrors('Unable to load messages.');
        }
    }

    public function create()
    {
        $schoolId = $this->schoolId();
        $teacherId = Auth::id();

        $classrooms = collect();
        if (Schema::hasTable('classroom_teacher')) {
            $classrooms = DB::table('classrooms')
                ->join('classroom_teacher', 'classrooms.id', '=', 'classroom_teacher.classroom_id')
                ->where('classroom_teacher.teacher_id', $teacherId)
                ->when(Schema::hasColumn('classroom_teacher', 'school_id'), fn ($query) => $query->where('classroom_teacher.school_id', $schoolId))
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->select('classrooms.*')
                ->orderBy('classrooms.name')
                ->get();
        } elseif (Schema::hasTable('classroom_user')) {
            $classrooms = DB::table('classrooms')
                ->join('classroom_user', 'classrooms.id', '=', 'classroom_user.classroom_id')
                ->where('classroom_user.user_id', $teacherId)
                ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                ->select('classrooms.*')
                ->orderBy('classrooms.name')
                ->get();
        }

        $parents = collect();
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'school_id')) {
            $parents = DB::table('users')
                ->where('school_id', $schoolId)
                ->where('role', 'parent')
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();
        }

        return view('teacher.messages.create', compact('classrooms', 'parents'));
    }

    public function store(StoreMessageRequest $request)
    {
        $schoolId = $this->schoolId();
        $teacher = Auth::user();
        $data = $request->validated();
        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);
        $replyTo = null;

        try {
            if (!empty($data['reply_to_id'])) {
                $replyTo = Message::query()->forSchool($schoolId)->findOrFail((int) $data['reply_to_id']);
                abort_unless($this->userCanSeeMessage($replyTo, $teacher, $schoolId), 403);

                $recipient = $this->directReplyRecipient($replyTo, $teacher, $schoolId);
                if (!$recipient) {
                    return back()->withInput()->withErrors(['body' => 'Cette conversation ne peut pas recevoir de reponse directe.']);
                }

                $requiresApproval = $recipient->role === User::ROLE_PARENT;
                $status = $requiresApproval ? 'pending' : 'approved';
                $message = $this->createMessageRecord($this->buildMessagePayload(
                    $columns,
                    $teacher,
                    $schoolId,
                    (string) $data['body'],
                    (string) ($data['subject'] ?: $replyTo->subjectText()),
                    [
                        'type' => $targetColumns['type'] ?? 'recipient_type',
                        'id' => $targetColumns['id'] ?? 'recipient_id',
                        'type_value' => 'user',
                        'id_value' => (int) $recipient->id,
                    ],
                    $requiresApproval,
                    $status,
                    $replyTo,
                    $status === 'approved' ? (int) $teacher->id : null
                ));

                if ($status === 'approved') {
                    $this->notifyMessageUsers(
                        [$recipient->id],
                        $message->subjectText(),
                        $message->bodyText(),
                        [
                            'message_id' => (int) $message->id,
                            'url' => $this->messageShowRouteForRole((string) $recipient->role, (int) $message->id),
                        ]
                    );
                }

                return redirect()
                    ->route('teacher.messages.show', $message)
                    ->with('success', $requiresApproval ? 'Reponse envoyee et en attente de validation.' : 'Reponse envoyee.');
            }

            $classroomId = (int) ($data['classroom_id'] ?? 0);
            $parentIds = collect($data['parent_ids'] ?? [])
                ->filter()
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all();

            if (!$classroomId && $parentIds === []) {
                return back()->withErrors([
                    'classroom_id' => 'Choisissez une classe ou des parents.',
                ])->withInput();
            }

            if ($classroomId > 0) {
                $allowedClassroom = DB::table('classrooms')
                    ->join('classroom_teacher', 'classrooms.id', '=', 'classroom_teacher.classroom_id')
                    ->where('classrooms.id', $classroomId)
                    ->where('classroom_teacher.teacher_id', (int) $teacher->id)
                    ->when(Schema::hasColumn('classroom_teacher', 'school_id'), fn ($query) => $query->where('classroom_teacher.school_id', $schoolId))
                    ->when(Schema::hasColumn('classrooms', 'school_id'), fn ($query) => $query->where('classrooms.school_id', $schoolId))
                    ->exists();

                if (!$allowedClassroom) {
                    return back()->withErrors([
                        'classroom_id' => 'La classe selectionnee est invalide pour cet enseignant.',
                    ])->withInput();
                }
            }

            if ($parentIds !== []) {
                $validParents = DB::table('users')
                    ->whereIn('id', $parentIds)
                    ->where('role', 'parent')
                    ->where('school_id', $schoolId)
                    ->count();

                if ($validParents !== count($parentIds)) {
                    return back()->withErrors([
                        'parent_ids' => 'Un ou plusieurs parents sont invalides pour cette ecole.',
                    ])->withInput();
                }
            }

            $records = [];
            if ($classroomId > 0) {
                $records[] = [
                    'type_value' => 'classroom',
                    'id_value' => $classroomId,
                    'user_ids' => null,
                ];
            } else {
                foreach ($parentIds as $parentId) {
                    $records[] = [
                        'type_value' => 'user',
                        'id_value' => $parentId,
                        'user_ids' => null,
                    ];
                }
            }

            foreach ($records as $record) {
                $this->createMessageRecord($this->buildMessagePayload(
                    $columns,
                    $teacher,
                    $schoolId,
                    (string) $data['body'],
                    (string) ($data['subject'] ?? null),
                    [
                        'type' => $targetColumns['type'] ?? 'recipient_type',
                        'id' => $targetColumns['id'] ?? 'recipient_id',
                        'type_value' => $record['type_value'],
                        'id_value' => $record['id_value'],
                        'user_ids' => $record['user_ids'],
                    ],
                    true,
                    'pending'
                ));
            }

            return redirect()->route('teacher.messages.index')->with('success', 'Message envoye (en attente).');
        } catch (\Throwable $e) {
            Log::error('Teacher message send failed', [
                'school_id' => $schoolId,
                'teacher_id' => $teacher->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['body' => "Echec de l'envoi du message."]);
        }
    }

    public function show(Message $message)
    {
        $schoolId = $this->schoolId();
        $teacher = Auth::user();

        abort_unless($this->userCanSeeMessage($message, $teacher, $schoolId), 403);

        $threadMessages = $this->loadVisibleThread($message, $teacher, $schoolId);
        abort_unless($threadMessages->contains(fn (Message $item) => (int) $item->id === (int) $message->id), 403);
        $this->markThreadAsRead($message, $teacher, $schoolId);

        $replyRecipient = $this->directReplyRecipient($message, $teacher, $schoolId);

        return view('teacher.messages.show', [
            'message' => $message,
            'threadMessages' => $threadMessages,
            'replyRecipient' => $replyRecipient,
        ]);
    }
}
