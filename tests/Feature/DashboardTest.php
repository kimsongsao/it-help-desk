<?php

use App\Models\Ticket;
use App\Models\User;

test('guests cannot view the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('requesters only see their own tickets on the dashboard', function () {
    $requester = User::factory()->create();
    $own = Ticket::factory()->for($requester, 'requester')->create();
    $other = Ticket::factory()->create();

    $this->actingAs($requester)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($own->subject)
        ->assertDontSee($other->subject)
        ->assertDontSee('My queue');
});

test('agents see their assigned queue on the dashboard', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->assigned($agent)->create();

    $this->actingAs($agent)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My queue')
        ->assertSee($ticket->subject);
});
