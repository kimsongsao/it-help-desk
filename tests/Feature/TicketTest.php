<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;

test('requesters can submit a ticket', function () {
    $requester = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($requester)->post(route('tickets.store'), [
        'subject' => 'VPN keeps disconnecting',
        'description' => 'Drops every 10 minutes.',
        'priority' => TicketPriority::High->value,
        'category_id' => $category->id,
    ]);

    $ticket = Ticket::sole();

    $response->assertRedirect(route('tickets.show', $ticket));
    expect($ticket->requester->is($requester))->toBeTrue()
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->assignee_id)->toBeNull();
});

test('ticket submission is validated', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tickets.store'), ['priority' => 'urgent', 'category_id' => 999])
        ->assertSessionHasErrors(['subject', 'description', 'priority', 'category_id']);

    expect(Ticket::count())->toBe(0);
});

test('requesters cannot assign or set status when submitting', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs(User::factory()->create())->post(route('tickets.store'), [
        'subject' => 'Printer jam',
        'description' => 'Paper stuck.',
        'priority' => TicketPriority::Low->value,
        'category_id' => Category::factory()->create()->id,
        'status' => TicketStatus::Closed->value,
        'assignee_id' => $agent->id,
    ]);

    $ticket = Ticket::sole();
    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->assignee_id)->toBeNull();
});

test('requesters only see their own tickets in the list', function () {
    $requester = User::factory()->create();
    $own = Ticket::factory()->for($requester, 'requester')->create();
    $other = Ticket::factory()->create();

    $this->actingAs($requester)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($own->subject)
        ->assertDontSee($other->subject);
});

test('requesters cannot view tickets of other users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('tickets.show', Ticket::factory()->create()))
        ->assertForbidden();
});

test('agents can view all tickets', function () {
    $tickets = Ticket::factory()->count(2)->create();

    $this->actingAs(User::factory()->agent()->create())
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($tickets[0]->subject)
        ->assertSee($tickets[1]->subject);
});

test('the ticket list can be filtered', function () {
    $agent = User::factory()->agent()->create();
    $mine = Ticket::factory()->assigned($agent)->create(['subject' => 'Monitor flickering']);
    $resolved = Ticket::factory()->status(TicketStatus::Resolved)->create(['subject' => 'Outlook crash']);

    $this->actingAs($agent)
        ->get(route('tickets.index', ['assignee' => 'me']))
        ->assertSee($mine->subject)
        ->assertDontSee($resolved->subject);

    $this->actingAs($agent)
        ->get(route('tickets.index', ['status' => 'resolved']))
        ->assertSee($resolved->subject)
        ->assertDontSee($mine->subject);

    $this->actingAs($agent)
        ->get(route('tickets.index', ['search' => 'outlook']))
        ->assertSee($resolved->subject)
        ->assertDontSee($mine->subject);
});

test('agents can triage a ticket', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($agent)->patch(route('tickets.update', $ticket), [
        'status' => TicketStatus::InProgress->value,
        'priority' => TicketPriority::Critical->value,
        'category_id' => $category->id,
        'assignee_id' => $agent->id,
    ])->assertRedirect(route('tickets.show', $ticket));

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->priority)->toBe(TicketPriority::Critical)
        ->and($ticket->category->is($category))->toBeTrue()
        ->and($ticket->assignee->is($agent))->toBeTrue();
});

test('tickets can only be assigned to staff', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(User::factory()->agent()->create())->patch(route('tickets.update', $ticket), [
        'status' => TicketStatus::Open->value,
        'priority' => TicketPriority::Low->value,
        'category_id' => $ticket->category_id,
        'assignee_id' => User::factory()->create()->id,
    ])->assertSessionHasErrors('assignee_id');
});

test('requesters cannot triage their own ticket', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)->patch(route('tickets.update', $ticket), [
        'status' => TicketStatus::Closed->value,
        'priority' => TicketPriority::Critical->value,
        'category_id' => $ticket->category_id,
    ])->assertForbidden();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Open);
});

test('resolving and reopening a ticket maintains its timestamps', function () {
    $ticket = Ticket::factory()->create();

    $ticket->update(['status' => TicketStatus::Resolved]);
    expect($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->closed_at)->toBeNull();

    $ticket->update(['status' => TicketStatus::Closed]);
    expect($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->closed_at)->not->toBeNull();

    $ticket->update(['status' => TicketStatus::Open]);
    expect($ticket->resolved_at)->toBeNull()
        ->and($ticket->closed_at)->toBeNull();
});

test('only admins can delete tickets', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(User::factory()->agent()->create())
        ->delete(route('tickets.destroy', $ticket))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('tickets.destroy', $ticket))
        ->assertRedirect(route('tickets.index'));

    $this->assertModelMissing($ticket);
});
