<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Services\TeacherHomeworkCreationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HomeworkController extends Controller
{
    public function __construct(
        private readonly TeacherHomeworkCreationService $creationService,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId  = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $teacherId = auth()->id();

        $homeworks = Homework::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->with(['classroom.level', 'subject', 'attachments'])
            ->latest()
            ->paginate(12);

        return view('teacher.homeworks.index', compact('homeworks'));
    }

    public function create(Request $request)
    {
        $schoolId  = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $teacher = $request->user();
        $classrooms = $this->creationService->classroomsForTeacher($teacher, (int) $schoolId);
        $subjects = $this->creationService->subjectsForTeacher($teacher, (int) $schoolId);

        return view('teacher.homeworks.create', compact('classrooms', 'subjects'));
    }

    public function store(Request $request)
    {
        $schoolId  = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:6000'],
            'due_at' => ['nullable', 'date'],
            'files.*' => ['nullable', 'file', 'max:10240'],
        ]);

        try {
            $this->creationService->create(
                $request->user(),
                (int) $schoolId,
                $data,
                $request->file('files', []),
            );
        } catch (HttpException $exception) {
            $message = $exception->getMessage() ?: 'Impossible de creer ce devoir.';
            $field = str_contains($message, 'Matiere') ? 'subject_id' : 'classroom_id';

            return back()->withInput()->withErrors([
                $field => $message,
            ]);
        }

        return redirect()->route('teacher.homeworks.index')->with('success', 'Devoir ajoute.');
    }
}
