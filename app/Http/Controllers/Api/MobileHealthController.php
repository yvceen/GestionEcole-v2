<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHealthReport;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHealthController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $children = Student::query()->active()->where('school_id', $parent->school_id)->where('parent_user_id', $parent->id)
            ->with(['classroom:id,name', 'healthProfile', 'healthReports' => fn ($query) => $query->latest('starts_at')->limit(10)])
            ->orderBy('full_name')->get();

        return response()->json(['children' => $children->map(fn (Student $student) => $this->payload($student))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $parent = $this->parent($request);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'condition_name' => ['required', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'severity' => ['required', 'in:low,medium,high,urgent'],
            'expected_return_at' => ['nullable', 'date'],
            'visible_to_driver' => ['nullable', 'boolean'],
        ]);
        $student = Student::query()->active()->where('school_id', $parent->school_id)->where('parent_user_id', $parent->id)->findOrFail($data['student_id']);
        $report = StudentHealthReport::create([
            ...$data, 'school_id' => $student->school_id, 'student_id' => $student->id,
            'reported_by_user_id' => $parent->id, 'source' => 'parent', 'type' => 'illness',
            'starts_at' => now(), 'status' => StudentHealthReport::STATUS_ACTIVE, 'visible_to_teacher' => true,
            'visible_to_driver' => (bool) ($data['visible_to_driver'] ?? false),
        ]);

        $groups = [
            'admin.health.show' => User::query()->where('school_id', $student->school_id)->where('role', User::ROLE_ADMIN)->pluck('id'),
            'school-life.health.show' => User::query()->where('school_id', $student->school_id)->where('role', User::ROLE_SCHOOL_LIFE)->pluck('id'),
        ];
        if ($student->classroom_id) {
            $groups['teacher.health.show'] = User::query()->where('school_id', $student->school_id)->where('role', User::ROLE_TEACHER)
                ->whereHas('teacherClassrooms', fn ($query) => $query->whereKey($student->classroom_id))->pluck('id');
        }
        if ($report->visible_to_driver) {
            $groups['chauffeur.health.show'] = TransportAssignment::query()->where('school_id', $student->school_id)->where('student_id', $student->id)
                ->where('is_active', true)->with('vehicle:id,driver_id')->get()->pluck('vehicle.driver_id');
        }
        foreach ($groups as $routeName => $ids) {
            $this->notifications->notifyUsers(
                $ids->map(fn ($id) => (int) $id)->filter()->unique()->values()->all(),
                'health_alert',
                'Nouvelle alerte santé',
                $student->full_name . ' : ' . $report->condition_name,
                [
                    'student_id' => $student->id,
                    'health_report_id' => $report->id,
                    'school_id' => $student->school_id,
                    'route' => route($routeName, $student, absolute: false),
                ],
            );
        }

        return response()->json(['message' => 'Health report created successfully.'], 201);
    }

    private function parent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (string) $user->role === User::ROLE_PARENT, 403);
        return $user;
    }

    private function payload(Student $student): array
    {
        return [
            'id' => (int) $student->id, 'name' => $student->full_name, 'classroom' => $student->classroom?->name ?? '',
            'profile' => [
                'blood_type' => $student->healthProfile?->blood_type ?? '',
                'allergies' => $student->healthProfile?->allergies ?? '',
                'chronic_conditions' => $student->healthProfile?->chronic_conditions ?? '',
                'medications' => $student->healthProfile?->medications ?? '',
                'emergency_instructions' => $student->healthProfile?->emergency_instructions ?? '',
            ],
            'reports' => $student->healthReports->map(fn ($report) => [
                'id' => (int) $report->id, 'condition_name' => $report->condition_name, 'symptoms' => $report->symptoms ?? '',
                'severity' => $report->severity, 'status' => $report->status,
                'starts_at' => $report->starts_at?->toIso8601String(), 'expected_return_at' => $report->expected_return_at?->toDateString(),
            ])->values(),
        ];
    }
}
