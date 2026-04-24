<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    use InteractsWithParentPortal;

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $childId = (int) $request->integer('child_id');
        $student = $childId > 0 ? $children->firstWhere('id', $childId) : null;

        $gradesQuery = Grade::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('student_id', $children->pluck('id'))
            ->with([
                'student:id,full_name,classroom_id',
                'student.classroom:id,name',
                'subject:id,name',
                'assessment:id,title',
                'teacher:id,name',
            ])
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
            ->latest('id');

        $grades = (clone $gradesQuery)
            ->paginate(20)
            ->withQueryString();

        $overallAverage = round((clone $gradesQuery)->get()->avg(function (Grade $grade) {
            $maxScore = max(1, (int) ($grade->max_score ?? 0));

            return ((float) $grade->score / $maxScore) * 100;
        }) ?? 0, 2);

        return view('parent.grades.index', compact('children', 'grades', 'childId', 'overallAverage'));
    }

    public function childGrades(Request $request, Student $student)
    {
        $student = $this->resolveOwnedStudent($student);
        $request->merge(['child_id' => $student->id]);

        return $this->index($request);
    }
}
