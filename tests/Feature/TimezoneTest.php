<?php

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Reports\HelpDeskReport;
use Carbon\CarbonImmutable;

test('dates are stored in utc and shown in the organization time zone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 02:00:00', 'UTC'));
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    expect($ticket->getRawOriginal('created_at'))->toStartWith('2026-10-03 02:00:00');

    $this->actingAs($agent)->get(route('tickets.show', $ticket))->assertSee('3 Oct 2026, 09:00');
});

test('people can see dates in their own time zone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 02:00:00', 'UTC'));
    $ticket = Ticket::factory()->create();

    $this->actingAs(User::factory()->agent()->create(['timezone' => 'Asia/Tokyo']))
        ->get(route('tickets.show', $ticket))
        ->assertSee('3 Oct 2026, 11:00');
});

test('users can choose a time zone in their profile', function () {
    $user = User::factory()->create();
    $profile = ['name' => $user->name, 'email' => $user->email, 'locale' => 'en'];

    $this->actingAs($user)->put(route('profile.update'), [...$profile, 'timezone' => 'Europe/Paris'])->assertSessionHasNoErrors();
    expect($user->refresh()->timezone)->toBe('Europe/Paris');

    $this->actingAs($user)->put(route('profile.update'), [...$profile, 'timezone' => ''])->assertSessionHasNoErrors();
    expect($user->refresh()->timezone)->toBeNull();

    $this->actingAs($user)->put(route('profile.update'), [...$profile, 'timezone' => 'Mars/Olympus'])->assertSessionHasErrors('timezone');
});

test('a new company uses the time zone of the browser that signed it up', function () {
    $this->post(route('register'), [
        'company_name' => 'Bangkok Traders', 'template' => 'general', 'name' => 'Somchai',
        'email' => 'somchai@bangkok.test', 'timezone' => 'Asia/Bangkok',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ]);

    expect(User::withoutGlobalScopes()->firstWhere('email', 'somchai@bangkok.test')->organization->timezone)->toBe('Asia/Bangkok');
});

test('people joining from another time zone keep their own', function () {
    $this->organization->update(['allow_self_registration' => true]);

    $this->post(route('join', $this->organization), [
        'name' => 'Remote', 'email' => 'remote@example.com', 'timezone' => 'Australia/Sydney',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ]);
    $this->post(route('logout'));
    $this->post(route('join', $this->organization), [
        'name' => 'Local', 'email' => 'local@example.com', 'timezone' => 'Asia/Phnom_Penh',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ]);

    expect(User::firstWhere('email', 'remote@example.com')->timezone)->toBe('Australia/Sydney')
        ->and(User::firstWhere('email', 'local@example.com')->timezone)->toBeNull();
});

test('sla business hours are counted in the organization time zone', function () {
    // Friday 16:30 in Phnom Penh (09:30 UTC): 30 minutes left today, the other 30 on Monday from 09:00.
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:00', 'UTC'));
    SlaPolicy::factory()->businessHours('09:00', '17:00', [1, 2, 3, 4, 5])->create(['first_response_minutes' => 60, 'resolution_minutes' => 60]);

    $this->actingAs(User::factory()->create())->post(route('tickets.store'), [
        'subject' => 'After hours', 'description' => 'x', 'priority' => TicketPriority::Low->value,
        'category_id' => Category::factory()->create()->id,
    ]);

    expect(Ticket::sole()->resolution_due_at->utc()->toDateTimeString())->toBe('2026-10-05 02:30:00');
});

test('reports count tickets on the local calendar day', function () {
    // 20:00 UTC on 2 October is 03:00 on 3 October in Phnom Penh.
    $this->travelTo(CarbonImmutable::parse('2026-10-02 20:00:00', 'UTC'));
    Ticket::factory()->create();

    $report = new HelpDeskReport(
        CarbonImmutable::parse('2026-10-02', 'Asia/Phnom_Penh')->startOfDay()->utc(),
        CarbonImmutable::parse('2026-10-03', 'Asia/Phnom_Penh')->endOfDay()->utc(),
        $this->organization->id,
        'Asia/Phnom_Penh',
    );

    expect($report->dailyVolume()['labels'])->toBe(['2026-10-02', '2026-10-03'])
        ->and($report->dailyVolume()['created'])->toBe([0, 1]);
});

test('report periods are whole local days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 20:00:00', 'UTC'));
    Ticket::factory()->create();

    $this->actingAs(User::factory()->supervisor()->create())
        ->getJson(route('api.v1.reports.summary', ['from' => '2026-10-03', 'to' => '2026-10-03']))
        ->assertJsonPath('data.period.timezone', 'Asia/Phnom_Penh')
        ->assertJsonPath('data.summary.created', 1);
});
