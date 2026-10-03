<?php

use App\Enums\Permission;
use App\Enums\Plan;
use App\Enums\WorkflowActionType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Notifications\TicketRoutedToTeam;
use App\Support\Tenancy;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

/**
 * Submit a ticket in the category as a new requester and return it.
 */
function submitTicketIn(Category $category, string $subject = 'Help please'): Ticket
{
    test()->actingAs(User::factory()->create())->post(route('tickets.store'), [
        'subject' => $subject, 'description' => 'Details', 'priority' => 'medium', 'category_id' => $category->id,
    ]);

    return Ticket::latest('id')->firstOrFail();
}

test('new tickets are routed to the team of their category and the team is notified', function () {
    Notification::fake();
    $member = User::factory()->agent()->create();
    $outsider = User::factory()->agent()->create();
    $team = Team::factory()->create(['name' => 'Network']);
    $team->members()->attach($member);
    $category = Category::factory()->create(['default_team_id' => $team->id]);

    $ticket = submitTicketIn($category);

    expect($ticket->team->is($team))->toBeTrue()
        ->and($ticket->activities()->where('type', 'team_changed')->exists())->toBeTrue();
    Notification::assertSentTo($member, TicketRoutedToTeam::class);
    Notification::assertNotSentTo($outsider, TicketRoutedToTeam::class);
});

test('organizations without the teams feature do not route tickets', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $category = Category::factory()->create(['default_team_id' => Team::factory()->create()->id]);

    expect(submitTicketIn($category)->team_id)->toBeNull();

    $this->actingAs(User::factory()->admin()->create())->get(route('teams.index'))->assertForbidden();
});

test('supervisors can create a team with members and routed categories', function () {
    $agents = User::factory()->agent()->count(2)->create();
    $categories = Category::factory()->count(2)->create();

    $this->actingAs(User::factory()->supervisor()->create())->post(route('teams.store'), [
        'name' => 'Billing', 'color' => 'green',
        'members' => $agents->pluck('id')->all(),
        'categories' => [$categories[0]->id],
    ])->assertRedirect(route('teams.index'));

    $team = Team::firstWhere('name', 'Billing');
    expect($team->members->pluck('id')->sort()->values()->all())->toBe($agents->pluck('id')->sort()->values()->all())
        ->and($categories[0]->refresh()->default_team_id)->toBe($team->id)
        ->and($categories[1]->refresh()->default_team_id)->toBeNull();
});

test('updating a team syncs its members and stops routing removed categories', function () {
    $team = Team::factory()->create();
    [$stays, $leaves] = User::factory()->agent()->count(2)->create();
    $team->members()->attach([$stays->id, $leaves->id]);
    $category = Category::factory()->create(['default_team_id' => $team->id]);

    $this->actingAs(User::factory()->admin()->create())->patch(route('teams.update', $team), [
        'name' => $team->name, 'color' => 'red', 'members' => [$stays->id], 'categories' => [],
    ])->assertRedirect(route('teams.index'));

    expect($team->members()->pluck('users.id')->all())->toBe([$stays->id])
        ->and($category->refresh()->default_team_id)->toBeNull();
});

test('only support agents can be team members', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('teams.store'), [
        'name' => 'Mixed', 'color' => 'blue', 'members' => [User::factory()->create()->id],
    ])->assertSessionHasErrors('members.0');
});

test('agents cannot manage teams', function () {
    $this->actingAs(User::factory()->agent()->create())->get(route('teams.index'))->assertForbidden();
});

test('team members can work tickets of their team without seeing every ticket', function () {
    $agent = User::factory()->withPermissions([Permission::TicketsWork, Permission::TicketsManage])->create();
    $team = Team::factory()->create();
    $team->members()->attach($agent);
    $teamTicket = Ticket::factory()->create(['team_id' => $team->id]);
    $otherTicket = Ticket::factory()->create();

    $this->actingAs($agent)->get(route('tickets.index'))
        ->assertSee($teamTicket->subject)
        ->assertDontSee($otherTicket->subject);
    $this->actingAs($agent)->get(route('tickets.show', $teamTicket))->assertOk();
    $this->actingAs($agent)->get(route('tickets.show', $otherTicket))->assertForbidden();
});

