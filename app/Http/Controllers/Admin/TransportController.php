<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\TransportAssignment;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Schema;

class TransportController extends Controller
{
    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) abort(403, 'School context missing.');
        return $schoolId;
    }

    /**
     * Display transport management dashboard
     * (Circuits, routes, pickups, student assignments)
     */
    public function index()
    {
        $schoolId = $this->schoolId();

        // Fetch counts for dashboard (guarded so missing migrations don't 500 the page)
        $vehiclesCount = 0;
        $routesCount = 0;
        $assignmentsCount = 0;

        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'school_id')) {
            $vehiclesCount = \App\Models\Vehicle::where('school_id', $schoolId)->count();
        }

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'school_id')) {
            $routesCount = \App\Models\Route::where('school_id', $schoolId)->count();
        }

        if (Schema::hasTable('transport_assignments') && Schema::hasColumn('transport_assignments', 'school_id')) {
            $assignmentsCount = \App\Models\TransportAssignment::where('school_id', $schoolId)->count();
        }

        $routes = Route::query()
            ->where('school_id', $schoolId)
            ->with(['vehicle.driver:id,name,phone', 'stops'])
            ->withCount([
                'assignments as active_assignments_count' => fn ($query) => $query->where('is_active', true),
                'stops',
            ])
            ->orderByDesc('is_active')
            ->orderBy('route_name')
            ->limit(6)
            ->get();

        $recentAssignments = TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'route:id,route_name', 'vehicle:id,name'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('admin.transport.index', [
            'schoolId' => $schoolId,
            'vehiclesCount' => $vehiclesCount,
            'routesCount' => $routesCount,
            'assignmentsCount' => $assignmentsCount,
            'activeVehiclesCount' => Vehicle::query()->where('school_id', $schoolId)->where('is_active', true)->count(),
            'routes' => $routes,
            'recentAssignments' => $recentAssignments,
        ]);
    }
}
