<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\SchoolCalendarEvent;
use App\Models\User;
use App\Services\CardTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class CardsQrCalendarWorkflowTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_admin_can_generate_and_regenerate_cards_for_owned_students_and_parents(): void
    {
        $school = $this->createSchool(['slug' => 'cards-admin-owned']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Card']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => '6A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Eleve Card']);

        $this->actingAs($admin)
            ->get(route('admin.cards.index', absolute: false))
            ->assertOk()
            ->assertSee('Eleve Card')
            ->assertSee('Parent Card');

        $this->actingAs($admin)
            ->get(route('admin.cards.index', ['scope' => 'parents'], false))
            ->assertOk()
            ->assertSee('Parent Card');

        $student->refresh();
        $parent->refresh();

        $this->assertNotNull($student->card_token);
        $this->assertNotNull($parent->card_token);

        $previousStudentToken = $student->card_token;
        $previousParentToken = $parent->card_token;

        $this->actingAs($admin)
            ->post(route('admin.cards.students.regenerate', $student, false))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.cards.parents.regenerate', $parent, false))
            ->assertRedirect();

        $this->assertNotSame($previousStudentToken, $student->fresh()->card_token);
        $this->assertNotSame($previousParentToken, $parent->fresh()->card_token);
    }

    public function test_school_life_qr_scan_records_check_in_then_check_out_and_rejects_invalid_cards(): void
    {
        $school = $this->createSchool(['slug' => 'qr-scan-owned']);
        $schoolLife = User::factory()->forSchool($school)->schoolLife()->create();
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Parent Scan']);
        $classroom = $this->createClassroomForSchool($school, [], ['name' => 'Gate 5A']);
        $student = $this->createStudentForSchool($school, $classroom, $parent, null, ['full_name' => 'Scan Student']);

        $otherSchool = $this->createSchool(['slug' => 'qr-scan-foreign']);
        $foreignParent = $this->createUserForSchool($otherSchool, User::ROLE_PARENT, ['name' => 'Foreign Parent']);
        $foreignClassroom = $this->createClassroomForSchool($otherSchool, [], ['name' => 'Foreign 7B']);
        $foreignStudent = $this->createStudentForSchool($otherSchool, $foreignClassroom, $foreignParent, null, ['full_name' => 'Foreign Student']);

        $cards = app(CardTokenService::class);
        $student = $cards->ensureStudentToken($student);
        $parent = $cards->ensureParentToken($parent);
        $foreignStudent = $cards->ensureStudentToken($foreignStudent);

        $firstScan = $this->actingAs($schoolLife)->postJson(route('school-life.qr-scan.store', absolute: false), [
            'code' => $cards->qrPayloadForStudent($student),
        ]);

        $firstScan->assertOk()
            ->assertJsonPath('action', 'check_in')
            ->assertJsonPath('record.student_name', 'Scan Student');

        $attendance = Attendance::query()
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->check_in_at);
        $this->assertNull($attendance->check_out_at);
        $this->assertSame(Attendance::RECORDED_VIA_QR, $attendance->recorded_via);

        $secondScan = $this->actingAs($schoolLife)->postJson(route('school-life.qr-scan.store', absolute: false), [
            'code' => $cards->qrPayloadForStudent($student),
        ]);

        $secondScan->assertOk()->assertJsonPath('action', 'check_out');

        $this->assertNotNull($attendance->fresh()->check_out_at);

        $this->actingAs($schoolLife)->postJson(route('school-life.qr-scan.store', absolute: false), [
            'code' => $cards->qrPayloadForParent($parent),
        ])->assertStatus(422);

        $this->actingAs($schoolLife)->postJson(route('school-life.qr-scan.store', absolute: false), [
            'code' => $cards->qrPayloadForStudent($foreignStudent),
        ])->assertStatus(422);
    }

    public function test_admin_can_manage_calendar_and_other_portals_can_view_school_scoped_events(): void
    {
        $school = $this->createSchool(['slug' => 'calendar-owned']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $director = $this->createUserForSchool($school, User::ROLE_DIRECTOR);
        $schoolLife = User::factory()->forSchool($school)->schoolLife()->create();
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $classroom = $this->createClassroomForSchool($school);
        $this->createStudentForSchool($school, $classroom, $parent, $studentUser, ['full_name' => 'Calendar Student']);

        $this->actingAs($admin)
            ->post(route('admin.calendar.store', absolute: false), [
                'title' => 'Conseil de classe',
                'type' => SchoolCalendarEvent::TYPE_EVENT,
                'starts_on' => now()->addWeek()->toDateString(),
                'ends_on' => now()->addWeek()->toDateString(),
                'description' => 'Reunion importante',
            ])
            ->assertRedirect(route('admin.calendar.index', absolute: false));

        $this->assertDatabaseHas('school_calendar_events', [
            'school_id' => $school->id,
            'title' => 'Conseil de classe',
            'type' => SchoolCalendarEvent::TYPE_EVENT,
        ]);

        foreach ([
            [$parent, 'parent.calendar.index'],
            [$teacher, 'teacher.calendar.index'],
            [$director, 'director.calendar.index'],
            [$schoolLife, 'school-life.calendar.index'],
            [$studentUser, 'student.calendar.index'],
        ] as [$user, $routeName]) {
            $this->actingAs($user)
                ->get(route($routeName, absolute: false))
                ->assertOk()
                ->assertSee('Conseil de classe')
                ->assertSee('Reunion importante');
        }
    }

    public function test_parent_and_student_card_views_stay_scoped_to_owned_profiles(): void
    {
        $school = $this->createSchool(['slug' => 'cards-parent-owned']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Owned Parent']);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT, ['name' => 'Other Parent']);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT, ['name' => 'Owned Student User']);
        $classroom = $this->createClassroomForSchool($school);
        $ownedChild = $this->createStudentForSchool($school, $classroom, $parent, $studentUser, ['full_name' => 'Owned Child']);
        $foreignChild = $this->createStudentForSchool($school, $classroom, $otherParent, null, ['full_name' => 'Foreign Child']);

        $cards = app(CardTokenService::class);
        $cards->ensureParentToken($parent);
        $cards->ensureStudentToken($ownedChild);
        $cards->ensureStudentToken($foreignChild);

        $this->actingAs($parent)
            ->get(route('parent.cards.index', absolute: false))
            ->assertOk()
            ->assertSee('Owned Child')
            ->assertDontSee('Foreign Child');

        $this->actingAs($parent)
            ->get(route('parent.cards.children.show', $ownedChild, false))
            ->assertOk()
            ->assertSee('Owned Child');

        $this->actingAs($parent)
            ->get(route('parent.cards.children.show', $foreignChild, false))
            ->assertNotFound();

        $this->actingAs($studentUser)
            ->get(route('student.card.show', absolute: false))
            ->assertOk()
            ->assertSee('Owned Child');
    }
}