test('the ticket list can be filtered by team', function () {
    $agent = User::factory()->agent()->create();
    $mine = Team::factory()->create();
    $mine->members()->attach($agent);
    $other = Team::factory()->create();
    $inMyTeam = Ticket::factory()->create(['team_id' => $mine->id, 'subject' => 'Mine to fix']);
    $inOtherTeam = Ticket::factory()->create(['team_id' => $other->id, 'subject' => 'Theirs to fix']);
    $noTeam = Ticket::factory()->create(['subject' => 'Nobody yet']);

    $this->actingAs($agent)->get(route('tickets.index', ['team' => 'mine']))
        ->assertSee($inMyTeam->subject)->assertDontSee($inOtherTeam->subject)->assertDontSee($noTeam->subject);
    $this->actingAs($agent)->get(route('tickets.index', ['team' => 'none']))
        ->assertSee($noTeam->subject)->assertDontSee($inMyTeam->subject);
    $this->actingAs($agent)->get(route('tickets.index', ['team' => $other->id]))
        ->assertSee($inOtherTeam->subject)->assertDontSee($inMyTeam->subject);
});

test('agents can move a ticket to another team', function () {
    $ticket = Ticket::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs(User::factory()->agent()->create())
        ->patch(route('tickets.update', $ticket), ['team_id' => $team->id])
        ->assertSessionHasNoErrors();

    expect($ticket->refresh()->team_id)->toBe($team->id);
});

test('teams of another organization cannot be used', function () {
    $foreignTeam = app(Tenancy::class)->runAs(Organization::factory()->create(), fn () => Team::factory()->create());

    $this->actingAs(User::factory()->agent()->create())
        ->patch(route('tickets.update', Ticket::factory()->create()), ['team_id' => $foreignTeam->id])
        ->assertSessionHasErrors('team_id');
});

test('requesters cannot change the team of their ticket', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)
        ->patch(route('tickets.update', $ticket), ['team_id' => Team::factory()->create()->id])
        ->assertForbidden();
});

test('workflows can route to a team and assign the least busy member of it', function () {
    $team = Team::factory()->create();
    $busyMember = User::factory()->agent()->create();
    $freeMember = User::factory()->agent()->create();
    $freeOutsider = User::factory()->agent()->create();
    $team->members()->attach([$busyMember->id, $freeMember->id]);
    Ticket::factory()->count(2)->assigned($busyMember)->create();
    Ticket::factory()->assigned($freeMember)->create();

    Workflow::factory()->create(['actions' => [
        ['type' => WorkflowActionType::AssignToTeam->value, 'value' => (string) $team->id],
        ['type' => WorkflowActionType::AssignLeastBusy->value, 'value' => null],
    ]]);

    $ticket = submitTicketIn(Category::factory()->create());

    expect($ticket->team->is($team))->toBeTrue()
        ->and($ticket->assignee->is($freeMember))->toBeTrue()
        ->and($ticket->assignee->is($freeOutsider))->toBeFalse();
});

test('the dashboard shows unassigned tickets waiting in my teams', function () {
    $agent = User::factory()->agent()->create();
    $team = Team::factory()->create();
    $team->members()->attach($agent);
    $waiting = Ticket::factory()->create(['team_id' => $team->id, 'subject' => 'Waiting in team']);

    $this->actingAs($agent)->get(route('dashboard'))->assertSee('Team queue')->assertSee($waiting->subject);
});

test('reports and the api include teams', function () {
    $team = Team::factory()->create(['name' => 'Service Desk']);
    Ticket::factory()->count(3)->create(['team_id' => $team->id]);
    Sanctum::actingAs(User::factory()->supervisor()->create());

    $this->getJson(route('api.v1.reports.summary'))
        ->assertJsonPath('data.teams.0.name', 'Service Desk')
        ->assertJsonPath('data.teams.0.open', 3);
    $this->getJson(route('api.v1.teams.index'))->assertJsonPath('data.0.name', 'Service Desk');
    $this->getJson(route('api.v1.tickets.index'))->assertJsonPath('data.0.team.name', 'Service Desk');
});
