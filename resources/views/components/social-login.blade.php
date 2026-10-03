@props(['action' => 'login'])

@php($providers = App\Enums\SocialProvider::enabled())

@if ($providers !== [])
    <div class="d-grid gap-2">
        @foreach ($providers as $provider)
            @if ($provider === App\Enums\SocialProvider::Telegram)
                <button type="button" class="btn btn-social w-100" data-telegram-login
                        data-bot-id="{{ str(config('services.telegram.bot_token'))->before(':') }}"
                        data-callback="{{ route('social.callback', $provider) }}">
                    <i class="ti ti-brand-telegram fs-2" style="color: #229ED9"></i>
                    {{ $action === 'register' ? __('Sign up with :provider', ['provider' => $provider->label()]) : __('Continue with :provider', ['provider' => $provider->label()]) }}
                </button>
            @else
                <a href="{{ route('social.redirect', $provider) }}" class="btn btn-social w-100">
                    @if ($provider === App\Enums\SocialProvider::Google)
                        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.6-.4-3.9z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.6-.4-3.9z"/></svg>
                    @else
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/><path fill="#fff" d="m16.67 15.56.53-3.49h-3.33V9.82c0-.96.47-1.89 1.96-1.89h1.51V4.96s-1.37-.24-2.68-.24c-2.74 0-4.53 1.67-4.53 4.69v2.66H7.08v3.49h3.05V24a12.2 12.2 0 0 0 3.75 0v-8.44h2.79z"/></svg>
                    @endif
                    {{ $action === 'register' ? __('Sign up with :provider', ['provider' => $provider->label()]) : __('Continue with :provider', ['provider' => $provider->label()]) }}
                </a>
            @endif
        @endforeach
    </div>

    <div class="hr-text my-4 text-secondary">{{ $action === 'register' ? __('or sign up with email') : __('or log in with email') }}</div>

    @if (in_array(App\Enums\SocialProvider::Telegram, $providers, true))
        @push('scripts')
            <script src="https://telegram.org/js/telegram-widget.js?22" async></script>
        @endpush
    @endif
@endif
