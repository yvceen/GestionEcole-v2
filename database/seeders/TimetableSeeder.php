<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\School;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimetableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->get();

        foreach ($schools as $school) {
            $teacher = User::query()
                ->where('school_id', $school->id)
                ->where('role', User::ROLE_TEACHER)
                ->orderBy('id')
                ->first();

            $classroom = Classroom::query()
                ->where('school_id', $school->id)
                ->orderBy('id')
                ->first();

            if (!$teacher || !$classroom) {
                continue;
            }

            $subjects = ['Mathematiques', 'Francais', 'Sciences', 'Histoire', 'Anglais'];

            foreach ($subjects as $index => $subject) {
                Timetable::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'classroom_id' => $classroom->id,
                        'day' => $index + 1,
                        'start_time' => '08:00',
                    ],
                    [
                        'end_time' => '09:00',
                        'subject' => $subject,
                        'teacher_id' => $teacher->id,
                        'room' => 'Salle ' . ($index + 1),
                    ]
                );
            }
        }
    }
}
