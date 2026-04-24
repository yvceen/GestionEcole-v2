<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillOwnershipColumns();
        $this->dropLegacyTeacherSubjectUnique();
        $this->addSafeForeignKeys();
    }

    public function down(): void
    {
        $this->dropForeignIfExists('homework_attachments', 'homework_attachments_school_id_foreign');
        $this->dropForeignIfExists('course_attachments', 'course_attachments_school_id_foreign');
        $this->dropForeignIfExists('courses', 'courses_school_id_foreign');
        $this->dropForeignIfExists('appointments', 'appointments_rejected_by_foreign');
        $this->dropForeignIfExists('appointments', 'appointments_approved_by_foreign');
        $this->dropForeignIfExists('appointments', 'appointments_parent_user_id_foreign');
        $this->dropForeignIfExists('appointments', 'appointments_school_id_foreign');
        $this->dropForeignIfExists('news', 'news_classroom_id_foreign');
        $this->dropForeignIfExists('news', 'news_school_id_foreign');
        $this->dropForeignIfExists('messages', 'messages_school_id_foreign');

        if (!$this->hasIndex('teacher_subjects', 'teacher_subject_teacher_id_subject_id_unique')
            && $this->hasIndex('teacher_subjects', 'teacher_subjects_unique')) {
            Schema::table('teacher_subjects', function (Blueprint $table): void {
                $table->unique(['teacher_id', 'subject_id'], 'teacher_subject_teacher_id_subject_id_unique');
            });
        }
    }

    private function backfillOwnershipColumns(): void
    {
        if (Schema::hasTable('appointments')
            && Schema::hasColumn('appointments', 'school_id')
            && Schema::hasColumn('appointments', 'parent_user_id')) {
            DB::statement("
                UPDATE appointments a
                LEFT JOIN users u ON u.id = a.parent_user_id
                SET a.school_id = COALESCE(a.school_id, u.school_id)
                WHERE a.school_id IS NULL
            ");
        }

        if (Schema::hasTable('news')
            && Schema::hasColumn('news', 'school_id')
            && Schema::hasColumn('news', 'classroom_id')) {
            DB::statement("
                UPDATE news n
                LEFT JOIN classrooms c ON c.id = n.classroom_id
                SET n.school_id = COALESCE(n.school_id, c.school_id)
                WHERE n.school_id IS NULL
            ");
        }

        if (Schema::hasTable('course_attachments') && Schema::hasTable('courses')
            && Schema::hasColumn('course_attachments', 'school_id')
            && Schema::hasColumn('course_attachments', 'course_id')
            && Schema::hasColumn('courses', 'school_id')) {
            DB::statement("
                UPDATE course_attachments ca
                JOIN courses c ON c.id = ca.course_id
                SET ca.school_id = c.school_id
                WHERE ca.school_id IS NULL
            ");
        }

        if (Schema::hasTable('homework_attachments') && Schema::hasTable('homeworks')
            && Schema::hasColumn('homework_attachments', 'school_id')
            && Schema::hasColumn('homework_attachments', 'homework_id')
            && Schema::hasColumn('homeworks', 'school_id')) {
            DB::statement("
                UPDATE homework_attachments ha
                JOIN homeworks h ON h.id = ha.homework_id
                SET ha.school_id = h.school_id
                WHERE ha.school_id IS NULL
            ");
        }
    }

    private function dropLegacyTeacherSubjectUnique(): void
    {
        if ($this->hasIndex('teacher_subjects', 'teacher_subject_teacher_id_subject_id_unique')
            && $this->hasIndex('teacher_subjects', 'teacher_subjects_unique')) {
            Schema::table('teacher_subjects', function (Blueprint $table): void {
                $table->dropUnique('teacher_subject_teacher_id_subject_id_unique');
            });
        }
    }

    private function addSafeForeignKeys(): void
    {
        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'school_id')
            && $this->canAddForeignKey("select count(*) c from messages m left join schools s on s.id = m.school_id where s.id is null")
            && !$this->hasForeignKey('messages', 'messages_school_id_foreign')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('news') && Schema::hasColumn('news', 'school_id')
            && $this->canAddForeignKey("select count(*) c from news n left join schools s on s.id = n.school_id where n.school_id is not null and s.id is null")
            && !$this->hasForeignKey('news', 'news_school_id_foreign')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('news') && Schema::hasColumn('news', 'classroom_id')
            && $this->canAddForeignKey("select count(*) c from news n left join classrooms c on c.id = n.classroom_id where n.classroom_id is not null and c.id is null")
            && !$this->hasForeignKey('news', 'news_classroom_id_foreign')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->foreign('classroom_id')
                    ->references('id')
                    ->on('classrooms')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'school_id')
            && $this->canAddForeignKey("select count(*) c from appointments a left join schools s on s.id = a.school_id where a.school_id is not null and s.id is null")
            && !$this->hasForeignKey('appointments', 'appointments_school_id_foreign')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'parent_user_id')
            && $this->canAddForeignKey("select count(*) c from appointments a left join users u on u.id = a.parent_user_id where a.parent_user_id is not null and u.id is null")
            && !$this->hasForeignKey('appointments', 'appointments_parent_user_id_foreign')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreign('parent_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'approved_by')
            && $this->canAddForeignKey("select count(*) c from appointments a left join users u on u.id = a.approved_by where a.approved_by is not null and u.id is null")
            && !$this->hasForeignKey('appointments', 'appointments_approved_by_foreign')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'rejected_by')
            && $this->canAddForeignKey("select count(*) c from appointments a left join users u on u.id = a.rejected_by where a.rejected_by is not null and u.id is null")
            && !$this->hasForeignKey('appointments', 'appointments_rejected_by_foreign')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreign('rejected_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'school_id')
            && $this->canAddForeignKey("select count(*) c from courses c left join schools s on s.id = c.school_id where s.id is null")
            && !$this->hasForeignKey('courses', 'courses_school_id_foreign')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('course_attachments') && Schema::hasColumn('course_attachments', 'school_id')
            && $this->canAddForeignKey("select count(*) c from course_attachments ca left join schools s on s.id = ca.school_id where s.id is null")
            && !$this->hasForeignKey('course_attachments', 'course_attachments_school_id_foreign')) {
            Schema::table('course_attachments', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('homework_attachments') && Schema::hasColumn('homework_attachments', 'school_id')
            && $this->canAddForeignKey("select count(*) c from homework_attachments ha left join schools s on s.id = ha.school_id where s.id is null")
            && !$this->hasForeignKey('homework_attachments', 'homework_attachments_school_id_foreign')) {
            Schema::table('homework_attachments', function (Blueprint $table): void {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            });
        }
    }

    private function canAddForeignKey(string $sql): bool
    {
        return ((int) DB::selectOne($sql)->c) === 0;
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (!$this->hasForeignKey($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($constraint): void {
            $table->dropForeign($constraint);
        });
    }
};
