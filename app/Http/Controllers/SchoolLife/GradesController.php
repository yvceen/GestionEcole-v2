<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        $classroomId = (int) $request->integer('classroom_id');
        $q = trim((string) $request->get('q', ''));

        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        $grades = Grade::query()
            ->where('school_id', $schoolId)
            ->with(['student.classroom', 'teacher:id,name', 'subject:id,name'])
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($q !== '', fn ($query) => $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('school-life.grades.index', compact('grades', 'classrooms', 'classroomId', 'q'));
    }
}
