<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\PickupRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PickupRequestController extends Controller
{
    use InteractsWithParentPortal;

    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index()
    {
        $children = $this->ownedChildren(['classroom.level']);
        $requests = PickupRequest::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->where('parent_user_id', $this->currentParent()->id)
            ->whereIn('student_id', $children->pluck('id'))
            ->with(['student.classroom', 'reviewedBy:id,name'])
            ->orderByDesc('requested_pickup_at')
            ->paginate(12);

        return view('parent.pickup-requests.index', compact('children', 'requests'));
    }

    public function create()
    {
        $children = $this->ownedChildrenQuery(['classroom.level'])->active()->orderBy('full_name')->get();

        return view('parent.pickup-requests.create', compact('children'));
    }

    public function store(Request $request)
    {
        $children = $this->ownedChildrenQuery(['classroom.level'])->active()->orderBy('full_name')->get();
        $childIds = $children->pluck('id')->all();

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'in:' . implode(',', $childIds ?: [0])],
            'requested_pickup_at' => ['required', 'date', 'after_or_equal:now'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $child = $children->firstWhere('id', (int) $data['student_id']);
        abort_unless($child, 404);

        $pickupRequest = PickupRequest::create([
            'school_id' => $this->schoolIdOrFail(),
            'student_id' => $child->id,
            'parent_user_id' => $this->currentParent()->id,
            'requested_pickup_at' => $data['requested_pickup_at'],
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'status' => PickupRequest::STATUS_PENDING,
        ]);

        $recipients = User::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->where('role', User::ROLE_SCHOOL_LIFE)
            ->pluck('id')
            ->all();

        $this->notifications->notifyUsers(
            $recipients,
            'pickup_request',
            'Nouvelle demande de recuperation',
            $this->currentParent()->name . ' demande la recuperation de ' . $child->full_name . '.',
            [
                'pickup_request_id' => $pickupRequest->id,
                'student_id' => $child->id,
                'school_id' => $this->schoolIdOrFail(),
                'route' => route('school-life.pickup-requests.index', absolute: false),
            ]
        );

        return redirect()->route('parent.pickup-requests.index')
            ->with('success', 'Demande de recuperation envoyee.');
    }
}
