<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentPromotionController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $years = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_current')
            ->orderByDesc('starts_at')
            ->get();

        $sourceYear = $years->firstWhere('id', $request->integer('source_year_id'))
            ?? $this->academicYears->requireCurrentYearForSchool($schoolId);
        $targetYear = $years->firstWhere('id', $request->integer('target_year_id'))
            ?? $years->firstWhere('id', $years->firstWhere('id', $sourceYear->id)?->id);

        if ($targetYear && (int) $targetYear->id === (int) $sourceYear->id) {
            $targetYear = $years->firstWhere('id', $years->where('id', '!=', $sourceYear->id)->first()?->id);
        }

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name')
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $placements = StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', (int) $sourceYear->id)
            ->with(['student.parentUser:id,name', 'classroom:id,name,level_id'])
            ->orderBy('classroom_id')
            ->orderBy('student_id')
            ->get()
            ->groupBy('classroom_id');

        if ($placements->isEmpty()) {
            $fallback = Student::query()
                ->where('school_id', $schoolId)
                ->active()
                ->with(['parentUser:id,name', 'classroom:id,name,level_id'])
                ->orderBy('classroom_id')
                ->orderBy('full_name')
                ->get()
                ->groupBy('classroom_id')
                ->map(function (Collection $students) use ($schoolId, $sourceYear) {
                    return $students->map(function (Student $student) use ($schoolId, $sourceYear) {
                        return (new StudentAcademicYear([
                            'school_id' => $schoolId,
                            'student_id' => $student->id,
                            'academic_year_id' => $sourceYear->id,
                            'classroom_id' => $student->classroom_id,
                            'status' => StudentAcademicYear::STATUS_ENROLLED,
                        ]))->setRelation('student', $student)
                            ->setRelation('classroom', $student->classroom);
                    });
                });

            $placements = $fallback;
        }

        return view('admin.academic-years.promotions', [
            'years' => $years,
            'sourceYear' => $sourceYear,
            'targetYear' => $targetYear,
            'classrooms' => $classrooms,
            'placements' => $placements,
            'statuses' => StudentAcademicYear::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();

        $data = $request->validate([
            'source_year_id' => ['required', Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'target_year_id' => ['required', 'different:source_year_id', Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'promotions' => ['required', 'array'],
            'promotions.*.status' => ['required', Rule::in(StudentAcademicYear::statuses())],
            'promotions.*.classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
        ]);

        $targetYear = AcademicYear::query()->where('school_id', $schoolId)->findOrFail((int) $data['target_year_id']);
        $sourcePlacements = StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', (int) $data['source_year_id'])
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($data, $schoolId, $sourcePlacements, $targetYear): void {
            foreach ($data['promotions'] as $studentId => $promotion) {
                $studentId = (int) $studentId;
                if ($studentId <= 0 || !in_array($studentId, $sourcePlacements, true)) {
                    continue;
                }

                $status = (string) ($promotion['status'] ?? StudentAcademicYear::STATUS_ENROLLED);
                $classroomId = !empty($promotion['classroom_id']) ? (int) $promotion['classroom_id'] : null;

                if (in_array($status, [StudentAcademicYear::STATUS_LEFT, StudentAcademicYear::STATUS_TRANSFERRED], true)) {
                    $classroomId = null;
                }

                StudentAcademicYear::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $studentId,
                        'academic_year_id' => (int) $data['target_year_id'],
                    ],
                    [
                        'classroom_id' => $classroomId,
                        'status' => $status,
                    ]
                );

                if ($targetYear->is_current && $classroomId) {
                    Student::query()
                        ->where('school_id', $schoolId)
                        ->whereKey($studentId)
                        ->update(['classroom_id' => $classroomId]);
                }
            }
        });

        return redirect()
            ->route('admin.academic-promotions.index', [
                'source_year_id' => $data['source_year_id'],
                'target_year_id' => $data['target_year_id'],
            ])
            ->with('success', 'Placements de la nouvelle annee scolaire enregistres.');
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
