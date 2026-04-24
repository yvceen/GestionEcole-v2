<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_teacher_can_record_and_update_attendance_for_an_assigned_class(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-teacher-flow']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Mme Presence']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Presence']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '6A']);
        $this->assignTeacherToClassroom($teacher, $classroom);

        $studentA = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Amina Present']);
        $studentB = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Bilal Retard']);
        $date = now()->toDateString();

        $this->actingAs($teacher)
            ->post(route('teacher.attendance.store', absolute: false), [
                'classroom_id' => $classroom->id,
                'date' => $date,
                'attendance' => [
                    $studentA->id => ['status' => Attendance::STATUS_ABSENT, 'note' => 'Malade'],
                    $studentB->id => ['status' => Attendance::STATUS_LATE, 'note' => 'Bus'],
                ],
            ])
            ->assertRedirect(route('teacher.attendance.index', ['classroom_id' => $classroom->id, 'date' => $date], false));

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'student_id' => $studentA->id,
            'classroom_id' => $classroom->id,
            'date' => $date,
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Malade',
            'marked_by_user_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'student_id' => $studentB->id,
            'classroom_id' => $classroom->id,
            'date' => $date,
            'status' => Attendance::STATUS_LATE,
            'note' => 'Bus',
            'marked_by_user_id' => $teacher->id,
        ]);
        $this->assertDatabaseCount('notifications', 2);

        $this->actingAs($teacher)
            ->post(route('teacher.attendance.store', absolute: false), [
                'classroom_id' => $classroom->id,
                'date' => $date,
                'attendance' => [
                    $studentA->id => ['status' => Attendance::STATUS_PRESENT, 'note' => ''],
                    $studentB->id => ['status' => Attendance::STATUS_LATE, 'note' => 'Bus traffic'],
                ],
            ])
            ->assertRedirect(route('teacher.attendance.index', ['classroom_id' => $classroom->id, 'date' => $date], false));

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentA->id,
            'date' => $date,
            'status' => Attendance::STATUS_PRESENT,
            'note' => null,
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $studentB->id,
            'date' => $date,
            'status' => Attendance::STATUS_LATE,
            'note' => 'Bus traffic',
        ]);
        $this->assertDatabaseCount('notifications', 2);

        $this->actingAs($teacher)
            ->get(route('teacher.attendance.index', ['classroom_id' => $classroom->id, 'date' => $date], false))
            ->assertOk()
            ->assertSee('Amina Present')
            ->assertSee('Bilal Retard')
            ->assertSee('Bus traffic');
    }

    public function test_teacher_cannot_record_attendance_for_an_unassigned_class(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-teacher-scope']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Hidden Class']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Scoped Student']);

        $this->actingAs($teacher)
            ->post(route('teacher.attendance.store', absolute: false), [
                'classroom_id' => $classroom->id,
                'date' => now()->toDateString(),
                'attendance' => [
                    $student->id => ['status' => Attendance::STATUS_ABSENT, 'note' => 'Should fail'],
                ],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_and_director_monitoring_pages_are_school_scoped(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-monitoring-owned']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $director = $this->createUserForSchool($school, User::ROLE_DIRECTOR);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '4A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Owned Attendance']);

        $otherSchool = $this->createSchool(['slug' => 'attendance-monitoring-foreign']);
        $foreignTeacher = $this->createUserForSchool($otherSchool, User::ROLE_TEACHER);
        $foreignParent = $this->createUserForSchool($otherSchool, User::ROLE_PARENT);
        $foreignClassroom = $this->createClassroomForSchool($otherSchool, [], ['name' => '9B']);
        $foreignStudent = $this->createStudentForSchool($otherSchool, $foreignClassroom, $foreignParent, null, ['full_name' => 'Foreign Attendance']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Owned absence note',
            'marked_by_user_id' => $teacher->id,
        ]);

        Attendance::create([
            'school_id' => $otherSchool->id,
            'student_id' => $foreignStudent->id,
            'classroom_id' => $foreignClassroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Foreign absence note',
            'marked_by_user_id' => $foreignTeacher->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['status' => Attendance::STATUS_ABSENT], false))
            ->assertOk()
            ->assertSee('Owned Attendance')
            ->assertSee('Owned absence note')
            ->assertDontSee('Foreign Attendance')
            ->assertDontSee('Foreign absence note');

        $this->actingAs($director)
            ->get(route('director.attendance.index', ['status' => Attendance::STATUS_ABSENT], false))
            ->assertOk()
            ->assertSee('Owned Attendance')
            ->assertSee('Owned absence note')
            ->assertDontSee('Foreign Attendance')
            ->assertDontSee('Foreign absence note');
    }

    public function test_parent_dashboard_and_attendance_page_only_show_owned_children_alerts(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-parent-visibility']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Alert']);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Other Parent']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '5C']);
        $child = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Owned Child']);
        $otherChild = $this->createStudentForSchool($school, $classroom, $otherParent, null, ['full_name' => 'Hidden Child']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $child->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_LATE,
            'note' => 'Bus delay',
            'marked_by_user_id' => $teacher->id,
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $otherChild->id,
            'classroom_id' => $classroom->id,
            'date' => now()->subDay()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
            'note' => 'Hidden absence',
            'marked_by_user_id' => $teacher->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Bus delay')
            ->assertDontSee('Hidden absence');

        $this->actingAs($parent)
            ->get(route('parent.attendance.index', absolute: false))
            ->assertOk()
            ->assertSee('Bus delay')
            ->assertDontSee('Hidden absence');
    }
}
