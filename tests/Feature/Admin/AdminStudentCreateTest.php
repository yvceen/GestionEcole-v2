<?php

namespace Tests\Feature\Admin;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminStudentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_student_with_existing_parent(): void
    {
        $school = School::create([
            'name' => 'Test School',
            'slug' => 'test-school-' . Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $level = Level::create([
            'code' => 'LVL' . Str::upper(Str::random(5)),
            'name' => 'Level 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level->school_id = $school->id;
        $level->save();

        $classroom = Classroom::create([
            'level_id' => $level->id,
            'section' => 'A',
            'name' => 'Class A',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $classroom->school_id = $school->id;
        $classroom->save();

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $parent = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/students', [
            'full_name' => 'Student Created',
            'birth_date' => '2015-05-01',
            'gender' => 'male',
            'classroom_id' => $classroom->id,
            'existing_parent_user_id' => $parent->id,
            'tuition_monthly' => 1000,
            'canteen_monthly' => 0,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => 0,
            'transport_enabled' => 0,
        ]);

        $response->assertRedirect(route('admin.students.index', absolute: false));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'full_name' => 'Student Created',
            'parent_user_id' => $parent->id,
            'classroom_id' => $classroom->id,
        ]);

        $student = Student::where('full_name', 'Student Created')->first();
        $this->assertNotNull($student);
    }
}
