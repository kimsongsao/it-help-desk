@php
    $priorityHints = [
        'low' => [__('Low'), __('A minor inconvenience, no rush'), 'ti-mood-smile'],
        'medium' => [__('Medium'), __('Slows me down, but I can still work'), 'ti-mood-neutral'],
        'high' => [__('High'), __('I cannot do an important part of my job'), 'ti-mood-sad'],
        'critical' => [__('Critical'), __('Work has stopped for me or my team'), 'ti-alert-triangle'],
    ];
    $selectedCategory = (int) old('category_id', $selectedCategory);
@endphp

<x-layouts.app :title="__('New ticket')">
    <x-slot:header>
        <x-page-header :pretitle="__('Tickets')" :title="__('Submit a new ticket')">
            <div class="text-secondary mt-1">{{ __('It only takes a minute. The more detail you share, the faster we can fix it.') }}</div>
        </x-page-header>
    </x-slot:header>

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="row row-cards">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="form-label required">{{ __('1. What do you need help with?') }}</div>
                            <div class="row g-2">
                                @foreach ($categories as $category)
                                    <div class="col-6 col-sm-4 col-xl-3">
                                        <label class="form-selectgroup-item category-tile w-100 h-100">
                                            <input type="radio" name="category_id" value="{{ $category->id }}" class="form-selectgroup-input" @checked($selectedCategory === $category->id) required>
                                            <span class="form-selectgroup-label">
                                                <i class="ti {{ $category->icon ?? 'ti-help-circle' }}"></i>
                                                <span class="fw-medium">{{ __($category->name) }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <x-field-error name="category_id" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label required" for="subject">{{ __('2. Give it a short title') }}</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="255" placeholder="{{ __('e.g. I cannot log in to my account') }}" @class(['form-control form-control-lg', 'is-invalid' => $errors->has('subject')])>
                            <x-field-error name="subject" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label required" for="description">{{ __('3. Describe the problem') }}</label>
                            <div class="form-hint mb-2">{{ __('Type, tap Dictate to speak, or record a voice note. Screenshots help a lot.') }}</div>
                            <x-composer name="description" :value="old('description')" rows="6" :placeholder="__('What happened? What do you need? Include any error messages or reference numbers.')" />
                        </div>

                        <div>
                            <div class="form-label required">{{ __('4. How urgent is it?') }}</div>
                            <div class="form-selectgroup form-selectgroup-boxes row g-2 mx-0">
                                @foreach (App\Enums\TicketPriority::cases() as $priority)
                                    @php([$label, $hint, $icon] = $priorityHints[$priority->value])
                                    <label class="form-selectgroup-item col-md-6 m-0">
                                        <input type="radio" name="priority" value="{{ $priority->value }}" class="form-selectgroup-input" @checked(old('priority', App\Enums\TicketPriority::Medium->value) === $priority->value)>
                                        <div class="form-selectgroup-label d-flex align-items-center p-3">
                                            <div class="me-3"><span class="form-selectgroup-check"></span></div>
                                            <div class="form-selectgroup-label-content text-start">
                                                <div class="fw-medium"><i class="ti {{ $icon }} text-{{ $priority->color() }}"></i> {{ $label }}</div>
                                                <div class="text-secondary small">{{ $hint }}</div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <x-field-error name="priority" />
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        <a href="{{ url()->previous() === url()->current() ? route('dashboard') : url()->previous() }}" class="btn btn-link">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary btn-lg ms-auto"><i class="ti ti-send icon"></i> {{ __('Submit ticket') }}</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="ti ti-bulb icon text-yellow me-1"></i> {{ __('Tips for a faster fix') }}</h3></div>
                    <div class="card-body">
                        <ul class="list-unstyled space-y-2 mb-0">
                            <li class="d-flex gap-2"><i class="ti ti-check text-green"></i> {{ __('Add a screenshot of any error message.') }}</li>
                            <li class="d-flex gap-2"><i class="ti ti-check text-green"></i> {{ __('Say when it started and what changed.') }}</li>
                            <li class="d-flex gap-2"><i class="ti ti-check text-green"></i> {{ __('Mention any order, invoice or device number.') }}</li>
                            <li class="d-flex gap-2"><i class="ti ti-check text-green"></i> {{ __('Short on time? Record a voice note instead of typing.') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title">{{ __('What happens next?') }}</h3></div>
                    <div class="card-body">
                        <ul class="steps steps-vertical mb-0">
                            <li class="step-item">{{ __('We receive your ticket and assign it to an agent.') }}</li>
                            <li class="step-item">{{ __('You get notified when an agent replies.') }}</li>
                            <li class="step-item">{{ __('Reply any time from the ticket page until it is solved.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
