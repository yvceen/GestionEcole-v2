<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PickupRequest;
use App\Models\Student;
use App\Models\StudentBehavior;
use App\Models\User;
use App\Services\AttendanceReportingService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSchoolLifeController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
        private readonly NotificationService $notifications,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_SCHOOL_LIFE, 403);

        $schoolId = $this->schoolId($user);
        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, now()->startOfDay());

        $recentScans = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNotNull('check_in_at')->orWhereNotNull('check_out_at');
            })
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'scannedBy:id,name'])
            ->orderByDesc('check_in_at')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $pickupRequests = PickupRequest::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [
                PickupRequest::STATUS_PENDING,
                PickupRequest::STATUS_APPROVED,
                PickupRequest::STATUS_COMPLETED,
            ])
            ->with(['student.classroom:id,name', 'parentUser:id,name,phone', 'reviewedBy:id,name'])
            ->orderBy('requested_pickup_at')
            ->limit(12)
            ->get();

        $behaviors = StudentBehavior::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'author:id,name'])
            ->latest('date')
            ->limit(8)
            ->get();

        $followUpStudents = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->with(['classroom:id,name', 'parentUser:id,name,phone'])
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('status', Attendance::STATUS_ABSENT),
                'attendances as late_count' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('status', Attendance::STATUS_LATE),
                'behaviors',
                'grades',
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->orderBy('full_name')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'present_today' => (int) ($attendanceSummary['today_present'] ?? 0),
                'absent_today' => (int) ($attendanceSummary['today_absent'] ?? 0),
                'late_today' => (int) ($attendanceSummary['today_late'] ?? 0),
                'pending_pickups' => (int) PickupRequest::query()
                    ->where('school_id', $schoolId)
                    ->where('status', PickupRequest::STATUS_PENDING)
                    ->count(),
            ],
            'recent_scans' => $recentScans->map(fn (Attendance $attendance) => [
                'id' => (int) $attendance->id,
                'student_name' => (string) ($attendance->student?->full_name ?? 'Student'),
                'classroom_name' => (string) ($attendance->student?->classroom?->name ?? 'Unassigned classroom'),
                'status' => (string) $attendance->status,
                'status_label' => $this->attendanceStatusLabel((string) $attendance->status),
                'check_in_at' => optional($attendance->check_in_at)->format('H:i'),
                'check_out_at' => optional($attendance->check_out_at)->format('H:i'),
                'date' => optional($attendance->date)->format('d/m/Y'),
                'scanned_by' => (string) ($attendance->scannedBy?->name ?? ''),
            ])->values()->all(),
            'pickup_requests' => $pickupRequests->map(fn (PickupRequest $pickup) => [
                'id' => (int) $pickup->id,
                'student_name' => (string) ($pickup->student?->full_name ?? 'Student'),
                'classroom_name' => (string) ($pickup->student?->classroom?->name ?? 'Unassigned classroom'),
                'parent_name' => (string) ($pickup->parentUser?->name ?? 'Parent'),
                'parent_phone' => (string) ($pickup->parentUser?->phone ?? ''),
                'requested_pickup_at' => optional($pickup->requested_pickup_at)->toIso8601String(),
                'requested_pickup_label' => optional($pickup->requested_pickup_at)->format('d/m/Y H:i') ?? '',
                'status' => (string) $pickup->status,
                'status_label' => $this->pickupStatusLabel((string) $pickup->status),
                'decision_note' => (string) ($pickup->decision_note ?? ''),
                'reviewed_by' => (string) ($pickup->reviewedBy?->name ?? ''),
            ])->values()->all(),
            'behaviors' => $behaviors->map(fn (StudentBehavior $behavior) => [
                'id' => (int) $behavior->id,
                'student_name' => (string) ($behavior->student?->full_name ?? 'Student'),
                'classroom_name' => (string) ($behavior->student?->classroom?->name ?? 'Unassigned classroom'),
                'type' => (string) $behavior->type,
                'type_label' => $this->behaviorTypeLabel((string) $behavior->type),
                'description' => (string) $behavior->description,
                'date' => optional($behavior->date)->format('d/m/Y') ?? '',
                'author' => (string) ($behavior->author?->name ?? ''),
            ])->values()->all(),
            'students_follow_up' => $followUpStudents->map(fn (Student $student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom_name' => (string) ($student->classroom?->name ?? 'Unassigned classroom'),
                'parent_name' => (string) ($student->parentUser?->name ?? ''),
                'parent_phone' => (string) ($student->parentUser?->phone ?? ''),
                'absences_count' => (int) ($student->absences_count ?? 0),
                'late_count' => (int) ($student->late_count ?? 0),
                'behaviors_count' => (int) ($student->behaviors_count ?? 0),
                'grades_count' => (int) ($student->grades_count ?? 0),
            ])->values()->all(),
        ]);
    }

    public function transitionPickup(Request $request, PickupRequest $pickupRequest): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless((string) $user->role === User::ROLE_SCHOOL_LIFE, 403);

        $schoolId = $this->schoolId($user);
        abort_unless((int) $pickupRequest->school_id === $schoolId, 404);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,complete'],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = match ((string) $data['action']) {
            'approve' => PickupRequest::STATUS_APPROVED,
            'reject' => PickupRequest::STATUS_REJECTED,
            'complete' => PickupRequest::STATUS_COMPLETED,
        };

        $pickupRequest->update([
            'status' => $status,
            'reviewed_by_user_id' => (int) $user->id,
            'reviewed_at' => now(),
            'completed_at' => $status === PickupRequest::STATUS_COMPLETED ? now() : $pickupRequest->completed_at,
            'decision_note' => trim((string) ($data['decision_note'] ?? '')) ?: $pickupRequest->decision_note,
        ]);

        $statusLabel = match ($status) {
            PickupRequest::STATUS_APPROVED => 'approved',
            PickupRequest::STATUS_REJECTED => 'rejected',
            PickupRequest::STATUS_COMPLETED => 'completed',
            default => 'updated',
        };

        $this->notifications->notifyUsers(
            [(int) $pickupRequest->parent_user_id],
            'pickup_request',
            'Pickup request ' . $statusLabel,
            'The pickup request for ' . ($pickupRequest->student?->full_name ?? 'your child') . ' was ' . $statusLabel . '.',
            [
                'pickup_request_id' => $pickupRequest->id,
                'status' => $status,
                'school_id' => (int) $pickupRequest->school_id,
                'route' => route('parent.pickup-requests.index', absolute: false),
            ]
        );

        return response()->json([
            'message' => match ($status) {
                PickupRequest::STATUS_APPROVED => 'Pickup request approved.',
                PickupRequest::STATUS_REJECTED => 'Pickup request rejected.',
                PickupRequest::STATUS_COMPLETED => 'Pickup request completed.',
                default => 'Pickup request updated.',
            },
            'pickup_request' => [
                'id' => (int) $pickupRequest->id,
                'status' => (string) $pickupRequest->status,
                'status_label' => $this->pickupStatusLabel((string) $pickupRequest->status),
                'decision_note' => (string) ($pickupRequest->decision_note ?? ''),
            ],
        ]);
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);

        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }

    private function attendanceStatusLabel(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => 'Present',
            Attendance::STATUS_ABSENT => 'Absent',
            Attendance::STATUS_LATE => 'Late',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function pickupStatusLabel(string $status): string
    {
        return match ($status) {
            PickupRequest::STATUS_PENDING => 'Pending',
            PickupRequest::STATUS_APPROVED => 'Approved',
            PickupRequest::STATUS_REJECTED => 'Rejected',
            PickupRequest::STATUS_COMPLETED => 'Completed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function behaviorTypeLabel(string $type): string
    {
        return match ($type) {
            StudentBehavior::TYPE_RETARD => 'Late arrival',
            StudentBehavior::TYPE_COMPORTEMENT => 'Behavior',
            StudentBehavior::TYPE_SANCTION => 'Sanction',
            StudentBehavior::TYPE_REMARQUE => 'Remark',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
