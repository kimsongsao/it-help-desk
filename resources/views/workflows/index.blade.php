<x-layouts.app :title="__('Workflows')">
    <x-slot:header>
        <x-page-header :pretitle="__('Configuration')" :title="__('Workflows')">
            <div class="text-secondary mt-1">{{ __('Automation rules run in order when their trigger fires. Changes made by a workflow do not trigger other workflows.') }}</div>
            <x-slot:actions>
                <a href="{{ route('workflows.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add workflow') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @forelse ($workflows as $trigger => $group)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-bolt icon me-1 text-yellow"></i> {{ __('When: :trigger', ['trigger' => $trigger]) }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">{{ __('Order') }}</th>
                            <th>{{ __('Workflow') }}</th>
                            <th>{{ __('Conditions') }}</th>
                            <th>{{ __('Actions') }}</th>
                            <th>{{ __('Runs') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group as $workflow)
                            <tr @class(['opacity-50' => ! $workflow->is_active])>
                                <td class="text-secondary">{{ $workflow->position }}</td>
                                <td>
                                    <div class="fw-medium">
                                        {{ $workflow->name }}
                                        @unless ($workflow->is_active)<span class="badge bg-secondary-lt ms-1">{{ __('Inactive') }}</span>@endunless
                                        @if ($workflow->stop_processing)<span class="badge bg-orange-lt ms-1" title="{{ __('Later workflows are skipped when this one matches') }}">{{ __('Stops') }}</span>@endif
                                    </div>
                                    <div class="text-secondary small">{{ $workflow->description }}</div>
                                </td>
                                <td class="text-secondary small">
                                    @forelse ($workflow->conditions as $condition)
                                        <div>{{ App\Enums\WorkflowField::from($condition['field'])->label() }} {{ App\Enums\WorkflowOperator::from($condition['operator'])->label() }} <strong class="text-body">{{ $condition['value'] }}</strong></div>
                                        @if (! $loop->last)<div class="text-uppercase" style="font-size: .65rem">{{ $workflow->match_type === 'any' ? __('or') : __('and') }}</div>@endif
                                    @empty
                                        {{ __('Always') }}
                                    @endforelse
                                </td>
                                <td class="small">
                                    @foreach ($workflow->actions as $action)
                                        <div>{{ App\Enums\WorkflowActionType::from($action['type'])->label() }} <strong>{{ str($action['value'] ?? '')->limit(40) }}</strong></div>
                                    @endforeach
                                </td>
                                <td class="text-secondary">
                                    {{ $workflow->run_count }}
                                    @if ($workflow->last_run_at)<div class="small">last {{ $workflow->last_run_at->diffForHumans() }}</div>@endif
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="{{ route('workflows.edit', $workflow) }}" class="btn btn-sm">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('workflows.destroy', $workflow) }}" data-confirm="{{ __('Delete this workflow?') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty">
                <div class="empty-icon"><i class="ti ti-git-branch fs-1"></i></div>
                <p class="empty-title">{{ __('No workflows yet') }}</p>
                <p class="empty-subtitle text-secondary">{{ __('Automate routing, prioritisation and escalation of tickets.') }}</p>
                <div class="empty-action"><a href="{{ route('workflows.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add workflow') }}</a></div>
            </div>
        </div>
    @endforelse
</x-layouts.app>
