<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parent_student_fees') || !Schema::hasTable('students')) {
            return;
        }

        if (!Schema::hasColumn('parent_student_fees', 'school_id')) {
            Schema::table('parent_student_fees', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("\n                UPDATE parent_student_fees pf\n                JOIN students s ON s.id = pf.student_id\n                SET pf.school_id = s.school_id\n                WHERE pf.school_id IS NULL\n            ");
        } else {
            DB::table('parent_student_fees')
                ->select(['id', 'student_id'])
                ->whereNull('school_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $schoolId = DB::table('students')
                            ->where('id', $row->student_id)
                            ->value('school_id');

                        if ($schoolId) {
                            DB::table('parent_student_fees')
                                ->where('id', $row->id)
                                ->update(['school_id' => $schoolId]);
                        }
                    }
                });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE parent_student_fees MODIFY school_id BIGINT UNSIGNED NOT NULL');
        }

        try {
            DB::statement('CREATE INDEX parent_student_fees_school_id_index ON parent_student_fees (school_id)');
        } catch (\Throwable $e) {
            // Index already exists.
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('parent_student_fees', 'school_id')) {
            try {
                DB::statement('DROP INDEX parent_student_fees_school_id_index ON parent_student_fees');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            Schema::table('parent_student_fees', function (Blueprint $table) {
                $table->dropColumn('school_id');
            });
        }
    }
};
