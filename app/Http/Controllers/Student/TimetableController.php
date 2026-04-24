<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\BuildsTimetableGrid;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Timetable;

class TimetableController extends Controller
{
    use BuildsTimetableGrid;
    use InteractsWithStudentPortal;

    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    public function index()
    {
        $schoolId = $this->schoolIdOrFail();
        $student = $this->currentStudent(['classroom.level']);

        $slots = Timetable::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', (int) $student->classroom_id)
            ->with('teacher:id,name')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        $settings = $this->loadTimetableSetting($schoolId);
        $grid = $this->buildTimetableGridPayload($slots, $settings);

        return view('student.timetable.index', [
            'student' => $student,
            'selectedClass' => $student->classroom,
            'settings' => $settings,
            'days' => self::DAYS,
            'slots' => $slots,
            'times' => $grid['times'],
            'slotsByDay' => $grid['slotsByDay'],
            'lunchBlock' => $grid['lunchBlock'],
        ]);
    }
}
