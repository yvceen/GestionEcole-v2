<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        // ✅ school context (director عندو school_id)
        $schoolId = auth()->user()?->school_id;
        if (!$schoolId) abort(403, 'School context missing.');

        // Filters
        $classroomId = $request->get('classroom_id');
        $subjectId   = $request->get('subject_id');
        $teacherId   = $request->get('teacher_id');
        $from        = $request->get('from'); // YYYY-MM-DD
        $to          = $request->get('to');   // YYYY-MM-DD

        // Dropdowns
        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get(['id','name']);
        $subjects   = Subject::where('school_id', $schoolId)->orderBy('name')->get(['id','name']);
        $teachers   = User::where('school_id', $schoolId)->where('role', 'teacher')->orderBy('name')->get(['id','name']);

        /*
        |--------------------------------------------------------------------------
        | Base query (grades)
        |--------------------------------------------------------------------------
        */
        $base = Grade::query()
            ->where('grades.school_id', $schoolId)
            ->when($classroomId, fn($q) => $q->where('grades.classroom_id', $classroomId))
            ->when($subjectId,   fn($q) => $q->where('grades.subject_id', $subjectId))
            ->when($teacherId,   fn($q) => $q->where('grades.teacher_id', $teacherId))
            // date filter (على created_at باش ما نحتاجوش join assessments)
            ->when($from, fn($q) => $q->whereDate('grades.created_at', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('grades.created_at', '<=', $to));

        /*
        |--------------------------------------------------------------------------
        | 1) Summary rows (group by classroom + subject + teacher)
        |--------------------------------------------------------------------------
        */
        $rows = (clone $base)
            ->join('classrooms', 'classrooms.id', '=', 'grades.classroom_id')
            ->join('subjects', 'subjects.id', '=', 'grades.subject_id')
            ->join('users as teachers', 'teachers.id', '=', 'grades.teacher_id')
            ->selectRaw('
                grades.classroom_id,
                grades.subject_id,
                grades.teacher_id,
                classrooms.name as classroom,
                subjects.name as subject,
                teachers.name as teacher,
                ROUND(AVG(grades.score), 2) as avg_score,
                MIN(grades.score) as min_score,
                MAX(grades.score) as max_score,
                COUNT(*) as notes_count
            ')
            ->groupBy(
                'grades.classroom_id',
                'grades.subject_id',
                'grades.teacher_id',
                'classrooms.name',
                'subjects.name',
                'teachers.name'
            )
            ->orderBy('classrooms.name')
            ->orderBy('subjects.name')
            ->orderBy('teachers.name')
            ->get()
            ->map(function ($r) {
                return [
                    'classroom' => $r->classroom,
                    'subject'   => $r->subject,
                    'teacher'   => $r->teacher,
                    'avg'       => number_format((float)$r->avg_score, 2),
                    'min'       => $r->min_score !== null ? number_format((float)$r->min_score, 2) : '—',
                    'max'       => $r->max_score !== null ? number_format((float)$r->max_score, 2) : '—',
                    'count'     => (int)$r->notes_count,
                ];
            })
            ->all();

        /*
        |--------------------------------------------------------------------------
        | 2) KPIs (total notes, moyenne globale, élèves en difficulté)
        |--------------------------------------------------------------------------
        */
        $totalNotes = (clone $base)->count();

        $globalAvg = (clone $base)
            ->selectRaw('ROUND(AVG(grades.score), 2) as avg_score')
            ->value('avg_score');

        // élèves en difficulté: moyenne < 10 (يمكن تبدل threshold)
        $difficultyThreshold = 10;

        $studentsDifficultyCount = (clone $base)
            ->select('grades.student_id', DB::raw('AVG(grades.score) as avg_score'))
            ->groupBy('grades.student_id')
            ->havingRaw('AVG(grades.score) < ?', [$difficultyThreshold])
            ->get()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3) Detailed grades list (آخر النقط + relations)
        |--------------------------------------------------------------------------
        */
        $grades = (clone $base)
            ->with([
                'student:id,full_name,classroom_id,school_id',
                'subject:id,name,school_id',
                'teacher:id,name,school_id',
                'classroom:id,name,school_id',
                'assessment:id,title,date',
            ])
            ->latest('grades.id')
            ->paginate(30)
            ->withQueryString();

        return view('director.results.index', compact(
            'grades',
            'rows',
            'classrooms',
            'subjects',
            'teachers',
            'classroomId',
            'subjectId',
            'teacherId',
            'from',
            'to',
            'totalNotes',
            'globalAvg',
            'studentsDifficultyCount',
            'difficultyThreshold'
        ));
    }
}