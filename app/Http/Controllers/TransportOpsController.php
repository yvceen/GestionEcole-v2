<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\TransportAssignment;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TransportOpsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $user = $this->authorizedOperator();
        $schoolId = $this->schoolId();
        $routeId = $request->integer('route_id');
        $vehicleId = $request->integer('vehicle_id');

        $assignments = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($routeId > 0, fn ($query) => $query->where('route_id', $routeId))
            ->when($vehicleId > 0, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->with([
                'student:id,full_name,classroom_id,parent_user_id',
                'student.classroom:id,name',
                'student.parentUser:id,name,phone',
                'route:id,route_name,vehicle_id',
                'route.stops:id,route_id,name,scheduled_time,stop_order',
                'vehicle:id,name,registration_number,assistant_name,driver_id',
                'vehicle.driver:id,name',
                'transportLogs' => fn ($query) => $query->whereDate('logged_at', now()->toDateString())->latest('logged_at'),
            ])
            ->orderBy('route_id')
            ->orderBy('student_id')
            ->paginate(20)
            ->withQueryString();

        $routes = Route::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('route_name')->get(['id', 'route_name']);
        $vehicles = Vehicle::query()->where('school_id', $schoolId)->where('is_active', true)->orderByRaw('COALESCE(name, registration_number)')->get(['id', 'name', 'registration_number']);
        $recentLogs = TransportLog::query()
            ->where('school_id', $schoolId)
            ->whereDate('logged_at', now()->toDateString())
            ->with(['student:id,full_name', 'route:id,route_name', 'vehicle:id,name,registration_number'])
            ->latest('logged_at')
            ->limit(12)
            ->get();

        return view('transport.ops.index', compact('user', 'assignments', 'routes', 'vehicles', 'routeId', 'vehicleId', 'recentLogs'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizedOperator();
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'transport_assignment_id' => ['required', 'integer'],
            'status' => ['required', 'in:' . implode(',', TransportLog::statuses())],
            'route_stop_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $assignment = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->with(['student.parentUser:id,name', 'route:id,route_name', 'vehicle:id,name,registration_number'])
            ->findOrFail((int) $data['transport_assignment_id']);

        $stopId = (int) ($data['route_stop_id'] ?? 0);
        $stop = null;
        if ($stopId > 0) {
            $stop = RouteStop::query()
                ->whereHas('route', fn ($query) => $query->where('school_id', $schoolId))
                ->findOrFail($stopId);
        }

        $log = TransportLog::query()
            ->where('school_id', $schoolId)
            ->where('transport_assignment_id', $assignment->id)
            ->where('status', $data['status'])
            ->whereDate('logged_at', now()->toDateString())
            ->first();

        if ($log) {
            $log->update([
                'route_stop_id' => $stop?->id,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'recorded_by_user_id' => $user->id,
                'logged_at' => now(),
            ]);
        } else {
            $log = TransportLog::create([
                'school_id' => $schoolId,
                'transport_assignment_id' => $assignment->id,
                'student_id' => $assignment->student_id,
                'route_id' => $assignment->route_id,
                'vehicle_id' => $assignment->vehicle_id,
                'route_stop_id' => $stop?->id,
                'status' => $data['status'],
                'recorded_by_user_id' => $user->id,
                'logged_at' => now(),
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
            ]);

            if ($assignment->student?->parent_user_id) {
                $this->notifications->notifyUsers(
                    [(int) $assignment->student->parent_user_id],
                    'transport',
                    $assignment->student->full_name,
                    $data['status'] === TransportLog::STATUS_BOARDED ? 'child boarded' : 'child dropped',
                    [
                        'student_id' => $assignment->student_id,
                        'route' => route('parent.transport.index', absolute: false),
                    ]
                );
            }
        }

        return back()->with('success', $data['status'] === TransportLog::STATUS_BOARDED ? 'Montee enregistree.' : 'Descente enregistree.');
    }

    private function authorizedOperator(): User
    {
        $user = auth()->user();
        abort_unless($user && in_array((string) $user->role, [User::ROLE_SCHOOL_LIFE, User::ROLE_CHAUFFEUR], true), 403);

        return $user;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
