<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) abort(403, 'School context missing.');
        return $schoolId;
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $directorId = auth()->id();

        $folder = $request->get('folder', 'inbox'); // inbox | sent
        $qText = trim((string) $request->get('q', ''));

        $counts = [
            'inbox' => Message::query()->forSchool($schoolId)->approved()->addressedToUser($directorId)->count(),
            'sent' => Message::query()->forSchool($schoolId)->where('sender_id', $directorId)->count(),
        ];

        $query = Message::query()
            ->forSchool($schoolId)
            ->with(['sender']);

        if ($folder === 'sent') {
            $query->where('sender_id', $directorId);
        } else {
            $query->approved()->addressedToUser($directorId);
        }

        if ($qText !== '') {
            $query->where(function ($x) use ($qText) {
                $x->where('subject', 'like', "%{$qText}%")
                  ->orWhere('body', 'like', "%{$qText}%");
            });
        }

        $messages = $query->latest('created_at')->paginate(15);

        return view('director.messages.index', [
            'messages' => $messages,
            'folder' => $folder,
            'q' => $qText,
            'counts' => $counts,
        ]);
    }

    public function show(Message $message)
    {
        $schoolId = $this->schoolId();
        $directorId = auth()->id();

        abort_unless((int)$message->school_id === $schoolId, 404);

        // Director can view: messages addressed to them (direct or in JSON) OR sent by them
        $isMine = ((int)$message->sender_id === (int)$directorId);
        $isAddressedToMe = $message->isForUser($directorId);

        abort_unless($isMine || $isAddressedToMe, 403);

        return view('director.messages.show', compact('message'));
    }
}
