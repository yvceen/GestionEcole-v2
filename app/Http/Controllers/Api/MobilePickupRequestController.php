<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePickupRequestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedParent($request);
        $schoolId = $this->schoolId($user);
        $children = $this->children($user, $schoolId);

        $requests = PickupRequest::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->whereIn('student_id', $children->pluck('id'))
            ->with(['student.classroom:id,name', 'reviewedBy:id,name'])
            ->orderByDesc('requested_pickup_at')
            ->limit(50)
            ->get();

        return response()->json([
            'items' => $requests->map(fn (PickupRequest $pickupRequest) => $this->payload($pickupRequest))->values(),
            'children' => $children->map(fn (Student $student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedParent($request);
        $schoolId = $this->schoolId($user);
        $children = $this->children($user, $schoolId);
        $childIds = $children->pluck('id')->map(fn ($id) => (int) $id)->all();

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'in:' . implode(',', $childIds ?: [0])],
            'requested_pickup_at' => ['required', 'date', 'after_or_equal:now'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $child = $children->firstWhere('id', (int) $data['student_id']);
        abort_unless($child, 404);

        $pickupRequest = PickupRequest::create([
            'school_id' => $schoolId,
            'student_id' => (int) $child->id,
            'parent_user_id' => (int) $user->id,
            'requested_pickup_at' => $data['requested_pickup_at'],
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'status' => PickupRequest::STATUS_PENDING,
        ]);

        $recipients = User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_SCHOOL_LIFE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $this->notifications->notifyUsers(
            $recipients,
            'pickup_request',
            'Nouvelle demande de recuperation',
            $user->name . ' demande la recuperation de ' . $child->full_name . '.',
            [
                'pickup_request_id' => (int) $pickupRequest->id,
                'student_id' => (int) $child->id,
                'school_id' => $schoolId,
                'route' => route('school-life.pickup-requests.index', absolute: false),
            ]
        );

        $pickupRequest->load(['student.classroom:id,name', 'reviewedBy:id,name']);

        return response()->json([
            'message' => 'Pickup request created successfully.',
            'item' => $this->payload($pickupRequest),
        ], 201);
    }

    private function authenticatedParent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_PARENT, 403);

        return $user;
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function children(User $user, int $schoolId)
    {
        return Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->with('classroom:id,name')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'classroom_id']);
    }

    private function payload(PickupRequest $pickupRequest): array
    {
        return [
            'id' => (int) $pickupRequest->id,
            'status' => (string) $pickupRequest->status,
            'status_label' => ucfirst((string) $pickupRequest->status),
            'requested_pickup_at' => optional($pickupRequest->requested_pickup_at)?->toIso8601String(),
            'requested_pickup_label' => $pickupRequest->requested_pickup_at?->format('d/m/Y H:i') ?? '',
            'reason' => (string) ($pickupRequest->reason ?? ''),
            'decision_note' => (string) ($pickupRequest->decision_note ?? ''),
            'reviewed_at' => optional($pickupRequest->reviewed_at)?->toIso8601String(),
            'reviewed_by' => (string) ($pickupRequest->reviewedBy?->name ?? ''),
            'student' => [
                'id' => (int) ($pickupRequest->student?->id ?? 0),
                'name' => (string) ($pickupRequest->student?->full_name ?? ''),
                'classroom' => (string) ($pickupRequest->student?->classroom?->name ?? ''),
            ],
            'created_at' => optional($pickupRequest->created_at)?->toIso8601String(),
        ];
    }
}
