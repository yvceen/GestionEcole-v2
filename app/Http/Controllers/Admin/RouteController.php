<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRouteRequest;
use App\Models\Classroom;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');
        $routes = Route::query()
            ->where('school_id', $schoolId)
            ->with(['vehicle.driver:id,name,phone'])
            ->withCount([
                'assignments as active_assignments_count' => fn ($query) => $query->where('is_active', true),
                'stops',
            ])
            ->paginate(15);

        return view('admin.transport.routes.index', compact('routes'));
    }

    public function create()
    {
        $vehicles = Vehicle::query()
            ->where('school_id', app('current_school_id'))
            ->where('is_active', true)
            ->get();

        return view('admin.transport.routes.create', compact('vehicles'));
    }

    public function store(StoreRouteRequest $request)
    {
        $data = $request->validated();
        $stopsJson = $data['stops'] ?? '[]';
        unset($data['stops']);

        $route = Route::create([
            'school_id' => app('current_school_id'),
            ...$data,
        ]);

        $stops = json_decode($stopsJson, true);
        if (!is_array($stops) || count($stops) < 1) {
            return back()->withErrors(['stops' => 'Ajoutez au moins un arret.'])->withInput();
        }

        $rows = [];
        foreach (array_values($stops) as $idx => $stop) {
            $rows[] = [
                'route_id' => $route->id,
                'name' => $stop['name'] ?? null,
                'lat' => (float) ($stop['lat'] ?? 0),
                'lng' => (float) ($stop['lng'] ?? 0),
                'stop_order' => $idx + 1,
                'scheduled_time' => !empty($stop['scheduled_time']) ? $stop['scheduled_time'] : null,
                'notes' => $stop['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        RouteStop::insert($rows);

        return redirect()
            ->route('admin.transport.routes.index')
            ->with('success', 'Route creee avec succes.');
    }

    public function show(Route $route)
    {
        abort_unless($route->school_id === app('current_school_id'), 403);

        $schoolId = (int) app('current_school_id');
        $route->load(['vehicle.driver:id,name,phone', 'stops', 'assignments.student.classroom']);
        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.transport.routes.show', compact('route', 'classrooms'));
    }

    public function edit(Route $route)
    {
        abort_unless($route->school_id === app('current_school_id'), 403);

        $vehicles = Vehicle::query()
            ->where('school_id', app('current_school_id'))
            ->where('is_active', true)
            ->get();

        $route->load('stops');

        return view('admin.transport.routes.edit', compact('route', 'vehicles'));
    }

    public function update(StoreRouteRequest $request, Route $route)
    {
        abort_unless($route->school_id === app('current_school_id'), 403);

        $data = $request->validated();
        $stopsJson = $data['stops'] ?? '[]';
        unset($data['stops']);

        $route->update($data);

        $stops = json_decode($stopsJson, true);
        if (!is_array($stops) || count($stops) < 1) {
            return back()->withErrors(['stops' => 'Ajoutez au moins un arret.'])->withInput();
        }

        $route->stops()->delete();
        $rows = [];
        foreach (array_values($stops) as $idx => $stop) {
            $rows[] = [
                'route_id' => $route->id,
                'name' => $stop['name'] ?? null,
                'lat' => (float) ($stop['lat'] ?? 0),
                'lng' => (float) ($stop['lng'] ?? 0),
                'stop_order' => $idx + 1,
                'scheduled_time' => !empty($stop['scheduled_time']) ? $stop['scheduled_time'] : null,
                'notes' => $stop['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        RouteStop::insert($rows);

        return redirect()
            ->route('admin.transport.routes.show', $route)
            ->with('success', 'Route mise a jour avec succes.');
    }

    public function destroy(Route $route)
    {
        abort_unless($route->school_id === app('current_school_id'), 403);

        $route->delete();

        return redirect()
            ->route('admin.transport.routes.index')
            ->with('success', 'Route supprimee avec succes.');
    }

    public function assignStudents(Request $request, Route $route): RedirectResponse
    {
        abort_unless($route->school_id === app('current_school_id'), 403);

        $schoolId = (int) app('current_school_id');
        $data = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'classroom_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'in:morning,evening,both'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['required', 'date'],
        ]);

        $studentIds = collect($data['student_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0);

        if ((int) ($data['classroom_id'] ?? 0) > 0) {
            $studentIds = $studentIds->merge(
                Student::query()
                    ->where('school_id', $schoolId)
                    ->active()
                    ->where('classroom_id', (int) $data['classroom_id'])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
            );
        }

        $studentIds = $studentIds->unique()->values();
        if ($studentIds->isEmpty()) {
            return back()->withErrors(['student_ids' => 'Choisissez au moins un eleve ou une classe.'])->withInput();
        }

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->whereIn('id', $studentIds->all())
            ->get(['id']);

        DB::transaction(function () use ($students, $route, $schoolId, $data): void {
            foreach ($students as $student) {
                TransportAssignment::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => (int) $student->id,
                    ],
                    [
                        'route_id' => (int) $route->id,
                        'vehicle_id' => (int) ($route->vehicle_id ?? 0) ?: null,
                        'period' => $data['period'] ?? 'both',
                        'pickup_point' => $data['pickup_point'] ?? null,
                        'assigned_date' => $data['assigned_date'],
                        'ended_date' => null,
                        'is_active' => true,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.transport.routes.show', $route)
            ->with('success', 'Affectations transport mises a jour pour les eleves selectionnes.');
    }
}
