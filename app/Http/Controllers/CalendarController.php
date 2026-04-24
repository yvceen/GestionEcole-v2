<?php

namespace App\Http\Controllers;

use App\Models\SchoolCalendarEvent;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function adminIndex(Request $request)
    {
        return redirect()->route('admin.events.index');
    }

    public function create()
    {
        return redirect()->route('admin.events.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.events.index');
    }

    public function edit(SchoolCalendarEvent $event)
    {
        return redirect()->route('admin.events.index');
    }

    public function update(Request $request, SchoolCalendarEvent $event)
    {
        return redirect()->route('admin.events.index');
    }

    public function destroy(SchoolCalendarEvent $event)
    {
        return redirect()->route('admin.events.index');
    }

    public function parentIndex(Request $request)
    {
        return redirect()->route('parent.events.index');
    }

    public function studentIndex(Request $request)
    {
        return redirect()->route('student.events.index');
    }

    public function teacherIndex(Request $request)
    {
        return redirect()->route('teacher.events.index');
    }

    public function directorIndex(Request $request)
    {
        return redirect()->route('director.events.index');
    }

    public function schoolLifeIndex(Request $request)
    {
        return redirect()->route('school-life.events.index');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', SchoolCalendarEvent::types())],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function resolveSchoolEvent(SchoolCalendarEvent $event): SchoolCalendarEvent
    {
        abort_unless((int) $event->school_id === $this->currentSchoolId(), 404);

        return $event;
    }

    private function currentSchoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
