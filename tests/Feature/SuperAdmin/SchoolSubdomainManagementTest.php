<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSubdomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_creation_generates_a_subdomain_automatically(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('super.schools.store'), [
            'school_name' => 'Groupe Scolaire Achbal Ryad',
            'slug' => 'achbal-ryad-campus',
            'admin_name' => 'Campus Admin',
            'admin_email' => 'campus-admin@example.test',
            'admin_password' => 'secret123',
            'is_active' => '1',
        ]);

        $school = School::query()->where('slug', 'achbal-ryad-campus')->firstOrFail();

        $response->assertRedirect(rtrim($school->appUrl(), '/') . '/login?created=1');

        $this->assertSame('groupe-scolaire-achbal-ryad', $school->subdomain);
        $this->assertDatabaseHas('users', [
            'email' => 'campus-admin@example.test',
            'school_id' => $school->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_duplicate_school_names_generate_unique_suffixed_subdomains(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('super.schools.store'), [
            'school_name' => 'GS Achbal Ryad',
            'admin_name' => 'Campus Admin One',
            'admin_email' => 'campus-one@example.test',
            'admin_password' => 'secret123',
            'is_active' => '1',
        ])->assertRedirect('http://gs-achbal-ryad.myedu.school/login?created=1');

        $this->actingAs($superAdmin)->post(route('super.schools.store'), [
            'school_name' => 'GS Achbal Ryad',
            'admin_name' => 'Campus Admin Two',
            'admin_email' => 'campus-two@example.test',
            'admin_password' => 'secret123',
            'is_active' => '1',
        ])->assertRedirect('http://gs-achbal-ryad-2.myedu.school/login?created=1');

        $subdomains = School::query()
            ->orderBy('id')
            ->pluck('subdomain')
            ->all();

        $this->assertSame(['gs-achbal-ryad', 'gs-achbal-ryad-2'], $subdomains);
    }

    public function test_reserved_subdomains_are_adjusted_safely(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('super.schools.store'), [
            'school_name' => 'Admin',
            'admin_name' => 'Reserved Campus Admin',
            'admin_email' => 'reserved-admin@example.test',
            'admin_password' => 'secret123',
            'is_active' => '1',
        ])->assertRedirect('http://admin-school.myedu.school/login?created=1');

        $school = School::query()->where('name', 'Admin')->firstOrFail();

        $this->assertSame('admin-school', $school->subdomain);
    }

    public function test_editing_school_does_not_regenerate_subdomain_automatically(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $school = School::create([
            'name' => 'Original Campus',
            'slug' => 'original-campus',
            'subdomain' => 'original-campus',
            'is_active' => true,
        ]);
        User::factory()->admin()->forSchool($school)->create([
            'email' => 'original-admin@example.test',
        ]);

        $this->actingAs($superAdmin)->put(route('super.schools.update', $school), [
            'name' => 'Renamed Campus',
            'slug' => 'renamed-campus',
            'admin_name' => 'Updated Admin',
            'admin_email' => 'updated-admin@example.test',
            'admin_password' => '',
            'is_active' => '1',
        ])->assertRedirect(route('super.dashboard'));

        $school->refresh();

        $this->assertSame('original-campus', $school->subdomain);
    }
}
