<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Services\CardTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AgendaAndAttendanceApiScanTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_admin_can_create_and_feed_agenda_events(): void
    {
        $school = $this->createSchool(['slug' => 'agenda-events-admin']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Mme Agenda']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '3A']);

        $this->actingAs($admin)
            ->post(route('admin.events.store', absolute: false), [
                'title' => 'Controle sciences',
                'type' => Event::TYPE_EXAM,
                'start' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
                'end' => now()->addDay()->setTime(10, 30)->toDateTimeString(),
                'classroom_id' => $classroom->id,
                'teacher_id' => $teacher->id,
                'color' => '#ef4444',
            ])
            ->assertRedirect(route('admin.events.index', absolute: false));

        $this->assertDatabaseHas('events', [
            'school_id' => $school->id,
            'title' => 'Controle sciences',
            'type' => Event::TYPE_EXAM,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'color' => '#ef4444',
        ]);

        $feedResponse = $this->actingAs($admin)
            ->getJson(route('agenda.feed', [
                'start' => now()->startOfWeek()->toIso8601String(),
                'end' => now()->endOfWeek()->addWeek()->toIso8601String(),
                'classroom_id' => $classroom->id,
                'teacher_id' => $teacher->id,
            ], false));

        $feedResponse->assertOk()
            ->assertJsonFragment([
                'title' => 'Controle sciences',
                'backgroundColor' => '#ef4444',
            ]);
    }

    public function test_teacher_and_school_life_can_scan_attendance_once_per_day_through_api(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-api-scan']);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER, ['name' => 'Teacher Scan']);
        $schoolLife = User::factory()->forSchool($school)->schoolLife()->create(['name' => 'School Life Scan']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Scan 4B']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Scan API Student']);

        $tokenService = app(CardTokenService::class);
        $student = $tokenService->ensureStudentToken($student);

        $this->actingAs($teacher)
            ->postJson(route('api.attendance.scan', absolute: false), [
                'qr_token' => $tokenService->qrPayloadForStudent($student),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'duplicate' => false,
                'student_name' => 'Scan API Student',
            ]);

        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'recorded_via' => Attendance::RECORDED_VIA_QR,
        ]);

        $this->actingAs($schoolLife)
            ->postJson(route('api.attendance.scan', absolute: false), [
                'qr_token' => $tokenService->qrPayloadForStudent($student),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'duplicate' => true,
                'student_name' => 'Scan API Student',
            ]);
    }

    public function test_scan_page_and_api_are_for_teacher_or_school_life_only(): void
    {
        $school = $this->createSchool(['slug' => 'attendance-scan-roles']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);

        $this->actingAs($parent)->get(route('attendance.scan.page', absolute: false))->assertForbidden();
        $this->actingAs($parent)->postJson(route('api.attendance.scan', absolute: false), [
            'qr_token' => 'MYEDU:STUDENT:STD-INVALID0000',
        ])->assertForbidden();
    }
}
