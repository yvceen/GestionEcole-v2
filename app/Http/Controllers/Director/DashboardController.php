<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) abort(403, 'School context missing.');

        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = $now->copy()->endOfWeek(Carbon::SUNDAY);

        // KPIs
        $studentsCount   = Student::where('school_id', $schoolId)->count();
        $classroomsCount = Classroom::where('school_id', $schoolId)->count();
        $teachersCount   = User::where('school_id', $schoolId)->where('role', 'teacher')->count();
        $parentsCount    = User::where('school_id', $schoolId)->where('role', 'parent')->count();

        // Activity this week
        $coursesThisWeek = Course::where('school_id', $schoolId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $homeworksThisWeek = Homework::where('school_id', $schoolId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        // Alerts: classrooms with 0 courses this week
        $classroomsNoCourses = Classroom::where('school_id', $schoolId)
            ->whereNotExists(function ($q) use ($schoolId, $startOfWeek, $endOfWeek) {
                $q->select(DB::raw(1))
                    ->from('courses')
                    ->whereColumn('courses.classroom_id', 'classrooms.id')
                    ->where('courses.school_id', $schoolId)
                    ->whereBetween('courses.created_at', [$startOfWeek, $endOfWeek]);
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        // Alerts: teachers with 0 homeworks this week
        $teachersNoHomeworks = User::where('school_id', $schoolId)
            ->where('role', 'teacher')
            ->whereNotExists(function ($q) use ($schoolId, $startOfWeek, $endOfWeek) {
                $q->select(DB::raw(1))
                    ->from('homeworks')
                    ->whereColumn('homeworks.teacher_id', 'users.id')
                    ->where('homeworks.school_id', $schoolId)
                    ->whereBetween('homeworks.created_at', [$startOfWeek, $endOfWeek]);
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'is_active']);

        // Lagging classrooms (Top 20) by lowest activity (courses + homeworks)
        $laggingClassrooms = Classroom::where('school_id', $schoolId)
            ->select(['id', 'name'])
            ->withCount([
                'teachers as teachers_count' => function ($q) use ($schoolId) {
                    $q->where('users.school_id', $schoolId);
                }
            ])
            ->get()
            ->map(function ($c) use ($schoolId, $startOfWeek, $endOfWeek) {
                $c->courses_week = Course::where('school_id', $schoolId)
                    ->where('classroom_id', $c->id)
                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->count();

                $c->homeworks_week = Homework::where('school_id', $schoolId)
                    ->where('classroom_id', $c->id)
                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->count();

                $c->activity_week = (int)$c->courses_week + (int)$c->homeworks_week;

                return $c;
            })
            ->sortBy(fn($c) => $c->activity_week)
            ->take(20)
            ->values();

        // ✅ مؤشرات إضافية (غير حسابات، ما كتمسّش DB)
        $activeTeachers = User::where('school_id', $schoolId)->where('role', 'teacher')->where('is_active', 1)->count();
        $inactiveTeachers = max(0, $teachersCount - $activeTeachers);

        // نسبة الأقسام اللي عندها cours هذا الأسبوع
        $classroomsWithCoursesCount = max(0, $classroomsCount - $classroomsNoCourses->count());
        $coverageCoursesPct = $classroomsCount > 0 ? round(($classroomsWithCoursesCount / $classroomsCount) * 100) : 0;

        // نسبة الأساتذة اللي دارو homework هذا الأسبوع
        $teachersWithHomeworksCount = max(0, $teachersCount - $teachersNoHomeworks->count());
        $coverageHomeworksPct = $teachersCount > 0 ? round(($teachersWithHomeworksCount / $teachersCount) * 100) : 0;

        // “État global” بسيط للمُدير
        $globalStatus = 'OK';
        if ($coverageCoursesPct < 60 || $coverageHomeworksPct < 60) $globalStatus = 'CRITIQUE';
        elseif ($coverageCoursesPct < 85 || $coverageHomeworksPct < 85) $globalStatus = 'ATTENTION';

        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, $now->copy()->startOfDay());

        return view('director.dashboard', compact(
            'studentsCount',
            'classroomsCount',
            'teachersCount',
            'parentsCount',
            'coursesThisWeek',
            'homeworksThisWeek',
            'classroomsNoCourses',
            'teachersNoHomeworks',
            'laggingClassrooms',
            'startOfWeek',
            'endOfWeek',
            'activeTeachers',
            'inactiveTeachers',
            'coverageCoursesPct',
            'coverageHomeworksPct',
            'globalStatus',
            'attendanceSummary'
        ));
    }
}
