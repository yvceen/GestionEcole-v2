<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentsController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $q = trim((string) $request->get('q', ''));
        $classroomId = (int) $request->integer('classroom_id');

        $classrooms = Classroom::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->with(['classroom.level', 'parentUser:id,name,phone,email'])
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('parentUser', fn ($parent) => $parent
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%"));
                });
            })
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query->where('school_id', $schoolId)->where('status', 'absent'),
                'attendances as late_count' => fn ($query) => $query->where('school_id', $schoolId)->where('status', 'late'),
                'grades',
            ])
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('school-life.students.index', compact('students', 'classrooms', 'classroomId', 'q'));
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
