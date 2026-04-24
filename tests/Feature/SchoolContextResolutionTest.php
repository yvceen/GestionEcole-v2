<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsSchoolContext;
use Tests\TestCase;

class SchoolContextResolutionTest extends TestCase
{
    use BuildsSchoolContext;
    use RefreshDatabase;

    public function test_valid_school_subdomain_resolves_the_correct_school_context(): void
    {
        $school = $this->createSchool([
            'name' => 'Atlas Academy',
            'slug' => 'atlas-academy',
            'subdomain' => 'atlas-academy',
        ]);

        $this->get('http://' . $school->subdomain . '.myedu.test/login')
            ->assertOk()
            ->assertSee('Atlas Academy');
    }

    public function test_unknown_school_subdomain_returns_not_found(): void
    {
        $this->get('http://unknown-campus.myedu.test/login')
            ->assertNotFound();
    }

    public function test_root_domain_access_still_works_without_school_context(): void
    {
        $school = $this->createSchool([
            'name' => 'Root Safety Academy',
            'subdomain' => 'root-safety-academy',
        ]);

        $this->get('http://' . $school->subdomain . '.myedu.test/login')
            ->assertOk()
            ->assertSee('Root Safety Academy');

        $this->get('http://myedu.school/login')
            ->assertOk()
            ->assertDontSee('Root Safety Academy');
    }

    public function test_school_context_does_not_leak_across_subdomain_requests(): void
    {
        $schoolA = $this->createSchool([
            'name' => 'Atlas Academy',
            'subdomain' => 'atlas-academy',
        ]);
        $schoolB = $this->createSchool([
            'name' => 'Cedar College',
            'subdomain' => 'cedar-college',
        ]);

        $this->get('http://' . $schoolA->subdomain . '.myedu.test/login')
            ->assertOk()
            ->assertSee('Atlas Academy')
            ->assertDontSee('Cedar College');

        $this->get('http://' . $schoolB->subdomain . '.myedu.test/login')
            ->assertOk()
            ->assertSee('Cedar College')
            ->assertDontSee('Atlas Academy');
    }

    public function test_host_based_school_resolution_blocks_user_with_mismatched_school(): void
    {
        $schoolA = $this->createSchool([
            'slug' => 'atlas-academy',
            'subdomain' => 'atlas-academy',
        ]);
        $schoolB = $this->createSchool([
            'slug' => 'cedar-college',
            'subdomain' => 'cedar-college',
        ]);
        $adminA = $this->createUserForSchool($schoolA, User::ROLE_ADMIN);

        $this->actingAs($adminA)
            ->get('http://' . $schoolB->subdomain . '.myedu.test/admin')
            ->assertForbidden();
    }

    public function test_super_admin_can_use_host_context_to_view_school_scoped_admin_news(): void
    {
        $schoolA = $this->createSchool([
            'slug' => 'host-atlas',
            'subdomain' => 'host-atlas',
        ]);
        $schoolB = $this->createSchool([
            'slug' => 'host-cedar',
            'subdomain' => 'host-cedar',
        ]);
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'host-superadmin@example.test',
        ]);

        News::create([
            'school_id' => $schoolA->id,
            'scope' => 'school',
            'classroom_id' => null,
            'title' => 'Atlas scoped news',
            'status' => 'published',
            'date' => now()->toDateString(),
        ]);
        News::create([
            'school_id' => $schoolB->id,
            'scope' => 'school',
            'classroom_id' => null,
            'title' => 'Cedar scoped news',
            'status' => 'published',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($superAdmin)
            ->get('http://' . $schoolA->subdomain . '.myedu.test/admin/news')
            ->assertOk()
            ->assertSee('Atlas scoped news')
            ->assertDontSee('Cedar scoped news');
    }
}
