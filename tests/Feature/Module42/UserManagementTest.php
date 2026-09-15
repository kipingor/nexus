<?php

declare(strict_types=1);

namespace Tests\Feature\Module42;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 4.2 — User management within a tenant.
 *
 * All requests go through the central-domain web.php routes.
 * The InitializeTenancyFromAuth middleware initialises the tenant context
 * from the authenticated user's tenant_id so BelongsToTenant scopes apply.
 *
 * Tenant creation (via factory) triggers SeedTenantDefaults which:
 *  - calls tenancy()->initialize($tenant)
 *  - seeds the four default roles and all permissions
 * so the tenancy singleton is already set up after factory()->create().
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);

        // Reset any tenant context left by a previous test
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Create tenant — side-effect: SeedTenantDefaults runs and
        // initialises tenancy() to this tenant, seeding roles/permissions.
        $this->tenant = Tenant::factory()->create(['id' => 'test-corp']);

        // BelongsToTenant creating hook fires → tenant_id auto-set from context
        $this->admin  = User::factory()->create(['email' => 'admin@test.local']);
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create(['email' => 'member@test.local']);
        $this->member->assignRole('sales');
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function test_admin_can_list_tenant_users(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Users/Index')
                ->has('users.data', 2)); // admin + member
    }

    public function test_sales_member_cannot_access_user_list(): void
    {
        $this->actingAs($this->member)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect('/login');
    }

    // ─── Invite / Store ───────────────────────────────────────────────────────

    public function test_admin_can_invite_new_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name'  => 'New User',
                'email' => 'newuser@test.local',
                'phone' => '+254700000000',
                'role'  => 'ops',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email'     => 'newuser@test.local',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_invite_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name'  => 'Dupe',
                'email' => $this->member->email, // already taken
                'role'  => 'ops',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_invite_requires_a_valid_role(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name'  => 'Bad Role User',
                'email' => 'badrole@test.local',
                'role'  => 'superuser', // does not exist
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_sales_member_cannot_invite_users(): void
    {
        $this->actingAs($this->member)
            ->post(route('users.store'), [
                'name'  => 'Sneaky',
                'email' => 'sneaky@test.local',
                'role'  => 'ops',
            ])
            ->assertForbidden();
    }

    // ─── Edit / Update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.edit', $this->member))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Users/Edit')
                ->where('user.id', $this->member->id));
    }

    public function test_admin_can_update_user_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->member), [
                'name'      => $this->member->name,
                'phone'     => null,
                'is_active' => true,
                'role'      => 'finance',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($this->member->fresh()->hasRole('finance'));
    }

    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->member), [
                'name'      => $this->member->name,
                'phone'     => null,
                'is_active' => false,
                'role'      => $this->member->roles->first()?->name ?? 'sales',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertFalse($this->member->fresh()->is_active);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_user(): void
    {
        $userId = $this->member->id;

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->member))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    // ─── Roles listing ───────────────────────────────────────────────────────

    public function test_admin_can_view_roles_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Roles/Index')
                ->has('roles', 4)); // admin, finance, ops, sales
    }

    // ─── Tenant isolation ────────────────────────────────────────────────────

    public function test_admin_cannot_see_or_edit_other_tenant_users(): void
    {
        // Create a second tenant and a user in it.
        // SeedTenantDefaults will re-initialise tenancy to tenantB.
        $tenantB  = Tenant::factory()->create(['id' => 'other-corp']);
        $userB    = User::factory()->create(['email' => 'userb@other.local']);

        // Switch tenancy back to tenantA for the requests
        tenancy()->initialize($this->tenant);

        // The list must NOT include userB
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where(
                'users.data',
                fn ($data) => collect($data)->every(
                    fn ($u) => $u['id'] !== $userB->id
                )
            ));

        // Direct access to userB's edit page must 404 (scope hides it)
        $this->actingAs($this->admin)
            ->get(route('users.edit', $userB->id))
            ->assertNotFound();
    }
}
