<?php

namespace App\Models;

use App\Enums\WorkflowTrigger;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'trigger', 'match_type', 'conditions', 'actions', 'position', 'stop_processing', 'is_active'])]
class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'trigger' => WorkflowTrigger::class,
            'conditions' => 'array',
            'actions' => 'array',
            'position' => 'integer',
            'stop_processing' => 'boolean',
            'is_active' => 'boolean',
            'run_count' => 'integer',
            'last_run_at' => 'datetime',
        ];
    }

    /**
     * Scope the query to the active workflows for a trigger, in execution order.
     */
    #[Scope]
    protected function runnableFor(Builder $query, WorkflowTrigger $trigger): void
    {
        $query->where('is_active', true)
            ->where('trigger', $trigger)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Record that the workflow ran.
     */
    public function recordRun(): void
    {
        $this->increment('run_count', 1, ['last_run_at' => now()]);
    }
}
