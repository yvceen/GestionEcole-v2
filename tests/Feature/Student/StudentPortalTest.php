<?php

namespace Tests\Feature\Student;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\Grade;
use App\Models\Homework;
use App\Models\HomeworkAttachment;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_student_dashboard_and_content_only_use_visible_school_scoped_records(): void
    {
        $school = $this->createSchool(['slug' => 'student-portal-dashboard']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Mme Atlas']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '3A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, $studentUser, [
            'full_name' => 'Student Visible',
        ]);

        Course::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Published Algebra',
            'description' => 'Visible course',
            'status' => 'approved',
        ]);

        Course::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Draft Algebra',
            'description' => 'Should stay hidden',
            'status' => 'draft',
        ]);

        Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Visible Homework',
            'description' => 'Publish me',
            'due_at' => now()->addDay(),
            'status' => 'approved',
        ]);

        Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Hidden Homework',
            'description' => 'Do not show',
            'due_at' => now()->addDays(2),
            'status' => 'draft',
        ]);

        Timetable::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'day' => 5,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'subject' => 'Mathematiques',
            'teacher_id' => $teacher->id,
            'room' => 'B12',
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'absent',
            'marked_by_user_id' => $teacher->id,
        ]);

        $dashboard = $this->actingAs($studentUser)
            ->get(route('student.dashboard', absolute: false));

        $dashboard->assertOk()
            ->assertSee('Student Visible')
            ->assertSee('Visible Homework')
            ->assertDontSee('Hidden Homework')
            ->assertSee('Mathematiques');

        $courses = $this->actingAs($studentUser)
            ->get(route('student.courses.index', absolute: false));

        $courses->assertOk()
            ->assertSee('Published Algebra')
            ->assertDontSee('Draft Algebra');

        $homeworks = $this->actingAs($studentUser)
            ->get(route('student.homeworks.index', absolute: false));

        $homeworks->assertOk()
            ->assertSee('Visible Homework')
            ->assertDontSee('Hidden Homework');
    }

    public function test_student_can_download_only_own_class_attachments(): void
    {
        Storage::fake('public');

        $school = $this->createSchool(['slug' => 'student-portal-attachments']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $otherClassroom = $this->createClassroomForSchool($school, [], ['name' => 'Other Class']);
        $this->createStudentForSchool($school, $classroom, $parent, $studentUser);

        $ownCourse = Course::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Own Course',
            'status' => 'approved',
        ]);
        $ownHomework = Homework::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Own Homework',
            'status' => 'approved',
        ]);
        $foreignCourse = Course::create([
            'school_id' => $school->id,
            'classroom_id' => $otherClassroom->id,
            'teacher_id' => $teacher->id,
            'title' => 'Foreign Course',
            'status' => 'approved',
        ]);

        Storage::disk('public')->put('courses/own.pdf', 'course file');
        Storage::disk('public')->put('homeworks/own.pdf', 'homework file');
        Storage::disk('public')->put('courses/foreign.pdf', 'foreign file');

        $ownCourseAttachment = CourseAttachment::create([
            'school_id' => $school->id,
            'course_id' => $ownCourse->id,
            'original_name' => 'own-course.pdf',
            'path' => 'courses/own.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);
        $ownHomeworkAttachment = HomeworkAttachment::create([
            'school_id' => $school->id,
            'homework_id' => $ownHomework->id,
            'original_name' => 'own-homework.pdf',
            'path' => 'homeworks/own.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);
        $foreignCourseAttachment = CourseAttachment::create([
            'school_id' => $school->id,
            'course_id' => $foreignCourse->id,
            'original_name' => 'foreign-course.pdf',
            'path' => 'courses/foreign.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);

        $this->actingAs($studentUser)
            ->get(route('student.courses.attachments.download', $ownCourseAttachment, false))
            ->assertOk();

        $this->actingAs($studentUser)
            ->get(route('student.homeworks.attachments.download', $ownHomeworkAttachment, false))
            ->assertOk();

        $this->actingAs($studentUser)
            ->get(route('student.courses.attachments.download', $foreignCourseAttachment, false))
            ->assertForbidden();
    }

    public function test_student_grades_and_attendance_pages_only_show_owned_records(): void
    {
        $school = $this->createSchool(['slug' => 'student-portal-grades']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $otherStudentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'M. Notes']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, $studentUser, ['full_name' => 'Owned Student']);
        $otherStudent = $this->createStudentForSchool($school, $classroom, $otherParent, $otherStudentUser, ['full_name' => 'Hidden Student']);

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
            'title' => 'Controle 1',
            'type' => 'exam',
            'date' => now()->toDateString(),
            'coefficient' => 1,
            'max_score' => 20,
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 16,
            'max_score' => 20,
            'comment' => 'Owned comment',
        ]);

        Grade::create([
            'school_id' => $school->id,
            'student_id' => $otherStudent->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'score' => 8,
            'max_score' => 20,
            'comment' => 'Hidden comment',
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => 'late',
            'note' => 'Bus delay',
            'marked_by_user_id' => $teacher->id,
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $otherStudent->id,
            'classroom_id' => $classroom->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'absent',
            'note' => 'Hidden absence',
            'marked_by_user_id' => $teacher->id,
        ]);

        $grades = $this->actingAs($studentUser)
            ->get(route('student.grades.index', absolute: false));

        $grades->assertOk()
            ->assertSee('Controle 1')
            ->assertSee('Owned comment')
            ->assertDontSee('Hidden comment');

        $attendance = $this->actingAs($studentUser)
            ->get(route('student.attendance.index', absolute: false));

        $attendance->assertOk()
            ->assertSee('Bus delay')
            ->assertDontSee('Hidden absence');
    }

    public function test_student_finance_receipt_views_show_only_owned_payments(): void
    {
        $school = $this->createSchool(['slug' => 'student-portal-finance']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $otherStudentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Shared']);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent, $studentUser, ['full_name' => 'Owned Finance Student']);
        $otherStudent = $this->createStudentForSchool($school, $classroom, $parent, $otherStudentUser, ['full_name' => 'Sibling Finance Student']);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => 'R-STU-000001',
            'parent_id' => $parent->id,
            'method' => 'cash',
            'total_amount' => 1600,
            'issued_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $student->id,
            'amount' => 700,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        Payment::create([
            'school_id' => $school->id,
            'receipt_id' => $receipt->id,
            'student_id' => $otherStudent->id,
            'amount' => 900,
            'method' => 'cash',
            'period_month' => now()->startOfMonth()->toDateString(),
            'paid_at' => now(),
            'received_by_admin_user_id' => $admin->id,
        ]);

        $financeIndex = $this->actingAs($studentUser)
            ->get(route('student.finance.index', absolute: false));

        $financeIndex->assertOk()
            ->assertSee('R-STU-000001');

        $receiptView = $this->actingAs($studentUser)
            ->get(route('student.finance.receipts.show', $receipt, false));

        $receiptView->assertOk()
            ->assertSee('Owned Finance Student')
            ->assertDontSee('Sibling Finance Student')
            ->assertSee('700.00 MAD')
            ->assertDontSee('900.00 MAD');

        $pdfResponse = $this->actingAs($studentUser)
            ->get(route('student.finance.receipts.pdf', $receipt, false));

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $pdfResponse->assertOk();
        } else {
            $pdfResponse->assertStatus(503);
        }
    }

    public function test_inactive_student_cannot_log_in(): void
    {
        $school = $this->createSchool(['slug' => 'student-portal-inactive']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT, [
            'email' => 'inactive-student@example.test',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);
        $classroom = $this->createClassroomForSchool($school);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $this->createStudentForSchool($school, $classroom, $parent, $studentUser);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'inactive-student@example.test',
                'password' => 'Password123!',
            ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
