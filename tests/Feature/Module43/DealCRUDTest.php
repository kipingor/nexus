<?php

declare(strict_types=1);

namespace Tests\Feature\Module43;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealCRUDTest extends TestCase
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

    public function test_admin_can_view_pipeline(): void
    {
        Deal::factory()->forTenant($this->tenant)->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/crm/deals')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CRM/Deals/Pipeline'));
    }

    public function test_admin_can_view_list(): void
    {
        $this->actingAs($this->admin)
            ->get('/crm/deals?view=list')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CRM/Deals/Index'));
    }

    public function test_admin_can_create_deal(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/deals', [
                'title'    => 'ERP Implementation',
                'value'    => 500000,
                'currency' => 'KES',
                'stage'    => 'new',
            ])
            ->assertRedirect('/crm/deals');

        $this->assertDatabaseHas('deals', [
            'title'     => 'ERP Implementation',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_deal_requires_title_and_value(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/deals', [])
            ->assertSessionHasErrors(['title', 'value', 'stage']);
    }

    public function test_admin_can_update_deal_stage(): void
    {
        $deal = Deal::factory()->forTenant($this->tenant)->open()->create();

        $this->actingAs($this->admin)
            ->put("/crm/deals/{$deal->id}", [
                'title'    => $deal->title,
                'value'    => $deal->value,
                'currency' => 'KES',
                'stage'    => 'won',
            ])
            ->assertRedirect("/crm/deals/{$deal->id}");

        $deal->refresh();
        $this->assertSame('won', $deal->stage);
        $this->assertNotNull($deal->closed_at);
    }

    public function test_moving_deal_back_to_open_clears_closed_at(): void
    {
        $deal = Deal::factory()->forTenant($this->tenant)->won()->create();

        $this->actingAs($this->admin)
            ->put("/crm/deals/{$deal->id}", [
                'title'    => $deal->title,
                'value'    => $deal->value,
                'currency' => 'KES',
                'stage'    => 'qualified',
            ]);

        $this->assertNull($deal->fresh()->closed_at);
    }

    public function test_admin_can_delete_deal(): void
    {
        $deal = Deal::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->delete("/crm/deals/{$deal->id}")
            ->assertRedirect('/crm/deals');

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
    }

    public function test_deal_scoped_to_tenant(): void
    {
        $other     = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherDeal = Deal::factory()->forTenant($other)->create();
        tenancy()->initialize($this->tenant);

        $this->actingAs($this->admin)
            ->get("/crm/deals/{$otherDeal->id}")
            ->assertNotFound();
    }

    public function test_deal_can_be_linked_to_contact(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->post('/crm/deals', [
                'title'      => 'Consulting Project',
                'value'      => 150000,
                'currency'   => 'KES',
                'stage'      => 'qualified',
                'contact_id' => $contact->id,
            ]);

        $this->assertDatabaseHas('deals', [
            'contact_id' => $contact->id,
            'tenant_id'  => $this->tenant->id,
        ]);
    }
}
