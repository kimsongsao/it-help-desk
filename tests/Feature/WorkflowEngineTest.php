<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;

function openTicket(array $attributes = [], ?User $requester = null): Ticket
{
    test()->actingAs($requester ?? User::factory()->create())->post(route('tickets.store'), [
        'subject' => 'Something is wrong',
        'description' => 'Details here.',
        'priority' => TicketPriority::Low->value,
        'category_id' => Category::factory()->create()->id,
        ...$attributes,
    ]);

    return Ticket::latest('id')->firstOrFail();
}

test('a creation workflow sets the priority when its condition matches', function () {
    $workflow = Workflow::factory()->create([
        'conditions' => [['field' => 'subject', 'operator' => 'contains', 'value' => 'OUTAGE']],
        'actions' => [['type' => WorkflowActionType::SetPriority->value, 'value' => 'critical']],
    ]);

    $matching = openTicket(['subject' => 'Email outage in Paris']);
    $other = openTicket(['subject' => 'Mouse broken']);

    expect($matching->priority)->toBe(TicketPriority::Critical)
        ->and($other->priority)->toBe(TicketPriority::Low)
        ->and($workflow->refresh()->run_count)->toBe(1)
        ->and($matching->activities()->where('type', 'workflow_applied')->exists())->toBeTrue();
});

test('all conditions must match unless the workflow matches any', function () {
    $conditions = [
        ['field' => 'priority', 'operator' => 'equals', 'value' => 'high'],
        ['field' => 'requester_department', 'operator' => 'equals', 'value' => 'finance'],
    ];
    $requester = User::factory()->inDepartment(Department::factory()->create(['name' => 'Sales']))->create();

    Workflow::factory()->create(['name' => 'All', 'match_type' => 'all', 'conditions' => $conditions, 'actions' => [['type' => 'add_internal_note', 'value' => 'all matched']]]);
    Workflow::factory()->create(['name' => 'Any', 'match_type' => 'any', 'conditions' => $conditions, 'actions' => [['type' => 'add_internal_note', 'value' => 'any matched']]]);

    $ticket = openTicket(['priority' => 'high'], $requester);

    expect($ticket->comments()->pluck('body')->all())->toBe(['any matched']);
});

test('workflows can assign tickets to the least busy agent', function () {
    $busy = User::factory()->agent()->create();
    $free = User::factory()->agent()->create();
    User::factory()->agent()->inactive()->create();
    Ticket::factory()->count(2)->assigned($busy)->create();

    Workflow::factory()->create(['actions' => [['type' => WorkflowActionType::AssignLeastBusy->value, 'value' => null]]]);

    $ticket = openTicket();

    expect($ticket->assignee->is($free))->toBeTrue();
});

test('workflow notes are written by automation and hidden from the requester', function () {
    Workflow::factory()->create(['actions' => [['type' => WorkflowActionType::AddInternalNote->value, 'value' => 'Check monitoring']]]);
    $requester = User::factory()->create();

    $ticket = openTicket(requester: $requester);
    $note = $ticket->comments()->sole();

    expect($note->is_internal)->toBeTrue()
        ->and($note->author)->toBeNull();

    $this->actingAs($requester)->get(route('tickets.show', $ticket))->assertDontSee('Check monitoring');
    $this->actingAs(User::factory()->agent()->create())->get(route('tickets.show', $ticket))->assertSee('Check monitoring')->assertSee('Automation');
});

test('update workflows can react to a field change', function () {
    Workflow::factory()->create([
        'trigger' => WorkflowTrigger::TicketUpdated,
        'conditions' => [
            ['field' => 'priority', 'operator' => 'changed', 'value' => null],
            ['field' => 'priority', 'operator' => 'equals', 'value' => 'critical'],
        ],
        'actions' => [['type' => WorkflowActionType::Escalate->value, 'value' => 'Critical tickets go to level 1']],
    ]);
    $ticket = openTicket();
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['status' => TicketStatus::InProgress->value]);
    expect($ticket->refresh()->escalation_level)->toBe(0);

    $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['priority' => TicketPriority::Critical->value]);
    expect($ticket->refresh()->escalation_level)->toBe(1);
});

test('changes made by workflows do not trigger other workflows', function () {
    Workflow::factory()->create(['actions' => [['type' => 'set_priority', 'value' => 'high']]]);
    $updateWorkflow = Workflow::factory()->create([
        'trigger' => WorkflowTrigger::TicketUpdated,
        'actions' => [['type' => 'add_internal_note', 'value' => 'should not run']],
    ]);

    $ticket = openTicket();

    expect($ticket->priority)->toBe(TicketPriority::High)
        ->and($updateWorkflow->refresh()->run_count)->toBe(0);
});

test('a workflow can stop later workflows from running', function () {
    Workflow::factory()->create(['position' => 1, 'stop_processing' => true, 'actions' => [['type' => 'set_priority', 'value' => 'medium']]]);
    Workflow::factory()->create(['position' => 2, 'actions' => [['type' => 'set_priority', 'value' => 'critical']]]);
    Workflow::factory()->create(['position' => 3, 'is_active' => false, 'actions' => [['type' => 'set_priority', 'value' => 'high']]]);

    expect(openTicket()->priority)->toBe(TicketPriority::Medium);
});

test('workflow status changes respect the allowed transitions', function () {
    Workflow::factory()->create([
        'trigger' => WorkflowTrigger::CommentAdded,
        'actions' => [['type' => 'set_status', 'value' => 'in_progress']],
    ]);
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->status(TicketStatus::Closed)->create();

    $this->actingAs(User::factory()->agent()->create())
        ->post(route('tickets.comments.store', $ticket), ['body' => 'Closing remark']);

    expect($ticket->refresh()->status)->toBe(TicketStatus::Closed);
});
