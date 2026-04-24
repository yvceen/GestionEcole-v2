<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Payment;

class ChildrenController extends Controller
{
    use InteractsWithParentPortal;

    public function index()
    {
        $children = $this->ownedChildren(['classroom.level']);
        $schoolId = $this->schoolIdOrFail();

        $gradeAverages = Grade::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $children->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(function ($rows) {
                return round($rows->avg(function ($grade) {
                    $maxScore = max(1, (int) ($grade->max_score ?? 0));

                    return ((float) $grade->score / $maxScore) * 100;
                }) ?? 0, 2);
            });

        $attendanceSummary = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $children->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => [
                'absent' => $rows->where('status', 'absent')->count(),
                'late' => $rows->where('status', 'late')->count(),
            ]);

        $paymentsSummary = Payment::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $children->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => [
                'count' => $rows->count(),
                'total' => (float) $rows->sum('amount'),
            ]);

        return view('parent.children', compact('children', 'gradeAverages', 'attendanceSummary', 'paymentsSummary'));
    }
}
