<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Homework;
use App\Services\AttendanceAutoAbsentService;
use App\Services\AttendanceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
        private readonly AttendanceAutoAbsentService $attendanceAutoAbsences,
    ) {
    }

    public function index(Request $request)
    {
        // ✅ school context
        $schoolId = app()->bound('current_school_id') ? app('current_school_id') : null;
        if (!$schoolId) {
            abort(403, 'School context missing.');
        }

        // ✅ Scoped counts
        $studentsCount   = Student::query()->where('school_id', $schoolId)->active()->count();
        $classroomsCount = Classroom::query()->where('school_id', $schoolId)->count();

        $usersCount    = User::query()->where('school_id', $schoolId)->count();
        $teachersCount = User::query()->where('school_id', $schoolId)->where('role', 'teacher')->count();
        $parentsCount  = User::query()->where('school_id', $schoolId)->where('role', 'parent')->count();

        $now = Carbon::now();
        $pendingHomeworks = 0;
        if (Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'status')) {
            $pendingHomeworks = Homework::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereIn('status', ['pending', 'draft']);
                })
                ->count();
        }

        // ✅ Revenue this month (scoped)
        $revenueThisMonth = (float) DB::table('payments')
            ->where('school_id', $schoolId)
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->sum('amount');

        // ✅ Chart last 12 months (scoped)
        $rows = DB::table('payments')
            ->selectRaw('YEAR(paid_at) as y, MONTH(paid_at) as m, SUM(amount) as total')
            ->where('school_id', $schoolId)
            ->where('paid_at', '>=', $now->copy()->startOfMonth()->subMonths(11))
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', $r->y, $r->m);
            $map[$key] = (float) $r->total;
        }

        $chartLabels = [];
        $chartValues = [];
        $chartKeys   = [];

        for ($i = 11; $i >= 0; $i--) {
            $d = $now->copy()->startOfMonth()->subMonths($i);
            $key = $d->format('Y-m');

            $chartKeys[]   = $key;                     // YYYY-MM
            $chartLabels[] = $d->translatedFormat('M Y'); // Jan 2026...
            $chartValues[] = $map[$key] ?? 0.0;
        }

        // ✅ Month details (scoped)
        $selected = $request->get('month'); // YYYY-MM
        $monthPayments = collect();

        if ($selected && preg_match('/^\d{4}-\d{2}$/', $selected)) {
            $selectedMonth = Carbon::createFromFormat('Y-m', $selected)->startOfMonth();

            $monthPayments = DB::table('payments')
                ->where('school_id', $schoolId)
                ->whereBetween('paid_at', [
                    $selectedMonth->copy()->startOfMonth(),
                    $selectedMonth->copy()->endOfMonth()
                ])
                ->orderBy('paid_at', 'desc')
                ->limit(200)
                ->get();
        }

        $this->attendanceAutoAbsences->markDueAbsencesForSchool($schoolId, $now);

        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, $now->copy()->startOfDay());

        return view('admin.dashboard', compact(
            'studentsCount',
            'classroomsCount',
            'usersCount',
            'teachersCount',
            'parentsCount',
            'revenueThisMonth',
            'chartLabels',
            'chartValues',
            'chartKeys',
            'selected',
            'monthPayments',
            'pendingHomeworks',
            'attendanceSummary'
        ));
    }
}
