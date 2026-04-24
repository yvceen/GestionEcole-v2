<?php

namespace Tests\Feature\Database;

use App\Models\School;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FreshInstallSeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_builds_two_school_demo_graphs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $schools = School::orderBy('slug')->get();

        $this->assertCount(2, $schools);
        $this->assertSame(['atlas-academy', 'cedar-college'], $schools->pluck('slug')->all());

        foreach ($schools as $school) {
            $this->assertTrue(User::where('school_id', $school->id)->where('role', User::ROLE_ADMIN)->exists());
            $this->assertTrue(User::where('school_id', $school->id)->where('role', User::ROLE_DIRECTOR)->exists());
            $this->assertTrue(User::where('school_id', $school->id)->where('role', User::ROLE_TEACHER)->exists());
            $this->assertTrue(User::where('school_id', $school->id)->where('role', User::ROLE_PARENT)->exists());
            $this->assertTrue(User::where('school_id', $school->id)->where('role', User::ROLE_STUDENT)->exists());

            $this->assertGreaterThan(0, DB::table('levels')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('classrooms')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('subjects')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('teacher_subjects')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('assessments')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('grades')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('messages')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('news')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('appointments')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('courses')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('homeworks')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('vehicles')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('routes')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('transport_assignments')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('school_lives')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('timetable_settings')->where('school_id', $school->id)->count());
            $this->assertGreaterThan(0, DB::table('timetables')->where('school_id', $school->id)->count());
        }
    }

    public function test_seeded_school_owned_records_remain_school_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $checks = [
            "select count(*) c from teacher_subjects ts join users u on u.id = ts.teacher_id where ts.school_id <> u.school_id" => 0,
            "select count(*) c from teacher_subjects ts join subjects s on s.id = ts.subject_id where ts.school_id <> s.school_id" => 0,
            "select count(*) c from assessments a join classrooms c on c.id = a.classroom_id where a.school_id <> c.school_id" => 0,
            "select count(*) c from assessments a join users u on u.id = a.teacher_id where a.school_id <> u.school_id" => 0,
            "select count(*) c from assessments a join subjects s on s.id = a.subject_id where a.school_id <> s.school_id" => 0,
            "select count(*) c from grades g join assessments a on a.id = g.assessment_id where g.school_id <> a.school_id" => 0,
            "select count(*) c from messages m join users u on u.id = m.sender_id where m.school_id <> u.school_id" => 0,
            "select count(*) c from news n join classrooms c on c.id = n.classroom_id where n.classroom_id is not null and n.school_id <> c.school_id" => 0,
            "select count(*) c from appointments a join users u on u.id = a.parent_user_id where a.parent_user_id is not null and a.school_id <> u.school_id" => 0,
            "select count(*) c from courses c join classrooms cl on cl.id = c.classroom_id where c.school_id <> cl.school_id" => 0,
            "select count(*) c from courses c join users u on u.id = c.teacher_id where c.teacher_id is not null and c.school_id <> u.school_id" => 0,
            "select count(*) c from homeworks h join classrooms cl on cl.id = h.classroom_id where h.school_id <> cl.school_id" => 0,
            "select count(*) c from homeworks h join users u on u.id = h.teacher_id where h.school_id <> u.school_id" => 0,
            "select count(*) c from routes r join vehicles v on v.id = r.vehicle_id where r.vehicle_id is not null and r.school_id <> v.school_id" => 0,
            "select count(*) c from transport_assignments ta join routes r on r.id = ta.route_id where ta.school_id <> r.school_id" => 0,
            "select count(*) c from transport_assignments ta join students s on s.id = ta.student_id where ta.school_id <> s.school_id" => 0,
            "select count(*) c from transport_assignments ta join vehicles v on v.id = ta.vehicle_id where ta.vehicle_id is not null and ta.school_id <> v.school_id" => 0,
        ];

        foreach ($checks as $sql => $expected) {
            $this->assertSame($expected, (int) DB::selectOne($sql)->c, $sql);
        }
    }
}
