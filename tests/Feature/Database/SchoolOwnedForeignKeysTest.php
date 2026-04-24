<?php

namespace Tests\Feature\Database;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolOwnedForeignKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_message_with_invalid_school_id(): void
    {
        $sender = User::factory()->forSchool($this->school())->admin()->create();

        $this->expectException(QueryException::class);

        DB::table('messages')->insert([
            'school_id' => 999999,
            'sender_id' => $sender->id,
            'sender_role' => $sender->role,
            'recipient_type' => 'user',
            'recipient_id' => $sender->id,
            'subject' => 'Invalid message',
            'body' => 'This should fail.',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_appointment_with_invalid_parent_reference(): void
    {
        $school = $this->school();

        $this->expectException(QueryException::class);

        DB::table('appointments')->insert([
            'school_id' => $school->id,
            'parent_user_id' => 999999,
            'parent_id' => null,
            'parent_name' => 'Ghost Parent',
            'parent_phone' => '0600000000',
            'parent_email' => 'ghost@example.com',
            'title' => 'Invalid appointment',
            'message' => null,
            'scheduled_at' => now(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_course_with_invalid_school_id(): void
    {
        $school = $this->school();
        $level = Level::create([
            'school_id' => $school->id,
            'code' => 'CP',
            'name' => 'CP',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $classroom = Classroom::create([
            'school_id' => $school->id,
            'level_id' => $level->id,
            'section' => 'A',
            'name' => 'CP A',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        DB::table('courses')->insert([
            'school_id' => 999999,
            'classroom_id' => $classroom->id,
            'teacher_id' => null,
            'title' => 'Invalid course',
            'description' => null,
            'published_at' => null,
            'status' => 'approved',
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'created_by_user_id' => null,
            'file' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function school(): School
    {
        return School::create([
            'name' => 'Constraint School',
            'slug' => 'constraint-school',
            'is_active' => true,
        ]);
    }
}
