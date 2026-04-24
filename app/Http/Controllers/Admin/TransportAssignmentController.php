<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportAssignment;
use App\Models\Student;
use App\Models\Route;
use App\Models\Vehicle;
use App\Http\Requests\StoreTransportAssignmentRequest;

class TransportAssignmentController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');

        $assignments = TransportAssignment::where('school_id', $schoolId)
            ->with(['student.classroom', 'route.vehicle.driver', 'vehicle.driver'])
            ->paginate(15);

        return view('admin.transport.assignments.index', compact('assignments'));
    }

    public function create()
    {
        $schoolId = app('current_school_id');

        $students = Student::where('school_id', $schoolId)->active()->orderBy('full_name')->get();

        $vehicles = Vehicle::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('driver')
            ->get();

        $routes = Route::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();

        return view('admin.transport.assignments.create', compact('students', 'vehicles', 'routes'));
    }

    public function store(StoreTransportAssignmentRequest $request)
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        // default period if not provided (باش ما يطيحش)
        $data['period'] = $data['period'] ?? 'both';

        $route = Route::where('school_id', $schoolId)->findOrFail($data['route_id']);

        // إذا ما تختارش vehicle: خدها من route
        if (empty($data['vehicle_id'])) {
            $data['vehicle_id'] = $route->vehicle_id;
        } else {
            // إذا route مربوط بvehicle خاصهم يتطابقو
            if ($route->vehicle_id && (int) $route->vehicle_id !== (int) $data['vehicle_id']) {
                return back()
                    ->withErrors(['vehicle_id' => 'هذا véhicule لا يطابق الـ route المختارة.'])
                    ->withInput();
            }
        }

        TransportAssignment::create([
            'school_id' => $schoolId,
            ...$data,
        ]);

        return redirect()->route('admin.transport.assignments.index')
            ->with('success', 'Affectation de transport créée avec succès.');
    }

    public function show(TransportAssignment $transportAssignment)
    {
        abort_unless($transportAssignment->school_id === app('current_school_id'), 403);

        $transportAssignment->load(['student', 'route', 'vehicle']);

        return view('admin.transport.assignments.show', compact('transportAssignment'));
    }

    public function edit(TransportAssignment $transportAssignment)
    {
        abort_unless($transportAssignment->school_id === app('current_school_id'), 403);

        $schoolId = app('current_school_id');

        $students = Student::where('school_id', $schoolId)->active()->orderBy('full_name')->get();

        $vehicles = Vehicle::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('driver')
            ->get();

        $routes = Route::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();

        return view('admin.transport.assignments.edit', compact('transportAssignment', 'students', 'vehicles', 'routes'));
    }

    public function update(StoreTransportAssignmentRequest $request, TransportAssignment $transportAssignment)
    {
        abort_unless($transportAssignment->school_id === app('current_school_id'), 403);

        $schoolId = app('current_school_id');
        $data = $request->validated();

        $data['period'] = $data['period'] ?? 'both';

        $route = Route::where('school_id', $schoolId)->findOrFail($data['route_id']);

        if (empty($data['vehicle_id'])) {
            $data['vehicle_id'] = $route->vehicle_id;
        } else {
            if ($route->vehicle_id && (int) $route->vehicle_id !== (int) $data['vehicle_id']) {
                return back()
                    ->withErrors(['vehicle_id' => 'هذا véhicule لا يطابق الـ route المختارة.'])
                    ->withInput();
            }
        }

        $transportAssignment->update($data);

        return redirect()->route('admin.transport.assignments.show', $transportAssignment)
            ->with('success', 'Affectation de transport mise à jour avec succès.');
    }

    public function destroy(TransportAssignment $transportAssignment)
    {
        abort_unless($transportAssignment->school_id === app('current_school_id'), 403);

        $transportAssignment->delete();

        return redirect()->route('admin.transport.assignments.index')
            ->with('success', 'Affectation de transport supprimée avec succès.');
    }
}
