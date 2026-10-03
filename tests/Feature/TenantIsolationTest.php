<?php

use App\Enums\WorkflowActionType;
use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Models\Workflow;
use App\Support\Tenancy;
use Laravel\Sanctum\Sanctum;

/**
 * Create records inside another organization.
 *
 * @template TReturn
 *
 * @param  callable(): TReturn  $callback
 * @return TReturn
 */
function inOtherOrganization(callable $callback, ?Organization $organization = null): mixed
{
    $organization ??= Organization::factory()->create(['name' => 'Other Company']);

    return app(Tenancy::class)->runAs($organization, $callback);
}

test('staff never see tickets of another organization', function () {
    $foreignTicket = inOtherOrganization(fn () => Ticket::factory()->create(['subject' => 'Foreign secret']));
    $ownTicket = Ticket::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('tickets.index'))
        ->assertSee($ownTicket->subject)
        ->assertDontSee('Foreign secret');

    $this->actingAs($admin)->get(route('tickets.show', $foreignTicket))->assertNotFound();
    $this->actingAs($admin)->patch(route('tickets.update', $foreignTicket), ['status' => 'closed'])->assertNotFound();
    $this->actingAs($admin)->delete(route('tickets.destroy', $foreignTicket))->assertNotFound();

    expect(Ticket::withoutGlobalScopes()->find($foreignTicket->id)->status->value)->toBe('open');
});

test('the api never exposes another organization', function () {
    $foreignTicket = inOtherOrganization(fn () => Ticket::factory()->create());
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson(route('api.v1.tickets.index'))->assertJsonCount(0, 'data');
    $this->getJson(route('api.v1.tickets.show', $foreignTicket))->assertNotFound();
    $this->postJson(route('api.v1.tickets.comments.store', $foreignTicket), ['body' => 'Hi'])->assertNotFound();
});

test('attachments of another organization are not served', function () {
    $attachment = inOtherOrganization(fn () => TicketAttachment::factory()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('attachments.show', $attachment))
        ->assertNotFound();
});

test('records of another organization cannot be referenced', function () {
    $foreignCategory = inOtherOrganization(fn () => Category::factory()->create());
    $foreignAgent = inOtherOrganization(fn () => User::factory()->agent()->create());
    $ticket = Ticket::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('tickets.store'), [
        'subject' => 'Help', 'description' => 'Please', 'priority' => 'low', 'category_id' => $foreignCategory->id,
    ])->assertSessionHasErrors('category_id');

    $this->actingAs($admin)->patch(route('tickets.update', $ticket), ['assignee_id' => $foreignAgent->id])
        ->assertSessionHasErrors('assignee_id');
});

test('each organization keeps its own roles and categories', function () {
    $other = Organization::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('categories.store'), ['name' => 'Hardware'])
        ->assertSessionHasNoErrors();

    inOtherOrganization(function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('categories.store'), ['name' => 'Hardware'])
            ->assertSessionHasNoErrors();
    }, $other);

    expect(Category::withoutGlobalScopes()->where('name', 'Hardware')->count())->toBe(2);
});

test('reports only count the organization of the viewer', function () {
    Ticket::factory()->count(2)->create();
    inOtherOrganization(fn () => Ticket::factory()->count(5)->create());

    $this->actingAs(User::factory()->supervisor()->create())
        ->getJson(route('api.v1.reports.summary'))
        ->assertJsonPath('data.summary.created', 2)
        ->assertJsonPath('data.summary.backlog', 2);
});

test('the sla check applies each organization its own workflows', function () {
    $ownWorkflow = Workflow::factory()->create([
        'trigger' => WorkflowTrigger::SlaBreached,
        'actions' => [['type' => WorkflowActionType::AddInternalNote->value, 'value' => 'Own note']],
    ]);
    $foreignTicket = inOtherOrganization(function () {
        SlaPolicy::factory()->create(['first_response_minutes' => 5, 'escalate_on_breach' => false]);

        $this->actingAs(User::factory()->create())->post(route('tickets.store'), [
            'subject' => 'Late', 'description' => 'x', 'priority' => 'low',
            'category_id' => Category::factory()->create()->id,
        ]);

        return Ticket::latest('id')->first();
    });
    app(Tenancy::class)->set(null);

    $this->travel(10)->minutes();
    $this->artisan('sla:check')->assertSuccessful();

    $foreignTicket = Ticket::withoutGlobalScopes()->find($foreignTicket->id);
    expect($foreignTicket->first_response_breached_at)->not->toBeNull()
        ->and($foreignTicket->comments()->count())->toBe(0)
        ->and($ownWorkflow->refresh()->run_count)->toBe(0);
});

test('members of a suspended organization are signed out', function () {
    $user = User::factory()->create();
    $this->organization->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your organization has been suspended.']);

    Sanctum::actingAs($user);
    $this->getJson(route('api.v1.me'))->assertForbidden();
});
