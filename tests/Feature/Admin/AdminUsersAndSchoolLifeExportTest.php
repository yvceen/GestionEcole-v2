<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AdminUsersAndSchoolLifeExportTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_admin_users_list_displays_student_role_correctly_and_filter_supports_students(): void
    {
        $school = $this->createSchool(['slug' => 'admin-user-role-badges']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN, ['name' => 'Admin Owner']);
        $this->createUserForSchool($school, User::ROLE_STUDENT, ['name' => 'Student Account']);
        $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Account']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', absolute: false))
            ->assertOk()
            ->assertSee('Student Account')
            ->assertSee('Parent Account')
            ->assertSee('Eleve')
            ->assertSee('Parent');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => User::ROLE_STUDENT], false))
            ->assertOk()
            ->assertSee('Student Account')
            ->assertSee('Eleve')
            ->assertDontSee('Parent Account');
    }

    public function test_admin_can_update_user_email_and_password_or_keep_existing_hash_when_blank(): void
    {
        $school = $this->createSchool(['slug' => 'admin-user-credentials']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $user = $this->createUserForSchool($school, User::ROLE_STUDENT, [
            'email' => 'student-old@example.test',
            'password' => bcrypt('OldPass123'),
        ]);

        $oldHash = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user, false), [
                'name' => $user->name,
                'email' => 'student-new@example.test',
                'phone' => $user->phone,
                'role' => User::ROLE_STUDENT,
                'password' => '',
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $user->refresh();
        $this->assertSame('student-new@example.test', $user->email);
        $this->assertSame($oldHash, $user->password);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user, false), [
                'name' => $user->name,
                'email' => 'student-new@example.test',
                'phone' => $user->phone,
                'role' => User::ROLE_STUDENT,
                'password' => 'Secure1234',
            ])
            ->assertRedirect(route('admin.users.index', absolute: false));

        $user->refresh();
        $this->assertTrue(Hash::check('Secure1234', $user->password));
    }

    public function test_school_life_export_is_excel_compatible_respects_filters_and_school_scope(): void
    {
        $school = $this->createSchool(['slug' => 'school-life-attendance-export']);
        $schoolLife = $this->createUserForSchool($school, User::ROLE_SCHOOL_LIFE);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Mme Appel']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'CE1 A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Salma Export']);

        $otherSchool = $this->createSchool(['slug' => 'school-life-attendance-export-foreign']);
        $foreignTeacher = $this->createUserForSchool($otherSchool, User::ROLE_TEACHER, ['name' => 'Foreign Teacher']);
        $foreignParent = $this->createUserForSchool($otherSchool, User::ROLE_PARENT);
        $foreignClassroom = $this->createClassroomForSchool($otherSchool, [], ['name' => 'Foreign Class']);
        $foreignStudent = $this->createStudentForSchool($otherSchool, $foreignClassroom, $foreignParent, null, ['full_name' => 'Foreign Student']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => '2026-02-10',
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Certificat en attente',
            'marked_by_user_id' => $teacher->id,
        ]);

        Attendance::create([
            'school_id' => $otherSchool->id,
            'student_id' => $foreignStudent->id,
            'classroom_id' => $foreignClassroom->id,
            'date' => '2026-02-10',
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Absence etrangere',
            'marked_by_user_id' => $foreignTeacher->id,
        ]);

        $response = $this->actingAs($schoolLife)
            ->get(route('school-life.attendance.export', [
                'status' => Attendance::STATUS_ABSENT,
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
                'date_from' => '2026-02-01',
                'date_to' => '2026-02-28',
            ], false));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Salma Export', false);
        $response->assertSee('CE1 A', false);
        $response->assertSee('Absent', false);
        $response->assertSee('Certificat en attente', false);
        $response->assertDontSee('Foreign Student', false);
        $response->assertDontSee('Absence etrangere', false);
    }
}
