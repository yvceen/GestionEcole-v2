<?php

namespace Tests\Feature\Admin;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicIntegrityConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_assessment_with_missing_subject(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();

        $this->expectException(QueryException::class);

        DB::table('assessments')->insert([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => 999999,
            'title' => 'Invalid Assessment',
            'date' => '2026-03-10',
            'max_score' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_grade_with_missing_assessment(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();
        $student = Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student A',
            'classroom_id' => $classroom->id,
        ]);

        $this->expectException(QueryException::class);

        DB::table('grades')->insert([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $this->createSubject($school)->id,
            'assessment_id' => 999999,
            'score' => 10,
            'max_score' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_cannot_delete_teacher_referenced_by_assessments(): void
    {
        [$school, $teacher, $classroom, $admin] = $this->createTeacherContext(withAdmin: true);
        $subject = $this->createSubject($school);

        Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Existing Assessment',
            'date' => '2026-03-11',
            'max_score' => 20,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $teacher, false));

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete_user');
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }

    public function test_admin_cannot_delete_classroom_referenced_by_assessments(): void
    {
        [$school, $teacher, $classroom, $admin] = $this->createTeacherContext(withAdmin: true);
        $subject = $this->createSubject($school);

        Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Existing Assessment',
            'date' => '2026-03-11',
            'max_score' => 20,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.structure.classrooms.destroy', $classroom, false));

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete_classroom');
        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
    }

    public function test_admin_cannot_delete_student_with_grades(): void
    {
        [$school, $teacher, $classroom, $admin] = $this->createTeacherContext(withAdmin: true);
        $subject = $this->createSubject($school);
        $student = Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student A',
            'classroom_id' => $classroom->id,
        ]);

        $assessment = Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Composition',
            'date' => '2026-03-11',
            'max_score' => 20,
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 15,
            'max_score' => 20,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.students.destroy', $student, false));

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete_student');
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_school_delete_still_removes_academic_rows(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();
        $subject = $this->createSubject($school);

        DB::table('teacher_subjects')->insert([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assigned_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student A',
            'classroom_id' => $classroom->id,
        ]);

        $assessment = Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Composition',
            'date' => '2026-03-11',
            'max_score' => 20,
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 14,
            'max_score' => 20,
        ]);

        $schoolId = $school->id;
        $subjectId = $subject->id;
        $assessmentId = $assessment->id;
        $gradeId = Grade::query()->value('id');

        $school->delete();

        $this->assertDatabaseMissing('schools', ['id' => $schoolId]);
        $this->assertDatabaseMissing('subjects', ['id' => $subjectId]);
        $this->assertDatabaseMissing('assessments', ['id' => $assessmentId]);
        $this->assertDatabaseMissing('grades', ['id' => $gradeId]);
    }

    private function createTeacherContext(?School $school = null, string $section = 'A', bool $withAdmin = false): array
    {
        $school ??= School::create([
            'name' => 'Integrity School',
            'slug' => 'integrity-school-' . Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $level = Level::create([
            'code' => 'LVL' . Str::upper(Str::random(5)),
            'name' => 'Level ' . $section,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level->school_id = $school->id;
        $level->save();

        $classroom = Classroom::create([
            'level_id' => $level->id,
            'section' => $section,
            'name' => 'Class ' . $section,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $classroom->school_id = $school->id;
        $classroom->save();

        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $admin = null;
        if ($withAdmin) {
            $admin = User::factory()->create([
                'school_id' => $school->id,
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        return [$school, $teacher, $classroom, $admin];
    }

    private function createSubject(School $school): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'name' => 'Subject ' . Str::upper(Str::random(4)),
            'code' => 'SUB-' . Str::upper(Str::random(4)),
            'is_active' => true,
        ]);
    }
}
