<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\Route;
use App\Models\School;
use App\Models\SchoolLife;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiSchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_life_records_are_isolated_per_school(): void
    {
        [$schoolA, $adminA, $classroomA] = $this->createAdminContext('A');
        [$schoolB, $adminB] = $this->createAdminContext('B');

        $response = $this->actingAs($adminA)->post(route('admin.school-life.store', absolute: false), [
            'title' => 'School A Life',
            'status' => 'published',
            'date' => '2026-03-20',
        ]);

        $response->assertRedirect(route('admin.school-life.index', absolute: false));
        $response->assertSessionHasNoErrors();

        $item = SchoolLife::where('title', 'School A Life')->firstOrFail();
        $this->assertSame($schoolA->id, (int) $item->school_id);

        $this->actingAs($adminB)
            ->get(route('admin.school-life.index', absolute: false))
            ->assertOk()
            ->assertDontSee('School A Life');

        $this->actingAs($adminB)
            ->get(route('admin.school-life.edit', $item, false))
            ->assertNotFound();
    }

    public function test_teacher_cannot_send_message_to_parent_from_another_school(): void
    {
        [, $teacherA] = $this->createTeacherContext('A');
        [, $parentB] = $this->createParentContext('B');

        $response = $this->actingAs($teacherA)->post(route('teacher.messages.store', absolute: false), [
            'subject' => 'Cross school message',
            'body' => 'This should not be sent.',
            'parent_ids' => [$parentB->id],
        ]);

        $response->assertSessionHasErrors('parent_ids.0');
        $this->assertDatabaseMissing('messages', [
            'subject' => 'Cross school message',
        ]);
    }

    public function test_parent_cannot_message_recipient_from_another_school(): void
    {
        [$schoolA, $parentA] = $this->createParentContext('A');
        [$schoolB, $adminB] = $this->createAdminContext('B');

        $response = $this->actingAs($parentA)->post(route('parent.messages.store', absolute: false), [
            'subject' => 'Parent cross school',
            'body' => 'This should not be sent.',
            'recipient_id' => $adminB->id,
        ]);

        $response->assertSessionHasErrors('recipient_id');
        $this->assertDatabaseMissing('messages', [
            'subject' => 'Parent cross school',
        ]);
    }

    public function test_admin_cannot_create_news_for_foreign_school_classroom(): void
    {
        [, $adminA] = $this->createAdminContext('A');
        [, , $classroomB] = $this->createAdminContext('B');

        $response = $this->actingAs($adminA)->post(route('admin.news.store', absolute: false), [
            'title' => 'Foreign classroom news',
            'status' => 'published',
            'date' => '2026-03-21',
            'scope' => 'classroom',
            'classroom_id' => $classroomB->id,
        ]);

        $response->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseMissing('news', [
            'title' => 'Foreign classroom news',
        ]);
    }

    public function test_admin_cannot_create_transport_assignment_with_foreign_school_route(): void
    {
        [$schoolA, $adminA, $classroomA] = $this->createAdminContext('A');
        [$schoolB] = $this->createAdminContext('B');

        $studentA = Student::create([
            'school_id' => $schoolA->id,
            'full_name' => 'Student A',
            'classroom_id' => $classroomA->id,
        ]);

        $vehicleB = Vehicle::create([
            'school_id' => $schoolB->id,
            'registration_number' => 'BUS-' . Str::upper(Str::random(4)),
            'vehicle_type' => 'bus',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $routeB = Route::create([
            'school_id' => $schoolB->id,
            'route_name' => 'Route B',
            'vehicle_id' => $vehicleB->id,
            'start_point' => 'Start',
            'end_point' => 'End',
            'monthly_fee' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminA)->post(route('admin.transport.assignments.store', absolute: false), [
            'student_id' => $studentA->id,
            'route_id' => $routeB->id,
            'assigned_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('route_id');
        $this->assertDatabaseMissing('transport_assignments', [
            'student_id' => $studentA->id,
            'route_id' => $routeB->id,
        ]);
    }

    private function createAdminContext(string $suffix): array
    {
        $school = School::create([
            'name' => 'School ' . $suffix,
            'slug' => 'school-' . strtolower($suffix) . '-' . Str::lower(Str::random(5)),
            'is_active' => true,
        ]);

        $level = Level::create([
            'code' => 'LVL' . Str::upper(Str::random(4)),
            'name' => 'Level ' . $suffix,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level->school_id = $school->id;
        $level->save();

        $classroom = Classroom::create([
            'level_id' => $level->id,
            'section' => $suffix,
            'name' => 'Class ' . $suffix,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $classroom->school_id = $school->id;
        $classroom->save();

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        return [$school, $admin, $classroom];
    }

    private function createTeacherContext(string $suffix): array
    {
        [$school, $admin, $classroom] = $this->createAdminContext($suffix);

        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $teacher->teacherClassrooms()->attach($classroom->id, ['school_id' => $school->id]);

        return [$school, $teacher, $classroom, $admin];
    }

    private function createParentContext(string $suffix): array
    {
        [$school] = $this->createAdminContext($suffix);

        $parent = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
            'is_active' => true,
        ]);

        return [$school, $parent];
    }
}
