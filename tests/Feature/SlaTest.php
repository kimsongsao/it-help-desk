<?php

use App\Actions\Tickets\AddTicketComment;
use App\Enums\EscalationType;
use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEscalated;
use App\Services\Sla\BusinessHoursCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

function submitTicket(User $requester, array $attributes = []): Ticket
{
    test()->actingAs($requester)->post(route('tickets.store'), [
        'subject' => 'Cannot print',
        'description' => 'The printer is offline.',
        'priority' => TicketPriority::Medium->value,
        'category_id' => Category::factory()->create()->id,
        ...$attributes,
    ]);

    return Ticket::latest('id')->firstOrFail();
}

test('new tickets get due dates from the matching sla policy', function () {
    $this->freezeTime();
    SlaPolicy::factory()->create(['priority' => TicketPriority::High, 'first_response_minutes' => 30, 'resolution_minutes' => 240]);

    $ticket = submitTicket(User::factory()->create(), ['priority' => TicketPriority::High->value]);

    expect($ticket->slaPolicy)->not->toBeNull()
        ->and($ticket->first_response_due_at->toDateTimeString())->toBe(now()->addMinutes(30)->toDateTimeString())
        ->and($ticket->resolution_due_at->toDateTimeString())->toBe(now()->addMinutes(240)->toDateTimeString())
        ->and($ticket->resolutionSlaStatus())->toBe(SlaStatus::OnTrack);
});

test('the most specific active sla policy wins', function () {
    $category = Category::factory()->create();
    SlaPolicy::factory()->create(['name' => 'Catch all']);
    SlaPolicy::factory()->create(['name' => 'High', 'priority' => TicketPriority::High]);
    SlaPolicy::factory()->create(['name' => 'Category', 'category_id' => $category->id]);
    SlaPolicy::factory()->create(['name' => 'Exact', 'priority' => TicketPriority::High, 'category_id' => $category->id]);
    SlaPolicy::factory()->create(['name' => 'Inactive exact', 'priority' => TicketPriority::High, 'category_id' => $category->id, 'is_active' => false]);

    $requester = User::factory()->create();

    expect(submitTicket($requester, ['priority' => 'high', 'category_id' => $category->id])->slaPolicy->name)->toBe('Exact')
        ->and(submitTicket($requester, ['priority' => 'low', 'category_id' => $category->id])->slaPolicy->name)->toBe('Category')
        ->and(submitTicket($requester, ['priority' => 'high'])->slaPolicy->name)->toBe('High')
        ->and(submitTicket($requester, ['priority' => 'low'])->slaPolicy->name)->toBe('Catch all');
});

test('tickets without a matching policy have no sla', function () {
    $ticket = submitTicket(User::factory()->create());

    expect($ticket->sla_policy_id)->toBeNull()
        ->and($ticket->resolutionSlaStatus())->toBeNull();
});

test('business hours targets skip nights and weekends', function () {
    $policy = SlaPolicy::factory()->businessHours('09:00', '17:00', [1, 2, 3, 4, 5])->make();
    $calculator = new BusinessHoursCalculator;

    // Friday 16:00 + 2 working hours = Monday 10:00.
    $friday = CarbonImmutable::parse('2026-10-02 16:00');
    expect($calculator->dueAt($friday, 120, $policy)->toDateTimeString())->toBe('2026-10-05 10:00:00');

    // Saturday morning starts counting on Monday at opening time.
    $saturday = CarbonImmutable::parse('2026-10-03 08:00');
    expect($calculator->dueAt($saturday, 60, $policy)->toDateTimeString())->toBe('2026-10-05 10:00:00');

    // Before opening on a weekday starts at opening time.
    $early = CarbonImmutable::parse('2026-10-06 07:00');
    expect($calculator->dueAt($early, 480, $policy)->toDateTimeString())->toBe('2026-10-06 17:00:00');
});

test('changing the priority recalculates the sla', function () {
    $this->freezeTime();
    SlaPolicy::factory()->create(['priority' => TicketPriority::Low, 'resolution_minutes' => 600]);
    SlaPolicy::factory()->create(['priority' => TicketPriority::Critical, 'first_response_minutes' => 15, 'resolution_minutes' => 60]);
    $ticket = submitTicket(User::factory()->create(), ['priority' => 'low']);

    $this->actingAs(User::factory()->agent()->create())
        ->patch(route('tickets.update', $ticket), ['priority' => TicketPriority::Critical->value]);

    expect($ticket->refresh()->resolution_due_at->toDateTimeString())->toBe(now()->addMinutes(60)->toDateTimeString());
});

test('putting a ticket on hold pauses the sla clock', function () {
    $this->travelTo(now()->startOfSecond());
    SlaPolicy::factory()->create(['resolution_minutes' => 120]);
    $ticket = submitTicket(User::factory()->create());
    $agent = User::factory()->agent()->create();
    $originalDue = $ticket->resolution_due_at;

    $this->travel(30)->minutes();
    $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['status' => TicketStatus::OnHold->value]);
    expect($ticket->refresh()->resolutionSlaStatus())->toBe(SlaStatus::Paused);

    $this->travel(5)->hours();
    $this->artisan('sla:check');
    expect($ticket->refresh()->resolution_breached_at)->toBeNull();

    $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['status' => TicketStatus::InProgress->value]);

    $ticket->refresh();
    expect($ticket->sla_paused_at)->toBeNull()
        ->and($ticket->resolution_due_at->equalTo($originalDue->addHours(5)))->toBeTrue()
        ->and($ticket->resolutionSlaStatus())->toBe(SlaStatus::OnTrack);
});

