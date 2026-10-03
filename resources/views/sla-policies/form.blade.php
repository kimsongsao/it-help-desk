<x-layouts.app :title="$policy->exists ? __('Edit SLA policy') : __('New SLA policy')">
    <x-slot:header>
        <x-page-header :pretitle="__('SLA policies')" :title="$policy->exists ? __('Edit SLA policy') : __('New SLA policy')" />
    </x-slot:header>

    <form method="POST" action="{{ $policy->exists ? route('sla-policies.update', $policy) : route('sla-policies.store') }}">
        @csrf
        @if ($policy->exists)
            @method('PATCH')
        @endif

        <div class="row row-cards">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Policy') }}</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $policy->name) }}" required maxlength="100" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('Description') }}</label>
                            <input id="description" name="description" type="text" value="{{ old('description', $policy->description) }}" maxlength="255" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="priority">{{ __('Priority') }}</label>
                                <select id="priority" name="priority" class="form-select">
                                    <option value="">{{ __('Any priority') }}</option>
                                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected(old('priority', $policy->priority?->value) === $priority->value)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="category_id">{{ __('Category') }}</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">{{ __('Any category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $policy->category_id) === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="first_response_minutes">{{ __('First response target') }}</label>
                                <div class="input-group">
                                    <input id="first_response_minutes" name="first_response_minutes" type="number" min="1" value="{{ old('first_response_minutes', $policy->first_response_minutes) }}" required @class(['form-control', 'is-invalid' => $errors->has('first_response_minutes')])>
                                    <span class="input-group-text">{{ __('minutes') }}</span>
                                </div>
                                <x-field-error name="first_response_minutes" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="resolution_minutes">{{ __('Resolution target') }}</label>
                                <div class="input-group">
                                    <input id="resolution_minutes" name="resolution_minutes" type="number" min="1" value="{{ old('resolution_minutes', $policy->resolution_minutes) }}" required @class(['form-control', 'is-invalid' => $errors->has('resolution_minutes')])>
                                    <span class="input-group-text">{{ __('minutes') }}</span>
                                </div>
                                <x-field-error name="resolution_minutes" />
                            </div>
                        </div>
                        <div class="form-hint">{{ __('Tip: 1 business day = 480 minutes with 09:00–17:00 business hours.') }}</div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title">{{ __('Calendar') }}</h3></div>
                    <div class="card-body">
                        <label class="form-check form-switch mb-3">
                            <input type="hidden" name="business_hours_only" value="0">
                            <input type="checkbox" name="business_hours_only" value="1" class="form-check-input" @checked(old('business_hours_only', $policy->business_hours_only))>
                            <span class="form-check-label">{{ __('Only count business hours') }}</span>
                        </label>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="business_hours_start">{{ __('Opens') }}</label>
                                <input id="business_hours_start" name="business_hours_start" type="time" value="{{ old('business_hours_start', substr((string) $policy->business_hours_start, 0, 5)) }}" @class(['form-control', 'is-invalid' => $errors->has('business_hours_start')])>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="business_hours_end">{{ __('Closes') }}</label>
                                <input id="business_hours_end" name="business_hours_end" type="time" value="{{ old('business_hours_end', substr((string) $policy->business_hours_end, 0, 5)) }}" @class(['form-control', 'is-invalid' => $errors->has('business_hours_end')])>
                                <x-field-error name="business_hours_end" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-label">{{ __('Working days') }}</div>
                                <div class="form-selectgroup">
                                    @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $day => $label)
                                        <label class="form-selectgroup-item">
                                            <input type="checkbox" name="business_days[]" value="{{ $day }}" class="form-selectgroup-input" @checked(in_array($day, array_map('intval', old('business_days', $policy->business_days ?? [])), true))>
                                            <span class="form-selectgroup-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-field-error name="business_days" />
                            </div>
                        </div>
                        <div class="form-hint">{{ __('Business hours are counted in your organization\'s time zone (:timezone).', ['timezone' => auth()->user()->organization->timezone]) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Escalation') }}</h3></div>
                    <div class="card-body">
                        <label class="form-check form-switch mb-3">
                            <input type="hidden" name="escalate_on_breach" value="0">
                            <input type="checkbox" name="escalate_on_breach" value="1" class="form-check-input" @checked(old('escalate_on_breach', $policy->escalate_on_breach))>
                            <span class="form-check-label">{{ __('Escalate automatically when breached') }}</span>
                        </label>
                        <label class="form-label" for="escalate_to_id">{{ __('Hand escalated tickets to') }}</label>
                        <select id="escalate_to_id" name="escalate_to_id" @class(['form-select', 'is-invalid' => $errors->has('escalate_to_id')])>
                            <option value="">{{ __('Keep current assignee') }}</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int) old('escalate_to_id', $policy->escalate_to_id) === $agent->id)>{{ $agent->name }} · {{ $agent->role->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="escalate_to_id" />
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <label class="form-check form-switch mb-0">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $policy->is_active))>
                            <span class="form-check-label">{{ __('Policy is active') }}</span>
                        </label>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('sla-policies.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save policy') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
