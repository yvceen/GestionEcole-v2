<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Attendance;
use App\Models\News;
use App\Models\Timetable;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use InteractsWithStudentPortal;

    public function index()
    {
        $schoolId = $this->schoolIdOrFail();
        $student = $this->currentStudent(['classroom.level', 'parentUser']);

        $coursesCount = $this->visibleCoursesQuery($student)->count();
        $homeworksCount = $this->visibleHomeworksQuery($student)->count();
        $unreadNotifications = $this->unreadNotificationsCount();

        $upcomingHomework = $this->visibleHomeworksQuery($student)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->with(['teacher:id,name'])
            ->orderBy('due_at')
            ->limit(3)
            ->get();

        $absenceCount = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'absent')
            ->count();

        $nextClass = $this->nextClassForStudent($student, $schoolId);
        $latestAnnouncements = News::query()
            ->where('school_id', $schoolId)
            ->where('status', 'published')
            ->orderByDesc('date')
            ->limit(5)
            ->get(['title', 'date', 'scope', 'classroom_id']);

        return view('student.dashboard', compact(
            'student',
            'coursesCount',
            'homeworksCount',
            'unreadNotifications',
            'upcomingHomework',
            'absenceCount',
            'nextClass',
            'latestAnnouncements'
        ));
    }

    private function nextClassForStudent($student, int $schoolId): ?array
    {
        $slots = Timetable::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', (int) $student->classroom_id)
            ->with('teacher:id,name')
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $now = now();
        $currentDay = (int) $now->dayOfWeekIso;
        $currentTime = $now->format('H:i:s');

        $nextSlot = $slots->first(function (Timetable $slot) use ($currentDay, $currentTime) {
            return (int) $slot->day > $currentDay
                || ((int) $slot->day === $currentDay && (string) $slot->start_time >= $currentTime);
        }) ?? $slots->first();

        if (!$nextSlot) {
            return null;
        }

        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];

        $dayLabel = $days[(int) $nextSlot->day] ?? 'Prochain cours';

        return [
            'subject' => $nextSlot->subject,
            'teacher' => $nextSlot->teacher?->name,
            'room' => $nextSlot->room,
            'day' => $dayLabel,
            'time' => Carbon::parse((string) $nextSlot->start_time)->format('H:i')
                . ' - '
                . Carbon::parse((string) $nextSlot->end_time)->format('H:i'),
        ];
    }
}
