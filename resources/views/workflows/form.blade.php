@php
    $fieldValues = [
        App\Enums\WorkflowField::Status->value => $valueOptions['status'],
        App\Enums\WorkflowField::Priority->value => $valueOptions['priority'],
        App\Enums\WorkflowField::Category->value => $valueOptions['category'],
        App\Enums\WorkflowField::Assignee->value => $valueOptions['user'],
    ];
    $actionValues = [
        App\Enums\WorkflowActionType::SetStatus->value => $valueOptions['status'],
        App\Enums\WorkflowActionType::SetPriority->value => $valueOptions['priority'],
        App\Enums\WorkflowActionType::SetCategory->value => $valueOptions['category'],
        App\Enums\WorkflowActionType::AssignTo->value => $valueOptions['user'],
        App\Enums\WorkflowActionType::AssignLeastBusy->value => null,
        App\Enums\WorkflowActionType::AddInternalNote->value => 'textarea',
        App\Enums\WorkflowActionType::Escalate->value => 'textarea',
    ];
    $builderConfig = [
        'conditions' => array_values(old('conditions', $workflow->conditions ?? [])),
        'actions' => array_values(old('actions', $workflow->actions ?? [])),
        'fieldValues' => $fieldValues,
        'actionValues' => $actionValues,
        'operatorsWithoutValue' => collect(App\Enums\WorkflowOperator::cases())->reject->needsValue()->map->value->values(),
    ];
@endphp

<x-layouts.app :title="$workflow->exists ? __('Edit workflow') : __('New workflow')">
    <x-slot:header>
        <x-page-header :pretitle="__('Workflows')" :title="$workflow->exists ? __('Edit workflow') : __('New workflow')" />
    </x-slot:header>

    <form method="POST" action="{{ $workflow->exists ? route('workflows.update', $workflow) : route('workflows.store') }}" id="workflow-builder">
        @csrf
        @if ($workflow->exists)
            @method('PATCH')
        @endif

        <div class="row row-cards">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><span class="badge bg-yellow text-yellow-fg me-2">1</span> {{ __('When') }}</h3></div>
                    <div class="card-body">
                        <select name="trigger" class="form-select" required>
                            @foreach (App\Enums\WorkflowTrigger::cases() as $trigger)
                                <option value="{{ $trigger->value }}" @selected(old('trigger', $workflow->trigger?->value) === $trigger->value)>{{ $trigger->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><span class="badge bg-blue text-blue-fg me-2">2</span> {{ __('If') }}</h3>
                        <div class="card-actions d-flex align-items-center gap-2">
                            <span class="text-secondary">{{ __('match') }}</span>
                            <select name="match_type" class="form-select form-select-sm w-auto">
                                <option value="all" @selected(old('match_type', $workflow->match_type) === 'all')>{{ __('all conditions') }}</option>
                                <option value="any" @selected(old('match_type', $workflow->match_type) === 'any')>{{ __('any condition') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-list="conditions" data-next="0"></div>
                        <div class="text-secondary small mt-2">{{ __('No conditions means the workflow always runs for its trigger.') }}</div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-3" data-add="conditions"><i class="ti ti-plus icon"></i> {{ __('Add condition') }}</button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title"><span class="badge bg-green text-green-fg me-2">3</span> {{ __('Then') }}</h3></div>
                    <div class="card-body">
                        <div data-list="actions" data-next="0"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-3" data-add="actions"><i class="ti ti-plus icon"></i> {{ __('Add action') }}</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Settings') }}</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $workflow->name) }}" required maxlength="100" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('Description') }}</label>
                            <textarea id="description" name="description" rows="2" maxlength="255" class="form-control">{{ old('description', $workflow->description) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="position">{{ __('Order') }}</label>
                            <input id="position" name="position" type="number" min="0" value="{{ old('position', $workflow->position ?? 0) }}" class="form-control">
                            <div class="form-hint">{{ __('Lower numbers run first.') }}</div>
                        </div>
                        <label class="form-check form-switch">
                            <input type="hidden" name="stop_processing" value="0">
                            <input type="checkbox" name="stop_processing" value="1" class="form-check-input" @checked(old('stop_processing', $workflow->stop_processing))>
                            <span class="form-check-label">{{ __('Stop running later workflows when this one matches') }}</span>
                        </label>
                        <label class="form-check form-switch mb-0">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $workflow->is_active))>
                            <span class="form-check-label">{{ __('Workflow is active') }}</span>
                        </label>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('workflows.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save workflow') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <template id="conditions-template">
        <div class="workflow-row row g-2 align-items-start">
            <div class="col-md-4">
                <select class="form-select" data-name="field" data-kind>
                    @foreach (App\Enums\WorkflowField::cases() as $field)
                        <option value="{{ $field->value }}">{{ $field->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" data-name="operator" data-operator>
                    @foreach (App\Enums\WorkflowOperator::cases() as $operator)
                        <option value="{{ $operator->value }}">{{ $operator->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col" data-value-slot></div>
            <div class="col-auto"><button type="button" class="btn btn-icon btn-ghost-danger" data-remove aria-label="{{ __('Remove condition') }}"><i class="ti ti-x"></i></button></div>
        </div>
    </template>

    <template id="actions-template">
        <div class="workflow-row row g-2 align-items-start">
            <div class="col-md-4">
                <select class="form-select" data-name="type" data-kind>
                    @foreach (App\Enums\WorkflowActionType::cases() as $actionType)
                        <option value="{{ $actionType->value }}">{{ $actionType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col" data-value-slot></div>
            <div class="col-auto"><button type="button" class="btn btn-icon btn-ghost-danger" data-remove aria-label="{{ __('Remove action') }}"><i class="ti ti-x"></i></button></div>
        </div>
    </template>

    <script type="application/json" id="workflow-config">@json($builderConfig)</script>

    @push('scripts')
        @vite('resources/js/workflow-builder.js')
    @endpush
</x-layouts.app>