test('a public staff reply counts as the first response but an internal note does not', function () {
    SlaPolicy::factory()->create();
    $ticket = submitTicket(User::factory()->create());
    $agent = User::factory()->agent()->create();
    $addComment = app(AddTicketComment::class);

    $addComment->handle($ticket, $agent, 'Looking internally', isInternal: true);
    expect($ticket->refresh()->first_responded_at)->toBeNull();

    $addComment->handle($ticket, $agent, 'We are on it');
    expect($ticket->refresh()->first_responded_at)->not->toBeNull()
        ->and($ticket->firstResponseSlaStatus())->toBe(SlaStatus::Met);
});

test('the sla check flags breaches and escalates once', function () {
    Notification::fake();
    $supervisor = User::factory()->supervisor()->create();
    SlaPolicy::factory()->create(['first_response_minutes' => 10, 'resolution_minutes' => 60, 'escalate_to_id' => $supervisor->id]);
    $ticket = submitTicket(User::factory()->create(), ['priority' => 'medium']);

    $this->travel(15)->minutes();
    $this->artisan('sla:check')->assertSuccessful();

    $ticket->refresh();
    expect($ticket->first_response_breached_at)->not->toBeNull()
        ->and($ticket->resolution_breached_at)->toBeNull()
        ->and($ticket->escalation_level)->toBe(1)
        ->and($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->assignee->is($supervisor))->toBeTrue()
        ->and($ticket->escalations()->sole()->type)->toBe(EscalationType::Automatic);

    Notification::assertSentTo($supervisor, TicketEscalated::class);

    $this->artisan('sla:check');
    expect($ticket->refresh()->escalation_level)->toBe(1);

    $this->travel(2)->hours();
    $this->artisan('sla:check');
    expect($ticket->refresh()->resolution_breached_at)->not->toBeNull()
        ->and($ticket->escalation_level)->toBe(2)
        ->and($ticket->resolutionSlaStatus())->toBe(SlaStatus::Breached);
});

test('policies can flag breaches without escalating', function () {
    SlaPolicy::factory()->create(['first_response_minutes' => 10, 'escalate_on_breach' => false]);
    $ticket = submitTicket(User::factory()->create());

    $this->travel(15)->minutes();
    $this->artisan('sla:check');

    expect($ticket->refresh()->first_response_breached_at)->not->toBeNull()
        ->and($ticket->escalation_level)->toBe(0);
});

test('tickets can be filtered by sla state', function () {
    SlaPolicy::factory()->create(['first_response_minutes' => 60, 'resolution_minutes' => 100]);
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();

    $breached = submitTicket($requester, ['subject' => 'Old breached ticket']);
    $this->travel(80)->minutes();
    $atRisk = submitTicket($requester, ['subject' => 'Fresh ticket']);
    $this->travel(30)->minutes();
    $this->artisan('sla:check');

    $this->actingAs($agent)->get(route('tickets.index', ['sla' => 'breached']))
        ->assertSee($breached->subject)->assertDontSee($atRisk->subject);

    $this->travel(50)->minutes();
    $this->actingAs($agent)->get(route('tickets.index', ['sla' => 'at_risk']))
        ->assertSee($atRisk->subject)->assertDontSee($breached->subject);
});

test('sla policies can be managed', function () {
    $supervisor = User::factory()->supervisor()->create();

    $this->actingAs($supervisor)->get(route('sla-policies.create'))->assertOk();

    $this->actingAs($supervisor)->post(route('sla-policies.store'), [
        'name' => 'VIP',
        'priority' => 'critical',
        'first_response_minutes' => 10,
        'resolution_minutes' => 120,
        'business_hours_only' => '1',
        'business_hours_start' => '08:00',
        'business_hours_end' => '18:00',
        'business_days' => ['1', '2', '3', '4', '5', '6'],
        'escalate_on_breach' => '1',
        'escalate_to_id' => $supervisor->id,
        'is_active' => '1',
    ])->assertRedirect(route('sla-policies.index'));

    $policy = SlaPolicy::sole();
    expect($policy->business_days)->toBe([1, 2, 3, 4, 5, 6])
        ->and($policy->escalateTo->is($supervisor))->toBeTrue();

    $this->actingAs($supervisor)->get(route('sla-policies.edit', $policy))->assertOk()->assertSee('VIP');
    $this->actingAs($supervisor)->get(route('sla-policies.index'))->assertOk()->assertSee('VIP');
});

test('sla policy targets are validated', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('sla-policies.store'), [
        'name' => 'Broken',
        'first_response_minutes' => 120,
        'resolution_minutes' => 60,
        'business_hours_only' => '1',
        'business_hours_start' => '17:00',
        'business_hours_end' => '09:00',
        'escalate_to_id' => User::factory()->create()->id,
    ])->assertSessionHasErrors(['resolution_minutes', 'business_hours_end', 'business_days', 'escalate_to_id']);
});

test('agents cannot manage sla policies', function () {
    $this->actingAs(User::factory()->agent()->create())
        ->get(route('sla-policies.index'))
        ->assertForbidden();
});
