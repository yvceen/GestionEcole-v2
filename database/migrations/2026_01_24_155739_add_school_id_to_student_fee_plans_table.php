<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_plans') || !Schema::hasTable('students')) {
            return;
        }

        if (!Schema::hasColumn('student_fee_plans', 'school_id')) {
            Schema::table('student_fee_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("\n                UPDATE student_fee_plans sf\n                JOIN students s ON s.id = sf.student_id\n                SET sf.school_id = s.school_id\n                WHERE sf.school_id IS NULL\n            ");
        } else {
            DB::table('student_fee_plans')
                ->select(['id', 'student_id'])
                ->whereNull('school_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $schoolId = DB::table('students')
                            ->where('id', $row->student_id)
                            ->value('school_id');

                        if ($schoolId) {
                            DB::table('student_fee_plans')
                                ->where('id', $row->id)
                                ->update(['school_id' => $schoolId]);
                        }
                    }
                });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE student_fee_plans MODIFY school_id BIGINT UNSIGNED NOT NULL');
        }

        try {
            DB::statement('CREATE INDEX student_fee_plans_school_id_index ON student_fee_plans (school_id)');
        } catch (\Throwable $e) {
            // Index already exists.
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_fee_plans', 'school_id')) {
            try {
                DB::statement('DROP INDEX student_fee_plans_school_id_index ON student_fee_plans');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            Schema::table('student_fee_plans', function (Blueprint $table) {
                $table->dropColumn('school_id');
            });
        }
    }
};
