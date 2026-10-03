<?php

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Reports\HelpDeskReport;
use Carbon\CarbonImmutable;

test('the report summarises tickets in the period', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 12:00'));
    $agent = User::factory()->agent()->create();
    $category = Category::factory()->create(['name' => 'Network']);

    $met = Ticket::factory()->assigned($agent)->create(['category_id' => $category->id, 'created_at' => now()->subHours(5)]);
    $met->forceFill(['resolution_due_at' => now()->addHour(), 'first_response_due_at' => now()->subHours(4), 'first_responded_at' => now()->subHours(4)->subMinutes(30)])->save();
    $met->update(['status' => TicketStatus::Resolved]);

    $late = Ticket::factory()->assigned($agent)->create(['created_at' => now()->subDays(2)]);
    $late->forceFill(['resolution_due_at' => now()->subDay()])->save();
    $late->update(['status' => TicketStatus::Resolved]);

    Ticket::factory()->create(['created_at' => now()->subDays(60)]);

    $report = new HelpDeskReport(now()->subDays(6)->startOfDay()->toImmutable(), now()->endOfDay()->toImmutable(), $this->organization->id);
    $summary = $report->summary();

    expect($summary['created'])->toBe(2)
        ->and($summary['resolved'])->toBe(2)
        ->and($summary['backlog'])->toBe(1)
        ->and($summary['resolution_compliance'])->toBe(50.0)
        ->and($summary['first_response_compliance'])->toBe(100.0)
        ->and($summary['avg_first_response_minutes'])->toBe(30.0);

    $volume = $report->dailyVolume();
    expect($volume['labels'])->toHaveCount(7)
        ->and(array_sum($volume['created']))->toBe(2)
        ->and(end($volume['resolved']))->toBe(2);

    expect($report->byCategory()->firstWhere('name', 'Network')->total)->toBe(1);

    $agentRow = $report->agentPerformance()->firstWhere('id', $agent->id);
    expect($agentRow->resolved_count)->toBe(2)
        ->and($agentRow->breached_count)->toBe(1);
});

test('the report page renders for users who can view reports', function () {
    Ticket::factory()->count(3)->create();

    $this->actingAs(User::factory()->supervisor()->create())
        ->get(route('reports.index', ['from' => now()->subWeek()->toDateString(), 'to' => now()->toDateString()]))
        ->assertOk()
        ->assertSee('Ticket volume')
        ->assertSee('Agent performance');
});

test('the report period is validated', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['from' => '2026-10-10', 'to' => '2026-10-01']))
        ->assertSessionHasErrors('to');
});

test('tickets can be exported as csv', function () {
    $ticket = Ticket::factory()->create(['subject' => 'Exported ticket']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.export'));

    $response->assertOk()->assertDownload();
    expect($response->streamedContent())
        ->toContain('Reference,Subject')
        ->toContain($ticket->reference)
        ->toContain('Exported ticket');
});

test('agents cannot view reports', function () {
    $this->actingAs(User::factory()->agent()->create())->get(route('reports.index'))->assertForbidden();
    $this->actingAs(User::factory()->agent()->create())->get(route('reports.export'))->assertForbidden();
});
