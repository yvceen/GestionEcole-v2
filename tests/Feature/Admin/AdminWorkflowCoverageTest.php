<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\ClassroomFee;
use App\Models\ParentStudentFee;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Route as TransportRoute;
use App\Models\School;
use App\Models\StudentFeePlan;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AdminWorkflowCoverageTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_admin_student_store_rejects_foreign_school_parent_and_classroom_ids(): void
    {
        $schoolA = $this->createSchool(['slug' => 'admin-students-a']);
        $schoolB = $this->createSchool(['slug' => 'admin-students-b']);
        $admin = $this->createUserForSchool($schoolA, User::ROLE_ADMIN);
        $foreignParent = $this->createUserForSchool($schoolB, User::ROLE_PARENT);
        $foreignClassroom = $this->createClassroomForSchool($schoolB);

        $response = $this->actingAs($admin)->post(route('admin.students.store', absolute: false), [
            'full_name' => 'Cross School Student',
            'birth_date' => '2015-05-01',
            'gender' => 'male',
            'classroom_id' => $foreignClassroom->id,
            'existing_parent_user_id' => $foreignParent->id,
            'tuition_monthly' => 1000,
            'canteen_monthly' => 0,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => 0,
            'transport_enabled' => 0,
        ]);

        $response->assertSessionHasErrors(['classroom_id', 'existing_parent_user_id']);
        $this->assertDatabaseMissing('students', ['full_name' => 'Cross School Student']);
    }

    public function test_admin_finance_store_payment_creates_receipt_and_payment_for_current_school(): void
    {
        $school = $this->createSchool(['slug' => 'finance-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent);

        StudentFeePlan::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'tuition_monthly' => 900,
            'canteen_monthly' => 100,
            'transport_monthly' => 0,
            'insurance_yearly' => 200,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        $month = now()->format('Y-m');

        $response = $this->actingAs($admin)->post(route('admin.finance.payments.store', absolute: false), [
            'parent_id' => $parent->id,
            'student_ids' => [$student->id],
            'months' => [$month],
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
            'note' => 'Paiement de test',
        ]);

        $receipt = \App\Models\Receipt::first();

        $response->assertRedirect(route('admin.finance.receipts.show', $receipt, false));
        $this->assertNotNull($receipt);
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'method' => 'cash',
        ]);
        $this->assertDatabaseHas('payments', [
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $student->id,
            'method' => 'cash',
        ]);
    }

    public function test_admin_finance_receipt_export_is_school_scoped_and_print_ready(): void
    {
        $school = $this->createSchool(['slug' => 'finance-export']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent);

        StudentFeePlan::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'tuition_monthly' => 800,
            'canteen_monthly' => 100,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        $this->actingAs($admin)->post(route('admin.finance.payments.store', absolute: false), [
            'parent_id' => $parent->id,
            'student_ids' => [$student->id],
            'months' => [now()->format('Y-m')],
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ]);

        $receipt = \App\Models\Receipt::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.finance.receipts.export', $receipt, false))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee($parent->name);
    }

    public function test_admin_finance_store_payment_rejects_foreign_school_parent(): void
    {
        $schoolA = $this->createSchool(['slug' => 'finance-a']);
        $schoolB = $this->createSchool(['slug' => 'finance-b']);
        $admin = $this->createUserForSchool($schoolA, User::ROLE_ADMIN);
        $foreignParent = $this->createUserForSchool($schoolB, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($schoolA);
        $student = $this->createStudentForSchool($schoolA, $classroom, $this->createUserForSchool($schoolA, User::ROLE_PARENT));

        StudentFeePlan::create([
            'school_id' => $schoolA->id,
            'student_id' => $student->id,
            'tuition_monthly' => 500,
            'canteen_monthly' => 0,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.finance.payments.store', absolute: false), [
            'parent_id' => $foreignParent->id,
            'student_ids' => [$student->id],
            'months' => [now()->format('Y-m')],
            'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_admin_finance_create_payment_page_renders_js_target_ids(): void
    {
        $school = $this->createSchool(['slug' => 'finance-create-page']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent One']);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.payments.create', absolute: false));

        $response->assertOk()
            ->assertSee('id="parent_id"', false)
            ->assertSee('id="parent_search"', false)
            ->assertSee('id="month_start"', false)
            ->assertSee('id="month_end"', false)
            ->assertSee('id="studentsBox"', false)
            ->assertSee('data-students-url-template="/admin/parents/__PARENT__/students"', false)
            ->assertSee('Aucun eleve lie a ce parent.', false);
    }

    public function test_admin_finance_parent_students_returns_school_scoped_children_for_selected_parent(): void
    {
        $school = $this->createSchool(['slug' => 'finance-parent-children']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);

        $directStudent = $this->createStudentForSchool($school, $classroom, $parent, null, [
            'full_name' => 'Direct Child',
        ]);

        $feeLinkedStudent = $this->createStudentForSchool($school, $classroom, null, null, [
            'full_name' => 'Fee Linked Child',
        ]);

        ParentStudentFee::create([
            'school_id' => $school->id,
            'parent_user_id' => $parent->id,
            'student_id' => $feeLinkedStudent->id,
            'tuition_monthly' => 750,
            'canteen_monthly' => 50,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'starts_month' => 9,
        ]);

        $foreignSchool = $this->createSchool(['slug' => 'finance-parent-children-foreign']);
        $foreignParent = $this->createUserForSchool($foreignSchool, User::ROLE_PARENT);
        $foreignClassroom = $this->createClassroomForSchool($foreignSchool);
        $this->createStudentForSchool($foreignSchool, $foreignClassroom, $foreignParent, null, [
            'full_name' => 'Foreign Child',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.parents.students', $parent, false));

        $rawContent = $response->getContent();

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonStructure([
                'count',
                'students' => [
                    ['id', 'full_name', 'fee_source', 'monthly_total', 'details'],
                ],
            ]);

        $this->assertFalse(str_starts_with($rawContent, "\xEF\xBB\xBF"));
        $this->assertSame('{', substr($rawContent, 0, 1));

        $students = collect($response->json('students'));
        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $studentNames = $students->pluck('full_name')->all();
        $feeLinkedPayload = $students->firstWhere('id', $feeLinkedStudent->id);

        $this->assertContains($directStudent->id, $studentIds);
        $this->assertContains($feeLinkedStudent->id, $studentIds);
        $this->assertContains('Direct Child', $studentNames);
        $this->assertContains('Fee Linked Child', $studentNames);
        $this->assertSame('parent_student_fee', $feeLinkedPayload['fee_source']);
        $this->assertSame(800, (int) $feeLinkedPayload['monthly_total']);
        $this->assertSame(750, (int) $feeLinkedPayload['details']['tuition']);
        $this->assertSame(50, (int) $feeLinkedPayload['details']['canteen']);
    }

    public function test_admin_finance_parent_students_falls_back_to_classroom_fee_when_student_plan_is_missing(): void
    {
        $school = $this->createSchool(['slug' => 'finance-classroom-fee-fallback']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);

        $student = $this->createStudentForSchool($school, $classroom, $parent, null, [
            'full_name' => 'Classroom Fee Child',
        ]);

        ClassroomFee::create([
            'classroom_id' => $classroom->id,
            'tuition_monthly' => 700,
            'canteen_monthly' => 25,
            'transport_monthly' => 10,
            'insurance_yearly' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.parents.students', $parent, false));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('students.0.id', $student->id)
            ->assertJsonPath('students.0.full_name', 'Classroom Fee Child')
            ->assertJsonPath('students.0.fee_source', 'classroom_fee')
            ->assertJsonPath('students.0.monthly_total', 735)
            ->assertJsonPath('students.0.details.tuition', 700)
            ->assertJsonPath('students.0.details.canteen', 25)
            ->assertJsonPath('students.0.details.transport', 10);
    }

    public function test_admin_finance_store_payment_rejects_student_not_linked_to_selected_parent(): void
    {
        $school = $this->createSchool(['slug' => 'finance-linked-student-check']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $selectedParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $foreignStudent = $this->createStudentForSchool($school, $classroom, $otherParent, null, [
            'full_name' => 'Wrong Parent Child',
        ]);

        StudentFeePlan::create([
            'school_id' => $school->id,
            'student_id' => $foreignStudent->id,
            'tuition_monthly' => 650,
            'canteen_monthly' => 0,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'insurance_paid' => false,
            'starts_month' => 9,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.finance.payments.create', absolute: false))
            ->post(route('admin.finance.payments.store', absolute: false), [
                'parent_id' => $selectedParent->id,
                'student_ids' => [$foreignStudent->id],
                'months' => [now()->format('Y-m')],
                'method' => 'cash',
            ]);

        $response->assertRedirect(route('admin.finance.payments.create', absolute: false))
            ->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_admin_finance_store_payment_uses_classroom_fee_fallback_when_student_plan_is_missing(): void
    {
        $school = $this->createSchool(['slug' => 'finance-store-classroom-fallback']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, [
            'full_name' => 'Fallback Classroom Student',
        ]);

        ClassroomFee::create([
            'classroom_id' => $classroom->id,
            'tuition_monthly' => 700,
            'canteen_monthly' => 25,
            'transport_monthly' => 10,
            'insurance_yearly' => 0,
            'is_active' => true,
        ]);

        $month = now()->format('Y-m');

        $response = $this->actingAs($admin)->post(route('admin.finance.payments.store', absolute: false), [
            'parent_id' => $parent->id,
            'student_ids' => [$student->id],
            'months' => [$month],
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ]);

        $receipt = Receipt::query()->first();

        $response->assertRedirect(route('admin.finance.receipts.show', $receipt, false));
        $this->assertDatabaseHas('payments', [
            'school_id' => $school->id,
            'receipt_id' => $receipt?->id,
            'student_id' => $student->id,
            'amount' => 735,
        ]);
    }

    public function test_admin_finance_unpaid_endpoint_uses_current_school_pricing_sources_only(): void
    {
        $school = $this->createSchool(['slug' => 'finance-unpaid-current-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, [
            'full_name' => 'Scoped Unpaid Student',
        ]);

        ClassroomFee::create([
            'classroom_id' => $classroom->id,
            'tuition_monthly' => 650,
            'canteen_monthly' => 30,
            'transport_monthly' => 20,
            'insurance_yearly' => 0,
            'is_active' => true,
        ]);

        $foreignSchool = $this->createSchool(['slug' => 'finance-unpaid-foreign-school']);
        $foreignParent = $this->createUserForSchool($foreignSchool, User::ROLE_PARENT);
        $foreignClassroom = $this->createClassroomForSchool($foreignSchool);
        $foreignStudent = $this->createStudentForSchool($foreignSchool, $foreignClassroom, $foreignParent, null, [
            'full_name' => 'Foreign Unpaid Student',
        ]);

        ClassroomFee::create([
            'classroom_id' => $foreignClassroom->id,
            'tuition_monthly' => 900,
            'canteen_monthly' => 0,
            'transport_monthly' => 0,
            'insurance_yearly' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.finance.unpaid', ['month' => now()->format('Y-m')], false));

        $response->assertOk();

        $unpaid = collect($response->json('unpaid_month'));

        $this->assertTrue($unpaid->contains(
            fn (array $row) => (int) $row['student_id'] === $student->id && (float) $row['amount'] === 700.0
        ));
        $this->assertFalse($unpaid->contains(
            fn (array $row) => (int) $row['student_id'] === $foreignStudent->id
        ));
    }

    public function test_admin_finance_index_lists_only_current_school_payments(): void
    {
        $school = $this->createSchool(['slug' => 'finance-index-current-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Current Parent']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Current Classroom']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, [
            'full_name' => 'Current School Student',
        ]);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => 'R-CURRENT-000001',
            'parent_id' => $parent->id,
            'method' => 'cash',
            'total_amount' => 800,
            'issued_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $student->id,
            'amount' => 800,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $foreignSchool = $this->createSchool(['slug' => 'finance-index-foreign-school']);
        $foreignAdmin = $this->createUserForSchool($foreignSchool, User::ROLE_ADMIN);
        $foreignParent = $this->createUserForSchool($foreignSchool, User::ROLE_PARENT, ['name' => 'Foreign Parent']);
        $foreignClassroom = $this->createClassroomForSchool($foreignSchool, [], ['name' => 'Foreign Classroom']);
        $foreignStudent = $this->createStudentForSchool($foreignSchool, $foreignClassroom, $foreignParent, null, [
            'full_name' => 'Foreign School Student',
        ]);

        $foreignReceipt = Receipt::create([
            'school_id' => $foreignSchool->id,
            'receipt_number' => 'R-FOREIGN-000001',
            'parent_id' => $foreignParent->id,
            'method' => 'cash',
            'total_amount' => 900,
            'issued_at' => now(),
            'received_by_admin_user_id' => $foreignAdmin->id,
        ]);

        Payment::create([
            'school_id' => $foreignSchool->id,
            'receipt_id' => $foreignReceipt->id,
            'student_id' => $foreignStudent->id,
            'amount' => 900,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $foreignAdmin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.index', absolute: false));

        $response->assertOk()
            ->assertSee('Current School Student')
            ->assertSee('R-CURRENT-000001')
            ->assertDontSee('Foreign School Student')
            ->assertDontSee('R-FOREIGN-000001');
    }

    public function test_admin_timetable_prevents_overlapping_slots_for_same_classroom(): void
    {
        $school = $this->createSchool(['slug' => 'timetable-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $classroom = $this->createClassroomForSchool($school);

        $first = $this->actingAs($admin)->post(route('admin.timetable.store', absolute: false), [
            'classroom_id' => $classroom->id,
            'day' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'subject' => 'Mathematiques',
            'teacher_id' => $teacher->id,
            'room' => 'A1',
        ]);

        $first->assertRedirect(route('admin.timetable.index', ['classroom_id' => $classroom->id], false));
        $this->assertDatabaseHas('timetables', [
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'subject' => 'Mathematiques',
        ]);

        $second = $this->actingAs($admin)->post(route('admin.timetable.store', absolute: false), [
            'classroom_id' => $classroom->id,
            'day' => 1,
            'start_time' => '08:30',
            'end_time' => '09:30',
            'subject' => 'Francais',
            'teacher_id' => $teacher->id,
            'room' => 'A2',
        ]);

        $second->assertSessionHasErrors('start_time');
        $this->assertSame(1, Timetable::count());
    }

    public function test_admin_transport_assignment_rejects_vehicle_that_does_not_match_route(): void
    {
        $school = $this->createSchool(['slug' => 'transport-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $chauffeur = $this->createUserForSchool($school, User::ROLE_CHAUFFEUR);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $this->createUserForSchool($school, User::ROLE_PARENT));
        $vehicleA = Vehicle::create([
            'school_id' => $school->id,
            'registration_number' => 'BUS-A',
            'vehicle_type' => 'bus',
            'capacity' => 20,
            'driver_id' => $chauffeur->id,
            'is_active' => true,
        ]);
        $vehicleB = Vehicle::create([
            'school_id' => $school->id,
            'registration_number' => 'BUS-B',
            'vehicle_type' => 'bus',
            'capacity' => 22,
            'driver_id' => $chauffeur->id,
            'is_active' => true,
        ]);
        $route = TransportRoute::create([
            'school_id' => $school->id,
            'route_name' => 'North Line',
            'vehicle_id' => $vehicleA->id,
            'start_point' => 'North',
            'end_point' => 'Campus',
            'monthly_fee' => 300,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.transport.assignments.store', absolute: false), [
            'student_id' => $student->id,
            'route_id' => $route->id,
            'vehicle_id' => $vehicleB->id,
            'period' => 'both',
            'assigned_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('transport_assignments', 0);
    }

    public function test_admin_news_store_notifies_only_current_school_users(): void
    {
        $schoolA = $this->createSchool(['slug' => 'news-a']);
        $schoolB = $this->createSchool(['slug' => 'news-b']);
        $admin = $this->createUserForSchool($schoolA, User::ROLE_ADMIN);
        $parentA = $this->createUserForSchool($schoolA, User::ROLE_PARENT);
        $studentUserA = $this->createUserForSchool($schoolA, User::ROLE_STUDENT);
        $classroomA = $this->createClassroomForSchool($schoolA);
        $this->createStudentForSchool($schoolA, $classroomA, $parentA, $studentUserA);

        $parentB = $this->createUserForSchool($schoolB, User::ROLE_PARENT);
        $studentUserB = $this->createUserForSchool($schoolB, User::ROLE_STUDENT);
        $classroomB = $this->createClassroomForSchool($schoolB);
        $this->createStudentForSchool($schoolB, $classroomB, $parentB, $studentUserB);

        $response = $this->actingAs($admin)->post(route('admin.news.store', absolute: false), [
            'title' => 'School Wide Update',
            'status' => 'published',
            'date' => now()->toDateString(),
            'scope' => 'school',
        ]);

        $response->assertRedirect(route('admin.news.index', absolute: false));
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parentA->id,
            'type' => 'news',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $studentUserA->id,
            'type' => 'news',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $parentB->id,
            'type' => 'news',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'recipient_user_id' => $studentUserB->id,
            'type' => 'news',
        ]);
    }

    public function test_admin_appointment_approve_updates_status_and_notifies_parent(): void
    {
        $school = $this->createSchool(['slug' => 'appointments-school']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'parent_user_id' => $parent->id,
            'parent_id' => $parent->id,
            'parent_name' => $parent->name,
            'parent_phone' => $parent->phone,
            'parent_email' => $parent->email,
            'title' => 'Appointment Review',
            'message' => 'Please approve.',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.appointments.index', absolute: false))
            ->post(route('admin.appointments.approve', $appointment, false));

        $response->assertRedirect(route('admin.appointments.index', absolute: false));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'appointment',
        ]);
    }
}
