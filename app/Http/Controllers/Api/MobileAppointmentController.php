<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user && (string) $user->role === User::ROLE_PARENT, 403);

        $schoolId = $this->schoolId($user);
        $children = $this->children($user, $schoolId);

        $items = Appointment::query()
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name'])
            ->where('school_id', $schoolId)
            ->where('parent_user_id', (int) $user->id)
            ->latest('scheduled_at')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentPayload($appointment))
            ->values();

        return response()->json([
            'items' => $items,
            'children' => $children->map(fn (Student $student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->full_name,
                'classroom' => (string) ($student->classroom?->name ?? ''),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user && (string) $user->role === User::ROLE_PARENT, 403);

        $schoolId = $this->schoolId($user);
        $children = $this->children($user, $schoolId);
        $childIds = $children->pluck('id')->map(fn ($id) => (int) $id)->all();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'student_id' => ['nullable', 'integer', Rule::in($childIds)],
        ]);

        $student = !empty($data['student_id'])
            ? $children->firstWhere('id', (int) $data['student_id'])
            : null;

        $appointment = Appointment::create([
            'parent_user_id' => (int) $user->id,
            'school_id' => $schoolId,
            'parent_id' => (int) $user->id,
            'student_id' => $student?->id,
            'parent_name' => (string) $user->name,
            'parent_phone' => $data['parent_phone'] ?? null,
            'parent_email' => (string) ($user->email ?? ''),
            'title' => $data['title'],
            'message' => $this->composeMessage($data['message'] ?? null, $student),
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'pending',
        ]);

        $this->notifySchoolTeam($appointment);
        $appointment->loadMissing(['student:id,full_name,classroom_id', 'student.classroom:id,name']);

        return response()->json([
            'message' => 'Rendez-vous demande avec succes.',
            'item' => $this->appointmentPayload($appointment),
        ], 201);
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

    private function appointmentPayload(Appointment $appointment): array
    {
        return [
            'id' => (int) $appointment->id,
            'title' => (string) $appointment->title,
            'message' => (string) ($appointment->message ?? ''),
            'status' => (string) $appointment->normalized_status,
            'scheduled_at' => optional($appointment->scheduled_for)->toIso8601String(),
            'admin_notes' => (string) ($appointment->admin_notes ?? ''),
            'student' => $appointment->student ? [
                'id' => (int) $appointment->student->id,
                'name' => (string) $appointment->student->full_name,
                'classroom' => (string) ($appointment->student->classroom?->name ?? ''),
            ] : null,
            'created_at' => optional($appointment->created_at)->toIso8601String(),
            'updated_at' => optional($appointment->updated_at)->toIso8601String(),
        ];
    }

    private function composeMessage(?string $message, ?Student $student): ?string
    {
        $message = trim((string) $message);

        if (!$student) {
            return $message !== '' ? $message : null;
        }

        $prefix = 'Enfant concerne : ' . $student->full_name;
        if ($student->classroom?->name) {
            $prefix .= ' (' . $student->classroom->name . ')';
        }

        return $message !== ''
            ? $prefix . "\n\n" . $message
            : $prefix;
    }

    private function notifySchoolTeam(Appointment $appointment): void
    {
        $schoolId = (int) $appointment->school_id;
        $recipientIds = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DIRECTOR, User::ROLE_SCHOOL_LIFE])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        app(NotificationService::class)->notifyUsers(
            $recipientIds,
            'appointment',
            'Nouvelle demande de rendez-vous',
            $appointment->title,
            [
                'appointment_id' => (int) $appointment->id,
                'school_id' => $schoolId,
                'student_id' => $appointment->student_id ? (int) $appointment->student_id : null,
            ]
        );
    }
}
