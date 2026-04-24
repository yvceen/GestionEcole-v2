<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Level;
use App\Models\School;
use Illuminate\Database\Seeder;

class LevelsAndClassroomsSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->get();

        if ($schools->isEmpty()) {
            $schools = collect([
                School::create([
                    'name' => 'Demo School',
                    'slug' => 'demo-school',
                    'is_active' => true,
                ]),
            ]);
        }

        $data = [
            ['code' => 'MS', 'name' => 'MS', 'sections' => []],
            ['code' => 'GS', 'name' => 'GS', 'sections' => []],
            ['code' => 'CP', 'name' => 'CP', 'sections' => ['A', 'B']],
            ['code' => 'CE1', 'name' => 'CE1', 'sections' => ['A', 'B']],
            ['code' => 'CE2', 'name' => 'CE2', 'sections' => ['A']],
            ['code' => '1AC', 'name' => '1AC', 'sections' => []],
        ];

        foreach ($schools as $school) {
            $order = 1;

            foreach ($data as $levelData) {
                $level = Level::updateOrCreate(
                    ['school_id' => $school->id, 'code' => $levelData['code']],
                    [
                        'name' => $levelData['name'],
                        'sort_order' => $order++,
                        'is_active' => true,
                    ]
                );

                if ($levelData['sections'] === []) {
                    Classroom::updateOrCreate(
                        ['school_id' => $school->id, 'level_id' => $level->id, 'section' => null],
                        ['name' => $level->name, 'sort_order' => 1, 'is_active' => true]
                    );
                    continue;
                }

                foreach ($levelData['sections'] as $index => $section) {
                    Classroom::updateOrCreate(
                        ['school_id' => $school->id, 'level_id' => $level->id, 'section' => $section],
                        [
                            'name' => $level->name . ' ' . $section,
                            'sort_order' => $index + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
