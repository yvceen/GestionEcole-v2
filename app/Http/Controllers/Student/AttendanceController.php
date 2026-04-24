<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\InteractsWithStudentPortal;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use InteractsWithStudentPortal;

    public function index(Request $request)
    {
        $student = $this->currentStudent(['classroom.level']);
        $schoolId = $this->schoolIdOrFail();
        $status = trim((string) $request->get('status', ''));
        $dateFrom = $this->parseDate((string) $request->get('date_from', ''));
        $dateTo = $this->parseDate((string) $request->get('date_to', ''), true);

        $baseQuery = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with('markedBy:id,name')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('date', '<=', $dateTo));

        $summarySource = (clone $baseQuery)->get();
        $summary = [
            'total' => $summarySource->count(),
            'present' => $summarySource->where('status', 'present')->count(),
            'absent' => $summarySource->where('status', 'absent')->count(),
            'late' => $summarySource->where('status', 'late')->count(),
        ];

        $attendances = (clone $baseQuery)
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return view('student.attendance.index', compact(
            'student',
            'attendances',
            'status',
            'dateFrom',
            'dateTo',
            'summary'
        ));
    }

    private function parseDate(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
