<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Classroom;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\ActivityParticipationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
        private readonly ActivityParticipationService $participants,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $classroomId = $request->integer('classroom_id');
        $teacherId = $request->integer('teacher_id');
        $type = trim((string) $request->get('type', ''));

        if (!in_array($type, Activity::types(), true)) {
            $type = '';
        }

        $requestedAcademicYearId = $request->integer('academic_year_id') ?: null;

        $activities = $this->academicYears->applyYearScope(Activity::query(), $schoolId, $requestedAcademicYearId)
            ->where('school_id', $schoolId)
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($teacherId > 0, fn ($query) => $query->where('teacher_id', $teacherId))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->with(['classroom:id,name', 'teacher:id,name'])
            ->withCount('participants', 'reports')
            ->orderBy('start_date')
            ->paginate(15)
            ->withQueryString();

        $classrooms = Classroom::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);
        $teachers = User::query()->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)->orderBy('name')->get(['id', 'name']);

        $currentAcademicYear = $this->academicYears->resolveYearForSchool($schoolId, $requestedAcademicYearId);

        return view('admin.activities.index', compact('activities', 'classrooms', 'teachers', 'classroomId', 'teacherId', 'type', 'currentAcademicYear') + [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('admin.activities.create', $this->formData(new Activity()) + [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canManage(), 403);
        $data = $this->validatedData($request);
        $data['school_id'] = $this->schoolId();
        $data['academic_year_id'] = $this->academicYears->requireCurrentYearForSchool($data['school_id'])->id;
        $data['color'] = $data['color'] ?: Activity::defaultColorForType((string) $data['type']);

        $activity = Activity::create($data);
        $this->participants->ensureParticipants($activity);

        return redirect()->route($this->routePrefix() . '.index')->with('success', 'Activite creee avec succes.');
    }

    public function edit(Activity $activity)
    {
        abort_unless($this->canManage(), 403);
        $activity = $this->resolveActivity($activity);

        return view('admin.activities.edit', $this->formData($activity) + [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ]);
    }

    public function update(Request $request, Activity $activity)
    {
        abort_unless($this->canManage(), 403);
        $activity = $this->resolveActivity($activity);
        $data = $this->validatedData($request);
        $data['color'] = $data['color'] ?: Activity::defaultColorForType((string) $data['type']);

        $activity->update($data);
        $this->participants->ensureParticipants($activity->fresh());

        return redirect()->route($this->routePrefix() . '.index')->with('success', 'Activite mise a jour.');
    }

    public function destroy(Activity $activity)
    {
        abort_unless($this->canManage(), 403);
        $this->resolveActivity($activity)->delete();

        return redirect()->route($this->routePrefix() . '.index')->with('success', 'Activite supprimee.');
    }

    protected function routePrefix(): string
    {
        return 'admin.activities';
    }

    protected function layoutComponent(): string
    {
        return 'admin-layout';
    }

    protected function canManage(): bool
    {
        return true;
    }

    private function formData(Activity $activity): array
    {
        $schoolId = $this->schoolId();

        return [
            'activity' => $activity,
            'types' => Activity::types(),
            'classrooms' => Classroom::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'teachers' => User::query()->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function validatedData(Request $request): array
    {
        $schoolId = $this->schoolId();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', Activity::types())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER))],
            'color' => ['nullable', 'string', 'max:24'],
        ]);
    }

    private function resolveActivity(Activity $activity): Activity
    {
        abort_unless((int) $activity->school_id === $this->schoolId(), 404);

        return $activity;
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
