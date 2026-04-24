<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1️⃣ Ensure columns exist AND are nullable (IMPORTANT)
         */
        Schema::table('teacher_subject', function (Blueprint $table) {

            // teacher_id
            if (!Schema::hasColumn('teacher_subject', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
            } else {
                $table->unsignedBigInteger('teacher_id')->nullable()->change();
            }

            // subject_id
            if (!Schema::hasColumn('teacher_subject', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->index();
            } else {
                $table->unsignedBigInteger('subject_id')->nullable()->change();
            }

            // school_id
            if (!Schema::hasColumn('teacher_subject', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index();
            } else {
                $table->unsignedBigInteger('school_id')->nullable()->change();
            }

            if (!Schema::hasColumn('teacher_subject', 'assigned_by_user_id')) {
                $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index();
            }

            if (!Schema::hasColumn('teacher_subject', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('teacher_subject', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        /**
         * 2️⃣ Drop existing FKs ONLY if they exist (fresh-safe)
         */
        foreach (['teacher_id', 'subject_id', 'school_id'] as $col) {
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'teacher_subject'
                  AND COLUMN_NAME = '{$col}'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");

            if ($fk) {
                Schema::table('teacher_subject', function (Blueprint $table) use ($col) {
                    $table->dropForeign([$col]);
                });
            }
        }

        /**
         * 3️⃣ Add foreign keys (SAFE because columns are nullable)
         */
        Schema::table('teacher_subject', function (Blueprint $table) {

            if (Schema::hasTable('users')) {
                $table->foreign('teacher_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('subjects')) {
                $table->foreign('subject_id')
                    ->references('id')
                    ->on('subjects')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('schools')) {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        foreach (['teacher_id', 'subject_id', 'school_id'] as $col) {
            $fk = DB::selectOne("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'teacher_subject'
                  AND COLUMN_NAME = '{$col}'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");

            if ($fk) {
                Schema::table('teacher_subject', function (Blueprint $table) use ($col) {
                    $table->dropForeign([$col]);
                });
            }
        }
    }
};