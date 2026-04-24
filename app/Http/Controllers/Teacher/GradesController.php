<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GradesController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user();

        $classroomId = $request->get('classroom_id');
        $subjectId = $request->get('subject_id');
        $assessmentId = $request->get('assessment_id');

        $classrooms = $teacher->teacherClassrooms()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('classrooms.school_id', $teacher->school_id)
            ->orderBy('name')
            ->get(['classrooms.id', 'classrooms.name']);

        $subjects = $teacher->subjects()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('subjects.school_id', $teacher->school_id)
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name']);

        $assessmentsQuery = Assessment::where('teacher_id', $teacher->id)
            ->where('school_id', $teacher->school_id)
            ->orderByDesc('date');

        if ($classroomId) {
            $assessmentsQuery->where('classroom_id', $classroomId);
        }
        if ($subjectId) {
            $assessmentsQuery->where('subject_id', $subjectId);
        }

        $assessments = $assessmentsQuery->get();

        $selectedAssessment = null;
        $students = collect();

        if ($assessmentId) {
            $selectedAssessment = Assessment::where('teacher_id', $teacher->id)
                ->where('school_id', $teacher->school_id)
                ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
                ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
                ->where('id', $assessmentId)
                ->first();

            if ($selectedAssessment && $selectedAssessment->classroom_id) {
                $students = Student::where('school_id', $teacher->school_id)
                    ->where('classroom_id', $selectedAssessment->classroom_id)
                    ->active()
                    ->orderBy('full_name')
                    ->get();
            }
        }

        return view('teacher.grades.index', compact(
            'classrooms',
            'subjects',
            'assessments',
            'selectedAssessment',
            'students',
            'classroomId',
            'subjectId',
            'assessmentId'
        ));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user();

        $data = $request->validate([
            'assessment_id' => ['required', 'integer', 'exists:assessments,id'],
            'scores' => ['required', 'array'],
        ]);

        $assessment = Assessment::where('teacher_id', $teacher->id)
            ->where('school_id', $teacher->school_id)
            ->where('id', $data['assessment_id'])
            ->firstOrFail();

        $allowedStudentIds = Student::where('school_id', $teacher->school_id)
            ->where('classroom_id', $assessment->classroom_id)
            ->active()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $scores = collect($data['scores'])
            ->filter(fn ($score) => $score !== null && $score !== '');

        if ($scores->isEmpty()) {
            return back()->withErrors(['scores' => 'Ajoutez au moins une note.'])->withInput();
        }

        $invalidStudentIds = $scores->keys()
            ->map(fn ($studentId) => (int) $studentId)
            ->reject(fn ($studentId) => in_array($studentId, $allowedStudentIds, true))
            ->values();

        if ($invalidStudentIds->isNotEmpty()) {
            return back()->withErrors(['scores' => 'Une ou plusieurs notes ne correspondent pas a cette classe.'])->withInput();
        }

        $maxScore = (float) ($assessment->max_score ?? 20);

        DB::transaction(function () use ($scores, $teacher, $assessment, $maxScore): void {
            foreach ($scores as $studentId => $score) {
                Validator::make(
                    ['score' => $score],
                    ['score' => ['numeric', 'min:0', 'max:' . $maxScore]]
                )->validate();

                Grade::updateOrCreate(
                    [
                        'school_id' => $teacher->school_id,
                        'student_id' => (int) $studentId,
                        'assessment_id' => (int) $assessment->id,
                    ],
                    [
                        'classroom_id' => (int) $assessment->classroom_id,
                        'teacher_id' => (int) $teacher->id,
                        'subject_id' => (int) $assessment->subject_id,
                        'score' => (float) $score,
                        'max_score' => (int) $maxScore,
                    ]
                );
            }
        });

        return back()->with('success', 'Notes enregistrees.');
    }
}
