<?php

namespace App\Services\Reports;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HelpDeskReport
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly int $organizationId,
        public readonly string $timezone = 'UTC',
    ) {}

    /**
     * Get the headline metrics for the period.
     *
     * @return array{created: int, resolved: int, backlog: int, escalations: int, avg_first_response_minutes: ?float, avg_resolution_minutes: ?float, first_response_compliance: ?float, resolution_compliance: ?float}
     */
    public function summary(): array
    {
        $created = $this->createdInPeriod();
        $resolved = $this->resolvedInPeriod();

        $firstResponse = $this->createdInPeriod()
            ->whereNotNull('first_response_due_at')
            ->where(fn (Builder $query) => $query->whereNotNull('first_responded_at')->orWhereNotNull('first_response_breached_at'))
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where first_responded_at <= first_response_due_at and first_response_breached_at is null) as met')
            ->first();

        $resolution = $this->resolvedInPeriod()
            ->whereNotNull('resolution_due_at')
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where resolved_at <= resolution_due_at and resolution_breached_at is null) as met')
            ->first();

        return [
            'created' => $created->count(),
            'resolved' => $resolved->count(),
            'backlog' => Ticket::active()->where('organization_id', $this->organizationId)->count(),
            'escalations' => TicketEscalation::whereBetween('created_at', [$this->from, $this->to])
                ->whereHas('ticket', fn (Builder $query) => $query->where('organization_id', $this->organizationId))
                ->count(),
            'avg_first_response_minutes' => $this->averageMinutes($this->createdInPeriod()->whereNotNull('first_responded_at'), 'first_responded_at'),
            'avg_resolution_minutes' => $this->averageMinutes($this->resolvedInPeriod(), 'resolved_at'),
            'first_response_compliance' => $this->percentage((int) $firstResponse->met, (int) $firstResponse->total),
            'resolution_compliance' => $this->percentage((int) $resolution->met, (int) $resolution->total),
        ];
    }

    /**
     * Get the number of tickets created and resolved on each day of the period.
     *
     * @return array{labels: list<string>, created: list<int>, resolved: list<int>}
     */
    public function dailyVolume(): array
    {
        // Timestamps are stored in UTC; count them on the local calendar day of the report's time zone.
        $rows = DB::select(<<<'SQL'
            select day::date as day,
                (select count(*) from tickets where organization_id = ?
                    and (created_at at time zone 'UTC' at time zone ?) >= day
                    and (created_at at time zone 'UTC' at time zone ?) < day + interval '1 day') as created,
                (select count(*) from tickets where organization_id = ?
                    and (resolved_at at time zone 'UTC' at time zone ?) >= day
                    and (resolved_at at time zone 'UTC' at time zone ?) < day + interval '1 day') as resolved
            from generate_series(?::date, ?::date, interval '1 day') as day
            order by day
            SQL, [
            $this->organizationId, $this->timezone, $this->timezone,
            $this->organizationId, $this->timezone, $this->timezone,
            $this->from->setTimezone($this->timezone)->toDateString(),
            $this->to->setTimezone($this->timezone)->toDateString(),
        ]);

        return [
            'labels' => array_map(fn (object $row): string => $row->day, $rows),
            'created' => array_map(fn (object $row): int => (int) $row->created, $rows),
            'resolved' => array_map(fn (object $row): int => (int) $row->resolved, $rows),
        ];
    }

    /**
     * Get the number of tickets created in the period for each priority.
     *
     * @return array<string, int>
     */
    public function byPriority(): array
    {
        $counts = $this->createdInPeriod()->toBase()->selectRaw('priority, count(*) as total')->groupBy('priority')->pluck('total', 'priority');

        return collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $priority): array => [$priority->label() => (int) ($counts[$priority->value] ?? 0)])
            ->all();
    }

    /**
     * Get the current number of tickets created in the period for each status.
     *
     * @return array<string, int>
     */
    public function byStatus(): array
    {
        $counts = $this->createdInPeriod()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status): array => [$status->label() => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Get the number of tickets created in the period for each category, busiest first.
     *
     * @return Collection<int, object{name: string, total: int}>
     */
    public function byCategory(): Collection
    {
        return $this->createdInPeriod()
            ->toBase()
            ->leftJoin('categories', 'categories.id', '=', 'tickets.category_id')
            ->selectRaw("coalesce(categories.name, 'Uncategorized') as name, count(*) as total")
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Get the workload and performance of every support agent.
     *
     * @return Collection<int, User>
     */
    public function agentPerformance(): Collection
    {
        $averageHours = $this->resolvedInPeriod()
            ->whereNotNull('assignee_id')
            ->toBase()
            ->selectRaw('assignee_id, avg(extract(epoch from (resolved_at - created_at))) / 3600 as hours')
            ->groupBy('assignee_id')
            ->pluck('hours', 'assignee_id');

        return User::assignable()
            ->where('organization_id', $this->organizationId)
            ->withOpenTicketCount()
            ->withCount([
                'assignedTickets as assigned_count' => fn (Builder $query) => $query->whereBetween('created_at', [$this->from, $this->to]),
                'assignedTickets as resolved_count' => fn (Builder $query) => $query->whereBetween('resolved_at', [$this->from, $this->to]),
                'assignedTickets as breached_count' => fn (Builder $query) => $query
                    ->whereBetween('created_at', [$this->from, $this->to])
                    ->where(fn (Builder $query) => $query
                        ->whereNotNull('first_response_breached_at')
                        ->orWhereNotNull('resolution_breached_at')
                        ->orWhereColumn('first_responded_at', '>', 'first_response_due_at')
                        ->orWhereColumn('resolved_at', '>', 'resolution_due_at')),
            ])
            ->orderBy('name')
            ->get()
            ->each(fn (User $agent) => $agent->setAttribute('avg_resolution_hours', isset($averageHours[$agent->id]) ? round((float) $averageHours[$agent->id], 1) : null));
    }

    /**
     * Get the workload and performance of every support team.
     *
     * @return Collection<int, Team>
     */
    public function teamPerformance(): Collection
    {
        $averageHours = $this->resolvedInPeriod()
            ->whereNotNull('team_id')
            ->toBase()
            ->selectRaw('team_id, avg(extract(epoch from (resolved_at - created_at))) / 3600 as hours')
            ->groupBy('team_id')
            ->pluck('hours', 'team_id');

        return Team::query()
            ->where('organization_id', $this->organizationId)
            ->withCount([
                'members',
                'tickets as open_count' => fn (Builder $query) => $query->active(),
                'tickets as created_count' => fn (Builder $query) => $query->whereBetween('created_at', [$this->from, $this->to]),
                'tickets as resolved_count' => fn (Builder $query) => $query->whereBetween('resolved_at', [$this->from, $this->to]),
                'tickets as breached_count' => fn (Builder $query) => $query
                    ->whereBetween('created_at', [$this->from, $this->to])
                    ->where(fn (Builder $query) => $query
                        ->whereNotNull('first_response_breached_at')
                        ->orWhereNotNull('resolution_breached_at')
                        ->orWhereColumn('first_responded_at', '>', 'first_response_due_at')
                        ->orWhereColumn('resolved_at', '>', 'resolution_due_at')),
            ])
            ->orderBy('name')
            ->get()
            ->each(fn (Team $team) => $team->setAttribute('avg_resolution_hours', isset($averageHours[$team->id]) ? round((float) $averageHours[$team->id], 1) : null));
    }

    /**
     * Get the tickets created in the period with the relations needed for an export.
     *
     * @return Builder<Ticket>
     */
    public function ticketsForExport(): Builder
    {
        return $this->createdInPeriod()->with(['requester.department', 'assignee', 'category', 'team'])->orderBy('id');
    }

    /**
     * @return Builder<Ticket>
     */
    private function createdInPeriod(): Builder
    {
        return Ticket::query()
            ->where('tickets.organization_id', $this->organizationId)
            ->whereBetween('tickets.created_at', [$this->from, $this->to]);
    }

    /**
     * @return Builder<Ticket>
     */
    private function resolvedInPeriod(): Builder
    {
        return Ticket::query()
            ->where('tickets.organization_id', $this->organizationId)
            ->whereBetween('resolved_at', [$this->from, $this->to]);
    }

    /**
     * Get the average number of minutes between creation and the given timestamp column.
     *
     * @param  Builder<Ticket>  $query
     */
    private function averageMinutes(Builder $query, string $column): ?float
    {
        $minutes = $query->toBase()->selectRaw("avg(extract(epoch from ({$column} - created_at))) / 60 as minutes")->value('minutes');

        return $minutes === null ? null : round((float) $minutes, 1);
    }

    /**
     * Express a ratio as a percentage, or null when there is nothing to measure.
     */
    private function percentage(int $part, int $total): ?float
    {
        return $total === 0 ? null : round($part / $total * 100, 1);
    }
}
