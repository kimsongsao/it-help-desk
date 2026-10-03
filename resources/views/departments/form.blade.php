<x-layouts.app :title="$department->exists ? __('Edit department') : __('New department')">
    <x-slot:header>
        <x-page-header :pretitle="__('Departments')" :title="$department->exists ? __('Edit department') : __('New department')" />
    </x-slot:header>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}" class="card">
                @csrf
                @if ($department->exists)
                    @method('PATCH')
                @endif
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required" for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $department->name) }}" required maxlength="100" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                        <x-field-error name="name" />
                    </div>
                    <div>
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <input id="description" name="description" type="text" value="{{ old('description', $department->description) }}" maxlength="255" class="form-control">
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('departments.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
