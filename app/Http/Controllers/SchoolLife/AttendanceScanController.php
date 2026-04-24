<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Services\AttendanceScanService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceScanController extends Controller
{
    public function __construct(
        private readonly AttendanceScanService $scanner,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $classroomId = $request->integer('classroom_id');
        $date = (string) $request->get('date', now()->toDateString());

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $records = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->where(function ($query) {
                $query->whereNotNull('check_in_at')->orWhereNotNull('check_out_at');
            })
            ->when($classroomId > 0, fn ($query) => $query->where('classroom_id', $classroomId))
            ->with(['student:id,full_name,classroom_id', 'student.classroom:id,name', 'scannedBy:id,name'])
            ->orderByDesc('check_in_at')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return view('school-life.attendance.scan', compact('classrooms', 'records', 'classroomId', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->scanner->process($validated['code'], $request->user(), $this->schoolId());
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->validator->errors()->first(),
                ], 422);
            }

            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    public function edit(Attendance $attendance)
    {
        $attendance = $this->resolveAttendance($attendance);

        return view('school-life.attendance.scan-edit', compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $attendance = $this->resolveAttendance($attendance);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', Attendance::statuses())],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update([
            'status' => $data['status'],
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'recorded_via' => Attendance::RECORDED_VIA_MANUAL,
            'marked_by_user_id' => auth()->id(),
            'scanned_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('school-life.qr-scan.index', [
            'date' => optional($attendance->date)->format('Y-m-d'),
            'classroom_id' => $attendance->classroom_id,
        ])->with('success', 'Pointage corrige avec succes.');
    }

    private function resolveAttendance(Attendance $attendance): Attendance
    {
        abort_unless((int) $attendance->school_id === $this->schoolId(), 404);

        return $attendance->loadMissing(['student.classroom', 'scannedBy:id,name']);
    }

    private function schoolId(): int
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return $schoolId;
    }
}
