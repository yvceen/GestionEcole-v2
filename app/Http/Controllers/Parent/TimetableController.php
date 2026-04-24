<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Concerns\BuildsTimetableGrid;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Student;
use App\Models\Timetable;

class TimetableController extends Controller
{
    use BuildsTimetableGrid;
    use InteractsWithParentPortal;

    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    public function childTimetable(Student $student)
    {
        $student = $this->resolveOwnedStudent($student, ['classroom.level']);
        $schoolId = $this->schoolIdOrFail();

        $slots = Timetable::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', (int) $student->classroom_id)
            ->with('teacher:id,name')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        $settings = $this->loadTimetableSetting($schoolId);
        $grid = $this->buildTimetableGridPayload($slots, $settings);

        return view('parent.timetable.index', [
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
