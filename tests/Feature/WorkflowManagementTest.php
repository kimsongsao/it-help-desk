<?php

use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\User;
use App\Models\Workflow;

test('supervisors can create a workflow', function () {
    $category = Category::factory()->create();
    $agent = User::factory()->agent()->create();

    $this->actingAs(User::factory()->supervisor()->create())
        ->post(route('workflows.store'), [
            'name' => 'Route network',
            'trigger' => WorkflowTrigger::TicketCreated->value,
            'match_type' => 'all',
            'conditions' => [
                ['field' => 'category_id', 'operator' => 'equals', 'value' => (string) $category->id],
                ['field' => 'subject', 'operator' => 'is_not_empty', 'value' => 'ignored'],
            ],
            'actions' => [
                ['type' => 'assign_to', 'value' => (string) $agent->id],
                ['type' => 'assign_least_busy', 'value' => 'ignored'],
            ],
            'position' => '5',
            'is_active' => '1',
        ])
        ->assertRedirect(route('workflows.index'));

    $workflow = Workflow::sole();
    expect($workflow->trigger)->toBe(WorkflowTrigger::TicketCreated)
        ->and($workflow->position)->toBe(5)
        ->and($workflow->conditions[1]['value'])->toBeNull()
        ->and($workflow->actions)->toBe([
            ['type' => 'assign_to', 'value' => (string) $agent->id],
            ['type' => 'assign_least_busy', 'value' => null],
        ]);
});

test('workflow conditions and actions are validated', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('workflows.store'), [
            'name' => 'Broken',
            'trigger' => 'ticket_created',
            'match_type' => 'all',
            'conditions' => [['field' => 'subject', 'operator' => 'contains', 'value' => '']],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
                ['type' => 'assign_to', 'value' => (string) User::factory()->create()->id],
            ],
        ])
        ->assertSessionHasErrors(['conditions.0.value', 'actions.0.value', 'actions.1.value']);

    expect(Workflow::count())->toBe(0);
});

test('a workflow needs at least one action', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('workflows.store'), ['name' => 'Empty', 'trigger' => 'ticket_created', 'match_type' => 'all'])
        ->assertSessionHasErrors('actions');
});

test('workflow pages render', function () {
    $admin = User::factory()->admin()->create();
    $workflow = Workflow::factory()->create(['actions' => [['type' => 'set_priority', 'value' => 'high']]]);

    $this->actingAs($admin)->get(route('workflows.index'))->assertOk()->assertSee($workflow->name);
    $this->actingAs($admin)->get(route('workflows.create'))->assertOk();
    $this->actingAs($admin)->get(route('workflows.edit', $workflow))->assertOk()->assertSee('set_priority');
});

test('workflows can be updated and deleted', function () {
    $admin = User::factory()->admin()->create();
    $workflow = Workflow::factory()->create(['actions' => [['type' => 'set_priority', 'value' => 'high']]]);

    $this->actingAs($admin)->patch(route('workflows.update', $workflow), [
        'name' => 'Renamed',
        'trigger' => 'sla_breached',
        'match_type' => 'any',
        'actions' => [['type' => 'escalate', 'value' => 'Breached']],
        'is_active' => '0',
    ])->assertRedirect(route('workflows.index'));

    expect($workflow->refresh()->name)->toBe('Renamed')
        ->and($workflow->trigger)->toBe(WorkflowTrigger::SlaBreached)
        ->and($workflow->is_active)->toBeFalse();

    $this->actingAs($admin)->delete(route('workflows.destroy', $workflow))->assertRedirect(route('workflows.index'));
    $this->assertModelMissing($workflow);
});

test('agents cannot manage workflows', function () {
    $this->actingAs(User::factory()->agent()->create())->get(route('workflows.index'))->assertForbidden();
});
