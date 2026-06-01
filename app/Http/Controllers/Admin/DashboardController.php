<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\StudentAcademicYear;
use App\Models\Homework;
use App\Services\AcademicYearService;
use App\Services\AttendanceAutoAbsentService;
use App\Services\AttendanceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
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

        $academicYear = $this->academicYears->resolveYearForSchool($schoolId, $request->integer('academic_year_id') ?: null);
        $academicYearId = (int) $academicYear->id;

        $studentsCount = StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->distinct('student_id')
            ->count('student_id');

        if ($studentsCount === 0) {
            $studentsCount = Student::query()->where('school_id', $schoolId)->active()->count();
        }

        $classroomsCount = StudentAcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('classroom_id')
            ->distinct('classroom_id')
            ->count('classroom_id');

        if ($classroomsCount === 0) {
            $classroomsCount = Classroom::query()->where('school_id', $schoolId)->count();
        }

        $usersCount    = User::query()->where('school_id', $schoolId)->count();
        $teachersCount = User::query()->where('school_id', $schoolId)->where('role', 'teacher')->count();
        $parentsCount  = User::query()->where('school_id', $schoolId)->where('role', 'parent')->count();

        $now = Carbon::now();
        $pendingHomeworks = 0;
        if (Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'status')) {
            $pendingHomeworks = Homework::query()
                ->where('school_id', $schoolId)
                ->where(function ($query) use ($academicYearId) {
                    if (Schema::hasColumn('homeworks', 'academic_year_id')) {
                        $query->where('academic_year_id', $academicYearId)
                            ->orWhereNull('academic_year_id');
                        return;
                    }

                    $query->whereRaw('1 = 1');
                })
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
            ->when(Schema::hasColumn('payments', 'academic_year_id'), function ($query) use ($academicYearId) {
                $query->where(function ($inner) use ($academicYearId) {
                    $inner->where('academic_year_id', $academicYearId)
                        ->orWhereNull('academic_year_id');
                });
            })
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->sum('amount');

        // ✅ Chart last 12 months (scoped)
        $rows = DB::table('payments')
            ->selectRaw('YEAR(paid_at) as y, MONTH(paid_at) as m, SUM(amount) as total, COUNT(*) as payments_count')
            ->where('school_id', $schoolId)
            ->when(Schema::hasColumn('payments', 'academic_year_id'), function ($query) use ($academicYearId) {
                $query->where(function ($inner) use ($academicYearId) {
                    $inner->where('academic_year_id', $academicYearId)
                        ->orWhereNull('academic_year_id');
                });
            })
            ->where('paid_at', '>=', $now->copy()->startOfMonth()->subMonths(11))
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();

        $map = [];
        $countMap = [];
        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', $r->y, $r->m);
            $map[$key] = (float) $r->total;
            $countMap[$key] = (int) $r->payments_count;
        }

        $chartLabels = [];
        $chartValues = [];
        $chartCounts = [];
        $chartKeys   = [];

        for ($i = 11; $i >= 0; $i--) {
            $d = $now->copy()->startOfMonth()->subMonths($i);
            $key = $d->format('Y-m');

            $chartKeys[]   = $key;                     // YYYY-MM
            $chartLabels[] = $d->translatedFormat('M Y'); // Jan 2026...
            $chartValues[] = $map[$key] ?? 0.0;
            $chartCounts[] = $countMap[$key] ?? 0;
        }

        // ✅ Month details (scoped)
        $selected = $request->get('month'); // YYYY-MM
        $selected = is_string($selected) && preg_match('/^\d{4}-\d{2}$/', $selected) ? $selected : null;

        $financeFrom = trim((string) $request->get('finance_from', ''));
        $financeTo = trim((string) $request->get('finance_to', ''));
        $financeMethod = trim((string) $request->get('finance_method', ''));
        $financeSearch = trim((string) $request->get('finance_search', ''));
        $financeMethods = ['cash' => 'Especes', 'transfer' => 'Virement', 'card' => 'Carte', 'check' => 'Cheque'];

        if (!array_key_exists($financeMethod, $financeMethods)) {
            $financeMethod = '';
        }

        if ($selected && $financeFrom === '' && $financeTo === '') {
            $selectedMonth = Carbon::createFromFormat('Y-m', $selected)->startOfMonth();
            $financeFrom = $selectedMonth->format('Y-m-d');
            $financeTo = $selectedMonth->copy()->endOfMonth()->format('Y-m-d');
        }

        if ($financeFrom === '' && $financeTo === '') {
            $financeFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $financeTo = $now->copy()->endOfMonth()->format('Y-m-d');
        }

        $financeFromDate = $this->parseDateFilter($financeFrom)?->startOfDay();
        $financeToDate = $this->parseDateFilter($financeTo)?->endOfDay();

        $financePaymentsQuery = DB::table('payments')
            ->leftJoin('students', 'students.id', '=', 'payments.student_id')
            ->leftJoin('users as parents', 'parents.id', '=', 'students.parent_user_id')
            ->leftJoin('receipts', 'receipts.id', '=', 'payments.receipt_id')
            ->select([
                'payments.id',
                'payments.amount',
                'payments.method',
                'payments.paid_at',
                'payments.note',
                'students.full_name as student_name',
                'parents.name as parent_name',
                'receipts.receipt_number',
            ])
            ->where('payments.school_id', $schoolId)
            ->when(Schema::hasColumn('payments', 'academic_year_id'), function ($query) use ($academicYearId) {
                $query->where(function ($inner) use ($academicYearId) {
                    $inner->where('payments.academic_year_id', $academicYearId)
                        ->orWhereNull('payments.academic_year_id');
                });
            })
            ->when($financeFromDate, fn ($query) => $query->where('payments.paid_at', '>=', $financeFromDate))
            ->when($financeToDate, fn ($query) => $query->where('payments.paid_at', '<=', $financeToDate))
            ->when($financeMethod !== '', fn ($query) => $query->where('payments.method', $financeMethod))
            ->when($financeSearch !== '', function ($query) use ($financeSearch) {
                $query->where(function ($inner) use ($financeSearch) {
                    $inner->where('students.full_name', 'like', "%{$financeSearch}%")
                        ->orWhere('parents.name', 'like', "%{$financeSearch}%")
                        ->orWhere('receipts.receipt_number', 'like', "%{$financeSearch}%")
                        ->orWhere('payments.note', 'like', "%{$financeSearch}%");
                });
            });

        $financeTotal = (float) (clone $financePaymentsQuery)->sum('payments.amount');
        $financeCount = (int) (clone $financePaymentsQuery)->count('payments.id');
        $monthPayments = $financePaymentsQuery
            ->orderByDesc('payments.paid_at')
            ->limit(300)
            ->get();

        $this->attendanceAutoAbsences->markDueAbsencesForSchool($schoolId, $now);

        $attendanceSummary = $this->attendanceReporting->schoolDashboardSummary($schoolId, $now->copy()->startOfDay(), $academicYearId);

        return view('admin.dashboard', compact(
            'studentsCount',
            'classroomsCount',
            'usersCount',
            'teachersCount',
            'parentsCount',
            'revenueThisMonth',
            'chartLabels',
            'chartValues',
            'chartCounts',
            'chartKeys',
            'selected',
            'monthPayments',
            'financeFrom',
            'financeTo',
            'financeMethod',
            'financeSearch',
            'financeMethods',
            'financeTotal',
            'financeCount',
            'pendingHomeworks',
            'attendanceSummary',
            'academicYear'
        ));
    }

    private function parseDateFilter(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
