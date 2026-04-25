<?php

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademicYear;
use App\Services\AcademicYearService;
use Carbon\Carbon;
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
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'academic_year_id')) {
                continue;
            }

            if (Schema::hasColumn($table, 'school_id')) {
                DB::table($table)
                    ->where('school_id', $schoolId)
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $year->id]);
                continue;
            }

            if ($table === 'payment_items' && Schema::hasColumn('payment_items', 'payment_id') && Schema::hasTable('payments')) {
                $paymentYearMap = DB::table('payments')
                    ->select('id')
                    ->where('school_id', $schoolId)
                    ->whereNotNull('academic_year_id')
                    ->pluck('academic_year_id', 'id');

                foreach ($paymentYearMap as $paymentId => $academicYearId) {
                    DB::table('payment_items')
                        ->where('payment_id', (int) $paymentId)
                        ->whereNull('academic_year_id')
                        ->update(['academic_year_id' => (int) $academicYearId]);
                }
            }
        }
    }

    private function backfillStudentPlacements(int $schoolId, AcademicYear $year): void
    {
        if (!Schema::hasTable('student_academic_years') || !Schema::hasTable('students')) {
            return;
        }

        $hasArchivedAt = Schema::hasColumn('students', 'archived_at');

        DB::table('students')
            ->select('id', 'classroom_id', $hasArchivedAt ? 'archived_at' : DB::raw('NULL as archived_at'))
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($schoolId, $year): void {
                foreach ($students as $student) {
                    StudentAcademicYear::query()->updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'student_id' => (int) $student->id,
                            'academic_year_id' => $year->id,
                        ],
                        [
                            'classroom_id' => $student->classroom_id ? (int) $student->classroom_id : null,
                            'status' => $student->archived_at ? StudentAcademicYear::STATUS_LEFT : StudentAcademicYear::STATUS_ENROLLED,
                        ]
                    );
                }
            });
    }
};
