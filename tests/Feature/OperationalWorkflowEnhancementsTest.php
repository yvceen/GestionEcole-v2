<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\ParentStudentFee;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\StudentFeePlan;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class OperationalWorkflowEnhancementsTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_admin_student_delete_is_guarded_when_operational_records_exist(): void
    {
        $school = $this->createSchool(['slug' => 'student-delete-guard']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Protected Student']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
            'marked_by_user_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student, false))
            ->assertSessionHasErrors('delete_student');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_admin_can_delete_student_without_linked_operational_records(): void
    {
        $school = $this->createSchool(['slug' => 'student-delete-safe']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Disposable Student']);

        StudentFeePlan::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'tuition_monthly' => 500,
            'transport_monthly' => 0,
            'canteen_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        ParentStudentFee::create([
            'school_id' => $school->id,
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'tuition_monthly' => 500,
            'transport_monthly' => 0,
            'canteen_monthly' => 0,
            'insurance_yearly' => 0,
            'starts_month' => 9,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student, false))
            ->assertRedirect(route('admin.students.index', absolute: false));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('student_fee_plans', ['student_id' => $student->id]);
        $this->assertDatabaseMissing('parent_student_fees', ['student_id' => $student->id]);
    }

    public function test_admin_can_archive_filter_and_reactivate_student_without_losing_history(): void
    {
        $school = $this->createSchool(['slug' => 'student-archive-flow']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Archived Lifecycle Student']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_LATE,
            'marked_by_user_id' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.students.archive', $student, false), [
                'archive_reason' => 'Transfert',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertNotNull($student->archived_at);
        $this->assertSame($admin->id, (int) $student->archived_by_user_id);
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => Attendance::STATUS_LATE]);

        $this->actingAs($admin)
            ->get(route('admin.students.index', absolute: false))
            ->assertOk()
            ->assertDontSee('Archived Lifecycle Student');

        $this->actingAs($admin)
            ->get(route('admin.students.index', ['status' => 'archived'], false))
            ->assertOk()
            ->assertSee('Archived Lifecycle Student')
            ->assertSee('Archive');

        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student, false))
            ->assertSessionHasErrors('delete_student');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id]);

        $this->actingAs($admin)
            ->post(route('admin.students.reactivate', $student, false))
            ->assertRedirect()
            ->assertSessionHas('success');

        $student->refresh();
        $this->assertNull($student->archived_at);

        $this->actingAs($admin)
            ->get(route('admin.students.index', absolute: false))
            ->assertOk()
            ->assertSee('Archived Lifecycle Student');
    }

    public function test_parent_finance_shows_unpaid_and_overdue_months_by_child(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 10));

        $school = $this->createSchool(['slug' => 'parent-finance-arrears']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Finance Child']);

        StudentFeePlan::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'tuition_monthly' => 500,
            'transport_monthly' => 100,
            'canteen_monthly' => 50,
            'insurance_yearly' => 0,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'amount' => 650,
            'method' => 'cash',
            'period_month' => '2025-09-01',
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.finance.index', absolute: false))
            ->assertOk()
            ->assertSee('Finance Child')
            ->assertSee('Mois impayes')
            ->assertSee('Retard paiement')
            ->assertSee('650.00 MAD');

        Carbon::setTestNow();
    }

    public function test_parent_pickup_request_is_visible_and_actionable_by_school_life_staff(): void
    {
        $school = $this->createSchool(['slug' => 'pickup-flow']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Pickup']);
        $schoolLife = $this->createUserForSchool($school, User::ROLE_SCHOOL_LIFE, ['name' => 'Responsable Vie']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'CE2']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Pickup Child']);

        $pickupAt = now()->addHours(2)->format('Y-m-d H:i:s');

        $this->actingAs($parent)
            ->post(route('parent.pickup-requests.store', absolute: false), [
                'student_id' => $student->id,
                'requested_pickup_at' => $pickupAt,
                'reason' => 'Medical appointment',
            ])
            ->assertRedirect(route('parent.pickup-requests.index', absolute: false));

        $request = PickupRequest::first();
        $this->assertNotNull($request);
        $this->assertSame(PickupRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $schoolLife->id,
            'type' => 'pickup_request',
        ]);

        $this->actingAs($schoolLife)
            ->get(route('school-life.pickup-requests.index', absolute: false))
            ->assertOk()
            ->assertSee('Pickup Child')
            ->assertSee('Medical appointment');

        $this->actingAs($schoolLife)
            ->post(route('school-life.pickup-requests.approve', $request, false), [
                'decision_note' => 'Parent autorise.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pickup_requests', [
            'id' => $request->id,
            'status' => PickupRequest::STATUS_APPROVED,
            'reviewed_by_user_id' => $schoolLife->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'pickup_request',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.pickup-requests.index', absolute: false))
            ->assertOk()
            ->assertSee('Approuvee')
            ->assertSee('Parent autorise.');
    }

    public function test_school_life_portal_is_school_scoped(): void
    {
        $school = $this->createSchool(['slug' => 'school-life-owned']);
        $schoolLife = $this->createUserForSchool($school, User::ROLE_SCHOOL_LIFE);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Owned Student']);

        $foreignSchool = $this->createSchool(['slug' => 'school-life-foreign']);
        $foreignParent = $this->createUserForSchool($foreignSchool, User::ROLE_PARENT);
        $foreignTeacher = $this->createUserForSchool($foreignSchool, User::ROLE_TEACHER);
        $foreignClassroom = $this->createClassroomForSchool($foreignSchool);
        $foreignStudent = $this->createStudentForSchool($foreignSchool, $foreignClassroom, $foreignParent, null, ['full_name' => 'Foreign Student']);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Vie scolaire',
            'code' => 'VS',
            'is_active' => true,
        ]);
        $foreignSubject = Subject::create([
            'school_id' => $foreignSchool->id,
            'name' => 'Foreign Subject',
            'code' => 'FS',
            'is_active' => true,
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'score' => 15,
            'max_score' => 20,
            'comment' => 'Owned note',
        ]);

        Grade::create([
            'school_id' => $foreignSchool->id,
            'student_id' => $foreignStudent->id,
            'classroom_id' => $foreignClassroom->id,
            'teacher_id' => $foreignTeacher->id,
            'subject_id' => $foreignSubject->id,
            'score' => 9,
            'max_score' => 20,
            'comment' => 'Foreign note',
        ]);

        $this->actingAs($schoolLife)
            ->get(route('school-life.students.index', absolute: false))
            ->assertOk()
            ->assertSee('Owned Student')
            ->assertDontSee('Foreign Student');

        $this->actingAs($schoolLife)
            ->get(route('school-life.grades.index', absolute: false))
            ->assertOk()
            ->assertSee('Owned note')
            ->assertDontSee('Foreign note');
    }
}
