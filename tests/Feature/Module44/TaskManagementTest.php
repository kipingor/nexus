<?php

declare(strict_types=1);

namespace Tests\Feature\Module44;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Project $project;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
        $this->project = Project::factory()->forTenant($this->tenant)->create();
        $this->admin = User::factory()->forTenant($this->tenant)->create();
        $this->admin->assignRole('admin');
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_admin_can_list_all_tasks(): void
    {
        Task::factory()->forProject($this->project)->count(2)->create();
        $this->actingAs($this->admin)->get('/tasks')->assertOk()->assertInertia(fn ($page) => $page->component('Tasks/Index'));
    }

    public function test_admin_can_create_task_for_project(): void
    {
        $this->actingAs($this->admin)->post('/projects/' . $this->project->id . '/tasks', [
            'title' => 'Prepare procurement brief', 'status' => 'todo', 'priority' => 'high', 'due_date' => '2026-09-15',
        ])->assertRedirect('/projects/' . $this->project->id);
        $this->assertDatabaseHas('tasks', ['title' => 'Prepare procurement brief', 'project_id' => $this->project->id, 'tenant_id' => $this->tenant->id, 'created_by' => $this->admin->id]);
    }

    public function test_task_creation_validates_required_fields(): void
    {
        $this->actingAs($this->admin)->post('/projects/' . $this->project->id . '/tasks', [])->assertSessionHasErrors(['title', 'status', 'priority']);
    }

    public function test_task_assignee_must_belong_to_current_tenant(): void
    {
        $other = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherUser = User::factory()->forTenant($other)->create();
        tenancy()->initialize($this->tenant);
        $this->actingAs($this->admin)->post('/projects/' . $this->project->id . '/tasks', [
            'title' => 'Cross tenant task', 'status' => 'todo', 'priority' => 'medium', 'assignee_id' => $otherUser->id,
        ])->assertSessionHasErrors(['assignee_id']);
    }

    public function test_admin_can_update_task(): void
    {
        $task = Task::factory()->forProject($this->project)->create(['status' => 'todo']);
        $this->actingAs($this->admin)->put('/tasks/' . $task->id, [
            'title' => 'Updated task', 'status' => 'in_progress', 'priority' => 'medium',
        ])->assertRedirect('/projects/' . $this->project->id);
        $this->assertSame('in_progress', $task->fresh()->status);
    }

    public function test_marking_task_done_sets_completed_at(): void
    {
        $task = Task::factory()->forProject($this->project)->create(['status' => 'todo']);
        $this->actingAs($this->admin)->patch('/tasks/' . $task->id . '/done')->assertRedirect();
        $task->refresh();
        $this->assertSame('done', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_admin_can_delete_task(): void
    {
        $task = Task::factory()->forProject($this->project)->create();
        $this->actingAs($this->admin)->delete('/tasks/' . $task->id)->assertRedirect('/projects/' . $this->project->id);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_is_scoped_to_tenant(): void
    {
        $other = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherProject = Project::factory()->forTenant($other)->create();
        $otherTask = Task::factory()->forProject($otherProject)->create();
        tenancy()->initialize($this->tenant);
        $this->actingAs($this->admin)->patch('/tasks/' . $otherTask->id . '/done')->assertNotFound();
    }

    public function test_ops_member_can_manage_tasks(): void
    {
        $ops = User::factory()->forTenant($this->tenant)->create();
        $ops->assignRole('ops');
        $this->actingAs($ops)->get('/tasks')->assertOk();
    }
}
