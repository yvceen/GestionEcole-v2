<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalAuthorizationRecipient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDigitalAuthorizationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $items = DigitalAuthorizationRecipient::query()
            ->where('school_id', $parent->school_id)
            ->where('parent_user_id', $parent->id)
            ->with(['authorization', 'student.classroom:id,name'])
            ->latest()
            ->limit(100)
            ->get();

        return response()->json(['items' => $items->map(fn ($recipient) => $this->payload($recipient))->values()]);
    }

    public function respond(Request $request, DigitalAuthorizationRecipient $recipient): JsonResponse
    {
        $parent = $this->parent($request);
        abort_unless((int) $recipient->parent_user_id === (int) $parent->id && (int) $recipient->school_id === (int) $parent->school_id, 404);
        $recipient->load(['authorization', 'student']);
        abort_if($recipient->authorization->status === 'closed', 422, 'Cette demande est clôturée.');
        abort_if($recipient->authorization->due_at?->isPast(), 422, 'La date limite de réponse est dépassée.');
        $data = $request->validate([
            'decision' => ['required', 'in:approved,declined'],
            'signed_name' => ['required', 'string', 'max:255'],
            'response_comment' => [$recipient->authorization->requires_comment ? 'required' : 'nullable', 'string', 'max:3000'],
            'confirmation' => ['accepted'],
        ]);
        $recipient->update([
            'status' => $data['decision'],
            'signed_name' => trim($data['signed_name']),
            'response_comment' => trim((string) ($data['response_comment'] ?? '')) ?: null,
            'responded_at' => now(),
            'response_ip' => $request->ip(),
            'response_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ]);

        foreach ([User::ROLE_ADMIN => 'admin', User::ROLE_SCHOOL_LIFE => 'school-life'] as $role => $prefix) {
            $managerIds = User::query()->where('school_id', $recipient->school_id)->where('role', $role)->pluck('id')->all();
            $this->notifications->notifyUsers($managerIds, 'digital_authorization_response', 'Réponse reçue', $recipient->student?->full_name . ' : ' . $recipient->authorization->title, [
                'digital_authorization_response_id' => $recipient->digital_authorization_id,
                'school_id' => $recipient->school_id,
                'route' => route($prefix . '.digital-authorizations.show', $recipient->authorization, absolute: false),
            ]);
        }

        return response()->json(['message' => 'Réponse enregistrée.', 'item' => $this->payload($recipient->fresh(['authorization', 'student.classroom:id,name']))]);
    }

    private function parent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (string) $user->role === User::ROLE_PARENT, 403);
        return $user;
    }

    private function payload(DigitalAuthorizationRecipient $recipient): array
    {
        $authorization = $recipient->authorization;
        return [
            'recipient_id' => (int) $recipient->id,
            'status' => (string) $recipient->status,
            'response_comment' => (string) ($recipient->response_comment ?? ''),
            'signed_name' => (string) ($recipient->signed_name ?? ''),
            'responded_at' => $recipient->responded_at?->toIso8601String(),
            'student' => [
                'id' => (int) $recipient->student_id,
                'name' => (string) ($recipient->student?->full_name ?? ''),
                'classroom' => (string) ($recipient->student?->classroom?->name ?? ''),
            ],
            'authorization' => [
                'id' => (int) $authorization->id,
                'title' => (string) $authorization->title,
                'category' => (string) $authorization->category,
                'description' => (string) $authorization->description,
                'instructions' => (string) ($authorization->instructions ?? ''),
                'event_at' => $authorization->event_at?->toIso8601String(),
                'due_at' => $authorization->due_at?->toIso8601String(),
                'status' => (string) $authorization->status,
                'requires_comment' => (bool) $authorization->requires_comment,
            ],
        ];
    }
}
