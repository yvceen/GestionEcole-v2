<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PickupRequestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $status = trim((string) $request->get('status', ''));
        if (!in_array($status, PickupRequest::statuses(), true)) {
            $status = '';
        }

        $requests = PickupRequest::query()
            ->where('school_id', $schoolId)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->with(['student.classroom.level', 'parentUser:id,name,phone,email', 'reviewedBy:id,name'])
            ->orderBy('requested_pickup_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => PickupRequest::where('school_id', $schoolId)->where('status', PickupRequest::STATUS_PENDING)->count(),
            'approved' => PickupRequest::where('school_id', $schoolId)->where('status', PickupRequest::STATUS_APPROVED)->count(),
            'completed' => PickupRequest::where('school_id', $schoolId)->where('status', PickupRequest::STATUS_COMPLETED)->count(),
            'rejected' => PickupRequest::where('school_id', $schoolId)->where('status', PickupRequest::STATUS_REJECTED)->count(),
        ];

        return view('school-life.pickup-requests.index', compact('requests', 'status', 'stats'));
    }

    public function approve(Request $request, PickupRequest $pickupRequest)
    {
        return $this->transition($request, $pickupRequest, PickupRequest::STATUS_APPROVED, 'Demande approuvee.');
    }

    public function reject(Request $request, PickupRequest $pickupRequest)
    {
        return $this->transition($request, $pickupRequest, PickupRequest::STATUS_REJECTED, 'Demande rejetee.');
    }

    public function complete(Request $request, PickupRequest $pickupRequest)
    {
        return $this->transition($request, $pickupRequest, PickupRequest::STATUS_COMPLETED, 'Demande marquee comme traitee.');
    }

    private function transition(Request $request, PickupRequest $pickupRequest, string $status, string $message)
    {
        abort_unless((int) $pickupRequest->school_id === $this->schoolId(), 404);

        $data = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $pickupRequest->update([
            'status' => $status,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'completed_at' => $status === PickupRequest::STATUS_COMPLETED ? now() : $pickupRequest->completed_at,
            'decision_note' => trim((string) ($data['decision_note'] ?? '')) ?: $pickupRequest->decision_note,
        ]);

        $statusLabel = match ($status) {
            PickupRequest::STATUS_APPROVED => 'approuvee',
            PickupRequest::STATUS_REJECTED => 'rejetee',
            PickupRequest::STATUS_COMPLETED => 'traitee',
            default => 'mise a jour',
        };

        $this->notifications->notifyUsers(
            [(int) $pickupRequest->parent_user_id],
            'pickup_request',
            'Demande de recuperation ' . $statusLabel,
            'La demande de recuperation de ' . ($pickupRequest->student?->full_name ?? 'votre enfant') . ' a ete ' . $statusLabel . '.',
            [
                'pickup_request_id' => $pickupRequest->id,
                'status' => $status,
                'school_id' => (int) $pickupRequest->school_id,
                'route' => route('parent.pickup-requests.index', absolute: false),
            ]
        );

        return back()->with('success', $message);
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
