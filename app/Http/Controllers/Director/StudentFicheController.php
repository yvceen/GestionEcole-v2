<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Student;

class StudentFicheController extends Controller
{
    public function show(Student $student)
    {
        $schoolId = auth()->user()?->school_id;
        if (!$schoolId) abort(403, 'School context missing.');

        // ✅ حماية: ما يقدرش يشوف تلميذ من مدرسة أخرى
        abort_unless((int)$student->school_id === (int)$schoolId, 403);

        $student->load([
            'classroom',
            'grades.subject',
            'grades.teacher',
            'grades.assessment',
            'attendances',
            'supportPlans',
        ]);

        return view('director.students.fiche', compact('student'));
    }
}