@php
    $memberIds = array_map('intval', old('members', $team->members?->pluck('id')->all() ?? []));
    $categoryIds = array_map('intval', old('categories', $team->categories?->pluck('id')->all() ?? []));
@endphp

<x-layouts.app :title="$team->exists ? __('Edit team') : __('New team')">
    <x-slot:header>
        <x-page-header :pretitle="__('Teams')" :title="$team->exists ? $team->name : __('New team')" />
    </x-slot:header>

    <form method="POST" action="{{ $team->exists ? route('teams.update', $team) : route('teams.store') }}">
        @csrf
        @if ($team->exists)
            @method('PATCH')
        @endif

        <div class="row row-cards">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Team') }}</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $team->name) }}" required maxlength="100" placeholder="{{ __('e.g. Service Desk') }}" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('Description') }}</label>
                            <input id="description" name="description" type="text" value="{{ old('description', $team->description) }}" maxlength="255" class="form-control">
                        </div>
                        <div class="form-label">{{ __('Color') }}</div>
                        <div class="row g-2">
                            @foreach (App\Models\Team::Colors as $color)
                                <div class="col-auto">
                                    <label class="form-colorinput">
                                        <input name="color" type="radio" value="{{ $color }}" class="form-colorinput-input" @checked(old('color', $team->color) === $color)>
                                        <span class="form-colorinput-color bg-{{ $color }}"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('Routing') }}</h3>
                            <div class="card-subtitle">{{ __('New tickets in these categories go to this team.') }}</div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach ($categories as $category)
                            <label class="list-group-item d-flex align-items-center gap-2">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="form-check-input m-0" @checked(in_array($category->id, $categoryIds, true))>
                                <i class="ti {{ $category->icon ?? 'ti-category' }} text-secondary"></i>
                                <span class="flex-fill">{{ __($category->name) }}</span>
                                @if ($category->defaultTeam && ! $category->defaultTeam->is($team))
                                    <span class="text-secondary small">{{ __('now: :team', ['team' => $category->defaultTeam->name]) }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('Members') }}</h3>
                            <div class="card-subtitle">{{ __('Members see the team queue and are notified when a ticket arrives unassigned.') }}</div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($agents as $agent)
                            <label class="list-group-item d-flex align-items-center gap-3">
                                <input type="checkbox" name="members[]" value="{{ $agent->id }}" class="form-check-input m-0" @checked(in_array($agent->id, $memberIds, true))>
                                <x-avatar :user="$agent" size="sm" />
                                <div class="flex-fill">
                                    <div>{{ $agent->name }}</div>
                                    <div class="text-secondary small">{{ __($agent->role->name) }}@if ($agent->teams->isNotEmpty()) · {{ $agent->teams->pluck('name')->implode(', ') }}@endif</div>
                                </div>
                            </label>
                        @empty
                            <div class="list-group-item text-secondary">{{ __('No support agents yet.') }}</div>
                        @endforelse
                    </div>
                    <x-field-error name="members.*" class="px-3 pb-2" />
                    <div class="card-footer d-flex">
                        @if ($team->exists)
                            <button type="submit" form="delete-team" class="btn btn-ghost-danger"><i class="ti ti-trash icon"></i> {{ __('Delete team') }}</button>
                        @endif
                        <a href="{{ route('teams.index') }}" class="btn btn-link ms-auto">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save team') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($team->exists)
        <form id="delete-team" method="POST" action="{{ route('teams.destroy', $team) }}" data-confirm="{{ __('Delete this team? Its tickets stay, without a team.') }}">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-layouts.app>
