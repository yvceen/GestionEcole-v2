<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {

            // أهم الأعمدة
            if (!Schema::hasColumn('grades', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }

            if (!Schema::hasColumn('grades', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->index()->after('school_id');
            }

            if (!Schema::hasColumn('grades', 'assessment_id')) {
                $table->unsignedBigInteger('assessment_id')->nullable()->index()->after('student_id');
            }

            if (!Schema::hasColumn('grades', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable()->index()->after('assessment_id');
            }

            if (!Schema::hasColumn('grades', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->index()->after('classroom_id');
            }

            if (!Schema::hasColumn('grades', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->index()->after('teacher_id');
            }

            // النقطة
            if (!Schema::hasColumn('grades', 'score')) {
                $table->decimal('score', 6, 2)->nullable()->after('subject_id');
            }

            if (!Schema::hasColumn('grades', 'max_score')) {
                $table->unsignedTinyInteger('max_score')->default(20)->after('score');
            }
        });

        // (اختياري) unique key باش updateOrCreate ما يكررش نفس note
        Schema::table('grades', function (Blueprint $table) {
            // بعض MySQL كيرفض يضيف index إذا شي عمود null كامل، ولكن غالباً غادي يدوز
            // إذا عطاك error فهاد unique، قوليا ونحيدوه
            $indexName = 'grades_student_assessment_unique';
            try {
                $table->unique(['student_id', 'assessment_id'], $indexName);
            } catch (\Throwable $e) {
                // ignore if already exists or cannot be created now
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {

            // نحيدو unique إذا كان
            try { $table->dropUnique('grades_student_assessment_unique'); } catch (\Throwable $e) {}

            // drop columns (اختياري)
            foreach (['max_score','score','subject_id','teacher_id','classroom_id','assessment_id','student_id','school_id'] as $col) {
                if (Schema::hasColumn('grades', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
