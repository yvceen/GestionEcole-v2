<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        // ✅ List schools + users count
        $schools = School::query()
            ->withCount(['users', 'students'])
            ->orderByDesc('id')
            ->get();

        // ✅ Global KPIs (اختياري)
        $schoolsCount = $schools->count();
        $activeSchoolsCount = $schools->where('is_active', true)->count();
        $inactiveSchoolsCount = $schoolsCount - $activeSchoolsCount;

        $usersCount = User::query()->count();
        $studentsCount = Student::query()->count();
        $adminsCount  = User::query()->where('role', 'admin')->count();
        $parentsCount = User::query()->where('role', 'parent')->count();
        $teachersCount= User::query()->where('role', 'teacher')->count();
        $schoolsCreatedThisMonth = School::query()
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();
        $paymentsCountThisMonth = Payment::query()
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();
        $avgStudentsPerSchool = $schoolsCount > 0 ? round($studentsCount / $schoolsCount, 1) : 0;

        // ✅ Revenue global this month (اختياري)
        $revenueThisMonth = (float) DB::table('payments')
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->sum('amount');

        // ✅ Revenue chart last 12 months (اختياري)
        $rows = DB::table('payments')
            ->selectRaw('YEAR(paid_at) as y, MONTH(paid_at) as m, SUM(amount) as total')
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

            $chartKeys[]   = $key;
            $chartLabels[] = $d->translatedFormat('M Y');
            $chartValues[] = $map[$key] ?? 0.0;
        }

        return view('super.dashboard', compact(
            'schools',
            'schoolsCount',
            'activeSchoolsCount',
            'inactiveSchoolsCount',
            'usersCount',
            'studentsCount',
            'adminsCount',
            'parentsCount',
            'teachersCount',
            'schoolsCreatedThisMonth',
            'paymentsCountThisMonth',
            'avgStudentsPerSchool',
            'revenueThisMonth',
            'chartLabels',
            'chartValues',
            'chartKeys'
        ));
    }
}
