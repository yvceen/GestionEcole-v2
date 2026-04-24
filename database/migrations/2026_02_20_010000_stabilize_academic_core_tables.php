<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillTeacherSubjectSchools();
        $this->backfillAssessments();
        $this->backfillGrades();

        if (Schema::hasTable('teacher_subjects') && !$this->hasIndex('teacher_subjects', 'teacher_subjects_school_subject_lookup_index')) {
            Schema::table('teacher_subjects', function (Blueprint $table): void {
                $table->index(['school_id', 'subject_id'], 'teacher_subjects_school_subject_lookup_index');
            });
        }

        if (Schema::hasTable('assessments') && !$this->hasIndex('assessments', 'assessments_school_teacher_date_index')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->index(['school_id', 'teacher_id', 'date'], 'assessments_school_teacher_date_index');
            });
        }

        if (Schema::hasTable('assessments') && !$this->hasIndex('assessments', 'assessments_school_classroom_subject_index')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->index(['school_id', 'classroom_id', 'subject_id'], 'assessments_school_classroom_subject_index');
            });
        }

        if (Schema::hasTable('grades') && !$this->hasIndex('grades', 'grades_school_assessment_student_index')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->index(['school_id', 'assessment_id', 'student_id'], 'grades_school_assessment_student_index');
            });
        }

        if (Schema::hasTable('grades') && !$this->hasIndex('grades', 'grades_school_student_subject_index')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->index(['school_id', 'student_id', 'subject_id'], 'grades_school_student_subject_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grades') && $this->hasIndex('grades', 'grades_school_student_subject_index')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropIndex('grades_school_student_subject_index');
            });
        }

        if (Schema::hasTable('grades') && $this->hasIndex('grades', 'grades_school_assessment_student_index')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropIndex('grades_school_assessment_student_index');
            });
        }

        if (Schema::hasTable('assessments') && $this->hasIndex('assessments', 'assessments_school_classroom_subject_index')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropIndex('assessments_school_classroom_subject_index');
            });
        }

        if (Schema::hasTable('assessments') && $this->hasIndex('assessments', 'assessments_school_teacher_date_index')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropIndex('assessments_school_teacher_date_index');
            });
        }

        if (Schema::hasTable('teacher_subjects') && $this->hasIndex('teacher_subjects', 'teacher_subjects_school_subject_lookup_index')) {
            Schema::table('teacher_subjects', function (Blueprint $table): void {
                $table->dropIndex('teacher_subjects_school_subject_lookup_index');
            });
        }
    }

    private function backfillTeacherSubjectSchools(): void
    {
        if (!Schema::hasTable('teacher_subjects')) {
            return;
        }

        DB::statement("
            UPDATE teacher_subjects ts
            LEFT JOIN users u ON u.id = ts.teacher_id
            LEFT JOIN subjects s ON s.id = ts.subject_id
            SET ts.school_id = COALESCE(ts.school_id, u.school_id, s.school_id)
            WHERE ts.school_id IS NULL
        ");
    }

    private function backfillAssessments(): void
    {
        if (!Schema::hasTable('assessments')) {
            return;
        }

        DB::statement("
            UPDATE assessments a
            LEFT JOIN classrooms c ON c.id = a.classroom_id
            LEFT JOIN users u ON u.id = a.teacher_id
            LEFT JOIN subjects s ON s.id = a.subject_id
            SET a.school_id = COALESCE(a.school_id, c.school_id, u.school_id, s.school_id),
                a.max_score = COALESCE(a.max_score, 20)
            WHERE a.school_id IS NULL OR a.max_score IS NULL
        ");
    }

    private function backfillGrades(): void
    {
        if (!Schema::hasTable('grades')) {
            return;
        }

        DB::statement("
            UPDATE grades g
            LEFT JOIN assessments a ON a.id = g.assessment_id
            LEFT JOIN students st ON st.id = g.student_id
            SET g.school_id = COALESCE(g.school_id, a.school_id, st.school_id),
                g.classroom_id = COALESCE(g.classroom_id, a.classroom_id, st.classroom_id),
                g.teacher_id = COALESCE(g.teacher_id, a.teacher_id),
                g.subject_id = COALESCE(g.subject_id, a.subject_id),
                g.max_score = COALESCE(g.max_score, a.max_score, 20)
            WHERE g.school_id IS NULL
               OR g.classroom_id IS NULL
               OR g.teacher_id IS NULL
               OR g.subject_id IS NULL
               OR g.max_score IS NULL
        ");
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM `$table`"))
            ->contains(fn ($index) => (string) $index->Key_name === $indexName);
    }
};
