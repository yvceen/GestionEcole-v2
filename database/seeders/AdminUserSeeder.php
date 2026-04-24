<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(
            ['slug' => 'demo-school'],
            ['name' => 'Demo School', 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'admin@school.test'],
            [
                'school_id' => $school->id,
                'name' => 'Demo School Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
