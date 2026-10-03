<?php

use App\Enums\EscalationType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEscalated;
use Illuminate\Support\Facades\Notification;

test('agents can escalate a ticket to a supervisor', function () {
    Notification::fake();
    $agent = User::factory()->agent()->create();
    $supervisor = User::factory()->supervisor()->create();
    $ticket = Ticket::factory()->assigned($agent)->create(['priority' => TicketPriority::Medium]);

    $this->actingAs($agent)
        ->post(route('tickets.escalations.store', $ticket), [
            'reason' => 'Needs vendor contact',
            'escalate_to_id' => $supervisor->id,
        ])
        ->assertRedirect(route('tickets.show', $ticket));

    $ticket->refresh();
    $escalation = $ticket->escalations()->sole();

    expect($ticket->escalation_level)->toBe(1)
        ->and($ticket->escalated_at)->not->toBeNull()
        ->and($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->assignee->is($supervisor))->toBeTrue()
        ->and($escalation->type)->toBe(EscalationType::Manual)
        ->and($escalation->escalatedBy->is($agent))->toBeTrue()
        ->and($escalation->reason)->toBe('Needs vendor contact');

    Notification::assertSentTo($supervisor, TicketEscalated::class);
    Notification::assertNotSentTo($agent, TicketEscalated::class);
});

test('repeated escalations raise the level and cap the priority at critical', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::High]);

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($agent)->post(route('tickets.escalations.store', $ticket), ['reason' => "Attempt {$attempt}"]);
    }

    expect($ticket->refresh()->escalation_level)->toBe(3)
        ->and($ticket->priority)->toBe(TicketPriority::Critical);
});

test('requesters cannot escalate tickets', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)
        ->post(route('tickets.escalations.store', $ticket), ['reason' => 'Hurry up'])
        ->assertForbidden();

    expect($ticket->refresh()->escalation_level)->toBe(0);
});

test('closed tickets cannot be escalated', function () {
    $ticket = Ticket::factory()->status(TicketStatus::Closed)->create();

    $this->actingAs(User::factory()->agent()->create())
        ->post(route('tickets.escalations.store', $ticket), ['reason' => 'Too late'])
        ->assertForbidden();
});

test('escalations must target an active agent', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(User::factory()->agent()->create())
        ->post(route('tickets.escalations.store', $ticket), [
            'reason' => 'Hand over',
            'escalate_to_id' => User::factory()->agent()->inactive()->create()->id,
        ])
        ->assertSessionHasErrors('escalate_to_id');
});

test('the ticket page shows the escalation history', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $this->actingAs($agent)->post(route('tickets.escalations.store', $ticket), ['reason' => 'Printer on fire']);

    $this->actingAs($agent)->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Level 1')
        ->assertSee('Printer on fire');
});
