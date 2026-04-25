<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFeePlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FinanceArrearsService
{
    public function forChildren(Collection $children, int $schoolId, ?Carbon $today = null, ?int $academicYearId = null): array
    {
        $today ??= now();

        if ($children->isEmpty()) {
            return [
                'by_child' => collect(),
                'total_unpaid_months' => 0,
                'total_overdue_months' => 0,
                'total_due' => 0.0,
                'total_overdue' => 0.0,
            ];
        }

        $children = $children->loadMissing('feePlan');
        $childIds = $children->pluck('id')->all();

        $paymentsQuery = Payment::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $childIds);

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'academic_year_id') && $academicYearId) {
            $paymentsQuery->where(function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)
                    ->orWhereNull('academic_year_id');
            });
        }

        $paidMonths = $paymentsQuery
            ->get(['student_id', 'period_month'])
            ->groupBy('student_id')
            ->map(fn (Collection $payments) => $payments
                ->map(fn (Payment $payment) => $payment->period_month?->format('Y-m'))
                ->filter()
                ->unique()
                ->values());

        $feePlans = $this->resolveFeePlans($childIds, $schoolId, $academicYearId);

        $byChild = $children->mapWithKeys(function (Student $child) use ($paidMonths, $today, $feePlans) {
            $feePlan = $feePlans->get($child->id) ?: $child->feePlan;
            $monthlyDue = $feePlan
                ? (float) $feePlan->tuition_monthly + (float) $feePlan->transport_monthly + (float) $feePlan->canteen_monthly
                : 0.0;

            $expectedMonths = $monthlyDue > 0
                ? $this->expectedMonths((int) ($feePlan->starts_month ?? 9), $today)
                : collect();

            $paid = $paidMonths->get($child->id, collect());
            $unpaid = $expectedMonths
                ->reject(fn (array $month) => $paid->contains($month['key']))
                ->values();

            $overdue = $unpaid
                ->filter(fn (array $month) => $month['is_overdue'])
                ->values();

            return [
                $child->id => [
                    'student' => $child,
                    'monthly_due' => $monthlyDue,
                    'unpaid_months' => $unpaid,
                    'overdue_months' => $overdue,
                    'unpaid_count' => $unpaid->count(),
                    'overdue_count' => $overdue->count(),
                    'unpaid_total' => $unpaid->count() * $monthlyDue,
                    'overdue_total' => $overdue->count() * $monthlyDue,
                ],
            ];
        });

        return [
            'by_child' => $byChild,
            'total_unpaid_months' => $byChild->sum('unpaid_count'),
            'total_overdue_months' => $byChild->sum('overdue_count'),
            'total_due' => (float) $byChild->sum('unpaid_total'),
            'total_overdue' => (float) $byChild->sum('overdue_total'),
        ];
    }

    private function resolveFeePlans(array $childIds, int $schoolId, ?int $academicYearId): Collection
    {
        if (empty($childIds) || !Schema::hasTable('student_fee_plans')) {
            return collect();
        }

        $query = StudentFeePlan::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $childIds);

        if (Schema::hasColumn('student_fee_plans', 'academic_year_id') && $academicYearId) {
            $query->where(function ($builder) use ($academicYearId) {
                $builder->where('academic_year_id', $academicYearId)
                    ->orWhereNull('academic_year_id');
            });
        }

        return $query->get()->keyBy('student_id');
    }

    private function expectedMonths(int $startsMonth, Carbon $today): Collection
    {
        $startsMonth = max(1, min(12, $startsMonth ?: 9));
        $startYear = (int) $today->month >= $startsMonth ? (int) $today->year : (int) $today->year - 1;
        $cursor = Carbon::create($startYear, $startsMonth, 1)->startOfMonth();
        $end = $today->copy()->startOfMonth();

        $months = collect();
        while ($cursor->lte($end)) {
            $months->push([
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('M Y'),
                'date' => $cursor->copy(),
                'is_overdue' => $cursor->lt($end),
            ]);
            $cursor->addMonth();
        }

        return $months;
    }
}
