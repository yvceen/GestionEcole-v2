<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Services\AcademicYearService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CourseController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $q = trim((string) $request->get('q', ''));

        $academicYearId = $this->academicYears->resolveYearForSchool($schoolId, $request->integer('academic_year_id') ?: null)->id;

        $courses = $this->academicYears->applyYearScope(Course::query(), $schoolId, $request->integer('academic_year_id') ?: null)
            ->where('school_id', $schoolId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%");
            })
            ->with(['classroom.level', 'teacher'])
            ->when($this->hasStatusColumn(), function ($query) {
                $query->orderByRaw("CASE
                    WHEN LOWER(COALESCE(status, '')) IN ('pending','draft','') THEN 0
                    WHEN LOWER(COALESCE(status, '')) IN ('approved','confirmed') THEN 1
                    ELSE 2
                END");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses.index', compact('courses', 'q', 'academicYearId'));
    }

    public function create()
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name,school_id')
            ->orderBy('name')
            ->get();

        return view('admin.courses.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $classroomExists = Classroom::query()
            ->where('id', (int) $data['classroom_id'])
            ->where('school_id', $schoolId)
            ->exists();

        if (!$classroomExists) {
            return back()->withErrors([
                'classroom_id' => 'La classe selectionnee est invalide pour cette ecole.',
            ])->withInput();
        }

        Course::create([
            'school_id' => $schoolId,
            'academic_year_id' => $this->academicYears->requireCurrentYearForSchool($schoolId)->id,
            'classroom_id' => (int) $data['classroom_id'],
            'teacher_id' => auth()->id(),
            'created_by_user_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'published_at' => now(),
        ]);

        return redirect()->route('admin.courses.index')->with('success', 'Cours ajoute.');
    }

    public function approve(Course $course)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        abort_unless((int) ($course->school_id ?? 0) === $schoolId, 404);

        $payload = [];
        if ($this->hasColumn('status')) {
            $payload['status'] = 'approved';
        }
        if ($this->hasColumn('approved_at')) {
            $payload['approved_at'] = now();
        }
        if ($this->hasColumn('approved_by')) {
            $payload['approved_by'] = auth()->id();
        }
        if ($this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = null;
        }
        if ($this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = null;
        }
        if ($payload !== []) {
            $course->update($payload);
        }

        try {
            $service = app(NotificationService::class);
            $parentIds = $service->parentIdsByClassroom((int) $course->classroom_id, $schoolId);
            $studentIds = $service->studentUserIdsByClassroom((int) $course->classroom_id, $schoolId);
            $teacherIds = array_filter([(int) ($course->teacher_id ?? 0)]);

            $service->notifyUsers(
                array_values(array_unique(array_merge($parentIds, $studentIds, $teacherIds))),
                'course',
                'Cours approuve',
                (string) ($course->title ?? 'Un cours a ete publie pour votre classe.'),
                ['course_id' => (int) $course->id, 'classroom_id' => (int) $course->classroom_id]
            );
        } catch (\Throwable $e) {
            Log::warning('Course approval notification failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Cours approuve.');
    }

    public function reject(Course $course)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        abort_unless((int) ($course->school_id ?? 0) === $schoolId, 404);

        $payload = [];
        if ($this->hasColumn('status')) {
            $payload['status'] = 'rejected';
        }
        if ($this->hasColumn('rejected_at')) {
            $payload['rejected_at'] = now();
        }
        if ($this->hasColumn('rejected_by')) {
            $payload['rejected_by'] = auth()->id();
        }
        if ($this->hasColumn('approved_at')) {
            $payload['approved_at'] = null;
        }
        if ($this->hasColumn('approved_by')) {
            $payload['approved_by'] = null;
        }
        if ($payload !== []) {
            $course->update($payload);
        }

        $teacherId = (int) ($course->teacher_id ?? 0);
        if ($teacherId > 0) {
            try {
                app(NotificationService::class)->notifyUsers(
                    [$teacherId],
                    'course',
                    'Cours refuse',
                    (string) ($course->title ?? 'Votre cours a ete refuse.'),
                    ['url' => route('teacher.courses.index'), 'course_id' => (int) $course->id]
                );
            } catch (\Throwable $e) {
                Log::warning('Course rejection notification failed', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Cours rejete.');
    }

    private function hasColumn(string $column): bool
    {
        return Schema::hasTable('courses') && Schema::hasColumn('courses', $column);
    }

    private function hasStatusColumn(): bool
    {
        return $this->hasColumn('status');
    }
}
