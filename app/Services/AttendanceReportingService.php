<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceReportingService
{
    public function __construct(
        private readonly AcademicYearService $academicYears,
        private readonly StudentPlacementService $placements,
    ) {
    }

    public function buildMonitoringData(int $schoolId, Request $request, int $perPage = 20): array
    {
        $academicYear = $this->academicYears->resolveYearForSchool($schoolId, $request->integer('academic_year_id') ?: null);

        [
            'classrooms' => $classrooms,
            'students' => $students,
            'selectedClassroom' => $selectedClassroom,
            'selectedStudent' => $selectedStudent,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ] = $this->resolveMonitoringFilters($schoolId, $request, $academicYear->id);

        $query = $this->filteredQuery($schoolId, $selectedClassroom, $selectedStudent, $status, $dateFrom, $dateTo, $academicYear->id);

        $summarySource = (clone $query)->get(['id', 'status']);
        $summary = [
            'total' => $summarySource->count(),
            'present' => $summarySource->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $summarySource->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $summarySource->where('status', Attendance::STATUS_LATE)->count(),
        ];

        $classSummary = (clone $query)
            ->selectRaw("
                classroom_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absences_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_count
            ", [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->with('classroom:id,name,level_id')
            ->groupBy('classroom_id')
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->limit(10)
            ->get();

        $studentSummary = (clone $query)
            ->selectRaw("
                student_id,
                classroom_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absences_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_count
            ", [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->with(['student:id,full_name,classroom_id', 'classroom:id,name'])
            ->groupBy('student_id', 'classroom_id')
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->limit(12)
            ->get();

        $records = (clone $query)
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'classrooms' => $classrooms,
            'students' => $students,
            'selectedClassroom' => $selectedClassroom,
            'selectedStudent' => $selectedStudent,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'currentAcademicYear' => $academicYear,
            'summary' => $summary,
            'classSummary' => $classSummary,
            'studentSummary' => $studentSummary,
            'records' => $records,
        ];
    }

    public function exportMonitoringRecords(int $schoolId, Request $request): Collection
    {
        $academicYear = $this->academicYears->resolveYearForSchool($schoolId, $request->integer('academic_year_id') ?: null);

        [
            'selectedClassroom' => $selectedClassroom,
            'selectedStudent' => $selectedStudent,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ] = $this->resolveMonitoringFilters($schoolId, $request, $academicYear->id);

        return $this->filteredQuery($schoolId, $selectedClassroom, $selectedStudent, $status, $dateFrom, $dateTo, $academicYear->id)
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->get();
    }

    public function schoolDashboardSummary(int $schoolId, ?Carbon $today = null, ?int $academicYearId = null): array
    {
        $today ??= now()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);

        $resolvedAcademicYearId = $academicYearId ?: $this->academicYears->requireCurrentYearForSchool($schoolId)->id;

        $todayQuery = $this->yearAwareAttendanceQuery($schoolId, $resolvedAcademicYearId)
            ->whereDate('date', $today->toDateString());

        $weeklyRows = $this->yearAwareAttendanceQuery($schoolId, $resolvedAcademicYearId)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->selectRaw("
                date,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_count
            ", [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $weeklyOverview = collect();
        for ($cursor = $weekStart->copy(); $cursor->lte($weekEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $row = $weeklyRows->get($key);
            $weeklyOverview->push([
                'date' => $key,
                'label' => $cursor->translatedFormat('D d/m'),
                'absent' => (int) ($row->absent_count ?? 0),
                'late' => (int) ($row->late_count ?? 0),
            ]);
        }

        return [
            'today_present' => (clone $todayQuery)->where('status', Attendance::STATUS_PRESENT)->count(),
            'today_absent' => (clone $todayQuery)->where('status', Attendance::STATUS_ABSENT)->count(),
            'today_late' => (clone $todayQuery)->where('status', Attendance::STATUS_LATE)->count(),
            'weekly_overview' => $weeklyOverview,
        ];
    }

    public function recentAttendanceAlertsForStudents(Collection $studentIds, int $schoolId, int $limit = 6): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return $this->yearAwareAttendanceQuery($schoolId, $this->academicYears->requireCurrentYearForSchool($schoolId)->id)
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->with(['student:id,full_name,classroom_id', 'classroom:id,name', 'markedBy:id,name'])
            ->orderByDesc('date')
            ->limit($limit)
            ->get();
    }

    public function teacherSessionHistory(int $schoolId, int $teacherId, array $classroomIds, int $limit = 8): Collection
    {
        if (empty($classroomIds)) {
            return collect();
        }

        $rows = $this->yearAwareAttendanceQuery($schoolId, $this->academicYears->requireCurrentYearForSchool($schoolId)->id)
            ->whereIn('classroom_id', $classroomIds)
            ->where('marked_by_user_id', $teacherId)
            ->selectRaw("
                classroom_id,
                date,
                COUNT(*) as total_students,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_count
            ", [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->groupBy('classroom_id', 'date')
            ->orderByDesc('date')
            ->limit($limit)
            ->get();

        $academicYearId = $this->academicYears->requireCurrentYearForSchool($schoolId)->id;
        $classroomNames = $this->classroomNamesForYear($schoolId, $academicYearId);

        return $rows->map(function ($row) use ($classroomNames) {
            return [
                'classroom_id' => (int) $row->classroom_id,
                'classroom_name' => $classroomNames[(int) $row->classroom_id] ?? ('Classe #' . $row->classroom_id),
                'date' => Carbon::parse($row->date),
                'total_students' => (int) $row->total_students,
                'absent_count' => (int) $row->absent_count,
                'late_count' => (int) $row->late_count,
            ];
        });
    }

    private function baseQuery(int $schoolId): Builder
    {
        return Attendance::query()
            ->where('school_id', $schoolId)
            ->with([
                'student:id,full_name,classroom_id',
                'classroom:id,name,level_id',
                'classroom.level:id,name',
                'markedBy:id,name',
                'scannedBy:id,name',
            ]);
    }

    private function filteredQuery(
        int $schoolId,
        ?Classroom $selectedClassroom,
        ?Student $selectedStudent,
        string $status,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $academicYearId
    ): Builder {
        return $this->academicYears->applyYearScope($this->baseQuery($schoolId), $schoolId, $academicYearId, 'attendances', true)
            ->when($selectedClassroom, fn (Builder $builder) => $builder->where('classroom_id', $selectedClassroom->id))
            ->when($selectedStudent, fn (Builder $builder) => $builder->where('student_id', $selectedStudent->id))
            ->when($status !== '', fn (Builder $builder) => $builder->where('status', $status))
            ->when($dateFrom, fn (Builder $builder) => $builder->where('date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $builder) => $builder->where('date', '<=', $dateTo));
    }

    private function resolveMonitoringFilters(int $schoolId, Request $request, ?int $academicYearId): array
    {
        $classrooms = $this->classroomsForYear($schoolId, $academicYearId);

        $selectedClassroom = $classrooms->firstWhere('id', $request->integer('classroom_id'));
        $dateFrom = $this->parseDate((string) $request->get('date_from', ''));
        $dateTo = $this->parseDate((string) $request->get('date_to', ''), true);
        $status = trim((string) $request->get('status', ''));
        if (!in_array($status, Attendance::statuses(), true)) {
            $status = '';
        }

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->when($selectedClassroom, function (Builder $query) use ($selectedClassroom, $schoolId, $academicYearId) {
                if ($this->placements->supportsPlacements() && $academicYearId) {
                    $query->whereHas('academicYears', function (Builder $placements) use ($selectedClassroom, $schoolId, $academicYearId) {
                        $placements->where('school_id', $schoolId)
                            ->where('academic_year_id', $academicYearId)
                            ->where('classroom_id', $selectedClassroom->id);
                    });

                    return;
                }

                $query->where('classroom_id', $selectedClassroom->id);
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'classroom_id']);

        $selectedStudent = $students->firstWhere('id', $request->integer('student_id'));
        if (!$selectedStudent && !$selectedClassroom && $request->integer('student_id') > 0) {
            $selectedStudent = Student::query()
                ->where('school_id', $schoolId)
                ->find($request->integer('student_id'), ['id', 'full_name', 'classroom_id']);
            if ($selectedStudent) {
                $students = Student::query()
                    ->where('school_id', $schoolId)
                    ->when($this->placements->classroomIdForStudent($selectedStudent, $schoolId, $academicYearId), fn (Builder $query, $classroomId) => $query->where('classroom_id', $classroomId))
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'classroom_id']);
                $selectedClassroom ??= $classrooms->firstWhere('id', $this->placements->classroomIdForStudent($selectedStudent, $schoolId, $academicYearId));
            }
        }

        return [
            'classrooms' => $classrooms,
            'students' => $students,
            'selectedClassroom' => $selectedClassroom,
            'selectedStudent' => $selectedStudent,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
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

    private function yearAwareAttendanceQuery(int $schoolId, ?int $academicYearId): Builder
    {
        return $this->academicYears->applyYearScope(
            Attendance::query()->where('school_id', $schoolId),
            $schoolId,
            $academicYearId,
            'attendances',
            true,
        );
    }

    private function classroomsForYear(int $schoolId, ?int $academicYearId): Collection
    {
        $query = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name')
            ->orderBy('name');

        if ($this->placements->supportsPlacements() && $academicYearId) {
            $classroomIds = \App\Models\StudentAcademicYear::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->whereNotNull('classroom_id')
                ->distinct()
                ->pluck('classroom_id');

            if ($classroomIds->isNotEmpty()) {
                $query->whereIn('id', $classroomIds->all());
            }
        }

        return $query->get(['id', 'name', 'level_id']);
    }

    private function classroomNamesForYear(int $schoolId, ?int $academicYearId): array
    {
        return $this->classroomsForYear($schoolId, $academicYearId)
            ->pluck('name', 'id')
            ->all();
    }
}
