<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceReportingService;
use App\Services\CardTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AttendanceQrEnhancementsTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_auto_absent_command_marks_missing_students_and_scan_updates_to_late_with_parent_notification(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 09:10:00'));

        $school = $this->createSchool(['slug' => 'qr-auto-absent-flow']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Teacher Gate']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Gate']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Gate 6A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Late Scan Student']);

        $tokenService = app(CardTokenService::class);
        $student = $tokenService->ensureStudentToken($student);

        $this->artisan('attendance:mark-auto-absent', ['--school' => $school->id, '--force' => true])
            ->expectsOutput('Auto absences marked: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'date' => '2026-04-20',
            'status' => Attendance::STATUS_ABSENT,
            'recorded_via' => Attendance::RECORDED_VIA_AUTO_ABSENT,
        ]);

        $this->actingAs($teacher)
            ->postJson(route('api.attendance.scan', absolute: false), [
                'qr_token' => $tokenService->qrPayloadForStudent($student),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'duplicate' => false,
                'student_name' => 'Late Scan Student',
                'status' => Attendance::STATUS_LATE,
            ]);

        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'date' => '2026-04-20',
            'status' => Attendance::STATUS_LATE,
            'recorded_via' => Attendance::RECORDED_VIA_QR,
            'marked_by_user_id' => $teacher->id,
            'scanned_by_user_id' => $teacher->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'attendance',
            'title' => 'Late Scan Student',
            'body' => 'Your child is late today',
        ]);
    }

    public function test_attendance_dashboard_summary_returns_present_late_and_absent_counts_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-21 10:15:00'));

        $school = $this->createSchool(['slug' => 'attendance-summary-admin']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Summary 5B']);

        $presentStudent = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Present Student']);
        $lateStudent = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Late Student']);
        $absentStudent = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Absent Student']);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $presentStudent->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'marked_by_user_id' => $teacher->id,
            'recorded_via' => Attendance::RECORDED_VIA_QR,
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $lateStudent->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_LATE,
            'marked_by_user_id' => $teacher->id,
            'recorded_via' => Attendance::RECORDED_VIA_QR,
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'student_id' => $absentStudent->id,
            'classroom_id' => $classroom->id,
            'date' => now()->toDateString(),
            'status' => Attendance::STATUS_ABSENT,
            'marked_by_user_id' => $teacher->id,
            'recorded_via' => Attendance::RECORDED_VIA_MANUAL,
        ]);

        $summary = app(AttendanceReportingService::class)->schoolDashboardSummary($school->id, now()->startOfDay());

        $this->assertSame(1, $summary['today_present']);
        $this->assertSame(1, $summary['today_late']);
        $this->assertSame(1, $summary['today_absent']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Presences du jour')
            ->assertSee('Absences du jour')
            ->assertSee('Retards du jour');
    }
}
