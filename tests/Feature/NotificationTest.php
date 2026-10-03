<?php

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketReplied;
use Illuminate\Support\Facades\Notification;

test('assigning a ticket notifies the new assignee', function () {
    Notification::fake();
    $assigner = User::factory()->agent()->create();
    $assignee = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $this->actingAs($assigner)->patch(route('tickets.update', $ticket), ['assignee_id' => $assignee->id]);

    Notification::assertSentTo($assignee, TicketAssigned::class);
});

test('self assignment does not send a notification', function () {
    Notification::fake();
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->patch(route('tickets.update', Ticket::factory()->create()), ['assignee_id' => $agent->id]);

    Notification::assertNothingSent();
});

test('a staff reply notifies the requester and a requester reply notifies the assignee', function () {
    Notification::fake();
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->assigned($agent)->create();

    $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), ['body' => 'Try restarting']);
    Notification::assertSentTo($requester, TicketReplied::class);

    $this->actingAs($requester)->post(route('tickets.comments.store', $ticket), ['body' => 'Still broken']);
    Notification::assertSentTo($agent, TicketReplied::class);
});

test('internal notes do not notify the requester', function () {
    Notification::fake();
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs(User::factory()->agent()->create())
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Secret', 'is_internal' => '1']);

    Notification::assertNotSentTo($requester, TicketReplied::class);
});

test('opening a notification marks it read and shows the ticket', function () {
    $assignee = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();
    $assignee->notify(new TicketAssigned($ticket));
    $notification = $assignee->notifications()->sole();

    $this->actingAs($assignee)->get(route('notifications.index'))->assertOk()->assertSee($ticket->subject);

    $this->actingAs($assignee)
        ->get(route('notifications.show', $notification->id))
        ->assertRedirect(route('tickets.show', $ticket));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('users cannot open notifications of other users', function () {
    $owner = User::factory()->agent()->create();
    $owner->notify(new TicketAssigned(Ticket::factory()->create()));

    $this->actingAs(User::factory()->agent()->create())
        ->get(route('notifications.show', $owner->notifications()->sole()->id))
        ->assertNotFound();
});
