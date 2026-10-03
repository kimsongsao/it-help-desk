<?php

use App\Enums\Permission;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('requesters can list only their own tickets', function () {
    $requester = Sanctum::actingAs(User::factory()->create());
    $own = Ticket::factory()->for($requester, 'requester')->create();
    Ticket::factory()->create();

    $this->getJson(route('api.v1.tickets.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id)
        ->assertJsonPath('data.0.reference', $own->reference)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('the ticket list can be filtered', function () {
    Sanctum::actingAs(User::factory()->agent()->create());
    Ticket::factory()->create(['priority' => TicketPriority::Low]);
    $critical = Ticket::factory()->create(['priority' => TicketPriority::Critical]);

    $this->getJson(route('api.v1.tickets.index', ['priority' => 'critical']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $critical->id);

    $this->getJson(route('api.v1.tickets.index', ['priority' => 'urgent']))->assertUnprocessable();
});

test('tickets can be created through the api with an sla', function () {
    SlaPolicy::factory()->create(['priority' => TicketPriority::High]);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('api.v1.tickets.store'), [
        'subject' => 'Monitor flickers',
        'description' => 'Since this morning.',
        'priority' => 'high',
        'category_id' => Category::factory()->create()->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.subject', 'Monitor flickers')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.sla.resolution_status', 'on_track');
});

test('ticket creation is validated', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('api.v1.tickets.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'description', 'priority', 'category_id']);
});

test('requesters cannot view or update tickets of others', function () {
    Sanctum::actingAs(User::factory()->create());
    $ticket = Ticket::factory()->create();

    $this->getJson(route('api.v1.tickets.show', $ticket))->assertForbidden();
    $this->patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'closed'])->assertForbidden();
});

test('agents can partially update a ticket', function () {
    $agent = Sanctum::actingAs(User::factory()->agent()->create());
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    $this->patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'in_progress', 'assignee_id' => $agent->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.priority', 'low')
        ->assertJsonPath('data.assignee.id', $agent->id);
});

test('the status workflow is enforced', function () {
    Sanctum::actingAs(User::factory()->agent()->create());
    $ticket = Ticket::factory()->status(TicketStatus::Closed)->create();

    $this->patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'in_progress'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->patchJson(route('api.v1.tickets.update', $ticket), ['status' => 'open'])->assertOk();
});

test('fields the user is not allowed to change are rejected', function () {
    $assigner = User::factory()->withPermissions([
        Permission::TicketsViewAll,
        Permission::TicketsAssign,
        Permission::ApiAccess,
    ])->create();
    Sanctum::actingAs($assigner);
    $ticket = Ticket::factory()->create();

    $this->patchJson(route('api.v1.tickets.update', $ticket), ['priority' => 'critical'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('priority');
});

test('comments hide internal notes from requesters', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();
    TicketComment::factory()->for($ticket)->create(['body' => 'Public']);
    TicketComment::factory()->for($ticket)->internal()->create(['body' => 'Private']);

    Sanctum::actingAs($requester);
    $this->getJson(route('api.v1.tickets.comments.index', $ticket))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Public');

    Sanctum::actingAs(User::factory()->agent()->create());
    $this->getJson(route('api.v1.tickets.comments.index', $ticket))->assertJsonCount(2, 'data');
});

test('comments can be added through the api', function () {
    $requester = Sanctum::actingAs(User::factory()->create());
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Any news?'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Any news?')
        ->assertJsonPath('data.author.id', $requester->id);

    $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'x', 'is_internal' => true])
        ->assertUnprocessable();
});

test('tickets can be escalated through the api', function () {
    Sanctum::actingAs(User::factory()->agent()->create());
    $ticket = Ticket::factory()->create();

    $this->postJson(route('api.v1.tickets.escalations.store', $ticket), ['reason' => 'VIP user'])
        ->assertCreated()
        ->assertJsonPath('data.level', 1)
        ->assertJsonPath('data.type', 'manual');

    $this->getJson(route('api.v1.tickets.show', $ticket))
        ->assertJsonPath('data.escalation_level', 1)
        ->assertJsonPath('data.escalations.0.reason', 'VIP user');

    $this->getJson(route('api.v1.tickets.activities.index', $ticket))
        ->assertOk()
        ->assertJsonFragment(['type' => 'escalated']);
});

test('only admins can delete tickets through the api', function () {
    $ticket = Ticket::factory()->create();

    Sanctum::actingAs(User::factory()->agent()->create());
    $this->deleteJson(route('api.v1.tickets.destroy', $ticket))->assertForbidden();

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->deleteJson(route('api.v1.tickets.destroy', $ticket))->assertNoContent();
    $this->assertModelMissing($ticket);
});

test('categories can be listed', function () {
    Sanctum::actingAs(User::factory()->create());
    Category::factory()->create(['name' => 'Hardware']);

    $this->getJson(route('api.v1.categories.index'))->assertOk()->assertJsonPath('data.0.name', 'Hardware');
});

test('the report summary requires the reports permission', function () {
    Sanctum::actingAs(User::factory()->agent()->create());
    $this->getJson(route('api.v1.reports.summary'))->assertForbidden();

    Sanctum::actingAs(User::factory()->supervisor()->create());
    $this->getJson(route('api.v1.reports.summary'))
        ->assertOk()
        ->assertJsonStructure(['data' => ['period', 'summary' => ['created', 'resolved', 'resolution_compliance'], 'daily_volume', 'agents']]);
});
