<?php

namespace App\Http\Controllers\Parent;

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
            $parent = Auth::user();
            $parentId = (int) $parent->id;
            $classroomIds = $this->classroomIdsForParent($parent, $schoolId);
            $columns = $this->messageColumns();
            $target = $this->targetColumns($columns);
            $selectedThreadId = (int) $request->integer('mid');

            if ($selectedThreadId > 0) {
                $selectedMessage = Message::query()
                    ->forSchool($schoolId)
                    ->whereThreadKey($selectedThreadId)
                    ->oldest('created_at')
                    ->first();

                if ($selectedMessage && $this->userCanSeeMessage($selectedMessage, $parent, $schoolId, $classroomIds)) {
                    $this->markThreadAsRead($selectedMessage, $parent, $schoolId);
                }
            }

            $threadMessages = Message::query()
                ->forSchool($schoolId)
                ->with(['sender'])
                ->where(function ($outer) use ($parentId, $classroomIds, $columns, $target) {
                    $outer->where('sender_id', $parentId);

                    $outer->orWhere(function ($direct) use ($parentId, $columns) {
                        if (in_array('status', $columns, true)) {
                            $direct->where('status', 'approved');
                        }

                        $direct->addressedToUser($parentId);
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
                ->get();

            $messages = $this->paginateCollection(
                $this->buildThreadSummaries($threadMessages, $parent, $schoolId),
                15
            )->appends($request->query());

            return view('parent.messages.index', compact('messages'));
        } catch (\Throwable $e) {
            Log::error($e);

            return back()->withErrors('Unable to load messages.');
        }
    }

    public function create()
    {
        $schoolId = $this->schoolId();

        $recipients = collect();
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'school_id')) {
            $recipients = DB::table('users')
                ->where('school_id', $schoolId)
                ->whereIn('role', ['admin', 'teacher', 'director'])
                ->select('id', 'name', 'email', 'role')
                ->orderBy('role')
                ->orderBy('name')
                ->get();
        }

        return view('parent.messages.create', compact('recipients'));
    }

    public function store(StoreMessageRequest $request)
    {
        $schoolId = $this->schoolId();
        $parent = Auth::user();
        $data = $request->validated();
        $columns = $this->messageColumns();
        $targetColumns = $this->targetColumns($columns);

        try {
            $recipient = null;
            $replyTo = null;

            if (!empty($data['reply_to_id'])) {
                $replyTo = Message::query()->forSchool($schoolId)->findOrFail((int) $data['reply_to_id']);
                abort_unless($this->userCanSeeMessage($replyTo, $parent, $schoolId, $this->classroomIdsForParent($parent, $schoolId)), 403);

                $recipient = $this->directReplyRecipient($replyTo, $parent, $schoolId);
            } else {
                $recipientId = (int) ($data['recipient_id'] ?? 0);
                $recipient = User::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($recipientId)
                    ->whereIn('role', ['admin', 'teacher', 'director'])
                    ->first();
            }

            if (!$recipient) {
                return back()->withErrors([
                    'recipient_id' => 'Le destinataire selectionne est invalide pour cette ecole.',
                ])->withInput();
            }

            $message = $this->createMessageRecord($this->buildMessagePayload(
                $columns,
                $parent,
                $schoolId,
                (string) $data['body'],
                (string) ($data['subject'] ?: $replyTo?->subjectText()),
                [
                    'type' => $targetColumns['type'] ?? 'recipient_type',
                    'id' => $targetColumns['id'] ?? 'recipient_id',
                    'type_value' => 'user',
                    'id_value' => (int) $recipient->id,
                ],
                false,
                'approved',
                $replyTo,
                (int) $parent->id
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

            return redirect()->route('parent.messages.show', $message)->with('success', 'Message envoye.');
        } catch (\Throwable $e) {
            Log::error('Parent message send failed', [
                'school_id' => $schoolId,
                'parent_id' => $parent->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['body' => "Echec de l'envoi du message."]);
        }
    }

    public function show(Message $message)
    {
        $schoolId = $this->schoolId();
        $parent = Auth::user();
        $classroomIds = $this->classroomIdsForParent($parent, $schoolId);

        abort_unless($this->userCanSeeMessage($message, $parent, $schoolId, $classroomIds), 403);

        $threadMessages = $this->loadVisibleThread($message, $parent, $schoolId, $classroomIds);
        abort_unless($threadMessages->contains(fn (Message $item) => (int) $item->id === (int) $message->id), 403);
        $this->markThreadAsRead($message, $parent, $schoolId);

        $replyRecipient = $this->directReplyRecipient($message, $parent, $schoolId);

        return view('parent.messages.show', [
            'message' => $message,
            'threadMessages' => $threadMessages,
            'replyRecipient' => $replyRecipient,
        ]);
    }
}
