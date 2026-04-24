<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\BuildsTimetableGrid;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Timetable;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    use BuildsTimetableGrid;

    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $teacher = auth()->user();
        $classroomIds = $teacher->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->pluck('classrooms.id')
            ->all();

        $classrooms = Classroom::query()
            ->whereIn('id', $classroomIds)
            ->with('level')
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $selectedClassroomId = (int) $request->integer('classroom_id');
        if ($selectedClassroomId <= 0 || !in_array($selectedClassroomId, $classroomIds, true)) {
            $selectedClassroomId = (int) ($classrooms->first()?->id ?? 0);
        }

        $slots = collect();
        if ($selectedClassroomId > 0) {
            $slots = Timetable::query()
                ->where('school_id', $schoolId)
                ->where('classroom_id', $selectedClassroomId)
                ->orderBy('day')
                ->orderBy('start_time')
                ->get();
        }

        $settings = $this->loadTimetableSetting($schoolId);
        $grid = $this->buildTimetableGridPayload($slots, $settings);

        return view('teacher.timetable.index', [
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClass' => $classrooms->firstWhere('id', $selectedClassroomId),
            'settings' => $settings,
            'days' => self::DAYS,
            'slots' => $slots,
            'times' => $grid['times'],
            'slotsByDay' => $grid['slotsByDay'],
            'lunchBlock' => $grid['lunchBlock'],
        ]);
    }
}
