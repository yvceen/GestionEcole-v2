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
use App\Models\PickupRequest;
use App\Models\Route as TransportRoute;
use App\Models\Student;
use App\Models\StudentAcademicYear;
use App\Models\StudentFeePlan;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
    ) {
    }

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
            $academicYearId = $this->academicYears->requireCurrentYearForSchool($schoolId)->id;
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
                ['student_id' => $student->id, 'academic_year_id' => $academicYearId],
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
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
                    'academic_year_id' => $academicYearId,
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

            StudentAcademicYear::query()->updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'classroom_id' => $student->classroom_id,
                    'status' => StudentAcademicYear::STATUS_ENROLLED,
                ]
            );
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
        $academicYearId = $this->academicYears->requireCurrentYearForSchool($schoolId)->id;

        $student->update([
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'parent_user_id' => $data['parent_user_id'] ?? null,
            'classroom_id' => $data['classroom_id'],
        ]);

        $student->feePlan()->updateOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $academicYearId],
            [
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
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
            ->where(function ($query) use ($academicYearId) {
                if (Schema::hasColumn('transport_assignments', 'academic_year_id')) {
                    $query->where('academic_year_id', $academicYearId)
                        ->orWhereNull('academic_year_id');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
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
                'academic_year_id' => $academicYearId,
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

        StudentAcademicYear::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
            ],
            [
                'classroom_id' => (int) $data['classroom_id'],
                'status' => $student->archived_at ? StudentAcademicYear::STATUS_LEFT : StudentAcademicYear::STATUS_ENROLLED,
            ]
        );

        return redirect()->route('admin.students.index')->with('success', 'Eleve modifie.');
    }

    public function destroy(Student $student)
    {
        $schoolId = (int) app('current_school_id');
        abort_unless((int) $student->school_id === $schoolId, 404);

        DB::transaction(function () use ($schoolId, $student): void {
            $linkedUser = $student->studentUser()->where('school_id', $schoolId)->first();

            $this->deleteStudentRelations($student, $schoolId);

            if ($linkedUser) {
                $this->deleteUserRelations($linkedUser, $schoolId, $student->id);
                $linkedUser->delete();
            }

            $student->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Eleve supprime.');
    }

    private function deleteStudentRelations(Student $student, int $schoolId): void
    {
        if ($this->hasTableColumns('homework_submissions', ['id', 'student_id'])) {
            $submissionIds = DB::table('homework_submissions')
                ->where('student_id', $student->id)
                ->pluck('id');

            if (
                $submissionIds->isNotEmpty()
                && $this->hasTableColumns('homework_submission_files', ['submission_id'])
            ) {
                DB::table('homework_submission_files')
                    ->whereIn('submission_id', $submissionIds->all())
                    ->delete();
            }

            DB::table('homework_submissions')
                ->where('student_id', $student->id)
                ->delete();
        }

        PickupRequest::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        if ($this->hasTableColumns('appointments', ['school_id', 'student_id'])) {
            DB::table('appointments')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('payment_items', ['student_id'])) {
            DB::table('payment_items')
                ->where('student_id', $student->id)
                ->delete();
        }

        Payment::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        ParentStudentFee::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        StudentFeePlan::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        if ($this->hasTableColumns('activity_participants', ['student_id'])) {
            DB::table('activity_participants')
                ->where('student_id', $student->id)
                ->delete();
        }

        Grade::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        if ($this->hasTableColumns('student_notes', ['school_id', 'student_id'])) {
            DB::table('student_notes')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('student_behaviors', ['school_id', 'student_id'])) {
            DB::table('student_behaviors')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('support_plans', ['school_id', 'student_id'])) {
            DB::table('support_plans')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('transport_logs', ['school_id', 'student_id'])) {
            DB::table('transport_logs')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        TransportAssignment::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();
    }

    private function deleteUserRelations(User $user, int $schoolId, ?int $linkedStudentId = null): void
    {
        $user->deviceTokens()->delete();
        $user->notifications()->delete();
        $user->tokens()->delete();
        $user->parentProfile()?->delete();

        if ($this->hasTableColumns('messages', ['school_id', 'sender_id'])) {
            DB::table('messages')
                ->where('school_id', $schoolId)
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id);

                    if (Schema::hasColumn('messages', 'target_type') && Schema::hasColumn('messages', 'target_id')) {
                        $query->orWhere(function ($nested) use ($user) {
                            $nested->where('target_type', 'user')
                                ->where('target_id', $user->id);
                        });
                    }

                    if (Schema::hasColumn('messages', 'recipient_type') && Schema::hasColumn('messages', 'recipient_id')) {
                        $query->orWhere(function ($nested) use ($user) {
                            $nested->where('recipient_type', 'user')
                                ->where('recipient_id', $user->id);
                        });
                    }
                })
                ->delete();
        }

        $this->removeUserFromMessageTargets((int) $user->id, $schoolId);

        if ($this->hasTableColumns('appointments', ['school_id', 'parent_user_id'])) {
            DB::table('appointments')
                ->where('school_id', $schoolId)
                ->where('parent_user_id', $user->id)
                ->delete();
        }

        PickupRequest::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', $user->id)
            ->delete();

        ParentStudentFee::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', $user->id)
            ->delete();

        if ($linkedStudentId === null) {
            Student::query()
                ->where('school_id', $schoolId)
                ->where('parent_user_id', $user->id)
                ->update(['parent_user_id' => null]);
        }

        if ($this->hasTableColumns('classroom_teacher', ['teacher_id'])) {
            DB::table('classroom_teacher')
                ->where('teacher_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('teacher_subjects', ['teacher_id'])) {
            DB::table('teacher_subjects')
                ->where('teacher_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('transport_logs', ['school_id', 'recorded_by_user_id'])) {
            DB::table('transport_logs')
                ->where('school_id', $schoolId)
                ->where('recorded_by_user_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('student_notes', ['school_id', 'created_by_user_id'])) {
            DB::table('student_notes')
                ->where('school_id', $schoolId)
                ->where('created_by_user_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('student_behaviors', ['school_id', 'created_by_user_id'])) {
            DB::table('student_behaviors')
                ->where('school_id', $schoolId)
                ->where('created_by_user_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('support_plans', ['school_id', 'created_by_user_id'])) {
            DB::table('support_plans')
                ->where('school_id', $schoolId)
                ->where('created_by_user_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('vehicles', ['school_id', 'driver_id'])) {
            DB::table('vehicles')
                ->where('school_id', $schoolId)
                ->where('driver_id', $user->id)
                ->update(['driver_id' => null]);
        }

        DB::table('grades')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $user->id)
            ->delete();

        DB::table('assessments')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $user->id)
            ->delete();

        if ($this->hasTableColumns('timetables', ['teacher_id'])) {
            DB::table('timetables')
                ->where('teacher_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('teacher_pedagogical_resources', ['teacher_id'])) {
            DB::table('teacher_pedagogical_resources')
                ->where('teacher_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('activity_reports', ['created_by_user_id'])) {
            DB::table('activity_reports')
                ->where('created_by_user_id', $user->id)
                ->delete();
        }

        if ($this->hasTableColumns('homework_user_views', ['user_id'])) {
            DB::table('homework_user_views')
                ->where('user_id', $user->id)
                ->delete();
        }
    }

    private function removeUserFromMessageTargets(int $userId, int $schoolId): void
    {
        if (!$this->hasTableColumns('messages', ['id', 'school_id', 'target_user_ids'])) {
            return;
        }

        $messagesQuery = DB::table('messages')
            ->select('id', 'target_user_ids')
            ->where('school_id', $schoolId)
            ->whereNotNull('target_user_ids');

        $hasTargetType = Schema::hasColumn('messages', 'target_type');
        $hasTargetId = Schema::hasColumn('messages', 'target_id');

        if ($hasTargetType) {
            $messagesQuery->addSelect('target_type');
        }

        if ($hasTargetId) {
            $messagesQuery->addSelect('target_id');
        }

        $messages = $messagesQuery->get();

        foreach ($messages as $message) {
            $targetUserIds = json_decode((string) $message->target_user_ids, true);
            if (!is_array($targetUserIds)) {
                continue;
            }

            $filteredIds = array_values(array_filter(
                array_map('intval', $targetUserIds),
                fn (int $id) => $id !== $userId
            ));

            if (count($filteredIds) === count($targetUserIds)) {
                continue;
            }

            $isDirectTarget = $hasTargetType
                && $hasTargetId
                && (string) ($message->target_type ?? '') === 'user'
                && (int) ($message->target_id ?? 0) === $userId;

            if ($filteredIds === [] && $isDirectTarget) {
                DB::table('messages')->where('id', $message->id)->delete();
                continue;
            }

            $updates = [
                'target_user_ids' => $filteredIds === [] ? null : json_encode($filteredIds),
            ];

            if ($hasTargetId) {
                $updates['target_id'] = $isDirectTarget ? ($filteredIds[0] ?? null) : $message->target_id;
            }

            if ($hasTargetType) {
                $updates['target_type'] = $filteredIds === [] && $isDirectTarget ? null : $message->target_type;
            }

            DB::table('messages')
                ->where('id', $message->id)
                ->update($updates);
        }
    }

    private function hasTableColumns(string $table, array $columns = []): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
