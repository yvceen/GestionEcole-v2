<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class RoleBoundaryMatrixTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_teacher_cannot_access_admin_only_modules(): void
    {
        $school = $this->createSchool(['slug' => 'role-teacher']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);

        $this->actingAs($teacher)->get(route('admin.students.index', absolute: false))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.finance.index', absolute: false))->assertForbidden();
    }

    public function test_parent_cannot_access_admin_or_teacher_internals(): void
    {
        $school = $this->createSchool(['slug' => 'role-parent']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $this->actingAs($parent)->get(route('admin.students.index', absolute: false))->assertForbidden();
        $this->actingAs($parent)->get(route('teacher.assessments.index', absolute: false))->assertForbidden();
    }

    public function test_student_cannot_access_parent_teacher_or_admin_modules(): void
    {
        $school = $this->createSchool(['slug' => 'role-student']);
        $student = $this->createUserForSchool($school, User::ROLE_STUDENT);

        $this->actingAs($student)->get(route('admin.students.index', absolute: false))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.homeworks.index', absolute: false))->assertForbidden();
        $this->actingAs($student)->get(route('parent.children.index', absolute: false))->assertForbidden();
    }

    public function test_director_can_access_director_portal_but_not_admin_modules(): void
    {
        $school = $this->createSchool(['slug' => 'role-director']);
        $director = $this->createUserForSchool($school, User::ROLE_DIRECTOR);

        $this->actingAs($director)->get(route('director.dashboard', absolute: false))->assertOk();
        $this->actingAs($director)->get(route('admin.students.index', absolute: false))->assertForbidden();
        $this->actingAs($director)->get(route('teacher.messages.index', absolute: false))->assertForbidden();
    }

    public function test_super_admin_can_access_super_dashboard_but_not_role_locked_teacher_portal(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Platform Super Admin',
            'email' => 'superadmin-boundaries@example.test',
        ]);

        $this->actingAs($superAdmin)->get(route('super.dashboard', absolute: false))->assertOk();
        $this->actingAs($superAdmin)->get(route('teacher.messages.index', absolute: false))->assertForbidden();
    }
}
