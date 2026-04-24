<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\TimetableSetting;
use Illuminate\Database\Seeder;

class TimetableSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::query()->get() as $school) {
            TimetableSetting::updateOrCreate(
                ['school_id' => $school->id],
                [
                    'day_start_time' => '08:00:00',
                    'day_end_time' => '17:00:00',
                    'slot_minutes' => 60,
                    'lunch_start' => '12:00:00',
                    'lunch_end' => '13:00:00',
                ]
            );
        }
    }
}
