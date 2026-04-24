<?php

namespace Tests\Concerns;

use App\Models\AppNotification;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

trait BuildsSchoolContext
{
    protected function createSchool(array $overrides = []): School
    {
        $slugBase = $overrides['slug'] ?? ('school-' . Str::lower(Str::random(6)));
        $subdomainBase = $overrides['subdomain'] ?? $slugBase;

        return School::create(array_merge([
            'name' => 'School ' . Str::upper(Str::random(3)),
            'slug' => $slugBase,
            'subdomain' => $subdomainBase,
            'is_active' => true,
        ], $overrides));
    }

    protected function createClassroomForSchool(School $school, array $levelOverrides = [], array $classroomOverrides = []): Classroom
    {
        $level = Level::create(array_merge([
            'school_id' => $school->id,
            'code' => 'LVL' . Str::upper(Str::random(5)),
            'name' => 'Level ' . Str::upper(Str::random(2)),
            'sort_order' => 1,
            'is_active' => true,
        ], $levelOverrides));

        return Classroom::create(array_merge([
            'school_id' => $school->id,
            'level_id' => $level->id,
            'section' => 'A',
            'name' => 'Class ' . Str::upper(Str::random(3)),
            'sort_order' => 1,
            'is_active' => true,
        ], $classroomOverrides));
    }

    protected function createUserForSchool(School $school, string $role, array $overrides = []): User
    {
        return User::factory()
            ->forSchool($school)
            ->state(array_merge([
                'role' => $role,
                'is_active' => true,
            ], $overrides))
            ->create();
    }

    protected function createStudentForSchool(
        School $school,
        Classroom $classroom,
        ?User $parent = null,
        ?User $studentUser = null,
        array $overrides = []
    ): Student {
        return Student::create(array_merge([
            'school_id' => $school->id,
            'full_name' => 'Student ' . Str::upper(Str::random(4)),
            'birth_date' => now()->subYears(10)->toDateString(),
            'gender' => 'male',
            'parent_user_id' => $parent?->id,
            'user_id' => $studentUser?->id,
            'classroom_id' => $classroom->id,
        ], $overrides));
    }

    protected function assignTeacherToClassroom(User $teacher, Classroom $classroom, ?User $assignedBy = null): void
    {
        $teacher->teacherClassrooms()->syncWithoutDetaching([
            $classroom->id => [
                'school_id' => $classroom->school_id,
                'assigned_by_user_id' => $assignedBy?->id,
            ],
        ]);
    }

    protected function createNotificationForUser(User $user, array $overrides = []): AppNotification
    {
        return AppNotification::create(array_merge([
            'recipient_user_id' => $user->id,
            'user_id' => $user->id,
            'recipient_role' => $user->role,
            'type' => 'message',
            'title' => 'Notification',
            'body' => 'Notification body',
            'data' => [],
            'read_at' => null,
        ], $overrides));
    }
}
