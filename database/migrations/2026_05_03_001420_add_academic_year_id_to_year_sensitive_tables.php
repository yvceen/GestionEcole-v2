<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'academic_year_id')) {
                continue;
            }

            $afterColumn = null;
            foreach (['school_id', 'student_id', 'classroom_id'] as $candidate) {
                if (Schema::hasColumn($table, $candidate)) {
                    $afterColumn = $candidate;
                    break;
                }
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $column = $blueprint->unsignedBigInteger('academic_year_id')->nullable();
                if ($afterColumn = $this->resolveAfterColumn($table)) {
                    $column->after($afterColumn);
                }

                if (Schema::hasColumn($table, 'school_id')) {
                    $blueprint->index(['school_id', 'academic_year_id'], "{$table}_school_year_idx");
                } else {
                    $blueprint->index('academic_year_id', "{$table}_academic_year_idx");
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'academic_year_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'school_id')) {
                    $blueprint->dropIndex("{$table}_school_year_idx");
                } else {
                    $blueprint->dropIndex("{$table}_academic_year_idx");
                }
                $blueprint->dropColumn('academic_year_id');
            });
        }
    }

    private function resolveAfterColumn(string $table): ?string
    {
        foreach (['school_id', 'student_id', 'classroom_id'] as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
};
