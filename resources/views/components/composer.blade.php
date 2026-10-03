@props([
    'name' => 'body',
    'value' => '',
    'rows' => 5,
    'placeholder' => '',
    'required' => false,
])

@php
    $config = config('helpdesk.attachments');
    $hasError = $errors->has($name) || $errors->has('attachments') || $errors->has('attachments.*') || $errors->has('voice_note');
    $i18n = [
        'dictate' => __('Dictate'),
        'stopDictation' => __('Stop dictation'),
        'recording' => __('Recording…'),
        'stop' => __('Stop'),
        'discard' => __('Discard'),
        'remove' => __('Remove'),
        'micDenied' => __('Microphone access was blocked. Allow it in your browser to use voice.'),
        'dictationFailed' => __('Dictation stopped. Please try again.'),
        'tooMany' => __('You can attach up to :count files.', ['count' => $config['max_files']]),
        'tooLarge' => __(':name is larger than :size.', ['size' => Illuminate\Support\Number::fileSize($config['max_size'] * 1024)]),
    ];
@endphp

<div>
    <div @class(['composer', 'is-invalid' => $hasError]) data-composer
         data-speech-lang="{{ config('helpdesk.locales.'.app()->getLocale().'.speech', 'en-US') }}"
         data-max-files="{{ $config['max_files'] }}"
         data-max-size="{{ $config['max_size'] }}"
         data-i18n="{{ json_encode($i18n) }}">
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" class="form-control" placeholder="{{ $placeholder }}" @required($required)>{{ $value }}</textarea>
        <div class="composer-voice"></div>
        <div class="composer-files"></div>
        <input type="file" name="attachments[]" multiple hidden data-file-input accept=".{{ implode(',.', $config['extensions']) }}">
        <input type="file" name="voice_note" hidden data-voice-input>
        <div class="composer-toolbar">
            <button type="button" class="btn btn-ghost-secondary btn-sm" data-dictate title="{{ __('Speak and we will type it for you') }}">
                <i class="ti ti-microphone icon"></i> <span data-label>{{ __('Dictate') }}</span>
            </button>
            <button type="button" class="btn btn-ghost-secondary btn-sm" data-record title="{{ __('Record a voice message instead of typing') }}">
                <i class="ti ti-circle-dot icon text-red"></i> {{ __('Record voice note') }}
            </button>
            <button type="button" class="btn btn-ghost-secondary btn-sm" data-attach>
                <i class="ti ti-paperclip icon"></i> {{ __('Attach files') }}
            </button>
            <span class="text-secondary small ms-auto d-none d-md-inline">{{ __('Drop files here · max :count files, :size each', ['count' => $config['max_files'], 'size' => Illuminate\Support\Number::fileSize($config['max_size'] * 1024)]) }}</span>
            {{ $toolbar ?? '' }}
        </div>
    </div>
    <div class="invalid-feedback d-block d-none" data-composer-error></div>
    <x-field-error :name="$name" />
    <x-field-error name="attachments" />
    <x-field-error name="attachments.*" />
    <x-field-error name="voice_note" />
</div>
