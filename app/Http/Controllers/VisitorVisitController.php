<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\VisitorVisit;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorVisitController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId($request->user());
        $status = (string) $request->get('status', 'inside');
        $date = (string) $request->get('date', now()->toDateString());
        $q = trim((string) $request->get('q', ''));

        $visits = VisitorVisit::query()
            ->where('school_id', $schoolId)
            ->with(['hostUser:id,name,role', 'student.classroom:id,name'])
            ->when($status === 'inside', fn ($query) => $query->where('status', VisitorVisit::STATUS_CHECKED_IN))
            ->when($status === 'expected', fn ($query) => $query->where('status', VisitorVisit::STATUS_EXPECTED))
            ->when($status === 'completed', fn ($query) => $query->where('status', VisitorVisit::STATUS_CHECKED_OUT))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', VisitorVisit::STATUS_CANCELLED))
            ->when($date !== '', fn ($query) => $query->where(function ($nested) use ($date) {
                $nested->whereDate('expected_at', $date)->orWhereDate('checked_in_at', $date);
            }))
            ->when($q !== '', fn ($query) => $query->where(function ($nested) use ($q) {
                $nested->where('visitor_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('identity_number', 'like', "%{$q}%")
                    ->orWhere('badge_code', 'like', "%{$q}%")
                    ->orWhereHas('hostUser', fn ($host) => $host->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$q}%"));
            }))
            ->latest('checked_in_at')
            ->latest('expected_at')
            ->paginate(20)
            ->withQueryString();

        $base = VisitorVisit::query()->where('school_id', $schoolId);
        $stats = [
            'inside' => (clone $base)->where('status', VisitorVisit::STATUS_CHECKED_IN)->count(),
            'expected_today' => (clone $base)->where('status', VisitorVisit::STATUS_EXPECTED)->whereDate('expected_at', today())->count(),
            'completed_today' => (clone $base)->where('status', VisitorVisit::STATUS_CHECKED_OUT)->whereDate('checked_out_at', today())->count(),
            'total_today' => (clone $base)->where(function ($query) {
                $query->whereDate('expected_at', today())->orWhereDate('checked_in_at', today());
            })->count(),
        ];

        return view('visitors.index', $this->viewData($request->user(), compact('visits', 'stats', 'status', 'date', 'q')));
    }

    public function create(Request $request)
    {
        $schoolId = $this->schoolId($request->user());
        $hosts = User::query()->where('school_id', $schoolId)->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DIRECTOR, User::ROLE_TEACHER, User::ROLE_SCHOOL_LIFE, User::ROLE_ACCUEIL])
            ->orderBy('name')->get(['id', 'name', 'role']);
        $students = Student::query()->active()->where('school_id', $schoolId)->with('classroom:id,name')->orderBy('full_name')->get(['id', 'full_name', 'classroom_id']);

        return view('visitors.create', $this->viewData($request->user(), compact('hosts', 'students')));
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId($request->user());
        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'identity_type' => ['nullable', 'string', 'max:60'],
            'identity_number' => ['nullable', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:60'],
            'purpose' => ['required', 'in:' . implode(',', array_keys(VisitorVisit::purposes()))],
            'purpose_details' => ['nullable', 'string', 'max:3000'],
            'host_user_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'expected_at' => ['nullable', 'required_if:arrival_mode,expected', 'date'],
            'entry_note' => ['nullable', 'string', 'max:2000'],
            'arrival_mode' => ['required', 'in:expected,now'],
        ]);

        $host = !empty($data['host_user_id']) ? User::query()->where('school_id', $schoolId)->findOrFail($data['host_user_id']) : null;
        $student = !empty($data['student_id']) ? Student::query()->where('school_id', $schoolId)->findOrFail($data['student_id']) : null;
        $arrivesNow = $data['arrival_mode'] === 'now';
        $visit = VisitorVisit::create([
            ...$data,
            'school_id' => $schoolId,
            'host_user_id' => $host?->id,
            'student_id' => $student?->id,
            'expected_at' => $data['expected_at'] ?? ($arrivesNow ? now() : null),
            'checked_in_at' => $arrivesNow ? now() : null,
            'status' => $arrivesNow ? VisitorVisit::STATUS_CHECKED_IN : VisitorVisit::STATUS_EXPECTED,
            'badge_code' => 'V-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $request->user()->id,
            'checked_in_by_user_id' => $arrivesNow ? $request->user()->id : null,
        ]);

        if ($arrivesNow) {
            $this->notifyHost($visit);
        }

        return redirect()->route($this->routePrefix($request->user()) . '.show', $visit)
            ->with('success', $arrivesNow ? 'Le visiteur est enregistré comme présent.' : 'La visite prévue a été enregistrée.');
    }

    public function show(Request $request, VisitorVisit $visitor)
    {
        $this->authorizeVisit($request->user(), $visitor);
        $visitor->load(['hostUser:id,name,role,phone', 'student.classroom:id,name', 'createdBy:id,name', 'checkedInBy:id,name', 'checkedOutBy:id,name']);

        return view('visitors.show', $this->viewData($request->user(), compact('visitor')));
    }

    public function checkIn(Request $request, VisitorVisit $visitor)
    {
        $this->authorizeVisit($request->user(), $visitor);
        abort_unless($visitor->status === VisitorVisit::STATUS_EXPECTED, 422, 'Cette visite ne peut pas être enregistrée comme arrivée.');
        $data = $request->validate(['entry_note' => ['nullable', 'string', 'max:2000']]);
        $visitor->update([
            'status' => VisitorVisit::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $request->user()->id,
            'entry_note' => trim((string) ($data['entry_note'] ?? $visitor->entry_note)) ?: null,
        ]);
        $this->notifyHost($visitor);

        return back()->with('success', 'Entrée du visiteur confirmée.');
    }

    public function checkOut(Request $request, VisitorVisit $visitor)
    {
        $this->authorizeVisit($request->user(), $visitor);
        abort_unless($visitor->status === VisitorVisit::STATUS_CHECKED_IN, 422, 'Ce visiteur n’est pas actuellement présent.');
        $data = $request->validate(['exit_note' => ['nullable', 'string', 'max:2000']]);
        $visitor->update([
            'status' => VisitorVisit::STATUS_CHECKED_OUT,
            'checked_out_at' => now(),
            'checked_out_by_user_id' => $request->user()->id,
            'exit_note' => trim((string) ($data['exit_note'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Sortie du visiteur enregistrée.');
    }

    public function cancel(Request $request, VisitorVisit $visitor)
    {
        $this->authorizeVisit($request->user(), $visitor);
        abort_unless($visitor->status === VisitorVisit::STATUS_EXPECTED, 422);
        $visitor->update(['status' => VisitorVisit::STATUS_CANCELLED]);

        return back()->with('success', 'Visite prévue annulée.');
    }

    private function notifyHost(VisitorVisit $visit): void
    {
        if (!$visit->host_user_id) {
            return;
        }
        $this->notifications->notifyUsers([(int) $visit->host_user_id], 'visitor_arrival', 'Votre visiteur est arrivé', $visit->visitor_name . ' vous attend à l’accueil.', [
            'visitor_visit_id' => $visit->id,
            'school_id' => $visit->school_id,
        ]);
    }

    private function authorizeVisit(User $user, VisitorVisit $visitor): void
    {
        abort_unless((int) $visitor->school_id === $this->schoolId($user), 404);
    }

    private function schoolId(User $user): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) $user->school_id;
        abort_unless($schoolId > 0, 403);
        return $schoolId;
    }

    private function routePrefix(User $user): string
    {
        return match ((string) $user->role) {
            User::ROLE_ADMIN => 'admin.visitors',
            User::ROLE_ACCUEIL => 'accueil.visitors',
            default => 'school-life.visitors',
        };
    }

    private function viewData(User $user, array $data): array
    {
        return $data + [
            'layoutComponent' => match ((string) $user->role) {
                User::ROLE_ADMIN => 'admin-layout',
                User::ROLE_ACCUEIL => 'accueil-layout',
                default => 'school-life-layout',
            },
            'routePrefix' => $this->routePrefix($user),
        ];
    }
}
