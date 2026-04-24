<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    use InteractsWithStudentPortal;

    public function index(Request $request)
    {
        $student = $this->currentStudent(['classroom.level']);
        $schoolId = $this->schoolIdOrFail();
        $subjectId = max(0, (int) $request->integer('subject_id'));

        $baseQuery = Grade::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with([
                'subject:id,name',
                'assessment:id,title',
                'teacher:id,name',
            ]);

        $subjects = (clone $baseQuery)
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $gradesQuery = (clone $baseQuery)
            ->when($subjectId > 0, fn ($query) => $query->where('subject_id', $subjectId))
            ->latest('id');

        $gradeRows = (clone $gradesQuery)
            ->paginate(20)
            ->withQueryString();

        $gradeCollection = (clone $gradesQuery)->get();

        $subjectAverages = $gradeCollection
            ->groupBy(fn (Grade $grade) => (int) $grade->subject_id)
            ->map(function ($rows) {
                $subject = $rows->first()?->subject;
                $averageScore = round($rows->avg(fn (Grade $grade) => (float) $grade->score), 2);
                $averagePercent = round($rows->avg(function (Grade $grade) {
                    $maxScore = max(1, (int) ($grade->max_score ?? 0));

                    return ((float) $grade->score / $maxScore) * 100;
                }), 2);

                return [
                    'subject' => $subject?->name ?? 'Matiere',
                    'average_score' => $averageScore,
                    'average_percent' => $averagePercent,
                    'count' => $rows->count(),
                ];
            })
            ->sortBy('subject')
            ->values();

        $overallAverage = round($gradeCollection->avg(function (Grade $grade) {
            $maxScore = max(1, (int) ($grade->max_score ?? 0));

            return ((float) $grade->score / $maxScore) * 100;
        }) ?? 0, 2);

        return view('student.grades.index', compact(
            'student',
            'gradeRows',
            'subjects',
            'subjectId',
            'subjectAverages',
            'overallAverage'
        ));
    }
}
