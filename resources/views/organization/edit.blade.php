@php
    $maxAgents = $organization->plan->maxAgents();
    $seatPercent = $maxAgents ? min(100, round($agentCount / $maxAgents * 100)) : 0;
@endphp

<x-layouts.app :title="__('Organization settings')">
    <x-slot:header>
        <x-page-header :pretitle="__('Administration')" :title="__('Organization settings')" />
    </x-slot:header>

    <div class="row row-cards">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('organization.update') }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header"><h3 class="card-title">{{ __('Company') }}</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required" for="name">{{ __('Company name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $organization->name) }}" required maxlength="255" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                        <x-field-error name="name" />
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="default_locale">{{ __('Default language') }}</label>
                        <select id="default_locale" name="default_locale" class="form-select">
                            @foreach (config('helpdesk.locales') as $locale => $language)
                                <option value="{{ $locale }}" @selected(old('default_locale', $organization->default_locale) === $locale)>{{ $language['name'] }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">{{ __('Used for people who have not picked a language yet.') }}</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="timezone">{{ __('Time zone') }}</label>
                        <x-timezone-select :selected="old('timezone', $organization->timezone)" />
                        <div class="form-hint">{{ __('Dates are shown in this time zone, and SLA business hours are counted in it.') }}</div>
                        <x-field-error name="timezone" />
                    </div>

                    <h4 class="mb-2">{{ __('Who can join') }}</h4>
                    <div class="mb-3">
                        <label class="form-label" for="allowed_email_domains">{{ __('Company email domains') }}</label>
                        <input id="allowed_email_domains" name="allowed_email_domains" type="text" value="{{ old('allowed_email_domains', implode(', ', $organization->allowed_email_domains)) }}" placeholder="acme.com, acme.com.kh" @class(['form-control', 'is-invalid' => $errors->has('allowed_email_domains') || $errors->has('allowed_email_domains.*')])>
                        <div class="form-hint">{{ __('Employees with these email addresses can sign up through the join link or with Google / Facebook, and join as requesters.') }}</div>
                        <x-field-error name="allowed_email_domains.*" />
                    </div>
                    <label class="form-check form-switch">
                        <input type="hidden" name="allow_self_registration" value="0">
                        <input type="checkbox" name="allow_self_registration" value="1" class="form-check-input" @checked(old('allow_self_registration', $organization->allow_self_registration))>
                        <span class="form-check-label">{{ __('Anyone with the join link can sign up (any email address)') }}</span>
                        <span class="form-check-description">{{ __('Useful for customer service desks where your customers submit tickets.') }}</span>
                    </label>

                    @if ($organization->allow_self_registration || $organization->allowed_email_domains !== [])
                        <div class="mt-3">
                            <label class="form-label" for="join-link">{{ __('Join link') }}</label>
                            <input id="join-link" type="text" readonly value="{{ route('join', $organization) }}" class="form-control font-monospace" onclick="this.select()">
                        </div>
                    @endif
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Save settings') }}</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Plan & usage') }}</h3>
                    <div class="card-actions"><span class="badge bg-{{ $organization->plan->color() }}-lt fs-5">{{ $organization->plan->label() }}</span></div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ __('Support agents') }}</span>
                        <strong>{{ $agentCount }} / {{ $maxAgents ?? __('Unlimited') }}</strong>
                    </div>
                    @if ($maxAgents)
                        <div class="progress progress-sm mb-3">
                            <div @class(['progress-bar', 'bg-red' => $seatPercent >= 100, 'bg-orange' => $seatPercent >= 80 && $seatPercent < 100]) style="width: {{ $seatPercent }}%"></div>
                        </div>
                    @endif
                    <div class="datagrid mb-3">
                        <div class="datagrid-item"><div class="datagrid-title">{{ __('Users') }}</div><div class="datagrid-content">{{ $userCount }}</div></div>
                        <div class="datagrid-item"><div class="datagrid-title">{{ __('Tickets') }}</div><div class="datagrid-content">{{ $ticketCount }}</div></div>
                    </div>
                    <div class="subheader mb-2">{{ __('Included features') }}</div>
                    <ul class="list-unstyled mb-0">
                        @foreach ($features as $feature)
                            @php($included = $organization->hasFeature($feature))
                            <li @class(['d-flex align-items-center gap-2 py-1', 'text-secondary' => ! $included])>
                                <i @class(['ti', 'ti-circle-check-filled text-green' => $included, 'ti-lock' => ! $included])></i>
                                {{ $feature->label() }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if ($organization->plan !== App\Enums\Plan::Enterprise)
                    <div class="card-footer">
                        <a href="{{ config('helpdesk.company.url') }}" target="_blank" rel="noopener" class="btn btn-outline-primary w-100"><i class="ti ti-rocket icon"></i> {{ __('Contact us to upgrade') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
