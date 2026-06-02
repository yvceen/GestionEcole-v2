<?php

namespace App\Http\Controllers\Chauffeur;

use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use App\Models\TransportAssignment;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId();
        $vehicleId = (int) $request->integer('vehicle_id');
        $routeId = (int) $request->integer('route_id');
        $status = (string) $request->get('status', '');
        $q = trim((string) $request->get('q', ''));

        $vehicleIds = Vehicle::query()
            ->where('school_id', $schoolId)
            ->where('driver_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $vehicles = Vehicle::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $vehicleIds)
            ->with(['routes' => fn ($query) => $query->where('is_active', true)->orderBy('route_name')])
            ->orderByRaw('COALESCE(name, registration_number)')
            ->get();

        $routes = $vehicles
            ->flatMap(fn (Vehicle $vehicle) => $vehicle->routes)
            ->unique('id')
            ->sortBy('route_name')
            ->values();

        $assignmentsQuery = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('vehicle_id', $vehicleIds)
            ->when($vehicleId > 0 && in_array($vehicleId, $vehicleIds, true), fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when($routeId > 0, fn ($query) => $query->where('route_id', $routeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('student', function ($studentQuery) use ($q) {
                    $studentQuery->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('classroom', fn ($classroomQuery) => $classroomQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                            $parentQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->with([
                'student:id,full_name,classroom_id,parent_user_id',
                'student.classroom:id,name',
                'student.parentUser:id,name,phone,email',
                'route:id,route_name,vehicle_id',
                'route.stops:id,route_id,name,scheduled_time,stop_order',
                'vehicle:id,name,registration_number,assistant_name,driver_id',
                'transportLogs' => fn ($query) => $query->whereDate('logged_at', now()->toDateString())->latest('logged_at'),
            ])
            ->orderBy('route_id')
            ->orderBy('pickup_point')
            ->orderBy('student_id');

        $assignments = $assignmentsQuery->get()
            ->filter(function (TransportAssignment $assignment) use ($status) {
                $boarded = $assignment->transportLogs->firstWhere('status', TransportLog::STATUS_BOARDED);
                $dropped = $assignment->transportLogs->firstWhere('status', TransportLog::STATUS_DROPPED);

                return match ($status) {
                    'waiting' => !$boarded,
                    'boarded' => $boarded && !$dropped,
                    'done' => $boarded && $dropped,
                    default => true,
                };
            })
            ->values();

        $todayLogs = TransportLog::query()
            ->where('school_id', $schoolId)
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereDate('logged_at', now()->toDateString())
            ->with(['student:id,full_name', 'route:id,route_name', 'vehicle:id,name,registration_number', 'stop:id,name'])
            ->latest('logged_at')
            ->get();

        $stats = [
            'students' => $assignments->count(),
            'boarded' => $assignments->filter(fn ($assignment) => $assignment->transportLogs->firstWhere('status', TransportLog::STATUS_BOARDED))->count(),
            'dropped' => $assignments->filter(fn ($assignment) => $assignment->transportLogs->firstWhere('status', TransportLog::STATUS_DROPPED))->count(),
            'waiting' => $assignments->filter(fn ($assignment) => !$assignment->transportLogs->firstWhere('status', TransportLog::STATUS_BOARDED))->count(),
        ];

        return view('chauffeur.dashboard', compact(
            'user',
            'vehicles',
            'routes',
            'assignments',
            'todayLogs',
            'stats',
            'vehicleId',
            'routeId',
            'status',
            'q'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'transport_assignment_id' => ['required', 'integer'],
            'status' => ['required', 'in:' . implode(',', TransportLog::statuses())],
            'route_stop_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $assignment = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('vehicle', fn ($query) => $query->where('driver_id', $user->id))
            ->with(['student.parentUser:id,name', 'route:id,route_name', 'vehicle:id,name,registration_number'])
            ->findOrFail((int) $data['transport_assignment_id']);

        $stop = null;
        $stopId = (int) ($data['route_stop_id'] ?? 0);
        if ($stopId > 0) {
            $stop = RouteStop::query()
                ->where('route_id', $assignment->route_id)
                ->findOrFail($stopId);
        }

        $log = TransportLog::query()
            ->where('school_id', $schoolId)
            ->where('transport_assignment_id', $assignment->id)
            ->where('status', $data['status'])
            ->whereDate('logged_at', now()->toDateString())
            ->first();

        $payload = [
            'route_stop_id' => $stop?->id,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'recorded_by_user_id' => $user->id,
            'logged_at' => now(),
        ];

        if ($log) {
            $log->update($payload);
        } else {
            $log = TransportLog::create($payload + [
                'school_id' => $schoolId,
                'transport_assignment_id' => $assignment->id,
                'student_id' => $assignment->student_id,
                'route_id' => $assignment->route_id,
                'vehicle_id' => $assignment->vehicle_id,
                'status' => $data['status'],
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

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
