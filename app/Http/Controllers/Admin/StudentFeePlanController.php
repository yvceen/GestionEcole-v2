<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Models\TransportAssignment;
use App\Models\Route as TransportRoute;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class StudentFeePlanController extends Controller
{
    public function edit(Student $student)
    {
        $student->load(['feePlan', 'parentUser', 'classroom.level']);

        // إذا ماكانش feePlan (احتياط)
        if (!$student->feePlan) {
            $student->feePlan = StudentFeePlan::create([
                'student_id' => $student->id,
                'starts_month' => 9,
            ]);
            $student->load('feePlan');
        }

        $schoolId = (int) app('current_school_id');

        $routes = TransportRoute::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get();

        $vehicles = Vehicle::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('driver')
            ->orderBy('registration_number')
            ->get();

        $transportAssignment = TransportAssignment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        return view('admin.students.fees.edit', compact('student', 'routes', 'vehicles', 'transportAssignment'));
    }

    public function update(Request $request, Student $student)
    {
        $student->load('feePlan');

        $data = $request->validate([
            'tuition_monthly' => ['required','numeric','min:0'],
            'transport_monthly' => ['nullable','numeric','min:0'],
            'canteen_monthly' => ['nullable','numeric','min:0'],
            'insurance_yearly' => ['nullable','numeric','min:0'],
            'insurance_paid' => ['nullable','boolean'],
            'starts_month' => ['required','integer','min:1','max:12'],
            'notes' => ['nullable','string','max:2000'],
            'transport_enabled' => ['nullable','boolean'],
            'transport_route_id' => ['nullable','integer','exists:routes,id'],
            'transport_vehicle_id' => ['nullable','integer','exists:vehicles,id'],
            'transport_period' => ['nullable','in:morning,evening,both'],
            'transport_pickup_point' => ['nullable','string','max:255'],
        ]);

        $student->feePlan->update([
            'tuition_monthly' => $data['tuition_monthly'],
            'transport_monthly' => $data['transport_monthly'] ?? 0,
            'canteen_monthly' => $data['canteen_monthly'] ?? 0,
            'insurance_yearly' => $data['insurance_yearly'] ?? 0,
            'insurance_paid' => (bool)($data['insurance_paid'] ?? false),
            'starts_month' => $data['starts_month'],
            'notes' => $data['notes'] ?? null,
        ]);

        $schoolId = (int) app('current_school_id');
        $transportEnabled = (bool)($data['transport_enabled'] ?? false);
        $routeId = (int)($data['transport_route_id'] ?? 0);
        $vehicleId = (int)($data['transport_vehicle_id'] ?? 0);

        $route = $routeId ? TransportRoute::find($routeId) : null;
        $vehicle = $vehicleId ? Vehicle::find($vehicleId) : null;

        if ($route && (int)$route->school_id !== $schoolId) {
            abort(403, 'Invalid route.');
        }
        if ($vehicle && (int)$vehicle->school_id !== $schoolId) {
            abort(403, 'Invalid vehicle.');
        }

        $assignment = TransportAssignment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if ($transportEnabled) {
            if (!$routeId) {
                return back()->withErrors(['transport_route_id' => 'Veuillez choisir une route.'])->withInput();
            }

            $payload = [
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'route_id' => $routeId,
                'vehicle_id' => $vehicleId ?: ($route?->vehicle_id ?? null),
                'period' => $data['transport_period'] ?? 'both',
                'pickup_point' => $data['transport_pickup_point'] ?? null,
                'assigned_date' => $assignment?->assigned_date ?? now()->toDateString(),
                'ended_date' => null,
                'is_active' => true,
                'notes' => $assignment?->notes,
            ];

            if ($assignment) {
                $assignment->update($payload);
            } else {
                TransportAssignment::create($payload);
            }
        } elseif ($assignment && $assignment->is_active) {
            $assignment->update([
                'is_active' => false,
                'ended_date' => now()->toDateString(),
            ]);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Fees updated');
    }
}
