<?php

declare(strict_types=1);

namespace Tests\Feature\Module43;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactCRUDTest extends TestCase
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

    public function test_admin_can_list_contacts(): void
    {
        Contact::factory()->forTenant($this->tenant)->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/crm/contacts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CRM/Contacts/Index'));
    }

    public function test_unauthenticated_user_redirected_from_contacts(): void
    {
        $this->get('/crm/contacts')->assertRedirect('/login');
    }

    public function test_sales_member_can_view_contacts(): void
    {
        $sales = User::factory()->forTenant($this->tenant)->create();
        $sales->assignRole('sales');

        $this->actingAs($sales)
            ->get('/crm/contacts')
            ->assertOk();
    }

    public function test_finance_member_cannot_view_contacts(): void
    {
        $finance = User::factory()->forTenant($this->tenant)->create();
        $finance->assignRole('finance');

        $this->actingAs($finance)
            ->get('/crm/contacts')
            ->assertForbidden();
    }

    public function test_admin_can_create_contact(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/contacts', [
                'first_name' => 'John',
                'last_name'  => 'Kamau',
                'email'      => 'john@example.co.ke',
                'phone'      => '+254712345678',
                'stage'      => 'lead',
                'source'     => 'referral',
            ])
            ->assertRedirect('/crm/contacts');

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name'  => 'Kamau',
            'tenant_id'  => $this->tenant->id,
        ]);
    }

    public function test_contact_creation_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post('/crm/contacts', [])
            ->assertSessionHasErrors(['first_name', 'stage', 'source']);
    }

    public function test_admin_can_view_contact_detail(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->get("/crm/contacts/{$contact->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CRM/Contacts/Show'));
    }

    public function test_admin_can_update_contact(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->lead()->create();

        $this->actingAs($this->admin)
            ->put("/crm/contacts/{$contact->id}", [
                'first_name' => 'Updated',
                'last_name'  => 'Name',
                'stage'      => 'customer',
                'source'     => 'website',
            ])
            ->assertRedirect("/crm/contacts/{$contact->id}");

        $contact->refresh();
        $this->assertSame('customer', $contact->stage);
        $this->assertSame('Updated', $contact->first_name);
    }

    public function test_admin_can_delete_contact(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->delete("/crm/contacts/{$contact->id}")
            ->assertRedirect('/crm/contacts');

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_contact_is_scoped_to_tenant(): void
    {
        $otherTenant  = Tenant::factory()->create();
        tenancy()->initialize($otherTenant);
        $otherContact = Contact::factory()->forTenant($otherTenant)->create();
        tenancy()->initialize($this->tenant);

        // Our tenant admin should not be able to see the other tenant's contact
        $this->actingAs($this->admin)
            ->get("/crm/contacts/{$otherContact->id}")
            ->assertNotFound();
    }

    public function test_contact_can_be_linked_to_company(): void
    {
        $company = Company::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->post('/crm/contacts', [
                'first_name' => 'Jane',
                'stage'      => 'prospect',
                'source'     => 'website',
                'company_id' => $company->id,
            ])
            ->assertRedirect('/crm/contacts');

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'company_id' => $company->id,
            'tenant_id'  => $this->tenant->id,
        ]);
    }
}
