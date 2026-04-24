<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $status = (string) $request->get('status', 'all');
        $schoolId = $this->schoolId();

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->withCount('teachers')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Subject::query()->where('school_id', $schoolId)->count(),
            'active' => Subject::query()->where('school_id', $schoolId)->where('is_active', true)->count(),
            'inactive' => Subject::query()->where('school_id', $schoolId)->where('is_active', false)->count(),
            'assigned' => Subject::query()->where('school_id', $schoolId)->whereHas('teachers')->count(),
        ];

        return view('admin.subjects.index', compact('subjects', 'q', 'status', 'stats'));
    }

    public function create()
    {
        return view('admin.subjects.create', $this->formData(new Subject()));
    }

    public function store(StoreSubjectRequest $request)
    {
        $data = $request->validated();
        $schoolId = $this->schoolId();
        $teacherIds = $this->validatedTeacherIds($request, $schoolId);

        DB::transaction(function () use ($data, $schoolId, $teacherIds, $request): void {
            $subject = Subject::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncTeachers($subject, $teacherIds, (int) $request->user()->id, $schoolId);
        });

        return redirect()->route('admin.subjects.index')->with('success', 'Matiere creee avec succes.');
    }

    public function edit(Subject $subject)
    {
        $subject = $this->resolveSchoolSubject($subject);

        return view('admin.subjects.edit', $this->formData($subject));
    }

    public function update(StoreSubjectRequest $request, Subject $subject)
    {
        $subject = $this->resolveSchoolSubject($subject);
        $data = $request->validated();
        $schoolId = $this->schoolId();
        $teacherIds = $this->validatedTeacherIds($request, $schoolId);

        DB::transaction(function () use ($subject, $data, $teacherIds, $request, $schoolId): void {
            $subject->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $this->syncTeachers($subject, $teacherIds, (int) $request->user()->id, $schoolId);
        });

        return redirect()->route('admin.subjects.index')->with('success', 'Matiere modifiee.');
    }

    public function destroy(Subject $subject)
    {
        $subject = $this->resolveSchoolSubject($subject);

        if ($subject->teachers()->exists() || $subject->assessments()->exists() || $subject->grades()->exists()) {
            return redirect()
                ->route('admin.subjects.index')
                ->with('error', 'Cette matiere est encore utilisee dans les affectations ou les notes.');
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Matiere supprimee.');
    }

    private function schoolId(): int
    {
        return app()->bound('current_school_id') && app('current_school_id')
            ? (int) app('current_school_id')
            : (int) auth()->user()?->school_id;
    }

    private function resolveSchoolSubject(Subject $subject): Subject
    {
        abort_unless((int) $subject->school_id === $this->schoolId(), 404);

        return $subject->load('teachers:id,name');
    }

    private function formData(Subject $subject): array
    {
        $schoolId = $this->schoolId();

        return [
            'subject' => $subject->loadMissing('teachers:id,name'),
            'teachers' => User::query()
                ->where('school_id', $schoolId)
                ->where('role', User::ROLE_TEACHER)
                ->orderBy('name')
                ->get(['id', 'name']),
            'assignedTeacherIds' => $subject->exists
                ? $subject->teachers->pluck('id')->map(fn ($id) => (int) $id)->all()
                : collect(old('teacher_ids', []))->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function validatedTeacherIds(Request $request, int $schoolId): array
    {
        $validated = $request->validate([
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('role', User::ROLE_TEACHER)),
            ],
        ]);

        return collect($validated['teacher_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function syncTeachers(Subject $subject, array $teacherIds, int $assignedByUserId, int $schoolId): void
    {
        $syncData = collect($teacherIds)->mapWithKeys(fn (int $teacherId) => [
            $teacherId => [
                'school_id' => $schoolId,
                'assigned_by_user_id' => $assignedByUserId,
            ],
        ])->all();

        $subject->teachers()->sync($syncData);
    }
}
