<?php

declare(strict_types=1);

namespace Tests\Feature\Module43;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyCRUDTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

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

    public function test_admin_can_list_companies(): void
    {
        Company::factory()->forTenant($this->tenant)->count(2)->create();

        $this->actingAs($this->admin)
            ->get('/crm/companies')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CRM/Companies/Index'));
    }

    public function test_admin_can_create_company(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/companies', [
                'name'     => 'Safaricom PLC',
                'industry' => 'Technology',
                'website'  => 'https://safaricom.co.ke',
            ])
            ->assertRedirect('/crm/companies');

        $this->assertDatabaseHas('companies', [
            'name'      => 'Safaricom PLC',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_company_creation_validates_name(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/companies', [])
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_company(): void
    {
        $company = Company::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->put("/crm/companies/{$company->id}", [
                'name'     => 'Updated Name',
                'industry' => 'Finance',
            ])
            ->assertRedirect("/crm/companies/{$company->id}");

        $this->assertSame('Updated Name', $company->fresh()->name);
    }

    public function test_admin_can_delete_company(): void
    {
        $company = Company::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->delete("/crm/companies/{$company->id}")
            ->assertRedirect('/crm/companies');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_company_scoped_to_tenant(): void
    {
        $other        = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherCompany = Company::factory()->forTenant($other)->create();
        tenancy()->initialize($this->tenant);

        $this->actingAs($this->admin)
            ->get("/crm/companies/{$otherCompany->id}")
            ->assertNotFound();
    }
}
