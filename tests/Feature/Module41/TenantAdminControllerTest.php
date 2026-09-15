<?php

declare(strict_types=1);

namespace Tests\Feature\Module41;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class TenantAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass CSRF for all controller tests in this class.
        // Laravel 13 registers PreventRequestForgery (not the legacy VerifyCsrfToken alias).
        $this->withoutMiddleware(PreventRequestForgery::class);

        // Central admin user (no tenant)
        $this->admin = User::factory()->create([
            'tenant_id' => null,
            'email'     => 'admin@nexus.co.ke',
        ]);
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function test_admin_can_list_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('central.tenants.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Central/Tenants/Index'));
    }

    public function test_unauthenticated_user_is_redirected_from_admin(): void
    {
        // Unauthenticated requests to protected central routes redirect to /login
        $response = $this->get(route('central.tenants.index'));
        $response->assertRedirect('/login');
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('central.tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Central/Tenants/Show'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_a_tenant(): void
    {
        $this->actingAs($this->admin)
            ->post(route('central.tenants.store'), [
                'name'      => 'New Corp',
                'subdomain' => 'new-corp',
                'plan'      => 'starter',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['slug' => 'new-corp']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Tenant::factory()->create(['id' => 'taken', 'slug' => 'taken']);

        $this->actingAs($this->admin)
            ->post(route('central.tenants.store'), [
                'name'      => 'Another Corp',
                'subdomain' => 'taken',
                'plan'      => 'starter',
            ])
            ->assertSessionHasErrors('subdomain');
    }

    // ─── Suspend / Activate ──────────────────────────────────────────────────

    public function test_admin_can_suspend_an_active_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin)
            ->post(route('central.tenants.suspend', $tenant))
            ->assertRedirect();

        $this->assertEquals('suspended', $tenant->fresh()->status);
    }

    public function test_admin_can_activate_a_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $this->actingAs($this->admin)
            ->post(route('central.tenants.activate', $tenant))
            ->assertRedirect();

        $this->assertEquals('active', $tenant->fresh()->status);
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_a_tenant(): void
    {
        /** @var TenantProvisioningService $svc */
        $svc    = app(TenantProvisioningService::class);
        $tenant = $svc->provision([
            'name'      => 'To Delete',
            'subdomain' => 'to-delete',
            'plan'      => 'starter',
        ]);

        $tenantId = $tenant->id;

        $this->actingAs($this->admin)
            ->delete(route('central.tenants.destroy', $tenant))
            ->assertRedirect(route('central.tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
    }
}
