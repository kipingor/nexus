<?php

declare(strict_types=1);

namespace Tests\Feature\Module41;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private TenantProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TenantProvisioningService::class);
    }

    public function test_it_provisions_a_new_tenant(): void
    {
        $tenant = $this->service->provision([
            'name'      => 'Acme Corp',
            'subdomain' => 'acme',
            'plan'      => 'starter',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Acme Corp', $tenant->name);
        $this->assertEquals('acme', $tenant->slug);
        // Service creates tenants on trial initially
        $this->assertEquals('trial', $tenant->status);
    }

    public function test_it_attaches_a_domain_to_the_tenant(): void
    {
        $tenant = $this->service->provision([
            'name'      => 'Beta Ltd',
            'subdomain' => 'beta',
            'plan'      => 'starter',
        ]);

        // Domain is slug + central domain suffix
        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
        ]);

        $domain = $tenant->domains()->first();
        $this->assertNotNull($domain);
        $this->assertStringStartsWith('beta.', $domain->domain);
    }

    public function test_it_suspends_a_tenant(): void
    {
        $tenant = $this->service->provision([
            'name'      => 'Suspend Me',
            'subdomain' => 'suspend-me',
            'plan'      => 'starter',
        ]);

        $this->service->suspend($tenant);

        $this->assertEquals('suspended', $tenant->fresh()->status);
    }

    public function test_it_activates_a_suspended_tenant(): void
    {
        $tenant = $this->service->provision([
            'name'      => 'Reactivate Me',
            'subdomain' => 'reactivate-me',
            'plan'      => 'starter',
        ]);

        $this->service->suspend($tenant);
        $this->service->activate($tenant);

        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_it_deletes_a_tenant_and_its_domains(): void
    {
        $tenant = $this->service->provision([
            'name'      => 'Delete Me',
            'subdomain' => 'delete-me',
            'plan'      => 'starter',
        ]);

        $tenantId = $tenant->id;

        $this->service->delete($tenant);

        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
        $this->assertDatabaseMissing('domains', ['tenant_id' => $tenantId]);
    }
}
