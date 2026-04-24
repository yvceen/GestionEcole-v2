<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Homework;
use App\Models\Level;
use App\Models\News;
use App\Models\Route;
use App\Models\School;
use App\Models\SchoolLife;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TransportAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MultiSchoolDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();

        foreach ($this->schools() as $index => $config) {
            $this->seedSchool($config, $index + 1);
        }
    }

    private function schools(): array
    {
        return [
            [
                'name' => 'Atlas Academy',
                'slug' => 'atlas-academy',
                'code' => 'atlas',
                'subjects' => [
                    ['name' => 'Mathematiques', 'code' => 'MATH'],
                    ['name' => 'Francais', 'code' => 'FR'],
                    ['name' => 'Sciences', 'code' => 'SCI'],
                ],
            ],
            [
                'name' => 'Cedar College',
                'slug' => 'cedar-college',
                'code' => 'cedar',
                'subjects' => [
                    ['name' => 'Anglais', 'code' => 'EN'],
                    ['name' => 'Histoire', 'code' => 'HIST'],
                    ['name' => 'Informatique', 'code' => 'INFO'],
                ],
            ],
        ];
    }

    private function seedSuperAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@platform.test'],
            [
                'school_id' => null,
                'name' => 'Platform Super Admin',
                'phone' => '0600000000',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );
    }

    private function seedSchool(array $config, int $offset): void
    {
        $school = School::updateOrCreate(
            ['slug' => $config['slug']],
            [
                'name' => $config['name'],
                'is_active' => true,
            ]
        );

        $admin = $this->upsertUser($school, 'admin', "admin.{$config['code']}@school.test", "{$config['name']} Admin", "06110000{$offset}");
        $director = $this->upsertUser($school, 'director', "director.{$config['code']}@school.test", "{$config['name']} Director", "06110010{$offset}");
        $teacherA = $this->upsertUser($school, 'teacher', "teacher1.{$config['code']}@school.test", "{$config['name']} Teacher 1", "06110020{$offset}");
        $teacherB = $this->upsertUser($school, 'teacher', "teacher2.{$config['code']}@school.test", "{$config['name']} Teacher 2", "06110030{$offset}");
        $parentA = $this->upsertUser($school, 'parent', "parent1.{$config['code']}@school.test", "{$config['name']} Parent 1", "06110040{$offset}");
        $parentB = $this->upsertUser($school, 'parent', "parent2.{$config['code']}@school.test", "{$config['name']} Parent 2", "06110050{$offset}");
        $chauffeur = $this->upsertUser($school, 'chauffeur', "chauffeur.{$config['code']}@school.test", "{$config['name']} Chauffeur", "06110060{$offset}");

        $levelPrimary = Level::updateOrCreate(
            ['school_id' => $school->id, 'code' => 'CP'],
            ['name' => 'CP', 'sort_order' => 1, 'is_active' => true]
        );

        $levelJunior = Level::updateOrCreate(
            ['school_id' => $school->id, 'code' => 'CE1'],
            ['name' => 'CE1', 'sort_order' => 2, 'is_active' => true]
        );

        $classroomA = Classroom::updateOrCreate(
            ['school_id' => $school->id, 'level_id' => $levelPrimary->id, 'section' => 'A'],
            ['name' => 'CP A', 'sort_order' => 1, 'is_active' => true]
        );

        $classroomB = Classroom::updateOrCreate(
            ['school_id' => $school->id, 'level_id' => $levelJunior->id, 'section' => 'B'],
            ['name' => 'CE1 B', 'sort_order' => 2, 'is_active' => true]
        );

        $studentUserA = $this->upsertUser($school, 'student', "student1.{$config['code']}@school.test", "{$config['name']} Student 1", "06110070{$offset}");
        $studentUserB = $this->upsertUser($school, 'student', "student2.{$config['code']}@school.test", "{$config['name']} Student 2", "06110080{$offset}");

        $studentA = Student::updateOrCreate(
            ['user_id' => $studentUserA->id],
            [
                'school_id' => $school->id,
                'full_name' => $studentUserA->name,
                'birth_date' => now()->subYears(8)->toDateString(),
                'gender' => 'female',
                'parent_user_id' => $parentA->id,
                'classroom_id' => $classroomA->id,
            ]
        );

        $studentB = Student::updateOrCreate(
            ['user_id' => $studentUserB->id],
            [
                'school_id' => $school->id,
                'full_name' => $studentUserB->name,
                'birth_date' => now()->subYears(9)->toDateString(),
                'gender' => 'male',
                'parent_user_id' => $parentB->id,
                'classroom_id' => $classroomA->id,
            ]
        );

        $subjects = collect($config['subjects'])->map(function (array $subject) use ($school) {
            return Subject::updateOrCreate(
                ['school_id' => $school->id, 'name' => $subject['name']],
                ['code' => $subject['code'], 'is_active' => true]
            );
        })->values();

        $this->upsertPivot('classroom_teacher', [
            'school_id' => $school->id,
            'teacher_id' => $teacherA->id,
            'classroom_id' => $classroomA->id,
        ], $admin->id);

        $this->upsertPivot('classroom_teacher', [
            'school_id' => $school->id,
            'teacher_id' => $teacherB->id,
            'classroom_id' => $classroomB->id,
        ], $admin->id);

        $this->upsertPivot('teacher_subjects', [
            'school_id' => $school->id,
            'teacher_id' => $teacherA->id,
            'subject_id' => $subjects[0]->id,
        ], $director->id);

        $this->upsertPivot('teacher_subjects', [
            'school_id' => $school->id,
            'teacher_id' => $teacherA->id,
            'subject_id' => $subjects[1]->id,
        ], $director->id);

        $this->upsertPivot('teacher_subjects', [
            'school_id' => $school->id,
            'teacher_id' => $teacherB->id,
            'subject_id' => $subjects[2]->id,
        ], $director->id);

        $assessment = Assessment::updateOrCreate(
            [
                'school_id' => $school->id,
                'teacher_id' => $teacherA->id,
                'classroom_id' => $classroomA->id,
                'subject_id' => $subjects[0]->id,
                'title' => 'Controle 1',
            ],
            [
                'type' => 'controle',
                'date' => now()->subDays(10)->toDateString(),
                'coefficient' => 1,
                'max_score' => 20,
                'description' => 'Evaluation de debut de trimestre',
            ]
        );

        foreach ([
            $studentA->id => ['score' => 16.5, 'comment' => 'Bon travail'],
            $studentB->id => ['score' => 13.0, 'comment' => 'Peut mieux faire'],
        ] as $studentId => $grade) {
            Grade::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'assessment_id' => $assessment->id,
                ],
                [
                    'school_id' => $school->id,
                    'classroom_id' => $classroomA->id,
                    'teacher_id' => $teacherA->id,
                    'subject_id' => $subjects[0]->id,
                    'score' => $grade['score'],
                    'max_score' => 20,
                    'comment' => $grade['comment'],
                ]
            );
        }

        News::updateOrCreate(
            ['school_id' => $school->id, 'title' => "Bienvenue a {$config['name']}"],
            ['scope' => 'school', 'classroom_id' => null, 'status' => 'published', 'date' => now()->toDateString()]
        );

        News::updateOrCreate(
            ['school_id' => $school->id, 'title' => "{$classroomA->name} - Reunion parents"],
            ['scope' => 'classroom', 'classroom_id' => $classroomA->id, 'status' => 'published', 'date' => now()->addDays(3)->toDateString()]
        );

        Appointment::updateOrCreate(
            ['school_id' => $school->id, 'parent_user_id' => $parentA->id, 'title' => 'Rendez-vous pedagogique'],
            [
                'parent_id' => $parentA->id,
                'parent_name' => $parentA->name,
                'parent_phone' => $parentA->phone,
                'parent_email' => $parentA->email,
                'message' => 'Discussion sur les progres de l eleve.',
                'scheduled_at' => now()->addDays(2),
                'status' => 'pending',
                'admin_notes' => null,
            ]
        );

        Appointment::updateOrCreate(
            ['school_id' => $school->id, 'parent_user_id' => $parentB->id, 'title' => 'Suivi de trimestre'],
            [
                'parent_id' => $parentB->id,
                'parent_name' => $parentB->name,
                'parent_phone' => $parentB->phone,
                'parent_email' => $parentB->email,
                'message' => 'Validation du plan d accompagnement.',
                'scheduled_at' => now()->addDays(5),
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]
        );

        Course::updateOrCreate(
            ['school_id' => $school->id, 'classroom_id' => $classroomA->id, 'title' => 'Lecon 1'],
            [
                'teacher_id' => $teacherA->id,
                'created_by_user_id' => $teacherA->id,
                'description' => 'Revision des notions fondamentales.',
                'published_at' => now()->subDay(),
                'status' => 'approved',
                'approved_at' => now()->subDay(),
                'approved_by' => $director->id,
                'file' => null,
            ]
        );

        Homework::updateOrCreate(
            ['school_id' => $school->id, 'classroom_id' => $classroomA->id, 'title' => 'Devoir maison 1'],
            [
                'teacher_id' => $teacherA->id,
                'description' => 'Faire les exercices 1 a 3 du cahier.',
                'due_at' => now()->addDays(7),
                'status' => 'approved',
                'approved_at' => now()->subHours(4),
                'approved_by' => $director->id,
            ]
        );

        $vehicle = Vehicle::updateOrCreate(
            ['registration_number' => strtoupper($config['code']) . '-BUS-01'],
            [
                'school_id' => $school->id,
                'vehicle_type' => 'bus',
                'capacity' => 24,
                'driver_id' => $chauffeur->id,
                'plate_number' => strtoupper($config['code']) . '-1234',
                'color' => $offset % 2 === 0 ? 'Blue' : 'White',
                'model_year' => 2022,
                'notes' => 'Vehicule de demonstration',
                'is_active' => true,
            ]
        );

        $route = Route::updateOrCreate(
            ['school_id' => $school->id, 'route_name' => "{$config['name']} Nord"],
            [
                'vehicle_id' => $vehicle->id,
                'start_point' => 'Quartier Nord',
                'end_point' => $school->name,
                'distance_km' => 12.5,
                'monthly_fee' => 350,
                'estimated_minutes' => 35,
                'stops' => ['Quartier Nord', 'Centre Ville', $school->name],
                'is_active' => true,
            ]
        );

        TransportAssignment::updateOrCreate(
            ['school_id' => $school->id, 'student_id' => $studentA->id],
            [
                'route_id' => $route->id,
                'vehicle_id' => $vehicle->id,
                'period' => 'both',
                'pickup_point' => 'Centre Ville',
                'assigned_date' => now()->subWeek()->toDateString(),
                'ended_date' => null,
                'is_active' => true,
                'notes' => 'Affectation de demonstration',
            ]
        );

        SchoolLife::updateOrCreate(
            ['school_id' => $school->id, 'title' => "Vie scolaire {$config['name']}"],
            ['status' => 'open', 'date' => now()->toDateString()]
        );

        $this->seedMessages($school, $admin, $teacherA, $parentA, $classroomA);
    }

    private function upsertUser(School $school, string $role, string $email, string $name, string $phone): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'school_id' => $school->id,
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make('password'),
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    private function upsertPivot(string $table, array $identity, int $assignedBy): void
    {
        DB::table($table)->updateOrInsert(
            $identity,
            [
                'assigned_by_user_id' => $assignedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedMessages(School $school, User $admin, User $teacher, User $parent, Classroom $classroom): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('messages'));

        $adminAnnouncement = [
            'school_id' => $school->id,
            'sender_id' => $admin->id,
            'sender_role' => $admin->role,
            'subject' => "Bienvenue {$school->name}",
            'body' => 'Annonce de demonstration pour les familles et enseignants.',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $teacherMessage = [
            'school_id' => $school->id,
            'sender_id' => $teacher->id,
            'sender_role' => $teacher->role,
            'subject' => 'Suivi pedagogique',
            'body' => 'Message de demonstration pour le suivi parent.',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (isset($columns['recipient_type'], $columns['recipient_id'])) {
            DB::table('messages')->updateOrInsert(
                [
                    'school_id' => $school->id,
                    'sender_id' => $admin->id,
                    'recipient_type' => 'classroom',
                    'recipient_id' => $classroom->id,
                    'subject' => $adminAnnouncement['subject'],
                ],
                array_merge($adminAnnouncement, [
                    'recipient_type' => 'classroom',
                    'recipient_id' => $classroom->id,
                ])
            );

            DB::table('messages')->updateOrInsert(
                [
                    'school_id' => $school->id,
                    'sender_id' => $teacher->id,
                    'recipient_type' => 'user',
                    'recipient_id' => $parent->id,
                    'subject' => $teacherMessage['subject'],
                ],
                array_merge($teacherMessage, [
                    'recipient_type' => 'user',
                    'recipient_id' => $parent->id,
                ])
            );

            return;
        }

        if (isset($columns['target_type'], $columns['target_id'])) {
            DB::table('messages')->updateOrInsert(
                [
                    'school_id' => $school->id,
                    'sender_id' => $admin->id,
                    'target_type' => 'classroom',
                    'target_id' => $classroom->id,
                    'subject' => $adminAnnouncement['subject'],
                ],
                array_merge($adminAnnouncement, [
                    'target_type' => 'classroom',
                    'target_id' => $classroom->id,
                    'target_user_ids' => null,
                ])
            );

            DB::table('messages')->updateOrInsert(
                [
                    'school_id' => $school->id,
                    'sender_id' => $teacher->id,
                    'target_type' => 'user',
                    'target_id' => $parent->id,
                    'subject' => $teacherMessage['subject'],
                ],
                array_merge($teacherMessage, [
                    'target_type' => 'user',
                    'target_id' => $parent->id,
                    'target_user_ids' => json_encode([$parent->id]),
                ])
            );
        }
    }
}
