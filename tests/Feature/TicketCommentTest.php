<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

test('requesters can reply to their own ticket', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Any update?'])
        ->assertRedirect(route('tickets.show', $ticket));

    $comment = TicketComment::sole();
    expect($comment->body)->toBe('Any update?')
        ->and($comment->author->is($requester))->toBeTrue()
        ->and($comment->is_internal)->toBeFalse();
});

test('requesters cannot reply to tickets of other users', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tickets.comments.store', Ticket::factory()->create()), ['body' => 'Hi'])
        ->assertForbidden();

    expect(TicketComment::count())->toBe(0);
});

test('requesters cannot post internal notes', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Sneaky', 'is_internal' => '1'])
        ->assertSessionHasErrors('is_internal');

    expect(TicketComment::count())->toBe(0);
});

test('internal notes are hidden from the requester', function () {
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($agent)
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Waiting on vendor RMA', 'is_internal' => '1']);

    $this->actingAs($requester)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('Waiting on vendor RMA');

    $this->actingAs($agent)
        ->get(route('tickets.show', $ticket))
        ->assertSee('Waiting on vendor RMA');
});

test('a requester reply reopens a resolved ticket', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->status(TicketStatus::Resolved)->create();

    $this->actingAs($requester)
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Still broken.']);

    expect($ticket->refresh()->status)->toBe(TicketStatus::Open);
});
