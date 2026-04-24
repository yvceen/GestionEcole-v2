<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillAcademicSchoolIds();
        $this->assertAcademicReferencesAreValid();

        if ($this->hasIndex('grades', 'grades_student_id_assessment_id_unique') && $this->hasIndex('grades', 'grades_student_assessment_unique')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropUnique('grades_student_id_assessment_id_unique');
            });
        }

        $this->addAssessmentForeignKeys();
        $this->addGradeForeignKeys();
    }

    public function down(): void
    {
        if ($this->hasForeignKey('grades', 'grades_assessment_id_foreign')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropForeign('grades_assessment_id_foreign');
            });
        }

        if ($this->hasForeignKey('grades', 'grades_student_id_foreign')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropForeign('grades_student_id_foreign');
            });
        }

        if ($this->hasForeignKey('grades', 'grades_school_id_foreign')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropForeign('grades_school_id_foreign');
            });
        }

        if ($this->hasForeignKey('assessments', 'assessments_subject_id_foreign')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropForeign('assessments_subject_id_foreign');
            });
        }

        if ($this->hasForeignKey('assessments', 'assessments_teacher_id_foreign')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropForeign('assessments_teacher_id_foreign');
            });
        }

        if ($this->hasForeignKey('assessments', 'assessments_classroom_id_foreign')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropForeign('assessments_classroom_id_foreign');
            });
        }

        if ($this->hasForeignKey('assessments', 'assessments_school_id_foreign')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropForeign('assessments_school_id_foreign');
            });
        }

        if (!$this->hasIndex('grades', 'grades_student_id_assessment_id_unique') && $this->hasIndex('grades', 'grades_student_assessment_unique')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->unique(['student_id', 'assessment_id'], 'grades_student_id_assessment_id_unique');
            });
        }
    }

    private function addAssessmentForeignKeys(): void
    {
        if (!Schema::hasTable('assessments')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table): void {
            if (!$this->hasForeignKey('assessments', 'assessments_school_id_foreign')) {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            }

            if (!$this->hasForeignKey('assessments', 'assessments_classroom_id_foreign')) {
                $table->foreign('classroom_id')
                    ->references('id')
                    ->on('classrooms')
                    ->restrictOnDelete();
            }

            if (!$this->hasForeignKey('assessments', 'assessments_teacher_id_foreign')) {
                $table->foreign('teacher_id')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            }

            if (!$this->hasForeignKey('assessments', 'assessments_subject_id_foreign')) {
                $table->foreign('subject_id')
                    ->references('id')
                    ->on('subjects')
                    ->restrictOnDelete();
            }
        });
    }

    private function addGradeForeignKeys(): void
    {
        if (!Schema::hasTable('grades')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table): void {
            if (!$this->hasForeignKey('grades', 'grades_school_id_foreign')) {
                $table->foreign('school_id')
                    ->references('id')
                    ->on('schools')
                    ->restrictOnDelete();
            }

            if (!$this->hasForeignKey('grades', 'grades_student_id_foreign')) {
                $table->foreign('student_id')
                    ->references('id')
                    ->on('students')
                    ->restrictOnDelete();
            }

            if (!$this->hasForeignKey('grades', 'grades_assessment_id_foreign')) {
                $table->foreign('assessment_id')
                    ->references('id')
                    ->on('assessments')
                    ->restrictOnDelete();
            }
        });
    }

    private function backfillAcademicSchoolIds(): void
    {
        if (Schema::hasTable('assessments')) {
            DB::statement("
                UPDATE assessments a
                LEFT JOIN classrooms c ON c.id = a.classroom_id
                LEFT JOIN users u ON u.id = a.teacher_id
                LEFT JOIN subjects s ON s.id = a.subject_id
                SET a.school_id = COALESCE(a.school_id, c.school_id, u.school_id, s.school_id)
                WHERE a.school_id IS NULL
            ");
        }

        if (Schema::hasTable('grades')) {
            DB::statement("
                UPDATE grades g
                LEFT JOIN assessments a ON a.id = g.assessment_id
                LEFT JOIN students st ON st.id = g.student_id
                SET g.school_id = COALESCE(g.school_id, a.school_id, st.school_id)
                WHERE g.school_id IS NULL
            ");
        }
    }

    private function assertAcademicReferencesAreValid(): void
    {
        $checks = [
            'assessments.school_id' => "select count(*) c from assessments a left join schools s on s.id = a.school_id where a.school_id is null or s.id is null",
            'assessments.classroom_id' => "select count(*) c from assessments a left join classrooms c on c.id = a.classroom_id where a.classroom_id is null or c.id is null",
            'assessments.teacher_id' => "select count(*) c from assessments a left join users u on u.id = a.teacher_id where a.teacher_id is null or u.id is null",
            'assessments.subject_id' => "select count(*) c from assessments a left join subjects s on s.id = a.subject_id where a.subject_id is null or s.id is null",
            'grades.school_id' => "select count(*) c from grades g left join schools s on s.id = g.school_id where g.school_id is null or s.id is null",
            'grades.student_id' => "select count(*) c from grades g left join students st on st.id = g.student_id where g.student_id is null or st.id is null",
            'grades.assessment_id' => "select count(*) c from grades g left join assessments a on a.id = g.assessment_id where g.assessment_id is null or a.id is null",
            'assessments school alignment' => "select count(*) c from assessments a join subjects s on s.id = a.subject_id where a.school_id <> s.school_id",
            'grades school alignment' => "select count(*) c from grades g join assessments a on a.id = g.assessment_id where g.school_id <> a.school_id",
        ];

        foreach ($checks as $label => $sql) {
            $count = (int) (DB::selectOne($sql)->c ?? 0);
            if ($count > 0) {
                throw new RuntimeException("Academic foreign key preflight failed for {$label}: {$count} invalid rows.");
            }
        }
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        return collect(DB::select(
            'select constraint_name from information_schema.referential_constraints where constraint_schema = database() and table_name = ?',
            [$table]
        ))->contains(fn ($row) => (string) $row->constraint_name === $constraintName);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM `$table`"))
            ->contains(fn ($index) => (string) $index->Key_name === $indexName);
    }
};
