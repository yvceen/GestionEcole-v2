<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentHealthProfile;
use App\Models\StudentHealthReport;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentHealthController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($user);
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', 'active');

        $visibleReports = fn ($query) => $this->applyReportVisibility($query, $user)->latest('starts_at');
        $students = $this->accessibleStudents($user, $schoolId)
            ->with(['classroom:id,name', 'parentUser:id,name,phone', 'healthProfile', 'activeHealthReports' => $visibleReports])
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q, $user) {
                $nested->where('full_name', 'like', "%{$q}%")
                    ->orWhereHas('parentUser', fn ($parent) => $parent->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('healthProfile', fn ($health) => $health->where('allergies', 'like', "%{$q}%")->orWhere('chronic_conditions', 'like', "%{$q}%"))
                    ->orWhereHas('activeHealthReports', fn ($report) => $this->applyReportVisibility($report, $user)->where('condition_name', 'like', "%{$q}%"));
            }))
            ->when($status === 'active', fn ($query) => $query->whereHas('activeHealthReports', fn ($report) => $this->applyReportVisibility($report, $user)))
            ->when($status === 'attention', fn ($query) => $query->where(function ($nested) use ($user) {
                $nested->whereHas('activeHealthReports', fn ($report) => $this->applyReportVisibility($report, $user)->whereIn('severity', ['high', 'urgent']))
                    ->orWhereHas('healthProfile', fn ($profile) => $profile->whereNotNull('allergies')->orWhereNotNull('chronic_conditions'));
            }))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $base = $this->accessibleStudents($user, $schoolId);
        $stats = [
            'active' => (clone $base)->whereHas('activeHealthReports', fn ($report) => $this->applyReportVisibility($report, $user))->count(),
            'urgent' => (clone $base)->whereHas('activeHealthReports', fn ($query) => $this->applyReportVisibility($query, $user)->whereIn('severity', ['high', 'urgent']))->count(),
            'allergies' => (clone $base)->whereHas('healthProfile', fn ($query) => $query->whereNotNull('allergies')->where('allergies', '!=', ''))->count(),
            'profiles' => (clone $base)->whereHas('healthProfile')->count(),
        ];

        return view('health.index', $this->viewData($user, compact('students', 'stats', 'q', 'status')));
    }

    public function show(Request $request, Student $student)
    {
        $user = $request->user();
        $this->authorizeStudent($user, $student);
        $student->load(['classroom:id,name', 'parentUser:id,name,phone,email', 'healthProfile.updatedBy:id,name']);
        $student->load(['healthReports' => fn ($query) => $this->applyReportVisibility($query, $user)->with(['reporter:id,name', 'resolvedBy:id,name'])->latest('starts_at')]);

        return view('health.show', $this->viewData($user, compact('student')));
    }

    public function updateProfile(Request $request, Student $student)
    {
        $this->authorizeManager($request->user(), $student);
        $data = $request->validate([
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:3000'],
            'chronic_conditions' => ['nullable', 'string', 'max:3000'],
            'medications' => ['nullable', 'string', 'max:3000'],
            'dietary_restrictions' => ['nullable', 'string', 'max:3000'],
            'emergency_instructions' => ['nullable', 'string', 'max:3000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:120'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'doctor_phone' => ['nullable', 'string', 'max:40'],
            'allow_first_aid' => ['nullable', 'boolean'],
        ]);

        StudentHealthProfile::updateOrCreate(
            ['student_id' => $student->id],
            $data + ['school_id' => $student->school_id, 'updated_by_user_id' => $request->user()->id]
        );

        return back()->with('success', 'Dossier de santé mis à jour.');
    }

    public function storeReport(Request $request, Student $student)
    {
        $user = $request->user();
        $this->authorizeReporter($user, $student);
        $isParent = (string) $user->role === User::ROLE_PARENT;
        $data = $request->validate([
            'type' => ['required', 'in:illness,injury,medication,other'],
            'severity' => ['required', 'in:low,medium,high,urgent'],
            'condition_name' => ['required', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'starts_at' => ['nullable', 'date'],
            'expected_return_at' => ['nullable', 'date'],
            'visible_to_teacher' => ['nullable', 'boolean'],
            'visible_to_driver' => ['nullable', 'boolean'],
        ]);

        $report = StudentHealthReport::create([
            ...$data,
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'reported_by_user_id' => $user->id,
            'source' => $isParent ? 'parent' : 'school',
            'starts_at' => $data['starts_at'] ?? now(),
            'status' => StudentHealthReport::STATUS_ACTIVE,
            'visible_to_teacher' => $isParent ? true : (bool) ($data['visible_to_teacher'] ?? false),
            'visible_to_driver' => $isParent ? (bool) ($data['visible_to_driver'] ?? false) : (bool) ($data['visible_to_driver'] ?? false),
        ]);

        $this->notifyReportRecipients($student, $report);

        return back()->with('success', 'Alerte santé enregistrée et transmise.');
    }

    public function resolve(Request $request, StudentHealthReport $report)
    {
        $this->authorizeManager($request->user(), $report->student);
        $report->update([
            'status' => StudentHealthReport::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'La situation est marquée comme terminée.');
    }

    private function accessibleStudents(User $user, int $schoolId): Builder
    {
        $query = Student::query()->active()->where('school_id', $schoolId);

        return match ((string) $user->role) {
            User::ROLE_PARENT => $query->where('parent_user_id', $user->id),
            User::ROLE_TEACHER => $query->whereIn('classroom_id', $user->teacherClassrooms()->pluck('classrooms.id')),
            User::ROLE_CHAUFFEUR => $query->whereIn('id', TransportAssignment::query()->where('school_id', $schoolId)->where('is_active', true)->whereHas('vehicle', fn ($vehicle) => $vehicle->where('driver_id', $user->id))->pluck('student_id')),
            default => $query,
        };
    }

    private function applyReportVisibility(Builder $query, User $user): Builder
    {
        return match ((string) $user->role) {
            User::ROLE_TEACHER => $query->where('visible_to_teacher', true),
            User::ROLE_CHAUFFEUR => $query->where('visible_to_driver', true),
            default => $query,
        };
    }

    private function notifyReportRecipients(Student $student, StudentHealthReport $report): void
    {
        $groups = [
            'admin.health.show' => User::query()->where('school_id', $student->school_id)->where('role', User::ROLE_ADMIN)->pluck('id'),
            'school-life.health.show' => User::query()->where('school_id', $student->school_id)->where('role', User::ROLE_SCHOOL_LIFE)->pluck('id'),
        ];

        if ($report->visible_to_teacher && $student->classroom_id) {
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
    }

    private function authorizeStudent(User $user, Student $student): void
    {
        abort_unless($this->accessibleStudents($user, $this->schoolId($user))->whereKey($student->id)->exists(), 404);
    }

    private function authorizeManager(User $user, Student $student): void
    {
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE], true), 403);
        abort_unless((int) $student->school_id === $this->schoolId($user), 404);
    }

    private function authorizeReporter(User $user, Student $student): void
    {
        if ((string) $user->role === User::ROLE_PARENT) {
            abort_unless((int) $student->parent_user_id === (int) $user->id, 403);
            return;
        }
        $this->authorizeManager($user, $student);
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($schoolId > 0, 403);
        return $schoolId;
    }

    private function viewData(User $user, array $data): array
    {
        $role = (string) $user->role;
        return $data + [
            'layoutComponent' => match ($role) {
                User::ROLE_ADMIN => 'admin-layout',
                User::ROLE_SCHOOL_LIFE => 'school-life-layout',
                User::ROLE_DIRECTOR => 'director-layout',
                User::ROLE_TEACHER => 'teacher-layout',
                User::ROLE_CHAUFFEUR => 'chauffeur-layout',
                default => 'parent-layout',
            },
            'routePrefix' => match ($role) {
                User::ROLE_ADMIN => 'admin.health',
                User::ROLE_SCHOOL_LIFE => 'school-life.health',
                User::ROLE_DIRECTOR => 'director.health',
                User::ROLE_TEACHER => 'teacher.health',
                User::ROLE_CHAUFFEUR => 'chauffeur.health',
                default => 'parent.health',
            },
            'canManage' => in_array($role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE], true),
            'canReport' => in_array($role, [User::ROLE_ADMIN, User::ROLE_SCHOOL_LIFE, User::ROLE_PARENT], true),
            'isDriver' => $role === User::ROLE_CHAUFFEUR,
        ];
    }
}
