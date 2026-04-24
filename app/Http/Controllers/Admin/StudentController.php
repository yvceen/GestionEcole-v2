<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\ParentStudentFee;
use App\Models\Payment;
use App\Models\Route as TransportRoute;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $classroomId = $request->get('classroom');
        $q = trim((string) $request->get('q'));
        $status = (string) $request->get('status', 'active');
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }

        $classrooms = Classroom::with('level')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $studentsQuery = Student::with([
            'parentUser:id,name,phone',
            'classroom.level',
            'feePlan',
            'transportAssignment.route',
            'transportAssignment.vehicle.driver',
        ])
            ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status === 'archived', fn ($query) => $query->archived())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                            $parentQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('id');

        $students = $studentsQuery->paginate(20)->withQueryString();

        return view('admin.students.index', compact('students', 'classrooms', 'classroomId', 'q', 'status'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = Student::with(['parentUser:id,name,phone', 'classroom:id,name'])
            ->active()
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhereHas('parentUser', function ($parentQuery) use ($q) {
                        $parentQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            })
            ->orderBy('full_name')
            ->limit(8)
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'label' => $student->full_name,
                'meta' => ($student->classroom?->name ?? '-') .
                    ' - Parent: ' . ($student->parentUser?->name ?? '-') .
                    ' - ' . ($student->parentUser?->phone ?? '-'),
            ]);

        return response()->json($items);
    }

    public function create()
    {
        $classrooms = Classroom::with('level')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $parents = User::where('role', 'parent')->orderBy('name')->get();

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

        return view('admin.students.create', compact('classrooms', 'parents', 'routes', 'vehicles'));
    }

    public function store(StoreStudentRequest $request)
    {
        $data = $request->validated();

        $schoolId = (int) app('current_school_id');
        $parentId = (int) ($data['existing_parent_user_id'] ?? $data['parent_user_id'] ?? 0);
        $createParent = (bool) ($data['create_parent_account'] ?? false);
        $createStudentAccount = (bool) ($data['create_student_account'] ?? false);

        if ($createParent) {
            if (empty($data['parent_email']) || empty($data['parent_password'])) {
                return back()->withErrors(['parent_email' => 'Email et mot de passe parent sont obligatoires.'])->withInput();
            }

            if (User::where('email', $data['parent_email'])->exists()) {
                return back()->withErrors(['parent_email' => 'Cet email parent existe deja.'])->withInput();
            }
        } elseif ($parentId <= 0) {
            return back()->withErrors(['existing_parent_user_id' => 'Selectionnez un parent existant ou creez-en un.'])->withInput();
        }

        if ($createStudentAccount) {
            if (empty($data['student_account_email']) || empty($data['student_account_password'])) {
                return back()->withErrors(['student_account_email' => 'Email et mot de passe eleve sont obligatoires.'])->withInput();
            }

            if (User::where('email', $data['student_account_email'])->exists()) {
                return back()->withErrors(['student_account_email' => 'Cet email eleve existe deja.'])->withInput();
            }
        }

        $transportEnabled = (bool) ($data['transport_enabled'] ?? false);
        $routeId = (int) ($data['transport_route_id'] ?? 0);
        $vehicleId = (int) ($data['transport_vehicle_id'] ?? 0);
        $route = null;

        if ($transportEnabled) {
            $route = $routeId ? TransportRoute::find($routeId) : null;
            $vehicle = $vehicleId ? Vehicle::find($vehicleId) : null;

            if (!$route || (int) $route->school_id !== $schoolId) {
                return back()->withErrors(['transport_route_id' => 'Veuillez choisir une route valide.'])->withInput();
            }
            if ($vehicle && (int) $vehicle->school_id !== $schoolId) {
                return back()->withErrors(['transport_vehicle_id' => 'Vehicule invalide.'])->withInput();
            }
        }

        DB::transaction(function () use (
            $data,
            $schoolId,
            $createParent,
            $parentId,
            $createStudentAccount,
            $transportEnabled,
            $routeId,
            $vehicleId,
            $route
        ): void {
            $resolvedParentId = $parentId;

            if ($createParent) {
                $parentUser = User::create([
                    'school_id' => $schoolId,
                    'name' => $data['parent_name'] ?? ('Parent de ' . $data['full_name']),
                    'email' => $data['parent_email'],
                    'password' => Hash::make($data['parent_password']),
                    'role' => User::ROLE_PARENT,
                    'is_active' => true,
                ]);
                $resolvedParentId = (int) $parentUser->id;
            }

            $studentUserId = null;
            if ($createStudentAccount) {
                $studentUser = User::create([
                    'school_id' => $schoolId,
                    'name' => $data['full_name'],
                    'email' => $data['student_account_email'],
                    'password' => Hash::make($data['student_account_password']),
                    'role' => User::ROLE_STUDENT,
                    'is_active' => true,
                ]);
                $studentUserId = (int) $studentUser->id;
            }

            $student = Student::create([
                'full_name' => $data['full_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'parent_user_id' => $resolvedParentId ?: null,
                'user_id' => $studentUserId,
                'classroom_id' => $data['classroom_id'],
            ]);

            \App\Models\StudentFeePlan::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'school_id' => $schoolId,
                    'tuition_monthly' => $data['tuition_monthly'],
                    'canteen_monthly' => $data['canteen_monthly'] ?? 0,
                    'transport_monthly' => $data['transport_monthly'] ?? 0,
                    'insurance_yearly' => $data['insurance_yearly'] ?? 0,
                    'insurance_paid' => (bool) ($data['insurance_paid'] ?? false),
                    'starts_month' => 9,
                ]
            );

            if ($transportEnabled) {
                TransportAssignment::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'route_id' => $routeId,
                    'vehicle_id' => $vehicleId ?: ($route?->vehicle_id ?? null),
                    'period' => $data['transport_period'] ?? 'both',
                    'pickup_point' => $data['transport_pickup_point'] ?? null,
                    'assigned_date' => now()->toDateString(),
                    'ended_date' => null,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.students.index')->with('success', 'Eleve ajoute.');
    }

    public function edit(Student $student)
    {
        $student->load(['parentUser', 'classroom.level', 'feePlan', 'transportAssignment.route', 'transportAssignment.vehicle.driver']);

        $classrooms = Classroom::with('level')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $parents = User::where('role', 'parent')->orderBy('name')->get();

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

        $fee = ParentStudentFee::where('parent_user_id', $student->parent_user_id)
            ->where('student_id', $student->id)
            ->first();

        return view('admin.students.edit', compact('student', 'classrooms', 'parents', 'fee', 'routes', 'vehicles', 'transportAssignment'));
    }

    public function show(Student $student)
    {
        return redirect()->route('admin.students.edit', $student);
    }

    public function archive(Request $request, Student $student)
    {
        $schoolId = (int) app('current_school_id');
        abort_unless((int) $student->school_id === $schoolId, 404);

        $data = $request->validate([
            'archive_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$student->archived_at) {
            $student->update([
                'archived_at' => now(),
                'archived_by_user_id' => auth()->id(),
                'archive_reason' => trim((string) ($data['archive_reason'] ?? '')) ?: null,
            ]);
        }

        return back()->with('success', 'Eleve archive. Son historique reste conserve.');
    }

    public function reactivate(Student $student)
    {
        $schoolId = (int) app('current_school_id');
        abort_unless((int) $student->school_id === $schoolId, 404);

        $student->update([
            'archived_at' => null,
            'archived_by_user_id' => null,
            'archive_reason' => null,
        ]);

        return back()->with('success', 'Eleve reactive.');
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $data = $request->validated();
        $schoolId = (int) app('current_school_id');

        $student->update([
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'parent_user_id' => $data['parent_user_id'] ?? null,
            'classroom_id' => $data['classroom_id'],
        ]);

        $student->feePlan()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'school_id' => $schoolId,
                'tuition_monthly' => $data['tuition_monthly'],
                'canteen_monthly' => $data['canteen_monthly'] ?? 0,
                'transport_monthly' => $data['transport_monthly'] ?? 0,
                'insurance_yearly' => $data['insurance_yearly'] ?? 0,
                'insurance_paid' => (bool) ($data['insurance_paid'] ?? false),
                'starts_month' => 9,
            ]
        );

        $transportEnabled = (bool) ($data['transport_enabled'] ?? false);
        $routeId = (int) ($data['transport_route_id'] ?? 0);
        $vehicleId = (int) ($data['transport_vehicle_id'] ?? 0);

        $assignment = TransportAssignment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if ($transportEnabled) {
            $route = $routeId ? TransportRoute::find($routeId) : null;
            $vehicle = $vehicleId ? Vehicle::find($vehicleId) : null;

            if (!$route || (int) $route->school_id !== $schoolId) {
                return back()->withErrors(['transport_route_id' => 'Veuillez choisir une route valide.'])->withInput();
            }
            if ($vehicle && (int) $vehicle->school_id !== $schoolId) {
                return back()->withErrors(['transport_vehicle_id' => 'Vehicule invalide.'])->withInput();
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

        return redirect()->route('admin.students.index')->with('success', 'Eleve modifie.');
    }

    public function destroy(Student $student)
    {
        $schoolId = (int) app('current_school_id');
        abort_unless((int) $student->school_id === $schoolId, 404);

        $blockingReasons = [];

        if ($student->user_id) {
            $blockingReasons[] = 'un compte eleve lie';
        }

        if (Grade::where('school_id', $schoolId)->where('student_id', $student->id)->exists()) {
            $blockingReasons[] = 'des notes';
        }

        if (Attendance::where('school_id', $schoolId)->where('student_id', $student->id)->exists()) {
            $blockingReasons[] = 'des presences/absences';
        }

        if (Payment::where('school_id', $schoolId)->where('student_id', $student->id)->exists()) {
            $blockingReasons[] = 'des paiements';
        }

        if (TransportAssignment::where('school_id', $schoolId)->where('student_id', $student->id)->exists()) {
            $blockingReasons[] = 'un dossier transport';
        }

        if (!empty($blockingReasons)) {
            return back()->withErrors([
                'delete_student' => 'Suppression bloquee: cet eleve possede ' . implode(', ', $blockingReasons) . '. Archivez ou nettoyez ces donnees avant suppression.',
            ]);
        }

        DB::transaction(function () use ($schoolId, $student): void {
            StudentFeePlan::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();

            ParentStudentFee::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();

            $student->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Eleve supprime.');
    }
}
