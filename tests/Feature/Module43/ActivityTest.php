<?php

declare(strict_types=1);

namespace Tests\Feature\Module43;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    public function test_can_log_activity_on_contact(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->post("/crm/activities?for=contact,{$contact->id}", [
                'type'    => 'call',
                'subject' => 'Initial discovery call',
                'body'    => 'Discussed requirements.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'type'         => 'call',
            'subject'      => 'Initial discovery call',
            'actable_type' => Contact::class,
            'actable_id'   => $contact->id,
            'tenant_id'    => $this->tenant->id,
        ]);
    }

    public function test_can_log_activity_on_deal(): void
    {
        $deal = Deal::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->post("/crm/activities?for=deal,{$deal->id}", [
                'type'    => 'meeting',
                'subject' => 'Demo presentation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'type'         => 'meeting',
            'actable_type' => Deal::class,
            'actable_id'   => $deal->id,
        ]);
    }

    public function test_activity_requires_type_and_subject(): void
    {
        $contact = Contact::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->post("/crm/activities?for=contact,{$contact->id}", [])
            ->assertSessionHasErrors(['type', 'subject']);
    }

    public function test_can_mark_activity_as_done(): void
    {
        $contact  = Contact::factory()->forTenant($this->tenant)->create();
        $activity = Activity::factory()->forTenant($this->tenant)->forContact($contact)->create();
        $this->assertNull($activity->done_at);

        $this->actingAs($this->admin)
            ->patch("/crm/activities/{$activity->id}/done")
            ->assertRedirect();

        $this->assertNotNull($activity->fresh()->done_at);
    }

    public function test_can_delete_activity(): void
    {
        $contact  = Contact::factory()->forTenant($this->tenant)->create();
        $activity = Activity::factory()->forTenant($this->tenant)->forContact($contact)->create();

        $this->actingAs($this->admin)
            ->delete("/crm/activities/{$activity->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_activity_scoped_to_tenant(): void
    {
        $other          = Tenant::factory()->create();
        tenancy()->initialize($other);
        $otherContact   = Contact::factory()->forTenant($other)->create();
        $otherActivity  = Activity::factory()->forTenant($other)->forContact($otherContact)->create();
        tenancy()->initialize($this->tenant);

        // Patching another tenant's activity should 404
        $this->actingAs($this->admin)
            ->patch("/crm/activities/{$otherActivity->id}/done")
            ->assertNotFound();
    }
}
