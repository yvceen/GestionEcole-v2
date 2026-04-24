<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\AppNotification;
use App\Models\News;
use App\Services\AttendanceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use InteractsWithParentPortal;

    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
    ) {
    }

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $childIds = $children->pluck('id');
        $classroomIds = $children->pluck('classroom_id')->filter()->unique()->values();

        $courses = $classroomIds->isEmpty()
            ? collect()
            : $this->visibleCoursesQuery($classroomIds)
                ->with(['classroom.level', 'teacher'])
                ->latest()
                ->get();

        $homeworks = $classroomIds->isEmpty()
            ? collect()
            : $this->visibleHomeworksQuery($classroomIds)
                ->whereNotNull('due_at')
                ->where('due_at', '>=', now())
                ->with(['teacher:id,name', 'classroom:id,name'])
                ->orderBy('due_at')
                ->limit(6)
                ->get();

        $paymentsQuery = $this->ownedPaymentsQuery();
        $receipts = $this->ownedReceiptsQuery()
            ->with([
                'payments' => fn ($query) => $query
                    ->where('school_id', $this->schoolIdOrFail())
                    ->whereIn('student_id', $childIds)
                    ->with('student:id,full_name,classroom_id'),
            ])
            ->orderByDesc('issued_at')
            ->limit(3)
            ->get();

        $recentAttendanceAlerts = $this->attendanceReporting
            ->recentAttendanceAlertsForStudents($childIds, $this->schoolIdOrFail());
        $latestAnnouncements = News::query()
            ->published()
            ->visibleToClassrooms($this->schoolIdOrFail(), $classroomIds->map(fn ($id) => (int) $id)->all())
            ->orderByDesc('date')
            ->limit(5)
            ->get(['title', 'date', 'scope', 'classroom_id']);

        $attendanceNotificationCount = 0;
        if (Schema::hasTable('notifications')) {
            $userColumn = Schema::hasColumn('notifications', 'recipient_user_id')
                ? 'recipient_user_id'
                : 'user_id';

            $attendanceNotificationCount = AppNotification::query()
                ->where($userColumn, auth()->id())
                ->where('type', 'attendance')
                ->whereNull('read_at')
                ->count();
        }

        return view('parent.dashboard', [
            'children' => $children,
            'courses' => $courses,
            'homeworks' => $homeworks,
            'unreadNotifications' => $this->unreadNotificationsCount(),
            'paymentsTotal' => (float) (clone $paymentsQuery)->sum('amount'),
            'paymentsCount' => (int) (clone $paymentsQuery)->count(),
            'lastPayment' => (clone $paymentsQuery)->latest('paid_at')->first(),
            'receipts' => $receipts,
            'nextClass' => $this->nextTimetableSlotForChildren($children),
            'recentAttendanceAlerts' => $recentAttendanceAlerts,
            'attendanceNotificationCount' => $attendanceNotificationCount,
            'latestAnnouncements' => $latestAnnouncements,
        ]);
    }
}
