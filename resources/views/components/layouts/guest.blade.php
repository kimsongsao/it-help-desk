@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title>{{ $title ? "{$title} · " : '' }}{{ __(config('app.name')) }}</title>

        <x-theme-script />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="row g-0 min-vh-100">
            <div class="col-12 col-lg-6 col-xl-5 d-flex flex-column auth-form-side">
                <div class="d-flex align-items-center justify-content-between px-4 px-sm-5 pt-4">
                    <a href="{{ route('login') }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none fw-bold">
                        <span class="avatar avatar-sm bg-primary text-white"><i class="ti ti-headset fs-2"></i></span>
                        {{ __(config('app.name')) }}
                    </a>
                    <div class="d-flex align-items-center">
                        <x-language-switcher />
                        <button type="button" class="nav-link px-2" data-theme-toggle title="{{ __('Switch light / dark mode') }}" aria-label="{{ __('Switch light / dark mode') }}">
                            <i class="ti ti-moon fs-2 hide-theme-dark"></i>
                            <i class="ti ti-sun fs-2 hide-theme-light"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-fill d-flex align-items-center">
                    <div class="container container-tight py-5 px-4 px-sm-5">
                        {{ $slot }}
                        {{ $footer ?? '' }}
                    </div>
                </div>

                <x-copyright class="d-block text-center text-secondary small pb-4 px-4" />
            </div>

            <div class="col-lg-6 col-xl-7 d-none d-lg-flex auth-cover">
                <div class="auth-cover-content">
                    <span class="badge bg-white-lt text-white mb-4"><i class="ti ti-sparkles me-1"></i> {{ __('Support made simple') }}</span>
                    <h1 class="display-6 fw-bold text-white mb-3">{{ __('Get help fast, from the right team.') }}</h1>
                    <p class="text-white opacity-75 fs-3 mb-5">{{ __('Report a problem in seconds and follow every step until it is fixed.') }}</p>

                    <div class="d-grid gap-3 mb-5">
                        @foreach ([
                            ['ti-microphone', __('Type, talk or record a voice note')],
                            ['ti-photo', __('Attach screenshots and files')],
                            ['ti-bell-ringing', __('Get notified the moment an agent replies')],
                            ['ti-language', __('Available in Khmer and English')],
                        ] as [$icon, $feature])
                            <div class="d-flex align-items-center gap-3 text-white">
                                <span class="avatar bg-white-lt text-white"><i class="ti {{ $icon }} fs-2"></i></span>
                                <span class="fs-3">{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="card auth-preview shadow-lg" aria-hidden="true">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-green-lt">{{ __('Resolved') }}</span>
                                <span class="text-secondary small">HD-01024</span>
                            </div>
                            <div class="fw-medium mb-2">{{ __('Question about my latest invoice') }}</div>
                            <div class="d-flex align-items-center gap-2 text-secondary small">
                                <span class="avatar avatar-xs bg-blue-lt">AS</span>
                                {{ __('Fixed in 42 minutes') }}
                                <i class="ti ti-circle-check-filled text-green ms-auto fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
