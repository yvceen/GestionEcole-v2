<?php

namespace Tests\Feature\SchoolLife;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\AppNotification;
use App\Models\Route;
use App\Models\TransportAssignment;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class StudentLifeOperationsTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_parent_can_confirm_only_owned_child_activity_participation(): void
    {
        $school = $this->createSchool(['slug' => 'parent-activity-confirm']);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $otherParent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $teacher = $this->createUserForSchool($school, User::ROLE_TEACHER);
        $ownedChild = $this->createStudentForSchool($school, $classroom, $parent);
        $foreignChild = $this->createStudentForSchool($school, $classroom, $otherParent);

        $activity = Activity::create([
            'school_id' => $school->id,
            'title' => 'Atelier robotique',
            'type' => Activity::TYPE_ATELIER,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHour(),
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
        ]);

        ActivityParticipant::create([
            'school_id' => $school->id,
            'activity_id' => $activity->id,
            'student_id' => $ownedChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_PENDING,
        ]);
        ActivityParticipant::create([
            'school_id' => $school->id,
            'activity_id' => $activity->id,
            'student_id' => $foreignChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_PENDING,
        ]);

        $this->actingAs($parent)->post(route('parent.activities.confirm', $activity, false), [
            'student_id' => $ownedChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_CONFIRMED,
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_participants', [
            'school_id' => $school->id,
            'activity_id' => $activity->id,
            'student_id' => $ownedChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_CONFIRMED,
        ]);

        $this->actingAs($parent)->post(route('parent.activities.confirm', $activity, false), [
            'student_id' => $foreignChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_CONFIRMED,
        ])->assertNotFound();

        $this->assertDatabaseHas('activity_participants', [
            'school_id' => $school->id,
            'activity_id' => $activity->id,
            'student_id' => $foreignChild->id,
            'confirmation_status' => ActivityParticipant::CONFIRMATION_PENDING,
        ]);
    }

    public function test_school_life_transport_ops_is_school_scoped_and_updates_same_day_log(): void
    {
        $school = $this->createSchool(['slug' => 'transport-ops-school-life']);
        $schoolLife = $this->createUserForSchool($school, User::ROLE_SCHOOL_LIFE);
        $parent = $this->createUserForSchool($school, User::ROLE_PARENT);
        $classroom = $this->createClassroomForSchool($school);
        $student = $this->createStudentForSchool($school, $classroom, $parent);
        $vehicle = Vehicle::create([
            'school_id' => $school->id,
            'name' => 'Bus A',
            'registration_number' => 'BUS-A',
            'vehicle_type' => 'bus',
            'capacity' => 40,
            'is_active' => true,
        ]);
        $route = Route::create([
            'school_id' => $school->id,
            'route_name' => 'Ligne centre',
            'vehicle_id' => $vehicle->id,
            'start_point' => 'Centre',
            'end_point' => 'Ecole',
            'monthly_fee' => 300,
            'is_active' => true,
        ]);
        $assignment = TransportAssignment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'period' => 'both',
            'assigned_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($schoolLife)->post(route('transport.ops.store', absolute: false), [
            'transport_assignment_id' => $assignment->id,
            'status' => TransportLog::STATUS_BOARDED,
            'note' => 'Montee portail nord',
        ])->assertRedirect();

        $this->assertDatabaseHas('transport_logs', [
            'school_id' => $school->id,
            'transport_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => TransportLog::STATUS_BOARDED,
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $parent->id,
            'type' => 'transport',
        ]);

        $this->actingAs($schoolLife)->post(route('transport.ops.store', absolute: false), [
            'transport_assignment_id' => $assignment->id,
            'status' => TransportLog::STATUS_BOARDED,
            'note' => 'Montee mise a jour',
        ])->assertRedirect();

        $this->assertSame(
            1,
            TransportLog::query()
                ->where('school_id', $school->id)
                ->where('transport_assignment_id', $assignment->id)
                ->where('status', TransportLog::STATUS_BOARDED)
                ->whereDate('logged_at', now()->toDateString())
                ->count()
        );
        $this->assertSame(
            1,
            AppNotification::query()
                ->where('recipient_user_id', $parent->id)
                ->where('type', 'transport')
                ->count()
        );
    }
}
