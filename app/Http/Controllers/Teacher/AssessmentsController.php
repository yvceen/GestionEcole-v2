<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Models\Assessment;

class AssessmentsController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        $assessments = Assessment::where('teacher_id', $teacher->id)
            ->where('school_id', $teacher->school_id)
            ->orderByDesc('date')
            ->paginate(20);

        return view('teacher.assessments.index', compact('assessments'));
    }

    public function create()
    {
        $teacher = auth()->user();

        $classrooms = $teacher->teacherClassrooms()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('classrooms.school_id', $teacher->school_id)
            ->orderBy('name')
            ->get();

        $subjects = $teacher->subjects()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('subjects.school_id', $teacher->school_id)
            ->orderBy('name')
            ->get();

        return view('teacher.assessments.create', compact('classrooms', 'subjects'));
    }

    public function store(StoreAssessmentRequest $request)
    {
        $teacher = $request->user();
        $data = $request->validated();

        $classroomAssigned = $teacher->teacherClassrooms()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('classrooms.school_id', $teacher->school_id)
            ->whereKey($data['classroom_id'])
            ->exists();

        $subjectAssigned = $teacher->subjects()
            ->wherePivot('school_id', $teacher->school_id)
            ->where('subjects.school_id', $teacher->school_id)
            ->whereKey($data['subject_id'])
            ->exists();

        abort_unless($classroomAssigned && $subjectAssigned, 403);

        Assessment::create([
            'school_id' => $teacher->school_id,
            'classroom_id' => $data['classroom_id'],
            'teacher_id' => $teacher->id,
            'subject_id' => $data['subject_id'],
            'title' => $data['title'],
            'type' => $data['type'] ?? null,
            'date' => $data['date'],
            'coefficient' => $data['coefficient'] ?? 1,
            'max_score' => $data['max_score'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('teacher.assessments.index')
            ->with('success', 'Evaluation creee.');
    }
}
