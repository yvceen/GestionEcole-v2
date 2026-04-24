<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Appointment;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    use InteractsWithParentPortal;

    public function index()
    {
        $children = $this->ownedChildren(['classroom:id,name']);
        $appointments = Appointment::query()
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name'])
            ->where('school_id', $this->schoolIdOrFail())
            ->where('parent_user_id', $this->currentParent()->id)
            ->latest('scheduled_at')
            ->latest('id')
            ->paginate(12);

        return view('parent.appointments.index', compact('appointments', 'children'));
    }

    public function create()
    {
        $children = $this->ownedChildren(['classroom:id,name']);

        return view('parent.appointments.create', compact('children'));
    }

    public function store(Request $request)
    {
        $parent = $this->currentParent();
        $schoolId = $this->schoolIdOrFail();
        $children = $this->ownedChildren(['classroom:id,name']);
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
            'parent_user_id' => (int) $parent->id,
            'school_id' => $schoolId,
            'parent_id' => (int) $parent->id,
            'student_id' => $student?->id,
            'parent_name' => $parent->name ?? 'Parent',
            'parent_email' => $parent->email ?? null,
            'parent_phone' => $data['parent_phone'] ?? ($parent->phone ?? null),
            'title' => $data['title'],
            'message' => $this->composeMessage($data['message'] ?? null, $student),
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'pending',
        ]);

        $this->notifySchoolTeam($appointment);

        return redirect()
            ->route('parent.appointments.index')
            ->with('success', 'Votre demande de rendez-vous a ete envoyee.');
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
        if ($schoolId <= 0) {
            return;
        }

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
