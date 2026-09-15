<?php

declare(strict_types=1);

namespace Tests\Feature\Module44;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCRUDTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
        $this->admin = User::factory()->forTenant($this->tenant)->create();
        $this->admin->assignRole('admin');
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_admin_can_list_projects(): void
    {
        Project::factory()->forTenant($this->tenant)->count(2)->create();
        $this->actingAs($this->admin)->get('/projects')->assertOk()->assertInertia(fn ($page) => $page->component('Projects/Index'));
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get('/projects')->assertRedirect('/login');
    }

    public function test_sales_member_can_view_projects(): void
    {
        $sales = User::factory()->forTenant($this->tenant)->create();
        $sales->assignRole('sales');
        $this->actingAs($sales)->get('/projects')->assertOk();
    }

    public function test_finance_member_cannot_view_projects(): void
    {
        $finance = User::factory()->forTenant($this->tenant)->create();
        $finance->assignRole('finance');
        $this->actingAs($finance)->get('/projects')->assertForbidden();
    }

    public function test_admin_can_create_project(): void
    {
        $this->actingAs($this->admin)->post('/projects', [
            'name' => 'Nairobi Office Fit-out', 'code' => 'PRJ-001', 'status' => 'planning', 'priority' => 'high',
            'start_date' => '2026-08-01', 'due_date' => '2026-10-31', 'budget' => 250000,
        ])->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', ['name' => 'Nairobi Office Fit-out', 'tenant_id' => $this->tenant->id, 'code' => 'PRJ-001']);
    }

    public function test_project_creation_validates_required_fields(): void
    {
        $this->actingAs($this->admin)->post('/projects', [])->assertSessionHasErrors(['name', 'status', 'priority']);
    }

    public function test_project_code_is_unique_per_tenant(): void
    {
        Project::factory()->forTenant($this->tenant)->create(['code' => 'PRJ-001']);
        $this->actingAs($this->admin)->post('/projects', [
            'name' => 'Another project', 'code' => 'PRJ-001', 'status' => 'planning', 'priority' => 'medium',
        ])->assertSessionHasErrors(['code']);
    }

    public function test_admin_can_view_project_with_task_progress(): void
    {
        $project = Project::factory()->forTenant($this->tenant)->create();
        $this->actingAs($this->admin)->get('/projects/' . $project->id)->assertOk()->assertInertia(fn ($page) => $page->component('Projects/Show'));
    }

    public function test_admin_can_update_project(): void
    {
        $project = Project::factory()->forTenant($this->tenant)->create(['status' => 'planning']);
        $this->actingAs($this->admin)->put('/projects/' . $project->id, [
            'name' => 'Updated project', 'status' => 'active', 'priority' => 'urgent',
        ])->assertRedirect('/projects/' . $project->id);
        $this->assertSame('active', $project->fresh()->status);
        $this->assertSame('Updated project', $project->fresh()->name);
    }

    public function test_admin_can_delete_project_and_tasks(): void
    {
        $project = Project::factory()->forTenant($this->tenant)->create();
        $this->actingAs($this->admin)->delete('/projects/' . $project->id)->assertRedirect('/projects');
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_is_scoped_to_tenant(): void
    {
        $other = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherProject = Project::factory()->forTenant($other)->create();
        tenancy()->initialize($this->tenant);
        $this->actingAs($this->admin)->get('/projects/' . $otherProject->id)->assertNotFound();
    }
}
