<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function forSchool(School|int|null $school): static
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        return $this->state(fn () => [
            'school_id' => $schoolId,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'school_id' => null,
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function director(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_DIRECTOR,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_TEACHER,
        ]);
    }

    public function parent(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_PARENT,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_STUDENT,
        ]);
    }

    public function chauffeur(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_CHAUFFEUR,
        ]);
    }

    public function schoolLife(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_SCHOOL_LIFE,
        ]);
    }
}
