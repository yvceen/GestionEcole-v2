<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendanceLog;
use App\Models\StaffAttendanceMapping;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $date = (string) $request->get('date', now()->toDateString());
        $department = trim((string) $request->get('department', ''));
        $q = trim((string) $request->get('q', ''));
        $mapped = (string) $request->get('mapped', '');

        $base = StaffAttendanceLog::query()
            ->where('school_id', $schoolId)
            ->whereDate('punch_date', $date);

        $logs = (clone $base)
            ->with('user:id,name,email,role,school_id')
            ->when($department !== '', fn ($query) => $query->where('department_name', $department))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('employee_code', 'like', "%{$q}%")
                        ->orWhere('employee_name', 'like', "%{$q}%")
                        ->orWhere('department_name', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($mapped === 'yes', fn ($query) => $query->whereNotNull('user_id'))
            ->when($mapped === 'no', fn ($query) => $query->whereNull('user_id'))
            ->orderByDesc('punched_at')
            ->paginate(30)
            ->withQueryString();

        $departments = StaffAttendanceLog::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('department_name')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        $stats = [
            'logs' => (clone $base)->count(),
            'employees' => (clone $base)->distinct('employee_code')->count('employee_code'),
            'mapped' => (clone $base)->whereNotNull('user_id')->distinct('employee_code')->count('employee_code'),
            'unmapped' => (clone $base)->whereNull('user_id')->distinct('employee_code')->count('employee_code'),
        ];

        return view('admin.staff-attendance.index', compact('logs', 'departments', 'stats', 'date', 'department', 'q', 'mapped'));
    }

    public function mappings(Request $request)
    {
        $schoolId = $this->schoolId();
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');

        $mappings = StaffAttendanceMapping::query()
            ->where('school_id', $schoolId)
            ->with('user:id,name,email,role,school_id')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('employee_code', 'like', "%{$q}%")
                        ->orWhere('employee_name', 'like', "%{$q}%")
                        ->orWhere('department_name', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($status === 'mapped', fn ($query) => $query->whereNotNull('user_id'))
            ->when($status === 'unmapped', fn ($query) => $query->whereNull('user_id'))
            ->orderByRaw('user_id is null desc')
            ->orderBy('employee_code')
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereNotIn('role', [User::ROLE_PARENT, User::ROLE_STUDENT, User::ROLE_SUPER_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.staff-attendance.mappings', compact('mappings', 'users', 'q', 'status'));
    }

    public function updateMapping(Request $request, StaffAttendanceMapping $mapping)
    {
        $schoolId = $this->schoolId();
        abort_unless((int) $mapping->school_id === $schoolId, 404);

        $data = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $mapping->update([
            'user_id' => $data['user_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        StaffAttendanceLog::query()
            ->where('school_id', $schoolId)
            ->where('employee_code', $mapping->employee_code)
            ->update(['user_id' => $mapping->user_id]);

        return back()->with('success', 'Correspondance mise à jour.');
    }

    protected function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
