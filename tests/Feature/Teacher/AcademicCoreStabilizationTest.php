<?php

namespace Tests\Feature\Teacher;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicCoreStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_assessment_with_full_academic_context(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();
        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH-' . Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $teacher->teacherClassrooms()->attach($classroom->id, ['school_id' => $school->id]);
        $teacher->subjects()->attach($subject->id, ['school_id' => $school->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.assessments.store', absolute: false), [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Controle 1',
            'date' => '2026-03-01',
            'max_score' => 40,
            'description' => 'Chapitre 1',
        ]);

        $response->assertRedirect(route('teacher.assessments.index', absolute: false));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessments', [
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Controle 1',
            'max_score' => 40,
            'description' => 'Chapitre 1',
        ]);
    }

    public function test_teacher_cannot_create_assessment_for_unassigned_subject(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();
        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Physics',
            'code' => 'PHY-' . Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $teacher->teacherClassrooms()->attach($classroom->id, ['school_id' => $school->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.assessments.store', absolute: false), [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Controle refuse',
            'date' => '2026-03-01',
            'max_score' => 20,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('assessments', ['title' => 'Controle refuse']);
    }

    public function test_teacher_can_store_grades_only_for_students_in_the_assessment_classroom(): void
    {
        [$school, $teacher, $classroomA] = $this->createTeacherContext();
        [, , $classroomB] = $this->createTeacherContext($school, 'B');

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'French',
            'code' => 'FR-' . Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $teacher->teacherClassrooms()->attach($classroomA->id, ['school_id' => $school->id]);
        $teacher->subjects()->attach($subject->id, ['school_id' => $school->id]);

        $studentA = Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student A',
            'classroom_id' => $classroomA->id,
        ]);
        $studentB = Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student B',
            'classroom_id' => $classroomB->id,
        ]);

        $assessment = Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroomA->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Composition',
            'date' => '2026-03-02',
            'max_score' => 20,
        ]);

        $response = $this->actingAs($teacher)->post(route('teacher.grades.store', absolute: false), [
            'assessment_id' => $assessment->id,
            'scores' => [
                $studentA->id => 16.5,
                $studentB->id => 12,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('scores');

        $this->assertDatabaseCount('grades', 0);
    }

    public function test_admin_cannot_delete_a_subject_that_is_already_in_use(): void
    {
        [$school, $teacher, $classroom] = $this->createTeacherContext();
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'History',
            'code' => 'HIS-' . Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $assessment = Assessment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Devoir',
            'date' => '2026-03-03',
            'max_score' => 20,
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => Student::create([
                'school_id' => $school->id,
                'full_name' => 'Student C',
                'classroom_id' => $classroom->id,
            ])->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 14,
            'max_score' => 20,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.subjects.destroy', $subject, false));

        $response->assertRedirect(route('admin.subjects.index', absolute: false));
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    private function createTeacherContext(?School $school = null, string $section = 'A'): array
    {
        $school ??= School::create([
            'name' => 'Academic School',
            'slug' => 'academic-school-' . Str::lower(Str::random(6)),
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

        return [$school, $teacher, $classroom];
    }
}
