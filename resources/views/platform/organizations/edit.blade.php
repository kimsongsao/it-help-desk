<x-layouts.app :title="$organization->name">
    <x-slot:header>
        <x-page-header :pretitle="__('Platform')" :title="$organization->name">
            <div class="text-secondary mt-1">{{ $organization->slug }} · {{ __('Created :date', ['date' => $organization->created_at->translatedFormat('j M Y')]) }}</div>
        </x-page-header>
    </x-slot:header>

    <div class="row row-cards">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('platform.organizations.update', $organization) }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header"><h3 class="card-title">{{ __('Subscription') }}</h3></div>
                <div class="card-body">
                    <div class="form-label">{{ __('Plan') }}</div>
                    <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column mb-3">
                        @foreach (App\Enums\Plan::cases() as $plan)
                            <label class="form-selectgroup-item">
                                <input type="radio" name="plan" value="{{ $plan->value }}" class="form-selectgroup-input" @checked(old('plan', $organization->plan->value) === $plan->value)>
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3"><span class="form-selectgroup-check"></span></div>
                                    <div class="text-start">
                                        <div class="fw-medium">{{ $plan->label() }} · {{ $plan->maxAgents() ? __(':count agents', ['count' => $plan->maxAgents()]) : __('Unlimited agents') }}</div>
                                        <div class="text-secondary small">{{ collect($plan->features())->map->label()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <label class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $organization->is_active))>
                        <span class="form-check-label">{{ __('Organization is active') }}</span>
                        <span class="form-check-description">{{ __('Suspended organizations cannot sign in or use the API.') }}</span>
                    </label>
                    <x-field-error name="is_active" />
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('platform.organizations.index') }}" class="btn btn-link">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Usage') }}</h3></div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item"><div class="datagrid-title">{{ __('Support agents') }}</div><div class="datagrid-content">{{ $agentCount }} / {{ $organization->plan->maxAgents() ?? __('Unlimited') }}</div></div>
                        <div class="datagrid-item"><div class="datagrid-title">{{ __('Email domains') }}</div><div class="datagrid-content">{{ implode(', ', $organization->allowed_email_domains) ?: '—' }}</div></div>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">{{ __('Administrators') }}</h3></div>
                <div class="list-group list-group-flush">
                    @forelse ($administrators as $administrator)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <x-avatar :user="$administrator" size="sm" />
                            <div>
                                <div>{{ $administrator->name }}</div>
                                <div class="text-secondary small">{{ $administrator->email }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-secondary">{{ __('No administrators.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
