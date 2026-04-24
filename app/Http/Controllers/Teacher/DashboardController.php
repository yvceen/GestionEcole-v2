<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\AttendanceReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $teacherId = auth()->id();

        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = $now->copy()->endOfWeek(Carbon::SUNDAY);

        // ✅ IDs des classes assignées au prof (safe)
        $classroomIds = $this->getTeacherClassroomIds((int)$schoolId);

        // =========================
        // 1) KPIs
        // =========================
        $kpis = [
            'courses_total'    => 0,
            'courses_week'     => 0,
            'grades_week'      => 0,
            'attendance_week'  => 0,
            'assessments_week' => 0,
        ];

        // COURSES
        if (Schema::hasTable('courses')) {
            $q = DB::table('courses')->where('courses.school_id', $schoolId);

            if (Schema::hasColumn('courses', 'teacher_id')) {
                $q->where('courses.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('courses', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('courses.classroom_id', $classroomIds);
            }

            $kpis['courses_total'] = (clone $q)->count();
            $kpis['courses_week']  = (clone $q)->whereBetween('courses.created_at', [$startOfWeek, $endOfWeek])->count();
        }

        // ASSESSMENTS
        if (Schema::hasTable('assessments')) {
            $q = DB::table('assessments')->where('assessments.school_id', $schoolId);

            if (Schema::hasColumn('assessments', 'teacher_id')) {
                $q->where('assessments.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('assessments', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('assessments.classroom_id', $classroomIds);
            }

            $kpis['assessments_week'] = (clone $q)->whereBetween('assessments.created_at', [$startOfWeek, $endOfWeek])->count();
        }

        // GRADES
        if (Schema::hasTable('grades')) {
            $q = DB::table('grades')->where('grades.school_id', $schoolId);

            if (Schema::hasColumn('grades', 'teacher_id')) {
                $q->where('grades.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('grades', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('grades.classroom_id', $classroomIds);
            }

            $kpis['grades_week'] = (clone $q)->whereBetween('grades.created_at', [$startOfWeek, $endOfWeek])->count();
        }

        // ATTENDANCES
        if (Schema::hasTable('attendances')) {
            $q = DB::table('attendances')->where('attendances.school_id', $schoolId);

            // عندك marked_by_user_id (الأكثر منطقية)
            if (Schema::hasColumn('attendances', 'marked_by_user_id')) {
                $q->where('attendances.marked_by_user_id', $teacherId);
            } elseif (Schema::hasColumn('attendances', 'teacher_id')) {
                $q->where('attendances.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('attendances', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('attendances.classroom_id', $classroomIds);
            }

            $kpis['attendance_week'] = (clone $q)->whereBetween('attendances.created_at', [$startOfWeek, $endOfWeek])->count();
        }

        // =========================
        // 2) ALERTS (Classes sans cours / sans notes)
        // =========================
        $alerts = [
            'classrooms_no_courses' => collect(),
            'classrooms_no_grades'  => collect(),
            'hint' => "Objectif : au moins 1 cours + 1 évaluation/notes par classe chaque semaine.",
        ];

        $classrooms = collect();
        if (Schema::hasTable('classrooms') && !empty($classroomIds)) {
            $classrooms = DB::table('classrooms')
                ->where('classrooms.school_id', $schoolId)
                ->whereIn('classrooms.id', $classroomIds)
                ->orderBy('classrooms.name')
                ->get(['classrooms.id', 'classrooms.name']);
        }

        $today = $now->copy()->startOfDay();
        $classroomsWithAttendanceToday = collect();
        if (Schema::hasTable('attendances') && !empty($classroomIds)) {
            $classroomsWithAttendanceToday = DB::table('attendances')
                ->where('school_id', $schoolId)
                ->whereDate('date', $today->toDateString())
                ->whereIn('classroom_id', $classroomIds)
                ->when(
                    Schema::hasColumn('attendances', 'marked_by_user_id'),
                    fn ($query) => $query->where('marked_by_user_id', $teacherId)
                )
                ->select('classroom_id')
                ->distinct()
                ->pluck('classroom_id');
        }

        $pendingAttendanceClassrooms = $classrooms
            ->filter(fn ($classroom) => !$classroomsWithAttendanceToday->contains($classroom->id))
            ->values();

        $recentAttendanceSessions = $this->attendanceReporting->teacherSessionHistory(
            (int) $schoolId,
            (int) $teacherId,
            $classroomIds,
            5
        );

        // Classes sans cours cette semaine
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'classroom_id') && $classrooms->isNotEmpty()) {
            $hasCourseThisWeek = DB::table('courses')
                ->where('courses.school_id', $schoolId)
                ->whereBetween('courses.created_at', [$startOfWeek, $endOfWeek])
                ->whereIn('courses.classroom_id', $classrooms->pluck('id')->all())
                ->select('courses.classroom_id')
                ->distinct()
                ->pluck('courses.classroom_id')
                ->all();

            $alerts['classrooms_no_courses'] = $classrooms
                ->filter(fn($c) => !in_array($c->id, $hasCourseThisWeek))
                ->values();
        }

        // Classes sans notes cette semaine
        if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'classroom_id') && $classrooms->isNotEmpty()) {
            $hasGradesThisWeek = DB::table('grades')
                ->where('grades.school_id', $schoolId)
                ->whereBetween('grades.created_at', [$startOfWeek, $endOfWeek])
                ->whereIn('grades.classroom_id', $classrooms->pluck('id')->all())
                ->select('grades.classroom_id')
                ->distinct()
                ->pluck('grades.classroom_id')
                ->all();

            $alerts['classrooms_no_grades'] = $classrooms
                ->filter(fn($c) => !in_array($c->id, $hasGradesThisWeek))
                ->values();
        }

        // =========================
        // 3) RANKING (Top / Low)
        // =========================
        $ranking = [
            'top' => collect(),
            'low' => collect(),
        ];

        if ($classrooms->isNotEmpty()) {
            $items = $classrooms->map(function ($c) use ($schoolId, $startOfWeek, $endOfWeek) {
                $courses = 0;
                $grades  = 0;

                if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'classroom_id')) {
                    $courses = DB::table('courses')
                        ->where('courses.school_id', $schoolId)
                        ->where('courses.classroom_id', $c->id)
                        ->whereBetween('courses.created_at', [$startOfWeek, $endOfWeek])
                        ->count();
                }

                if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'classroom_id')) {
                    $grades = DB::table('grades')
                        ->where('grades.school_id', $schoolId)
                        ->where('grades.classroom_id', $c->id)
                        ->whereBetween('grades.created_at', [$startOfWeek, $endOfWeek])
                        ->count();
                }

                $score = ($courses * 2) + $grades;

                return [
                    'id'      => $c->id,
                    'name'    => $c->name,
                    'courses' => $courses,
                    'grades'  => $grades,
                    'score'   => $score,
                ];
            })->sortByDesc('score')->values();

            $ranking['top'] = $items->take(5)->values();
            $ranking['low'] = $items->reverse()->take(5)->values();
        }

        // =========================
        // 4) LATEST FEED
        // =========================
        $latest = [
            'courses'     => collect(),
            'grades'      => collect(),
            'attendances' => collect(),
        ];

        // Latest courses
        if (Schema::hasTable('courses')) {
            $q = DB::table('courses')->where('courses.school_id', $schoolId);

            if (Schema::hasColumn('courses', 'teacher_id')) {
                $q->where('courses.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('courses', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('courses.classroom_id', $classroomIds);
            }

            $titleCol = Schema::hasColumn('courses', 'title')
                ? 'title'
                : (Schema::hasColumn('courses', 'name') ? 'name' : null);

            $rows = $q->orderByDesc('courses.id')->limit(5)->get();

            $latest['courses'] = $rows->map(function ($it) use ($titleCol) {
                return [
                    'title'     => $titleCol ? ($it->{$titleCol} ?? 'Cours') : 'Cours',
                    'date'      => !empty($it->created_at) ? Carbon::parse($it->created_at)->format('d/m/Y H:i') : null,
                    'classroom' => null,
                ];
            });
        }

        // Latest grades (✅ بلا تكرار + بلا ambiguous)
        if (Schema::hasTable('grades')) {
            $q = DB::table('grades')
                ->where('grades.school_id', $schoolId);

            if (Schema::hasColumn('grades', 'teacher_id')) {
                $q->where('grades.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('grades', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('grades.classroom_id', $classroomIds);
            }

            if (Schema::hasTable('students') && Schema::hasColumn('grades', 'student_id')) {
                $q->leftJoin('students', 'students.id', '=', 'grades.student_id');
            }
            if (Schema::hasTable('subjects') && Schema::hasColumn('grades', 'subject_id')) {
                $q->leftJoin('subjects', 'subjects.id', '=', 'grades.subject_id');
            }
            if (Schema::hasTable('classrooms') && Schema::hasColumn('grades', 'classroom_id')) {
                $q->leftJoin('classrooms', 'classrooms.id', '=', 'grades.classroom_id');
            }

            $select = ['grades.*'];
            if (Schema::hasTable('students'))   $select[] = DB::raw('students.full_name as student_name');
            if (Schema::hasTable('subjects'))   $select[] = DB::raw('subjects.name as subject_name');
            if (Schema::hasTable('classrooms')) $select[] = DB::raw('classrooms.name as classroom_name');

            $rows = $q->select($select)
                ->orderByDesc('grades.id')
                ->limit(5)
                ->get();

            $latest['grades'] = $rows->map(function ($g) {
                return [
                    'student'   => $g->student_name ?? 'Élève',
                    'subject'   => $g->subject_name ?? null,
                    'classroom' => $g->classroom_name ?? null,
                    'score'     => isset($g->score) ? (string) $g->score : '—',
                    'date'      => !empty($g->created_at) ? Carbon::parse($g->created_at)->format('d/m/Y H:i') : null,
                ];
            });
        }

        // Latest attendances
        if (Schema::hasTable('attendances')) {
            $q = DB::table('attendances')->where('attendances.school_id', $schoolId);

            if (Schema::hasColumn('attendances', 'marked_by_user_id')) {
                $q->where('attendances.marked_by_user_id', $teacherId);
            } elseif (Schema::hasColumn('attendances', 'teacher_id')) {
                $q->where('attendances.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('attendances', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('attendances.classroom_id', $classroomIds);
            }

            if (Schema::hasTable('students') && Schema::hasColumn('attendances', 'student_id')) {
                $q->leftJoin('students', 'students.id', '=', 'attendances.student_id');
            }
            if (Schema::hasTable('classrooms') && Schema::hasColumn('attendances', 'classroom_id')) {
                $q->leftJoin('classrooms', 'classrooms.id', '=', 'attendances.classroom_id');
            }

            $select = ['attendances.*'];
            if (Schema::hasTable('students'))   $select[] = DB::raw('students.full_name as student_name');
            if (Schema::hasTable('classrooms')) $select[] = DB::raw('classrooms.name as classroom_name');

            $rows = $q->select($select)
                ->orderByDesc('attendances.id')
                ->limit(5)
                ->get();

            $latest['attendances'] = $rows->map(function ($a) {
                return [
                    'student'   => $a->student_name ?? 'Élève',
                    'classroom' => $a->classroom_name ?? null,
                    'status'    => $a->status ?? null,
                    'date'      => !empty($a->date)
                        ? Carbon::parse($a->date)->format('d/m/Y')
                        : (!empty($a->created_at) ? Carbon::parse($a->created_at)->format('d/m/Y H:i') : null),
                ];
            });
        }

        // =========================
        // 5) ANALYSE DES NOTES (semaine)
        // =========================
        $analysis = [
            'avg' => null,
            'min' => null,
            'max' => null,
            'distribution' => [
                ['label' => '0–5',   'value' => 0],
                ['label' => '5–10',  'value' => 0],
                ['label' => '10–15', 'value' => 0],
                ['label' => '15–20', 'value' => 0],
            ],
        ];

        if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'score')) {
            $q = DB::table('grades')
                ->where('grades.school_id', $schoolId)
                ->whereBetween('grades.created_at', [$startOfWeek, $endOfWeek]);

            if (Schema::hasColumn('grades', 'teacher_id')) {
                $q->where('grades.teacher_id', $teacherId);
            } elseif (Schema::hasColumn('grades', 'classroom_id') && !empty($classroomIds)) {
                $q->whereIn('grades.classroom_id', $classroomIds);
            }

            $analysis['avg'] = (clone $q)->avg('grades.score');
            $analysis['min'] = (clone $q)->min('grades.score');
            $analysis['max'] = (clone $q)->max('grades.score');

            $scores = (clone $q)->pluck('grades.score')->map(fn($v) => (float)$v);

            $analysis['distribution'] = [
                ['label' => '0–5',   'value' => $scores->filter(fn($s) => $s >= 0  && $s < 5)->count()],
                ['label' => '5–10',  'value' => $scores->filter(fn($s) => $s >= 5  && $s < 10)->count()],
                ['label' => '10–15', 'value' => $scores->filter(fn($s) => $s >= 10 && $s < 15)->count()],
                ['label' => '15–20', 'value' => $scores->filter(fn($s) => $s >= 15 && $s <= 20)->count()],
            ];
        }

        $latestAnnouncements = News::query()
            ->where('school_id', $schoolId)
            ->where('status', 'published')
            ->orderByDesc('date')
            ->limit(5)
            ->get(['title', 'date', 'scope', 'classroom_id']);

        // ✅ Compatibility مع view القديم ديالك
        $coursesCount = $kpis['courses_total'];

        return view('teacher.dashboard', compact(
            'startOfWeek',
            'endOfWeek',
            'kpis',
            'alerts',
            'ranking',
            'latest',
            'analysis',
            'coursesCount',
            'pendingAttendanceClassrooms',
            'recentAttendanceSessions',
            'latestAnnouncements'
        ));
    }

    /**
     * يرجّع IDs ديال classes ديال الأستاذ بأكثر طريقة safe ممكنة.
     */
    private function getTeacherClassroomIds(int $schoolId): array
    {
        $user = auth()->user();
        if (!$user) return [];

        // 1) Relation موجودة؟ (الأفضل)
        if (method_exists($user, 'teacherClassrooms')) {
            try {
                return $user->teacherClassrooms()
                    ->where('classrooms.school_id', $schoolId)
                    ->pluck('classrooms.id')
                    ->all();
            } catch (\Throwable $e) {
                // fallback
            }
        }

        // 2) Pivot table classroom_teacher ?
        if (Schema::hasTable('classroom_teacher')) {
            $q = DB::table('classroom_teacher');

            $teacherCol = Schema::hasColumn('classroom_teacher', 'teacher_id') ? 'teacher_id'
                : (Schema::hasColumn('classroom_teacher', 'user_id') ? 'user_id' : null);

            $classroomCol = Schema::hasColumn('classroom_teacher', 'classroom_id') ? 'classroom_id' : null;

            if ($teacherCol && $classroomCol) {
                $ids = $q->where($teacherCol, $user->id)->pluck($classroomCol)->all();

                if (!empty($ids) && Schema::hasTable('classrooms')) {
                    return DB::table('classrooms')
                        ->where('school_id', $schoolId)
                        ->whereIn('id', $ids)
                        ->pluck('id')
                        ->all();
                }

                return $ids;
            }
        }

        // 3) classrooms.teacher_id ?
        if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'teacher_id')) {
            return DB::table('classrooms')
                ->where('school_id', $schoolId)
                ->where('teacher_id', $user->id)
                ->pluck('id')
                ->all();
        }

        return [];
    }
}
