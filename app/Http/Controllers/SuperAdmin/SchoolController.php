<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolDomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolDomainService $schoolDomainService,
    ) {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $status = $request->get('status');

        $schools = School::query()
            ->withCount(['users', 'students'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('subdomain', 'like', "%{$q}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $schoolsCount = School::query()->count();
        $activeSchoolsCount = School::query()->where('is_active', true)->count();
        $inactiveSchoolsCount = $schoolsCount - $activeSchoolsCount;

        return view('super.schools.index', compact(
            'schools',
            'q',
            'status',
            'schoolsCount',
            'activeSchoolsCount',
            'inactiveSchoolsCount'
        ));
    }

    public function toggleActive(School $school)
    {
        $school->is_active = !$school->is_active;
        $school->save();

        return back()->with('success', $school->is_active ? 'Ecole activee.' : 'Ecole desactivee.');
    }

    public function create()
    {
        return view('super.schools.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:schools,slug'],
            'is_active' => ['nullable'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:6'],
        ]);

        return DB::transaction(function () use ($request) {
            $slug = $request->filled('slug')
                ? Str::slug((string) $request->slug)
                : Str::slug((string) $request->school_name);

            if ($slug === '') {
                $slug = 'school';
            }

            $base = $slug;
            $i = 2;
            while (School::query()->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }

            $school = School::create([
                'name' => $request->school_name,
                'slug' => $slug,
                'subdomain' => $this->schoolDomainService->generateUniqueSubdomain((string) $request->school_name),
                'is_active' => (bool) $request->is_active,
            ]);

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store("schools/{$school->id}", 'public');
                $school->update(['logo_path' => $path]);
            }

            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => bcrypt($request->admin_password),
                'role' => User::ROLE_ADMIN,
                'school_id' => $school->id,
            ]);

            return redirect()->away(rtrim($school->appUrl(), '/') . '/login?created=1');
        });
    }

    public function edit(School $school)
    {
        $admin = User::query()
            ->where('school_id', $school->id)
            ->where('role', User::ROLE_ADMIN)
            ->first();

        return view('super.schools.edit', compact('school', 'admin'));
    }

    public function update(Request $request, School $school)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:schools,slug,' . $school->id],
            'is_active' => ['nullable'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:6'],
        ]);

        return DB::transaction(function () use ($request, $school) {
            $school->update([
                'name' => $request->name,
                'slug' => $request->filled('slug') ? Str::slug((string) $request->slug) : $school->slug,
                'is_active' => (bool) $request->is_active,
            ]);

            if ($request->hasFile('logo')) {
                if ($school->logo_path) {
                    Storage::disk('public')->delete($school->logo_path);
                }

                $path = $request->file('logo')->store("schools/{$school->id}", 'public');
                $school->update(['logo_path' => $path]);
            }

            $admin = User::query()
                ->where('school_id', $school->id)
                ->where('role', User::ROLE_ADMIN)
                ->first();

            if ($admin) {
                if ($request->filled('admin_email') && $request->admin_email !== $admin->email) {
                    $request->validate([
                        'admin_email' => ['unique:users,email'],
                    ]);
                }

                $admin->name = $request->admin_name ?? $admin->name;

                if ($request->filled('admin_email')) {
                    $admin->email = $request->admin_email;
                }

                if ($request->filled('admin_password')) {
                    $admin->password = bcrypt($request->admin_password);
                }

                $admin->save();
            }

            return redirect()
                ->route('super.dashboard')
                ->with('success', 'Ecole modifiee.');
        });
    }

    public function destroy(School $school)
    {
        $schoolName = $school->name;
        $school->delete();

        return redirect()
            ->route('super.dashboard')
            ->with('success', "Ecole supprimee definitivement ({$schoolName}).");
    }
}
