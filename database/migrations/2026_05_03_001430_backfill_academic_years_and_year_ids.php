<?php

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'attendances',
        'assessments',
        'grades',
        'homeworks',
        'timetables',
        'payments',
        'payment_items',
        'parent_student_fees',
        'student_fee_plans',
        'transport_assignments',
        'activities',
        'activity_participants',
        'courses',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('schools') || !Schema::hasTable('academic_years')) {
            return;
        }

        $service = app(AcademicYearService::class);

        School::query()->select('id')->orderBy('id')->each(function (School $school) use ($service): void {
            $schoolId = (int) $school->id;
            if ($schoolId <= 0) {
                return;
            }

            $year = $service->getCurrentYearForSchool($schoolId) ?? $service->createDefaultYearForSchool($schoolId);
            $this->backfillYearSensitiveTables($schoolId, $year);
            $this->backfillStudentPlacements($schoolId, $year);
        });
    }

    public function down(): void
    {
        // Keep backfilled data intact.
    }

    private function backfillYearSensitiveTables(int $schoolId, AcademicYear $year): void
    {
        foreach ($this->tables as $table) {
            if (
                !Schema::hasTable($table)
                || !Schema::hasColumn($table, 'academic_year_id')
            ) {
                continue;
            }

            if (
                Schema::hasColumn($table, 'school_id')
                && $this->canUseColumns($table, ['school_id', 'academic_year_id'])
            ) {
                DB::table($table)
                    ->where('school_id', $schoolId)
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $year->id]);
                continue;
            }

            if (
                $table === 'payment_items'
                && Schema::hasTable('payment_items')
                && Schema::hasTable('payments')
                && $this->canUseColumns('payment_items', ['payment_id', 'academic_year_id'])
                && $this->canUseColumns('payments', ['id', 'school_id', 'academic_year_id'])
            ) {
                $payments = DB::table('payments')
                    ->select(['id', 'academic_year_id'])
                    ->where('school_id', $schoolId)
                    ->whereNotNull('academic_year_id')
                    ->get();

                foreach ($payments as $payment) {
                    $paymentId = property_exists($payment, 'id') ? (int) ($payment->id ?? 0) : 0;
                    $academicYearId = property_exists($payment, 'academic_year_id')
                        ? (int) ($payment->academic_year_id ?? 0)
                        : 0;

                    if ($paymentId <= 0 || $academicYearId <= 0) {
                        continue;
                    }

                    DB::table('payment_items')
                        ->where('payment_id', $paymentId)
                        ->whereNull('academic_year_id')
                        ->update(['academic_year_id' => $academicYearId]);
                }
            }
        }
    }

    private function backfillStudentPlacements(int $schoolId, AcademicYear $year): void
    {
        if (
            !Schema::hasTable('student_academic_years')
            || !Schema::hasTable('students')
            || !Schema::hasTable('academic_years')
            || !$this->canUseColumns('student_academic_years', ['school_id', 'student_id', 'academic_year_id', 'status'])
            || !$this->canUseColumns('students', ['id', 'school_id'])
        ) {
            return;
        }

        $hasClassroomId = Schema::hasColumn('students', 'classroom_id');
        $hasArchivedAt = Schema::hasColumn('students', 'archived_at');
        $hasValidClassroomsTable = Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'id');

        $studentSelects = ['id'];
        if ($hasClassroomId) {
            $studentSelects[] = 'classroom_id';
        }
        if ($hasArchivedAt) {
            $studentSelects[] = 'archived_at';
        }

        DB::table('students')
            ->select($studentSelects)
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($schoolId, $year): void {
                foreach ($students as $student) {
                    $studentId = property_exists($student, 'id') ? (int) ($student->id ?? 0) : 0;
                    if ($studentId <= 0) {
                        continue;
                    }

                    $classroomId = property_exists($student, 'classroom_id')
                        ? (int) ($student->classroom_id ?? 0)
                        : 0;
                    $archivedAt = property_exists($student, 'archived_at')
                        ? $student->archived_at
                        : null;

                    if (
                        $classroomId > 0
                        && !(Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'id'))
                    ) {
                        $classroomId = 0;
                    }

                    if (
                        $classroomId > 0
                        && Schema::hasTable('classrooms')
                        && Schema::hasColumn('classrooms', 'school_id')
                        && !DB::table('classrooms')
                            ->where('id', $classroomId)
                            ->where('school_id', $schoolId)
                            ->exists()
                    ) {
                        $classroomId = 0;
                    }

                    StudentAcademicYear::query()->updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'student_id' => $studentId,
                            'academic_year_id' => $year->id,
                        ],
                        [
                            'classroom_id' => $classroomId > 0 && $hasValidClassroomsTable ? $classroomId : null,
                            'status' => $archivedAt ? StudentAcademicYear::STATUS_LEFT : StudentAcademicYear::STATUS_ENROLLED,
                        ]
                    );
                }
            });
    }

    private function canUseColumns(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
