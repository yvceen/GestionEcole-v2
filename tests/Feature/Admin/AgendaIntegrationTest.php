<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class AgendaIntegrationTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_agenda_create_generates_news_and_notifications_for_target_roles(): void
    {
        $school = $this->createSchool(['slug' => 'agenda-news-notif']);
        $admin = $this->createUserForSchool($school, User::ROLE_ADMIN);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $classroom = $this->createClassroomForSchool($school);

        Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student Agenda',
            'birth_date' => now()->subYears(10)->toDateString(),
            'gender' => 'male',
            'parent_user_id' => $parent->id,
            'user_id' => $studentUser->id,
            'classroom_id' => $classroom->id,
        ]);

        $teacher->teacherClassrooms()->attach($classroom->id, ['school_id' => $school->id]);

        $this->actingAs($admin)->post(route('admin.events.store', absolute: false), [
            'title' => 'Sortie pedagogique',
            'type' => 'activity',
            'start' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'end' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'color' => '#2563eb',
        ])->assertRedirect(route('admin.events.index', absolute: false));

        $event = \App\Models\Event::query()->firstOrFail();

        $this->assertDatabaseHas('news', [
            'school_id' => $school->id,
            'source_type' => 'agenda_event',
            'source_id' => $event->id,
            'classroom_id' => $classroom->id,
            'scope' => 'classroom',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'agenda',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $studentUser->id,
            'type' => 'agenda',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $teacher->id,
            'type' => 'agenda',
        ]);
    }

    public function test_calendar_pages_redirect_to_agenda_pages(): void
    {
        $school = $this->createSchool(['slug' => 'agenda-calendar-redirect']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $studentUser = $this->createUserForSchool($school, User::ROLE_STUDENT);
        $classroom = $this->createClassroomForSchool($school);

        Student::create([
            'school_id' => $school->id,
            'full_name' => 'Student Redirect',
            'birth_date' => now()->subYears(11)->toDateString(),
            'gender' => 'female',
            'parent_user_id' => $parent->id,
            'user_id' => $studentUser->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.calendar.index', absolute: false))
            ->assertRedirect(route('parent.events.index', absolute: false));

        $this->actingAs($studentUser)
            ->get(route('student.calendar.index', absolute: false))
            ->assertRedirect(route('student.events.index', absolute: false));
    }
}
