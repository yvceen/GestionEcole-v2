<?php

namespace Tests\Feature\Parent;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Homework;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_parent_portal_pages_render_with_owned_school_scoped_data_and_no_raw_blade_tokens(): void
    {
        $school = $this->createSchool(['slug' => 'parent-portal-ui']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Atlas']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Mme Guide']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '6A']);
        $child = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Child Parent']);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematiques',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $assessment = Assessment::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'title' => 'Controle parent',
            'type' => 'exam',
            'date' => now()->toDateString(),
            'coefficient' => 1,
            'max_score' => 20,
        ]);

        Course::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Cours parent visible',
            'status' => 'approved',
        ]);

        Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Devoir parent visible',
            'due_at' => now()->addDay(),
            'status' => 'approved',
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $child->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 17,
            'max_score' => 20,
            'comment' => 'Bonne progression',
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $child->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => 'late',
            'note' => 'Transport',
            'marked_by_user_id' => $teacher->id,
        ]);

        Timetable::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'day' => 5,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'subject' => 'Physique',
            'teacher_id' => $teacher->id,
            'room' => 'A2',
        ]);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => 'R-PARENT-000001',
            'parent_id' => $parent->id,
            'method' => 'cash',
            'total_amount' => 700,
            'issued_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $child->id,
            'amount' => 700,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $dashboard = $this->actingAs($parent)
            ->get(route('parent.dashboard', absolute: false));

        $dashboard->assertOk()
            ->assertSee('Child Parent')
            ->assertSee('Devoir parent visible')
            ->assertDontSee('{{ $item');

        $this->actingAs($parent)->get(route('parent.children.index', absolute: false))
            ->assertOk()
            ->assertSee('Notes')
            ->assertSee('Finance');

        $this->actingAs($parent)->get(route('parent.courses.index', absolute: false))
            ->assertOk()
            ->assertSee('Cours parent visible');

        $this->actingAs($parent)->get(route('parent.homeworks.index', absolute: false))
            ->assertOk()
            ->assertSee('Devoir parent visible');

        $this->actingAs($parent)->get(route('parent.grades.index', absolute: false))
            ->assertOk()
            ->assertSee('Bonne progression');

        $this->actingAs($parent)->get(route('parent.attendance.index', absolute: false))
            ->assertOk()
            ->assertSee('Transport');

        $this->actingAs($parent)->get(route('parent.finance.index', absolute: false))
            ->assertOk()
            ->assertSee('R-PARENT-000001');

        $this->actingAs($parent)->get(route('parent.children.timetable', $child, false))
            ->assertOk()
            ->assertSee('Physique');
    }

    public function test_parent_cannot_access_other_parents_child_pages_or_finance_views(): void
    {
        $school = $this->createSchool(['slug' => 'parent-portal-security']);
        $ownerParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $classroom = $this->createClassroomForSchool($school);
        $child = $this->createStudentForSchool($school, $classroom, $ownerParent, null, ['full_name' => 'Protected Child']);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => 'R-PARENT-SEC-1',
            'parent_id' => $ownerParent->id,
            'method' => 'cash',
            'total_amount' => 300,
            'issued_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $child->id,
            'amount' => 300,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $responses = [
            $this->actingAs($otherParent)->get(route('parent.children.courses', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.children.homeworks', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.children.grades', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.children.attendance', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.children.timetable', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.children.finance', $child, false)),
            $this->actingAs($otherParent)->get(route('parent.finance.receipts.show', $receipt, false)),
        ];

        foreach ($responses as $response) {
            $this->assertTrue(in_array($response->getStatusCode(), [403, 404], true));
        }
    }

    public function test_parent_receipt_view_filters_out_non_owned_payments(): void
    {
        $school = $this->createSchool(['slug' => 'parent-portal-receipt']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $classroom = $this->createClassroomForSchool($school);
        $ownedChild = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Owned Child']);
        $foreignChild = $this->createStudentForSchool($school, $classroom, $otherParent, null, ['full_name' => 'Foreign Child']);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => 'R-PARENT-FILTER-1',
            'parent_id' => $parent->id,
            'method' => 'cash',
            'total_amount' => 1000,
            'issued_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $ownedChild->id,
            'amount' => 400,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $foreignChild->id,
            'amount' => 600,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.finance.receipts.show', $receipt, false))
            ->assertOk()
            ->assertSee('Owned Child')
            ->assertDontSee('Foreign Child')
            ->assertSee('400.00 MAD')
            ->assertDontSee('600.00 MAD');
    }
}
