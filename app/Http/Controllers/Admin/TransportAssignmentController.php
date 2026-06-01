<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportAssignment;
use App\Models\Classroom;
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
        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        $vehicles = Vehicle::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('driver')
            ->get();

        $routes = Route::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();

        return view('admin.transport.assignments.create', compact('students', 'classrooms', 'vehicles', 'routes'));
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
                    ->withErrors(['vehicle_id' => 'هذا vehicule لا يطابق الـ route المختارة.'])
                    ->withInput();
            }
        }

        $studentIds = collect($data['student_ids'] ?? [])
            ->push($data['student_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (!empty($data['classroom_id'])) {
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
            return back()
                ->withErrors(['student_id' => 'Choisissez au moins un eleve ou une classe.'])
                ->withInput();
        }

        $payload = collect($data)->except(['student_id', 'student_ids', 'classroom_id'])->all();

        foreach ($studentIds as $studentId) {
            TransportAssignment::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => (int) $studentId,
                ],
                [
                    ...$payload,
                    'student_id' => (int) $studentId,
                    'school_id' => $schoolId,
                    'ended_date' => null,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('admin.transport.assignments.index')
            ->with('success', $studentIds->count().' affectation(s) de transport mise(s) a jour.');
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
        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        $vehicles = Vehicle::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('driver')
            ->get();

        $routes = Route::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();

        return view('admin.transport.assignments.edit', compact('transportAssignment', 'students', 'classrooms', 'vehicles', 'routes'));
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
                    ->withErrors(['vehicle_id' => 'هذا vehicule لا يطابق الـ route المختارة.'])
                    ->withInput();
            }
        }

        $transportAssignment->update(collect($data)->except(['student_ids', 'classroom_id'])->all());

        return redirect()->route('admin.transport.assignments.show', $transportAssignment)
            ->with('success', 'Affectation de transport mise a jour avec succes.');
    }

    public function destroy(TransportAssignment $transportAssignment)
    {
        abort_unless($transportAssignment->school_id === app('current_school_id'), 403);

        $transportAssignment->delete();

        return redirect()->route('admin.transport.assignments.index')
            ->with('success', 'Affectation de transport supprimee avec succes.');
    }
}
