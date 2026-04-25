<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\ParentStudentFee;
use App\Models\PickupRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function schoolIdOrFail(): int
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        return (int) $schoolId;
    }

    private function allowedRoles(): array
    {
        return [
            User::ROLE_ADMIN,
            User::ROLE_DIRECTOR,
            User::ROLE_TEACHER,
            User::ROLE_PARENT,
            User::ROLE_STUDENT,
            User::ROLE_CHAUFFEUR,
            User::ROLE_SCHOOL_LIFE,
        ];
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolIdOrFail();

        $role = $request->get('role');
        $q = trim((string) $request->get('q'));

        $users = User::query()
            ->where('school_id', $schoolId)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'role', 'q'));
    }

    public function suggest(Request $request)
    {
        $schoolId = $this->schoolIdOrFail();

        $q = trim((string) $request->get('q'));
        $role = $request->get('role');

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = User::query()
            ->where('school_id', $schoolId)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->where(function ($nested) use ($q) {
                $nested->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'label' => $user->name,
                'meta' => ($user->email ?? '-') . ' - ' . ($user->phone ?? '-') . ' - ' . $user->role,
            ]);

        return response()->json($items);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => $this->allowedRoles(),
            'roleLabels' => User::roleLabels(),
            'classrooms' => $this->studentClassrooms($this->schoolIdOrFail()),
            'parents' => $this->parentUsers($this->schoolIdOrFail()),
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolIdOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:' . implode(',', $this->allowedRoles())],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'classroom_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT),
                'nullable',
                'integer',
                $this->classroomRule($schoolId),
            ],
            'parent_user_id' => ['nullable', 'integer', $this->parentRule($schoolId)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
        ]);

        DB::transaction(function () use ($data, $schoolId): void {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            if ((string) $data['role'] === User::ROLE_STUDENT) {
                $this->upsertStudentProfile($user, $data, $schoolId);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur cree.');
    }

    public function edit(User $user)
    {
        $schoolId = $this->schoolIdOrFail();
        abort_if((int) $user->school_id !== (int) $schoolId, 404);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->allowedRoles(),
            'roleLabels' => User::roleLabels(),
            'classrooms' => $this->studentClassrooms($schoolId),
            'parents' => $this->parentUsers($schoolId),
            'linkedStudent' => $this->linkedStudent($user, $schoolId),
        ]);
    }

    public function show(User $user)
    {
        $schoolId = $this->schoolIdOrFail();
        abort_if((int) $user->school_id !== (int) $schoolId, 404);

        $user->load([
            'school:id,name',
            'parentProfile',
            'studentProfile' => fn ($query) => $query
                ->where('school_id', $schoolId)
                ->with([
                    'classroom:id,name,school_id',
                    'parentUser:id,name,email,phone,school_id',
                    'transportAssignment.route:id,route_name,school_id,vehicle_id',
                    'transportAssignment.vehicle:id,name,registration_number,driver_id,school_id',
                ]),
            'children' => fn ($query) => $query
                ->where('school_id', $schoolId)
                ->with([
                    'classroom:id,name,school_id',
                    'studentUser:id,email,school_id',
                ])
                ->orderBy('full_name'),
            'subjects' => fn ($query) => $query
                ->where('subjects.school_id', $schoolId)
                ->orderBy('name'),
            'teacherClassrooms' => fn ($query) => $query
                ->where('classrooms.school_id', $schoolId)
                ->orderBy('name'),
        ]);

        $driverVehicles = collect();
        if ((string) $user->role === User::ROLE_CHAUFFEUR) {
            $driverVehicles = Vehicle::query()
                ->where('school_id', $schoolId)
                ->where('driver_id', $user->id)
                ->with(['routes' => fn ($query) => $query->orderBy('route_name')])
                ->orderByRaw('COALESCE(name, registration_number)')
                ->get();
        }

        return view('admin.users.show', [
            'user' => $user,
            'driverVehicles' => $driverVehicles,
            'linkedStudent' => $user->studentProfile,
            'linkedChildren' => $user->children,
            'teacherSubjects' => $user->subjects,
            'teacherClassrooms' => $user->teacherClassrooms,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $schoolId = $this->schoolIdOrFail();
        abort_if((int) $user->school_id !== (int) $schoolId, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:' . implode(',', $this->allowedRoles())],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'classroom_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_STUDENT),
                'nullable',
                'integer',
                $this->classroomRule($schoolId),
            ],
            'parent_user_id' => ['nullable', 'integer', $this->parentRule($schoolId)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
        ]);

        $linkedStudent = $this->linkedStudent($user, $schoolId);
        if ($linkedStudent && (string) $data['role'] !== User::ROLE_STUDENT) {
            return back()
                ->withErrors(['role' => 'Ce compte est lie a un dossier eleve. Modifiez le dossier eleve avant de changer ce role.'])
                ->withInput();
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $schoolId): void {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'password' => $data['password'] ?? $user->password,
            ]);

            if ((string) $data['role'] === User::ROLE_STUDENT) {
                $this->upsertStudentProfile($user, $data, $schoolId);
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur modifie.');
    }

    public function destroy(User $user)
    {
        $schoolId = $this->schoolIdOrFail();
        abort_if((int) $user->school_id !== (int) $schoolId, 404);

        if (Auth::id() === $user->id) {
            return back()->with('success', 'Impossible de supprimer votre propre compte.');
        }

        DB::transaction(function () use ($user, $schoolId): void {
            $linkedStudent = $this->linkedStudent($user, $schoolId);

            if ($linkedStudent) {
                $this->deleteStudentRelations($linkedStudent, $schoolId);
                $linkedStudent->delete();
            }

            $this->deleteUserRelations($user, $schoolId);
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprime.');
    }

    public function suggestParents(Request $request)
    {
        $schoolId = $this->schoolIdOrFail();

        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'parent')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'label' => $user->name . ' (' . $user->email . ')' . ($user->phone ? (' - ' . $user->phone) : ''),
            ]);

        return response()->json($items);
    }

    private function linkedStudent(User $user, int $schoolId): ?Student
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->first();
    }

    private function upsertStudentProfile(User $user, array $data, int $schoolId): void
    {
        Student::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'school_id' => $schoolId,
                'full_name' => $data['name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'parent_user_id' => $data['parent_user_id'] ?? null,
                'classroom_id' => (int) $data['classroom_id'],
            ]
        );
    }

    private function classroomRule(int $schoolId): Exists
    {
        return Rule::exists('classrooms', 'id')
            ->where(fn ($query) => $query->where('school_id', $schoolId));
    }

    private function parentRule(int $schoolId): Exists
    {
        return Rule::exists('users', 'id')
            ->where(fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_PARENT));
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

        if ($this->hasTableColumns('payments', ['school_id', 'student_id'])) {
            DB::table('payments')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('parent_student_fees', ['school_id', 'student_id'])) {
            DB::table('parent_student_fees')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('student_fee_plans', ['school_id', 'student_id'])) {
            DB::table('student_fee_plans')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('activity_participants', ['student_id'])) {
            DB::table('activity_participants')
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('grades', ['school_id', 'student_id'])) {
            DB::table('grades')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

        if ($this->hasTableColumns('attendances', ['school_id', 'student_id'])) {
            DB::table('attendances')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }

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

        if ($this->hasTableColumns('transport_assignments', ['school_id', 'student_id'])) {
            DB::table('transport_assignments')
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->delete();
        }
    }

    private function deleteUserRelations(User $user, int $schoolId): void
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

        Student::query()
            ->where('school_id', $schoolId)
            ->where('parent_user_id', $user->id)
            ->update(['parent_user_id' => null]);

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

        Vehicle::query()
            ->where('school_id', $schoolId)
            ->where('driver_id', $user->id)
            ->update(['driver_id' => null]);

        Grade::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $user->id)
            ->delete();

        Assessment::query()
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

    private function studentClassrooms(int $schoolId)
    {
        return Classroom::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function parentUsers(int $schoolId)
    {
        return User::query()
            ->where('school_id', $schoolId)
            ->where('role', User::ROLE_PARENT)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
