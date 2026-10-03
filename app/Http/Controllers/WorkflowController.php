<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Category;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    /**
     * List the workflows grouped by trigger.
     */
    public function index(): View
    {
        return view('workflows.index', [
            'workflows' => Workflow::orderBy('trigger')->orderBy('position')->orderBy('id')->get()->groupBy(fn (Workflow $workflow) => $workflow->trigger->label()),
        ]);
    }

    /**
     * Show the form for creating a workflow.
     */
    public function create(): View
    {
        return $this->form(new Workflow([
            'match_type' => 'all',
            'conditions' => [],
            'actions' => [],
            'is_active' => true,
        ]));
    }

    /**
     * Store a new workflow.
     */
    public function store(StoreWorkflowRequest $request): RedirectResponse
    {
        Workflow::create($request->workflowAttributes());

        return redirect()->route('workflows.index')->with('status', __('Workflow created.'));
    }

    /**
     * Show the form for editing a workflow.
     */
    public function edit(Workflow $workflow): View
    {
        return $this->form($workflow);
    }

    /**
     * Update the workflow.
     */
    public function update(UpdateWorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        $workflow->update($request->workflowAttributes());

        return redirect()->route('workflows.index')->with('status', __('Workflow updated.'));
    }

    /**
     * Delete the workflow.
     */
    public function destroy(Workflow $workflow): RedirectResponse
    {
        $workflow->delete();

        return redirect()->route('workflows.index')->with('status', __('Workflow deleted.'));
    }

    /**
     * Render the workflow builder.
     */
    private function form(Workflow $workflow): View
    {
        $option = fn (string|int $value, string $label): array => ['value' => (string) $value, 'label' => $label];

        return view('workflows.form', [
            'workflow' => $workflow,
            'valueOptions' => [
                'status' => array_map(fn (TicketStatus $status) => $option($status->value, $status->label()), TicketStatus::cases()),
                'priority' => array_map(fn (TicketPriority $priority) => $option($priority->value, $priority->label()), TicketPriority::cases()),
                'category' => Category::orderBy('name')->get()->map(fn (Category $category) => $option($category->id, $category->name))->all(),
                'user' => User::assignable()->orderBy('name')->get()->map(fn (User $user) => $option($user->id, $user->name))->all(),
            ],
        ]);
    }
}
