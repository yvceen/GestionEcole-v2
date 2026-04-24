<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileTransportController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $schoolId = $this->schoolId($user);

        return response()->json([
            'items' => match ((string) $user->role) {
                User::ROLE_PARENT => $this->parentAssignments($user, $schoolId),
                User::ROLE_STUDENT => $this->studentAssignments($user, $schoolId),
                default => [],
            },
        ]);
    }

    private function parentAssignments(User $user, int $schoolId): array
    {
        $children = Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->with([
                'classroom:id,name',
                'transportAssignment.route.stops',
                'transportAssignment.vehicle.driver:id,name,phone',
            ])
            ->orderBy('full_name')
            ->get();

        return $children->map(function (Student $student): array {
            return $this->assignmentPayload($student, $student->transportAssignment);
        })->values()->all();
    }

    private function studentAssignments(User $user, int $schoolId): array
    {
        $student = Student::query()
            ->active()
            ->where('school_id', $schoolId)
            ->where('user_id', (int) $user->id)
            ->with([
                'classroom:id,name',
                'transportAssignment.route.stops',
                'transportAssignment.vehicle.driver:id,name,phone',
            ])
            ->first();

        if (!$student) {
            return [];
        }

        return [$this->assignmentPayload($student, $student->transportAssignment)];
    }

    private function assignmentPayload(Student $student, ?TransportAssignment $assignment): array
    {
        $route = $assignment?->route;
        $vehicle = $assignment?->vehicle;
        $orderedStops = $route?->stops?->sortBy('stop_order')->values();
        $firstStop = $orderedStops?->first();
        $lastStop = $orderedStops?->last();

        return [
            'student' => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
            ],
            'assignment' => [
                'id' => (int) ($assignment?->id ?? 0),
                'is_active' => (bool) ($assignment?->is_active ?? false),
                'period' => (string) ($assignment?->period ?? ''),
                'pickup_point' => (string) ($assignment?->pickup_point ?? ''),
                'assigned_date' => $assignment?->assigned_date?->toDateString(),
            ],
            'route' => [
                'id' => (int) ($route?->id ?? 0),
                'name' => (string) ($route?->route_name ?? ''),
                'start_point' => (string) ($route?->start_point ?? ''),
                'end_point' => (string) ($route?->end_point ?? ''),
                'estimated_minutes' => (int) ($route?->estimated_minutes ?? 0),
                'is_active' => (bool) ($route?->is_active ?? false),
                'pickup_time' => $firstStop?->scheduled_time ? substr((string) $firstStop->scheduled_time, 0, 5) : '',
                'drop_time' => $lastStop?->scheduled_time ? substr((string) $lastStop->scheduled_time, 0, 5) : '',
                'stops' => $orderedStops?->map(fn ($stop) => [
                    'id' => (int) $stop->id,
                    'name' => (string) $stop->name,
                    'scheduled_time' => $stop->scheduled_time ? substr((string) $stop->scheduled_time, 0, 5) : '',
                    'order' => (int) ($stop->stop_order ?? 0),
                ])->all() ?? [],
            ],
            'vehicle' => [
                'id' => (int) ($vehicle?->id ?? 0),
                'name' => (string) ($vehicle?->name ?? ''),
                'registration_number' => (string) ($vehicle?->registration_number ?? ''),
                'driver_name' => (string) ($vehicle?->driver?->name ?? ''),
                'driver_phone' => (string) ($vehicle?->driver?->phone ?? ''),
            ],
        ];
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
