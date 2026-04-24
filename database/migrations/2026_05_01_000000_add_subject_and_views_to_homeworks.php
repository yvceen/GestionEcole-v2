<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('homeworks')) {
            Schema::table('homeworks', function (Blueprint $table) {
                if (!Schema::hasColumn('homeworks', 'subject_id')) {
                    $table->unsignedBigInteger('subject_id')->nullable()->after('teacher_id')->index();
                }
            });

            if (Schema::hasTable('subjects')) {
                try {
                    DB::statement(<<<'SQL'
                        UPDATE homeworks h
                        INNER JOIN (
                            SELECT school_id, teacher_id, MIN(subject_id) AS subject_id
                            FROM teacher_subjects
                            GROUP BY school_id, teacher_id
                            HAVING COUNT(DISTINCT subject_id) = 1
                        ) ts
                            ON ts.school_id = h.school_id
                           AND ts.teacher_id = h.teacher_id
                        SET h.subject_id = ts.subject_id
                        WHERE h.subject_id IS NULL
                    SQL);
                } catch (\Throwable) {
                    // Keep the migration resilient on environments with partial academic data.
                }
            }
        }

        if (!Schema::hasTable('homework_user_views')) {
            Schema::create('homework_user_views', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('homework_id')->index();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'homework_id'], 'homework_user_views_user_homework_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'subject_id')) {
            Schema::table('homeworks', function (Blueprint $table) {
                $table->dropColumn('subject_id');
            });
        }

        Schema::dropIfExists('homework_user_views');
    }
};
