<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Activity;

class ActivityController extends Controller
{
    use InteractsWithStudentPortal;

    public function index()
    {
        $student = $this->currentStudent(['classroom:id,name']);

        $activities = Activity::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereHas('participants', fn ($query) => $query->where('student_id', $student->id))
            ->with([
                'classroom:id,name',
                'teacher:id,name',
                'participants' => fn ($query) => $query->where('student_id', $student->id),
                'reports.author:id,name',
            ])
            ->orderBy('start_date')
            ->paginate(12);

        return view('student.activities.index', compact('activities', 'student'));
    }
}
