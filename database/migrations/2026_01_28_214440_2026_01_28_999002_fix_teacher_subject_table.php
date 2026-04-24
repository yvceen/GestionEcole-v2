<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_subject')) {
            Schema::create('teacher_subject', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index();
                $table->timestamps();
            });
            return;
        }

        Schema::table('teacher_subject', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_subject', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('teacher_subject', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->index()->after('school_id');
            }
            if (!Schema::hasColumn('teacher_subject', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->index()->after('teacher_id');
            }
            if (!Schema::hasColumn('teacher_subject', 'assigned_by_user_id')) {
                $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index()->after('subject_id');
            }
            if (!Schema::hasColumn('teacher_subject', 'created_at')) {
                $table->timestamps();
            }
        });

        // ✅ Backfill teacher_id من أي عمود قديم (إلا كان كاين)
        $cols = collect(DB::select("SHOW COLUMNS FROM `teacher_subject`"))
            ->pluck('Field')
            ->map(fn($x) => (string)$x)
            ->all();

        $possibleOldTeacherCols = [
            'user_id',
            'teacher_user_id',
            'prof_id',
            'enseignant_id',
            'teacher',
            'user',
        ];

        $old = null;
        foreach ($possibleOldTeacherCols as $c) {
            if (in_array($c, $cols, true)) { $old = $c; break; }
        }

        if ($old) {
            DB::statement("UPDATE `teacher_subject` SET `teacher_id` = `$old` WHERE `teacher_id` IS NULL");
        }
    }

    public function down(): void
    {
        // ما نحيد حتى حاجة
    }
};
