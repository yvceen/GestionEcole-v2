<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Homework;
use App\Models\MobileAppConfig;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentBehavior;
use App\Models\Timetable;
use App\Models\User;
use App\Services\AttendanceReportingService;
use App\Services\FinanceArrearsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class MobileDashboardController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
        private readonly FinanceArrearsService $financeArrears,
    ) {
    }

    public function show(): JsonResponse
    {
        /** @var User|null $user */
        $user = request()->user();
        abort_unless($user, 401);

        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);

        return response()->json([
            'profile' => $this->profilePayload($user, $schoolId),
            'stats' => $this->statsPayload($user, $schoolId),
            'children' => $this->childrenPayload($user, $schoolId),
            'student' => $this->studentPayload($user, $schoolId),
            'recent_notifications' => $this->recentNotificationsPayload($user),
            'sections' => $this->sectionsPayload($user, $schoolId),
            'app_update' => $this->appUpdatePayload($schoolId),
        ]);
    }

    private function profilePayload(User $user, int $schoolId): array
    {
        $schoolName = $schoolId > 0
            ? (string) (School::query()->whereKey($schoolId)->value('name') ?? '')
            : '';

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'role' => (string) $user->role,
            'role_label' => $this->mobileRoleLabel((string) $user->role),
            'school_id' => $schoolId > 0 ? $schoolId : null,
            'school_name' => $schoolName,
        ];
    }

    private function statsPayload(User $user, int $schoolId): array
    {
        $unread = AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->whereNull('read_at')
            ->count();

        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->parentStats($user, $schoolId, $unread),
            User::ROLE_STUDENT => $this->studentStats($user, $schoolId, $unread),
            User::ROLE_TEACHER => $this->teacherStats($user, $schoolId, $unread),
            User::ROLE_SCHOOL_LIFE => $this->schoolLifeStats($schoolId),
            User::ROLE_DIRECTOR => $this->directorStats($schoolId),
            User::ROLE_ADMIN => $this->adminStats($schoolId),
            User::ROLE_SUPER_ADMIN => $this->superAdminStats($unread),
            default => [$this->stat('Unread alerts', (string) $unread, 'Notifications waiting', 'notifications_active')],
        };
    }

    private function parentStats(User $user, int $schoolId, int $unread): array
    {
        $children = $this->parentChildrenQuery($user, $schoolId)->with('feePlan')->get();
        $childIds = $children->pluck('id');
        $presentToday = $childIds->isEmpty()
            ? 0
            : Attendance::query()
                ->whereIn('student_id', $childIds)
                ->whereDate('date', now()->toDateString())
                ->where('status', Attendance::STATUS_PRESENT)
                ->count();
        $arrears = $this->financeArrears->forChildren($children, $schoolId);

        return [
            $this->stat('Children', (string) $children->count(), 'Linked student profiles', 'family_restroom'),
            $this->stat('Present today', (string) $presentToday, 'Attendance marked present', 'check_circle'),
            $this->stat('Unread alerts', (string) $unread, 'Notifications waiting', 'notifications_active'),
            $this->stat('Due total', $this->money((float) $arrears['total_due']), 'Outstanding balance', 'payments'),
        ];
    }

    private function studentStats(User $user, int $schoolId, int $unread): array
    {
        $student = $this->studentRecord($user, $schoolId);
        $attendanceRate = $this->attendanceRateForStudent($student?->id);
        $gradeAverage = $this->gradeAverageForStudent($student?->id);

        return [
            $this->stat('Class', $student?->classroom?->name ?: '-', 'Current classroom', 'class'),
            $this->stat('Attendance', $attendanceRate !== null ? $attendanceRate . '%' : '-', 'Last 30 days', 'fact_check'),
            $this->stat('Average', $gradeAverage !== null ? $gradeAverage . '%' : '-', 'Recorded grades', 'grade'),
            $this->stat('Unread alerts', (string) $unread, 'Notifications waiting', 'notifications_active'),
        ];
    }

    private function teacherStats(User $user, int $schoolId, int $unread): array
    {
        $classroomIds = $user->teacherClassrooms()->where('classrooms.school_id', $schoolId)->pluck('classrooms.id')->all();

        return [
            $this->stat('Classrooms', (string) count($classroomIds), 'Assigned groups', 'class'),
            $this->stat('Subjects', (string) $user->subjects()->count(), 'Teaching load', 'menu_book'),
            $this->stat(
                'Grades this week',
                (string) Grade::query()
                    ->where('teacher_id', (int) $user->id)
                    ->whereBetween('created_at', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])
                    ->count(),
                'Recorded this week',
                'checklist'
            ),
            $this->stat('Unread alerts', (string) $unread, 'Notifications waiting', 'notifications_active'),
        ];
    }

    private function schoolLifeStats(int $schoolId): array
    {
        $today = now()->toDateString();

        return [
            $this->stat('Students', (string) Student::query()->where('school_id', $schoolId)->active()->count(), 'Active records', 'school'),
            $this->stat('Absent today', (string) Attendance::query()->where('school_id', $schoolId)->whereDate('date', $today)->where('status', Attendance::STATUS_ABSENT)->count(), 'Attendance monitoring', 'error_outline'),
            $this->stat('Late today', (string) Attendance::query()->where('school_id', $schoolId)->whereDate('date', $today)->where('status', Attendance::STATUS_LATE)->count(), 'Operations', 'schedule'),
            $this->stat('Pickup pending', (string) PickupRequest::query()->where('school_id', $schoolId)->where('status', PickupRequest::STATUS_PENDING)->count(), 'Awaiting action', 'local_taxi'),
        ];
    }

    private function directorStats(int $schoolId): array
    {
        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, now()->startOfDay());

        return [
            $this->stat('Students', (string) Student::query()->where('school_id', $schoolId)->count(), 'Total student records', 'school'),
            $this->stat('Teachers', (string) User::query()->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)->count(), 'Faculty members', 'group'),
            $this->stat('Classes', (string) Classroom::query()->where('school_id', $schoolId)->count(), 'Learning groups', 'meeting_room'),
            $this->stat('Absent today', (string) $attendanceSummary['today_absent'], 'School attendance', 'error_outline'),
        ];
    }

    private function adminStats(int $schoolId): array
    {
        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, now()->startOfDay());

        return [
            $this->stat('Users', (string) User::query()->where('school_id', $schoolId)->count(), 'People in scope', 'group'),
            $this->stat('Students', (string) Student::query()->where('school_id', $schoolId)->active()->count(), 'Active student records', 'school'),
            $this->stat('Classes', (string) Classroom::query()->where('school_id', $schoolId)->count(), 'Learning groups', 'meeting_room'),
            $this->stat('Late today', (string) $attendanceSummary['today_late'], 'Attendance operations', 'schedule'),
        ];
    }

    private function superAdminStats(int $unread): array
    {
        return [
            $this->stat('Schools', (string) School::query()->count(), 'Managed schools', 'domain'),
            $this->stat('Students', (string) Student::query()->count(), 'Global student records', 'school'),
            $this->stat('Teachers', (string) User::query()->where('role', User::ROLE_TEACHER)->count(), 'Global faculty count', 'group'),
            $this->stat('Unread alerts', (string) $unread, 'Notifications waiting', 'notifications_active'),
        ];
    }

    private function childrenPayload(User $user, int $schoolId): array
    {
        if ((string) $user->role !== User::ROLE_PARENT) {
            return [];
        }

        return $this->parentChildrenQuery($user, $schoolId)
            ->with('classroom:id,name')
            ->orderBy('full_name')
            ->limit(12)
            ->get()
            ->map(function (Student $student): array {
                $latestAttendance = Attendance::query()->where('student_id', (int) $student->id)->latest('date')->first();

                return [
                    'id' => (int) $student->id,
                    'name' => (string) $student->full_name,
                    'classroom' => (string) ($student->classroom?->name ?? ''),
                    'attendance_status' => (string) ($latestAttendance->status ?? ''),
                    'attendance_date' => $latestAttendance?->date?->toDateString(),
                    'average_grade' => $this->gradeAverageForStudent((int) $student->id),
                ];
            })
            ->values()
            ->all();
    }

    private function studentPayload(User $user, int $schoolId): ?array
    {
        if ((string) $user->role !== User::ROLE_STUDENT) {
            return null;
        }

        $student = $this->studentRecord($user, $schoolId);
        if (!$student) {
            return null;
        }

        $latestAttendance = Attendance::query()->where('student_id', (int) $student->id)->latest('date')->first();

        return [
            'id' => (int) $student->id,
            'name' => (string) $student->full_name,
            'classroom' => (string) ($student->classroom?->name ?? ''),
            'attendance_status' => (string) ($latestAttendance->status ?? ''),
            'attendance_date' => $latestAttendance?->date?->toDateString(),
            'average_grade' => $this->gradeAverageForStudent((int) $student->id),
        ];
    }

    private function recentNotificationsPayload(User $user): array
    {
        return AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(fn (AppNotification $notification) => [
                'id' => (int) $notification->id,
                'title' => (string) $notification->title,
                'body' => (string) $notification->body,
                'created_at' => $notification->created_at?->toIso8601String(),
                'read_at' => $notification->read_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function sectionsPayload(User $user, int $schoolId): array
    {
        return match ((string) $user->role) {
            User::ROLE_PARENT => $this->parentSections($user, $schoolId),
            User::ROLE_STUDENT => $this->studentSections($user, $schoolId),
            User::ROLE_TEACHER => $this->teacherSections($user, $schoolId),
            User::ROLE_SCHOOL_LIFE => $this->schoolLifeSections($schoolId),
            User::ROLE_DIRECTOR => $this->directorSections($schoolId),
            User::ROLE_ADMIN => $this->adminSections($schoolId),
            User::ROLE_SUPER_ADMIN => $this->superAdminSections(),
            default => [],
        };
    }

    private function appUpdatePayload(int $schoolId): ?array
    {
        if (!class_exists(MobileAppConfig::class)) {
            return null;
        }

        $platform = strtolower((string) request()->header('X-App-Platform', 'all'));
        if (!in_array($platform, ['android', 'ios'], true)) {
            $platform = 'all';
        }

        $config = MobileAppConfig::query()
            ->where('is_active', true)
            ->whereIn('platform', array_values(array_unique([$platform, 'all'])))
            ->where(function ($query) use ($schoolId): void {
                $query->whereNull('school_id');
                if ($schoolId > 0) {
                    $query->orWhere('school_id', $schoolId);
                }
            })
            ->orderByRaw('CASE WHEN school_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw("CASE WHEN platform = ? THEN 0 WHEN platform = 'all' THEN 1 ELSE 2 END", [$platform])
            ->latest('id')
            ->first();

        if (!$config) {
            return null;
        }

        return [
            'latest_version' => (string) $config->latest_version,
            'minimum_supported_version' => (string) ($config->minimum_supported_version ?? ''),
            'update_message' => (string) ($config->update_message ?? ''),
            'update_url' => (string) ($config->update_url ?? ''),
            'platform' => (string) $config->platform,
        ];
    }

    private function parentSections(User $user, int $schoolId): array
    {
        $children = $this->parentChildrenQuery($user, $schoolId)->with(['classroom:id,name', 'feePlan'])->get();
        $grades = Grade::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $children->pluck('id'))
            ->with(['student:id,full_name', 'subject:id,name', 'teacher:id,name'])
            ->latest('id')
            ->limit(6)
            ->get();
        $attendanceAlerts = $this->attendanceReporting->recentAttendanceAlertsForStudents($children->pluck('id'), $schoolId, 6);
        $arrears = $this->financeArrears->forChildren($children, $schoolId);

        return [
            $this->section('recent_results', 'Recent results', 'Latest recorded grades from the platform.', $grades->map(function (Grade $grade) {
                $maxScore = max(1, (int) ($grade->max_score ?? 0));
                return [
                    'title' => (string) ($grade->student?->full_name ?? 'Student'),
                    'subtitle' => $this->joinParts([$grade->subject?->name, $grade->teacher?->name]),
                    'trailing' => round((((float) $grade->score) / $maxScore) * 100) . '%',
                    'metadata' => (string) $grade->score . '/' . $maxScore,
                    'icon' => 'grade',
                ];
            })->values()->all(), 'No grade records are available yet.'),
            $this->section('attendance_history', 'Attendance history', 'Recent absences and late arrivals.', $attendanceAlerts->map(fn (Attendance $attendance) => [
                'title' => (string) ($attendance->student?->full_name ?? 'Student'),
                'subtitle' => $this->joinParts([$attendance->classroom?->name, $attendance->markedBy?->name]),
                'trailing' => $this->attendanceStatusLabel((string) $attendance->status),
                'metadata' => $attendance->date?->format('d/m/Y') ?? '',
                'badge' => $this->attendanceStatusLabel((string) $attendance->status),
                'icon' => 'fact_check',
            ])->values()->all(), 'No recent attendance alerts were found.'),
            $this->section('finance', 'Finance status', 'Outstanding balances and overdue months.', collect($arrears['by_child'])->map(function (array $item) {
                /** @var Student $student */
                $student = $item['student'];
                return [
                    'title' => (string) $student->full_name,
                    'subtitle' => 'Unpaid months: ' . (int) $item['unpaid_count'],
                    'trailing' => $this->money((float) $item['unpaid_total']),
                    'metadata' => 'Overdue: ' . (int) $item['overdue_count'],
                    'icon' => 'payments',
                ];
            })->values()->all(), 'No outstanding finance records were found.'),
        ];
    }

    private function studentSections(User $user, int $schoolId): array
    {
        $student = $this->studentRecord($user, $schoolId);
        if (!$student) {
            return [];
        }

        $grades = Grade::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with(['subject:id,name', 'teacher:id,name'])
            ->latest('id')
            ->limit(8)
            ->get();
        $attendanceRows = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with('markedBy:id,name')
            ->latest('date')
            ->limit(6)
            ->get();
        $upcomingHomeworks = Homework::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', (int) $student->classroom_id)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->with('teacher:id,name')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $subjectAverages = $grades->groupBy(fn (Grade $grade) => (int) $grade->subject_id)
            ->map(function (Collection $rows) {
                return [
                    'title' => (string) ($rows->first()?->subject?->name ?? 'Subject'),
                    'subtitle' => 'Average across ' . $rows->count() . ' results',
                    'trailing' => round($rows->avg(function (Grade $grade) {
                        $maxScore = max(1, (int) ($grade->max_score ?? 0));
                        return (((float) $grade->score) / $maxScore) * 100;
                    }) ?? 0) . '%',
                    'metadata' => (string) ($rows->first()?->teacher?->name ?? ''),
                    'icon' => 'grade',
                ];
            })->sortBy('title')->values()->all();

        return [
            $this->section('subject_averages', 'Subject averages', 'Current academic performance grouped by subject.', $subjectAverages, 'No grade averages are available yet.'),
            $this->section('attendance_history', 'Attendance history', 'Recent attendance records from the platform.', $attendanceRows->map(fn (Attendance $attendance) => [
                'title' => $this->attendanceStatusLabel((string) $attendance->status),
                'subtitle' => (string) ($attendance->markedBy?->name ?? 'Recorded at school'),
                'trailing' => $attendance->date?->format('d/m/Y') ?? '',
                'metadata' => (string) ($attendance->note ?? ''),
                'badge' => $this->attendanceStatusLabel((string) $attendance->status),
                'icon' => 'fact_check',
            ])->values()->all(), 'No attendance records are available yet.'),
            $this->section('upcoming_homeworks', 'Upcoming homework', 'Due items already assigned in the platform.', $upcomingHomeworks->map(fn ($homework) => [
                'title' => (string) ($homework->title ?? 'Homework'),
                'subtitle' => (string) ($homework->teacher?->name ?? ''),
                'trailing' => $homework->due_at?->format('d/m H:i') ?? '',
                'metadata' => (string) ($homework->description ?? ''),
                'icon' => 'assignment',
            ])->values()->all(), 'No upcoming homework is scheduled right now.'),
        ];
    }

    private function teacherSections(User $user, int $schoolId): array
    {
        $classrooms = $user->teacherClassrooms()
            ->where('classrooms.school_id', $schoolId)
            ->with('level:id,name')
            ->orderBy('name')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.level_id']);
        $classroomIds = $classrooms->pluck('id')->all();

        $todayRecorded = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', now()->toDateString())
            ->whereIn('classroom_id', $classroomIds)
            ->where('marked_by_user_id', (int) $user->id)
            ->select('classroom_id')
            ->distinct()
            ->pluck('classroom_id');

        $pendingAttendance = $classrooms->filter(fn ($classroom) => !$todayRecorded->contains($classroom->id))->values();
        $sessions = $this->attendanceReporting->teacherSessionHistory($schoolId, (int) $user->id, $classroomIds, 6);
        $slots = Timetable::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($user, $classroomIds) {
                $query->where('teacher_id', (int) $user->id);
                if (!empty($classroomIds)) {
                    $query->orWhereIn('classroom_id', $classroomIds);
                }
            })
            ->with('classroom:id,name')
            ->orderBy('day')
            ->orderBy('start_time')
            ->limit(8)
            ->get();

        return [
            $this->section('assigned_classes', 'Assigned classes', 'Classes already linked to this teacher.', $classrooms->map(fn ($classroom) => [
                'title' => (string) $classroom->name,
                'subtitle' => (string) ($classroom->level?->name ?? 'Assigned classroom'),
                'trailing' => (string) Student::query()->where('school_id', $schoolId)->where('classroom_id', $classroom->id)->active()->count(),
                'metadata' => 'students',
                'icon' => 'class',
            ])->values()->all(), 'No classrooms are assigned to this teacher yet.'),
            $this->section('attendance_pending', 'Attendance to complete', 'Today\'s classes without a submitted register.', $pendingAttendance->map(fn ($classroom) => [
                'title' => (string) $classroom->name,
                'subtitle' => 'Attendance register pending for today',
                'trailing' => now()->format('d/m'),
                'icon' => 'fact_check',
            ])->values()->all(), 'Attendance is already recorded for today\'s assigned classes.'),
            $this->section('recent_sessions', 'Recent attendance sessions', 'Latest attendance sessions recorded by this teacher.', $sessions->map(fn (array $session) => [
                'title' => (string) $session['classroom_name'],
                'subtitle' => 'Absent: ' . $session['absent_count'] . ' • Late: ' . $session['late_count'],
                'trailing' => $session['date']->format('d/m/Y'),
                'metadata' => 'Students: ' . $session['total_students'],
                'icon' => 'history',
            ])->values()->all(), 'No attendance sessions have been recorded yet.'),
            $this->section('timetable', 'Timetable preview', 'Upcoming timetable slots linked to this teacher or their classes.', $slots->map(fn (Timetable $slot) => [
                'title' => (string) $slot->subject,
                'subtitle' => trim(implode(' - ', array_filter([$slot->classroom?->name, $slot->room]))),
                'trailing' => $this->dayLabel((int) $slot->day) . ' ' . substr((string) $slot->start_time, 0, 5),
                'metadata' => substr((string) $slot->end_time, 0, 5),
                'icon' => 'calendar_month',
            ])->values()->all(), 'No timetable slots are configured yet.'),
        ];
    }

    private function schoolLifeSections(int $schoolId): array
    {
        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, now()->startOfDay());
        $recentAttendance = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->with(['student.classroom', 'markedBy:id,name'])
            ->orderByDesc('date')
            ->limit(8)
            ->get();
        $pickupRequests = PickupRequest::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [PickupRequest::STATUS_PENDING, PickupRequest::STATUS_APPROVED])
            ->with(['student.classroom', 'parentUser:id,name,phone'])
            ->orderBy('requested_pickup_at')
            ->limit(8)
            ->get();
        $behaviors = StudentBehavior::query()
            ->where('school_id', $schoolId)
            ->with(['student.classroom', 'author:id,name'])
            ->latest('date')
            ->limit(8)
            ->get();
        $studentsFollowUp = Student::query()
            ->where('school_id', $schoolId)
            ->active()
            ->with(['classroom:id,name', 'parentUser:id,name,phone'])
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('status', Attendance::STATUS_ABSENT),
                'attendances as late_count' => fn ($query) => $query
                    ->where('school_id', $schoolId)
                    ->where('status', Attendance::STATUS_LATE),
                'behaviors',
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->orderBy('full_name')
            ->limit(8)
            ->get();

        return [
            $this->section('weekly_attendance', 'Weekly attendance overview', 'School-wide attendance reporting.', collect($attendanceSummary['weekly_overview'])->map(fn (array $item) => [
                'title' => (string) $item['label'],
                'subtitle' => 'Absent: ' . $item['absent'] . ' | Late: ' . $item['late'],
                'trailing' => (string) ($item['absent'] + $item['late']),
                'metadata' => 'Alerts',
                'icon' => 'insights',
            ])->values()->all(), 'No weekly attendance data is available.'),
            $this->section('recent_attendance', 'Recent attendance incidents', 'Latest absences and late arrivals requiring follow-up.', $recentAttendance->map(fn (Attendance $attendance) => [
                'title' => (string) ($attendance->student?->full_name ?? 'Student'),
                'subtitle' => $this->joinParts([$attendance->student?->classroom?->name, $attendance->markedBy?->name]),
                'trailing' => $attendance->date?->format('d/m/Y') ?? '',
                'metadata' => $this->attendanceStatusLabel((string) $attendance->status),
                'badge' => $this->attendanceStatusLabel((string) $attendance->status),
                'icon' => 'error_outline',
            ])->values()->all(), 'No recent attendance incidents were found.'),
            $this->section('pickup_requests', 'Pickup requests', 'Pending and approved pickup operations.', $pickupRequests->map(fn (PickupRequest $pickup) => [
                'title' => (string) ($pickup->student?->full_name ?? 'Student'),
                'subtitle' => $this->joinParts([$pickup->student?->classroom?->name, $pickup->parentUser?->name]),
                'trailing' => $pickup->requested_pickup_at?->format('d/m H:i') ?? '',
                'metadata' => (string) ($pickup->parentUser?->phone ?? ''),
                'badge' => $this->pickupStatusLabel((string) $pickup->status),
                'icon' => 'local_taxi',
            ])->values()->all(), 'No pickup requests are awaiting action.'),
            $this->section('behavior_follow_up', 'Behavior follow-up', 'Recent behavior and discipline updates shared by school life.', $behaviors->map(fn (StudentBehavior $behavior) => [
                'title' => (string) ($behavior->student?->full_name ?? 'Student'),
                'subtitle' => $this->joinParts([$behavior->student?->classroom?->name, $behavior->author?->name]),
                'trailing' => $behavior->date?->format('d/m/Y') ?? '',
                'metadata' => (string) $behavior->description,
                'badge' => $this->behaviorTypeLabel((string) $behavior->type),
                'icon' => 'assignment',
            ])->values()->all(), 'No recent behavior items were found.'),
            $this->section('student_follow_up', 'Students to follow up', 'Students with the highest attendance or behavior pressure right now.', $studentsFollowUp->map(fn (Student $student) => [
                'title' => (string) $student->full_name,
                'subtitle' => $this->joinParts([$student->classroom?->name, $student->parentUser?->name]),
                'trailing' => 'Absences: ' . (int) ($student->absences_count ?? 0),
                'metadata' => 'Late: ' . (int) ($student->late_count ?? 0) . ' | Behavior: ' . (int) ($student->behaviors_count ?? 0),
                'icon' => 'school',
            ])->values()->all(), 'No student follow-up items are currently flagged.'),
        ];
    }

    private function directorSections(int $schoolId): array
    {
        $startOfWeek = now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = now()->endOfWeek(Carbon::SUNDAY);
        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, now()->startOfDay());

        $classroomsNoCourses = Classroom::query()->where('school_id', $schoolId)
            ->whereNotExists(function ($query) use ($schoolId, $startOfWeek, $endOfWeek) {
                $query->selectRaw('1')->from('courses')->whereColumn('courses.classroom_id', 'classrooms.id')->where('courses.school_id', $schoolId)->whereBetween('courses.created_at', [$startOfWeek, $endOfWeek]);
            })->orderBy('name')->limit(8)->get(['id', 'name']);

        $teachersNoHomeworks = User::query()->where('school_id', $schoolId)->where('role', User::ROLE_TEACHER)
            ->whereNotExists(function ($query) use ($schoolId, $startOfWeek, $endOfWeek) {
                $query->selectRaw('1')->from('homeworks')->whereColumn('homeworks.teacher_id', 'users.id')->where('homeworks.school_id', $schoolId)->whereBetween('homeworks.created_at', [$startOfWeek, $endOfWeek]);
            })->orderBy('name')->limit(8)->get(['id', 'name', 'is_active']);

        return [
            $this->section('attendance', 'Attendance overview', 'Weekly school attendance summary.', collect($attendanceSummary['weekly_overview'])->map(fn (array $item) => [
                'title' => (string) $item['label'],
                'subtitle' => 'Absent: ' . $item['absent'] . ' | Late: ' . $item['late'],
                'trailing' => (string) ($item['absent'] + $item['late']),
                'icon' => 'fact_check',
            ])->values()->all(), 'No attendance data is available yet.'),
            $this->section('classrooms_no_courses', 'Classes without courses this week', 'Classes missing weekly course activity.', $classroomsNoCourses->map(fn (Classroom $classroom) => [
                'title' => (string) $classroom->name,
                'subtitle' => 'No course created this week',
                'icon' => 'menu_book',
            ])->values()->all(), 'Every class has course activity this week.'),
            $this->section('teachers_no_homeworks', 'Teachers without homework this week', 'Faculty follow-up from the director dashboard.', $teachersNoHomeworks->map(fn (User $teacher) => [
                'title' => (string) $teacher->name,
                'subtitle' => 'No homework published this week',
                'trailing' => $teacher->is_active ? 'Active' : 'Inactive',
                'icon' => 'group',
            ])->values()->all(), 'Every teacher has homework activity this week.'),
        ];
    }

    private function adminSections(int $schoolId): array
    {
        $pendingHomeworks = Homework::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '')->orWhereIn('status', ['pending', 'draft']);
            })
            ->with(['teacher:id,name', 'classroom:id,name'])
            ->latest('id')
            ->limit(8)
            ->get();
        $recentPayments = Payment::query()->where('school_id', $schoolId)->with(['student:id,full_name'])->latest('paid_at')->limit(8)->get();

        return [
            $this->section('pending_homeworks', 'Pending homework approvals', 'Homework items still awaiting admin review.', $pendingHomeworks->map(fn ($homework) => [
                'title' => (string) ($homework->title ?? 'Homework'),
                'subtitle' => $this->joinParts([$homework->classroom?->name, $homework->teacher?->name]),
                'trailing' => ucfirst((string) ($homework->status ?? 'pending')),
                'badge' => ucfirst((string) ($homework->status ?? 'pending')),
                'icon' => 'assignment',
            ])->values()->all(), 'No pending homework approvals were found.'),
            $this->section('recent_payments', 'Recent payments', 'Latest finance transactions in this school.', $recentPayments->map(fn (Payment $payment) => [
                'title' => (string) ($payment->student?->full_name ?? 'Student'),
                'subtitle' => ucfirst((string) ($payment->method ?? 'payment')),
                'trailing' => $this->money((float) $payment->amount),
                'metadata' => $payment->paid_at?->format('d/m/Y H:i') ?? '',
                'icon' => 'payments',
            ])->values()->all(), 'No recent payments were found.'),
        ];
    }

    private function superAdminSections(): array
    {
        $schools = School::query()->withCount(['users', 'students'])->orderByDesc('id')->limit(10)->get();
        $revenueThisMonth = (float) Payment::query()->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        return [
            $this->section('global_status', 'Global platform status', 'Top-level platform summary for super admin.', [[
                'title' => 'Revenue this month',
                'subtitle' => 'Global payment volume',
                'trailing' => $this->money($revenueThisMonth),
                'icon' => 'insights',
            ]], 'No global status is available.'),
            $this->section('schools', 'Schools overview', 'Recent schools with user and student counts.', $schools->map(fn (School $school) => [
                'title' => (string) $school->name,
                'subtitle' => 'Users: ' . $school->users_count . ' | Students: ' . $school->students_count,
                'trailing' => $school->is_active ? 'Active' : 'Inactive',
                'badge' => $school->is_active ? 'active' : 'inactive',
                'metadata' => (string) ($school->subdomain ?? ''),
                'icon' => 'domain',
            ])->values()->all(), 'No schools are available yet.'),
        ];
    }

    private function studentRecord(User $user, int $schoolId): ?Student
    {
        return Student::query()->active()->with('classroom:id,name')->where('user_id', (int) $user->id)->when($schoolId > 0, fn ($query) => $query->where('school_id', $schoolId))->first();
    }

    private function parentChildrenQuery(User $user, int $schoolId)
    {
        return Student::query()->active()->where('parent_user_id', (int) $user->id)->when($schoolId > 0, fn ($query) => $query->where('school_id', $schoolId));
    }

    private function attendanceRateForStudent(?int $studentId): ?int
    {
        if (!$studentId) {
            return null;
        }

        $attendanceQuery = Attendance::query()->where('student_id', $studentId)->whereDate('date', '>=', now()->subDays(30)->toDateString());
        $total = (clone $attendanceQuery)->count();
        if ($total === 0) {
            return null;
        }

        $present = (clone $attendanceQuery)->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])->count();
        return (int) round(($present / $total) * 100);
    }

    private function gradeAverageForStudent(?int $studentId): ?int
    {
        if (!$studentId) {
            return null;
        }

        $grades = Grade::query()->where('student_id', $studentId)->get(['score', 'max_score']);
        if ($grades->isEmpty()) {
            return null;
        }

        $total = 0.0;
        $count = 0;
        foreach ($grades as $grade) {
            $maxScore = (int) ($grade->max_score ?? 0);
            if ($maxScore <= 0) {
                continue;
            }
            $total += (((float) $grade->score) / $maxScore) * 100;
            $count++;
        }

        return $count === 0 ? null : (int) round($total / $count);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', ' ') . ' MAD';
    }

    private function dayLabel(int $day): string
    {
        return match ($day) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Day',
        };
    }

    private function mobileRoleLabel(string $role): string
    {
        return match ($role) {
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_DIRECTOR => 'Director',
            User::ROLE_TEACHER => 'Teacher',
            User::ROLE_PARENT => 'Parent',
            User::ROLE_STUDENT => 'Student',
            User::ROLE_SCHOOL_LIFE => 'School Life',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    private function attendanceStatusLabel(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => 'Present',
            Attendance::STATUS_ABSENT => 'Absent',
            Attendance::STATUS_LATE => 'Late',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function pickupStatusLabel(string $status): string
    {
        return match ($status) {
            PickupRequest::STATUS_PENDING => 'Pending',
            PickupRequest::STATUS_APPROVED => 'Approved',
            PickupRequest::STATUS_REJECTED => 'Rejected',
            PickupRequest::STATUS_COMPLETED => 'Completed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function behaviorTypeLabel(string $type): string
    {
        return match ($type) {
            StudentBehavior::TYPE_RETARD => 'Late arrival',
            StudentBehavior::TYPE_COMPORTEMENT => 'Behavior',
            StudentBehavior::TYPE_SANCTION => 'Sanction',
            StudentBehavior::TYPE_REMARQUE => 'Remark',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function joinParts(array $parts): string
    {
        $filtered = array_values(array_filter(array_map(
            static fn ($value) => trim((string) ($value ?? '')),
            $parts
        )));

        return $filtered === [] ? '' : implode(' | ', $filtered);
    }

    private function stat(string $label, string $value, string $caption, string $icon): array
    {
        return ['label' => $label, 'value' => $value, 'caption' => $caption, 'icon' => $icon];
    }

    private function section(string $key, string $title, string $subtitle, array $items, string $emptyMessage): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'subtitle' => $subtitle,
            'empty_message' => $emptyMessage,
            'items' => array_values($items),
        ];
    }
}
