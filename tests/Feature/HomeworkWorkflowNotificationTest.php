<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomeworkWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_created_homework_is_pending(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom();
        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($teacher)->get('/teacher/homeworks')->assertOk();

        $response = $this->actingAs($teacher)->post('/teacher/homeworks', [
            'classroom_id' => $classroom->id,
            'title' => 'HW Pending',
            'description' => 'desc',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('teacher.homeworks.index', absolute: false));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('homeworks', [
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'HW Pending',
            'status' => 'pending',
        ]);
    }

    public function test_admin_approve_notifies_only_targeted_class_parents(): void
    {
        [$school, $classA] = $this->createSchoolAndClassroom('A');
        [, $classB] = $this->createSchoolAndClassroom('B', $school);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $parentA = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
            'is_active' => true,
        ]);
        $parentB = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
            'is_active' => true,
        ]);

        Student::create([
            'school_id' => $school->id,
            'full_name' => 'Child A',
            'parent_user_id' => $parentA->id,
            'classroom_id' => $classA->id,
        ]);
        Student::create([
            'school_id' => $school->id,
            'full_name' => 'Child B',
            'parent_user_id' => $parentB->id,
            'classroom_id' => $classB->id,
        ]);

        $homework = Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classA->id,
            'teacher_id' => $teacher->id,
            'title' => 'HW Approval',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.homeworks.approve', $homework, false));
        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parentA->id,
            'type' => 'homework',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $parentB->id,
            'type' => 'homework',
        ]);
    }

    public function test_parent_and_student_only_see_approved_homeworks(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom();

        $parent = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
            'is_active' => true,
        ]);
        $studentUser = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'student',
            'is_active' => true,
        ]);
        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        Student::create([
            'school_id' => $school->id,
            'full_name' => 'Child',
            'parent_user_id' => $parent->id,
            'user_id' => $studentUser->id,
            'classroom_id' => $classroom->id,
        ]);

        Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'HW Approved Visible',
            'status' => 'approved',
        ]);
        Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'HW Pending Hidden',
            'status' => 'pending',
        ]);

        $parentResponse = $this->actingAs($parent)->get('/parent/homeworks');
        $parentResponse->assertOk();
        $parentResponse->assertSee('HW Approved Visible');
        $parentResponse->assertDontSee('HW Pending Hidden');

        $studentResponse = $this->actingAs($studentUser)->get('/student/homeworks');
        $studentResponse->assertOk();
        $studentResponse->assertSee('HW Approved Visible');
        $studentResponse->assertDontSee('HW Pending Hidden');
    }

    private function createSchoolAndClassroom(string $suffix = 'A', ?School $existingSchool = null): array
    {
        $school = $existingSchool ?: School::create([
            'name' => 'Test School',
            'slug' => 'test-school-' . Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $level = Level::create([
            'code' => 'LVL' . Str::upper(Str::random(5)),
            'name' => 'Level ' . $suffix,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level->school_id = $school->id;
        $level->save();

        $classroom = Classroom::create([
            'level_id' => $level->id,
            'section' => $suffix,
            'name' => 'Class ' . $suffix,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $classroom->school_id = $school->id;
        $classroom->save();

        return [$school, $classroom];
    }
}
