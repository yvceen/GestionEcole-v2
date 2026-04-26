<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentNote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentsController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $levelId = $request->integer('level_id') ?: null;
        $classroomId = $request->integer('classroom_id') ?: null;
        $q = trim((string) $request->get('q', ''));
        $parentName = trim((string) $request->get('parent_name', ''));

        $levels = Level::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);
        $classrooms = Classroom::where('school_id', $schoolId)
            ->when($levelId, fn ($qq) => $qq->where('level_id', $levelId))
            ->orderBy('name')->get(['id', 'name', 'level_id']);

        $students = Student::where('school_id', $schoolId)
            ->active()
            ->with(['classroom.level', 'parentUser:id,name,email,phone'])
            ->when($classroomId, fn ($qq) => $qq->where('classroom_id', $classroomId))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('full_name', 'like', "%{$q}%")
                      ->orWhereHas('parentUser', function ($p) use ($q) {
                          $p->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                      });
                });
            })
            ->when($parentName !== '', function ($qq) use ($parentName) {
                $qq->whereHas('parentUser', function ($parentQuery) use ($parentName) {
                    $parentQuery->where('name', 'like', "%{$parentName}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('director.students.index', compact('levels', 'classrooms', 'students', 'levelId', 'classroomId', 'q', 'parentName'));
    }

    public function show(Request $request, Student $student)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');
        abort_unless($student->school_id == $schoolId, 404);

        $student->load(['classroom.level','notes.author']);

        $courses = Course::where('school_id',$schoolId)
            ->where('classroom_id',$student->classroom_id)
            ->with(['teacher','attachments'])
            ->latest()
            ->take(10)
            ->get();

        $homeworks = Homework::where('school_id',$schoolId)
            ->where('classroom_id',$student->classroom_id)
            ->with(['teacher'])
            ->latest()
            ->take(10)
            ->get();

        return view('director.students.show', compact('student','courses','homeworks'));
    }

    public function storeNote(Request $request, Student $student)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');
        abort_unless($student->school_id == $schoolId, 404);

        $data = $request->validate([
            'note' => ['required','string','min:2','max:5000'],
        ]);

        StudentNote::create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'created_by_user_id' => auth()->id(),
            'note' => $data['note'],
        ]);

        return back()->with('success', 'Note ajoutée ✅');
    }
}
