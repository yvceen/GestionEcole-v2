<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Student;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    private array $columns = [];

    public function index(Request $request)
    {
        $schoolId = $this->currentSchoolIdOrFailIfScoped();
        $q = trim((string) $request->get('q', ''));
        $statusFilter = self::normalizeStatus((string) $request->get('status', 'all'));
        $hasStatusColumn = $this->hasColumn('status');
        $orderDateColumn = $this->hasColumn('scheduled_at')
            ? 'scheduled_at'
            : ($this->hasColumn('date') ? 'date' : 'created_at');

        $items = Appointment::query()
            ->with(['parentUser:id,name,email,phone', 'student:id,full_name,classroom_id', 'student.classroom:id,name'])
            ->when($this->hasColumn('school_id'), fn ($query) => $query->where('school_id', $schoolId))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($scope) use ($q): void {
                    $scope->where('title', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%");

                    if ($this->hasColumn('parent_phone')) {
                        $scope->orWhere('parent_phone', 'like', "%{$q}%");
                    }

                    if ($this->hasColumn('parent_name')) {
                        $scope->orWhere('parent_name', 'like', "%{$q}%");
                    }

                    if ($this->hasColumn('student_id')) {
                        $scope->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('full_name', 'like', "%{$q}%"));
                    }

                    $scope->orWhereHas('parentUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($statusFilter !== 'all' && $hasStatusColumn, fn ($query) => $this->applyNormalizedStatusFilter($query, $statusFilter))
            ->when($hasStatusColumn, function ($query): void {
                $query->orderByRaw("CASE
                    WHEN LOWER(COALESCE(status, '')) IN ('pending','draft','') THEN 0
                    WHEN LOWER(COALESCE(status, '')) IN ('approved','confirmed') THEN 1
                    WHEN LOWER(COALESCE(status, '')) IN ('completed') THEN 2
                    ELSE 3
                END");
            })
            ->orderByDesc($orderDateColumn)
            ->paginate(12)
            ->withQueryString();

        $statsBase = Appointment::query()
            ->when($this->hasColumn('school_id'), fn ($query) => $query->where('school_id', $schoolId));

        $stats = [
            'pending' => $hasStatusColumn
                ? (clone $statsBase)->where(function ($query): void {
                    $query->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereIn('status', ['pending', 'draft']);
                })->count()
                : 0,
            'approved' => $hasStatusColumn
                ? (clone $statsBase)->whereIn('status', ['approved', 'confirmed'])->count()
                : 0,
            'completed' => $hasStatusColumn
                ? (clone $statsBase)->where('status', 'completed')->count()
                : 0,
            'rejected' => $hasStatusColumn
                ? (clone $statsBase)->whereIn('status', ['rejected', 'cancelled', 'archived'])->count()
                : 0,
            'this_week' => $this->hasColumn('created_at')
                ? (clone $statsBase)->where('created_at', '>=', Carbon::now()->subDays(7))->count()
                : 0,
        ];

        return view('admin.appointments.index', compact('items', 'q', 'statusFilter', 'stats'));
    }

    public function create()
    {
        return view('admin.appointments.create', $this->formData());
    }

    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolIdOrFailIfScoped();
        $data = $this->validateAppointment($request, $schoolId);

        Appointment::create($this->buildPayload($data, $schoolId));

        return redirect()
            ->route('admin.appointments.index')
            ->with('success', 'Rendez-vous cree avec succes.');
    }

    public function edit(Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);

        return view('admin.appointments.edit', array_merge($this->formData(), [
            'appointment' => $appointment->loadMissing(['student:id,full_name,classroom_id', 'student.classroom:id,name']),
        ]));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);
        $schoolId = $this->currentSchoolIdOrFailIfScoped();
        $data = $this->validateAppointment($request, $schoolId);
        $previousStatus = $appointment->normalized_status;

        $appointment->update($this->buildPayload($data, $schoolId, $appointment));
        $appointment->refresh();

        $this->notifyParentAboutUpdate($appointment, $previousStatus);

        return redirect()->route('admin.appointments.index')->with('success', 'Rendez-vous mis a jour.');
    }

    public function show(Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);
        $appointment->loadMissing(['parentUser:id,name,email,phone', 'student:id,full_name,classroom_id', 'student.classroom:id,name']);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function approve(Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);
        $payload = [];

        if ($this->hasColumn('status')) {
            $payload['status'] = 'approved';
        }

        if ($this->hasColumn('approved_at')) {
            $payload['approved_at'] = now();
        }

        if ($this->hasColumn('approved_by')) {
            $payload['approved_by'] = auth()->id();
        }

        if ($payload !== []) {
            $appointment->update($payload);
        }

        $this->notifyParentStatus($appointment, 'Rendez-vous approuve', 'Votre demande de rendez-vous a ete approuvee.');

        return back()->with('success', 'Rendez-vous approuve.');
    }

    public function reject(Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);
        $payload = [];

        if ($this->hasColumn('status')) {
            $payload['status'] = 'rejected';
        }

        if ($this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = now();
        }

        if ($this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = auth()->id();
        }

        if ($payload !== []) {
            $appointment->update($payload);
        }

        $this->notifyParentStatus($appointment, 'Rendez-vous refuse', 'Votre demande de rendez-vous a ete refusee.');

        return back()->with('success', 'Rendez-vous rejete.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->ensureTenantOwnership($appointment);
        $appointment->delete();

        return redirect()->route('admin.appointments.index')->with('success', 'Rendez-vous supprime.');
    }

    private function formData(): array
    {
        $schoolId = $this->currentSchoolIdOrFailIfScoped();

        return [
            'children' => Student::query()
                ->where('school_id', $schoolId)
                ->active()
                ->with('classroom:id,name')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'classroom_id']),
            'statuses' => [
                'pending' => 'En attente',
                'approved' => 'Approuve',
                'completed' => 'Termine',
                'rejected' => 'Refuse',
            ],
        ];
    }

    private function validateAppointment(Request $request, int $schoolId): array
    {
        $childIds = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['draft', 'pending', 'confirmed', 'approved', 'completed', 'archived', 'cancelled', 'rejected'])],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'admin_notes' => ['nullable', 'string'],
            'student_id' => ['nullable', 'integer', Rule::in($childIds)],
        ]);
    }

    private function buildPayload(array $data, int $schoolId, ?Appointment $appointment = null): array
    {
        $payload = [
            'title' => $data['title'],
            'parent_phone' => $data['parent_phone'] ?? ($appointment?->parent_phone),
        ];

        if ($this->hasColumn('school_id')) {
            $payload['school_id'] = $schoolId;
        }

        if ($this->hasColumn('message')) {
            $payload['message'] = $data['message'] ?? null;
        }

        if ($this->hasColumn('status')) {
            $payload['status'] = self::normalizeStatus($data['status'] ?? ($appointment?->status ?? 'pending'));
        }

        if ($this->hasColumn('admin_notes')) {
            $payload['admin_notes'] = $data['admin_notes'] ?? null;
        }

        if ($this->hasColumn('student_id')) {
            $payload['student_id'] = !empty($data['student_id']) ? (int) $data['student_id'] : null;
        }

        if ($this->hasColumn('scheduled_at')) {
            $payload['scheduled_at'] = $data['scheduled_at'];
        } elseif ($this->hasColumn('date')) {
            $payload['date'] = $data['scheduled_at'];
        }

        if (($payload['status'] ?? null) === 'approved' && $this->hasColumn('approved_at')) {
            $payload['approved_at'] = $appointment?->approved_at ?? now();
        }

        if (($payload['status'] ?? null) === 'approved' && $this->hasColumn('approved_by')) {
            $payload['approved_by'] = $appointment?->approved_by ?? auth()->id();
        }

        if (($payload['status'] ?? null) === 'rejected' && $this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = $appointment?->rejected_at ?? now();
        }

        if (($payload['status'] ?? null) === 'rejected' && $this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = $appointment?->rejected_by ?? auth()->id();
        }

        return $payload;
    }

    private function notifyParentStatus(Appointment $appointment, string $title, string $body): void
    {
        $recipientParentId = (int) ($appointment->parent_user_id ?? 0);
        if ($recipientParentId <= 0) {
            return;
        }

        try {
            app(NotificationService::class)->notifyUsers(
                [$recipientParentId],
                'appointment',
                $title,
                $body,
                [
                    'url' => route('parent.appointments.index'),
                    'appointment_id' => (int) $appointment->id,
                    'school_id' => (int) ($appointment->school_id ?? 0),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Appointment status notification failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyParentAboutUpdate(Appointment $appointment, string $previousStatus): void
    {
        $recipientParentId = (int) ($appointment->parent_user_id ?? 0);
        if ($recipientParentId <= 0) {
            return;
        }

        $status = $appointment->normalized_status;
        if ($status === $previousStatus && !$appointment->wasChanged(['scheduled_at', 'title', 'message', 'admin_notes', 'student_id'])) {
            return;
        }

        $title = match ($status) {
            'approved' => 'Rendez-vous approuve',
            'completed' => 'Rendez-vous termine',
            'rejected' => 'Rendez-vous refuse',
            default => 'Rendez-vous mis a jour',
        };

        $body = $appointment->title;
        if ($appointment->scheduled_for) {
            $body .= ' - ' . $appointment->scheduled_for->format('d/m/Y H:i');
        }

        try {
            app(NotificationService::class)->notifyUsers(
                [$recipientParentId],
                'appointment',
                $title,
                $body,
                [
                    'url' => route('parent.appointments.index'),
                    'appointment_id' => (int) $appointment->id,
                    'school_id' => (int) ($appointment->school_id ?? 0),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Appointment update notification failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasColumn(string $column): bool
    {
        if ($this->columns === []) {
            $this->columns = Schema::hasTable('appointments')
                ? Schema::getColumnListing('appointments')
                : [];
        }

        return in_array($column, $this->columns, true);
    }

    private function currentSchoolIdOrFailIfScoped(): int
    {
        if (!$this->hasColumn('school_id')) {
            return 0;
        }

        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if ($schoolId <= 0) {
            abort(403, 'School context missing.');
        }

        return $schoolId;
    }

    private function ensureTenantOwnership(Appointment $appointment): void
    {
        if (!$this->hasColumn('school_id')) {
            return;
        }

        $schoolId = $this->currentSchoolIdOrFailIfScoped();
        abort_unless((int) ($appointment->school_id ?? 0) === $schoolId, 404);
    }

    private static function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            '', 'draft', 'pending' => 'pending',
            'confirmed', 'approved' => 'approved',
            'completed' => 'completed',
            'archived', 'cancelled', 'rejected' => 'rejected',
            'all' => 'all',
            default => 'pending',
        };
    }

    private function applyNormalizedStatusFilter($query, string $status): void
    {
        if ($status === 'pending') {
            $query->where(function ($subQuery): void {
                $subQuery->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhereIn('status', ['pending', 'draft']);
            });
            return;
        }

        if ($status === 'approved') {
            $query->whereIn('status', ['approved', 'confirmed']);
            return;
        }

        if ($status === 'completed') {
            $query->where('status', 'completed');
            return;
        }

        if ($status === 'rejected') {
            $query->whereIn('status', ['rejected', 'cancelled', 'archived']);
        }
    }
}
