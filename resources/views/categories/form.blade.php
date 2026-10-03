<x-layouts.app :title="$category->exists ? __('Edit category') : __('New category')">
    <x-slot:header>
        <x-page-header :pretitle="__('Categories')" :title="$category->exists ? __('Edit category') : __('New category')" />
    </x-slot:header>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card">
                @csrf
                @if ($category->exists)
                    @method('PATCH')
                @endif
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required" for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required maxlength="100" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                        <x-field-error name="name" />
                    </div>
                    <div class="mb-3">
                        <div class="form-label">{{ __('Icon') }}</div>
                        <div class="form-selectgroup">
                            @foreach (['ti-device-laptop', 'ti-apps', 'ti-wifi', 'ti-key', 'ti-mail', 'ti-printer', 'ti-phone', 'ti-device-desktop', 'ti-cloud', 'ti-shield-lock', 'ti-database', 'ti-help-circle'] as $icon)
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="icon" value="{{ $icon }}" class="form-selectgroup-input" @checked(old('icon', $category->icon ?? 'ti-help-circle') === $icon)>
                                    <span class="form-selectgroup-label"><i class="ti {{ $icon }} fs-2"></i></span>
                                </label>
                            @endforeach
                        </div>
                        <x-field-error name="icon" />
                    </div>
                    @if ($teams->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label" for="default_team_id">{{ __('Route new tickets to team') }}</label>
                            <select id="default_team_id" name="default_team_id" @class(['form-select', 'is-invalid' => $errors->has('default_team_id')])>
                                <option value="">{{ __('No team') }}</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((int) old('default_team_id', $category->default_team_id) === $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error name="default_team_id" />
                        </div>
                    @endif
                    <div>
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <input id="description" name="description" type="text" value="{{ old('description', $category->description) }}" maxlength="255" @class(['form-control', 'is-invalid' => $errors->has('description')])>
                        <x-field-error name="description" />
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('categories.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
