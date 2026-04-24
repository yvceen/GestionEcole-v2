<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Parent\Concerns\InteractsWithParentPortal;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use InteractsWithParentPortal;

    public function index(Request $request)
    {
        $children = $this->ownedChildren(['classroom.level']);
        $childId = (int) $request->integer('child_id');
        $status = trim((string) $request->get('status', ''));
        $student = $childId > 0 ? $children->firstWhere('id', $childId) : null;
        $dateFrom = $this->parseDate((string) $request->get('date_from', ''));
        $dateTo = $this->parseDate((string) $request->get('date_to', ''), true);

        $baseQuery = Attendance::query()
            ->where('school_id', $this->schoolIdOrFail())
            ->whereIn('student_id', $children->pluck('id'))
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'markedBy:id,name'])
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
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

        return view('parent.attendance.index', compact(
            'children',
            'attendances',
            'childId',
            'status',
            'dateFrom',
            'dateTo',
            'summary'
        ));
    }

    public function childAttendance(Request $request, Student $student)
    {
        $student = $this->resolveOwnedStudent($student);
        $request->merge(['child_id' => $student->id]);

        return $this->index($request);
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
