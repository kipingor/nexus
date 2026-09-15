<?php

declare(strict_types=1);

namespace Tests\Feature\Module42;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 4.2 — Authentication flow tests.
 *
 * Tests the Fortify-powered login / logout flow and the basic auth guards
 * on the /dashboard route.  Uses central-domain users (tenant_id = null)
 * so no tenant initialisation is required.
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ─── Dashboard access guard ───────────────────────────────────────────────

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Dashboard/Index'));
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'password'  => bcrypt('correct-password'),
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(); // Fortify redirects to config('fortify.home') on success

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'password'  => bcrypt('correct-password'),
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect();

        $this->assertGuest();
    }

    // ─── Auth pages are accessible to guests ─────────────────────────────────

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_register_page_is_accessible_to_guests(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_forgot_password_page_is_accessible_to_guests(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    // ─── Authenticated users are bounced from guest-only pages ───────────────

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect();
    }
}
