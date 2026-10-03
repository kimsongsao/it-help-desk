<div {{ $attributes->class('dropdown') }}>
    <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" aria-label="{{ __('Language') }}" title="{{ __('Language') }}">
        <i class="ti ti-language fs-2"></i>
        <span class="ms-1 d-none d-sm-inline small fw-medium">{{ strtoupper(app()->getLocale()) }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        @foreach (config('helpdesk.locales') as $locale => $language)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button type="submit" @class(['dropdown-item', 'active' => app()->getLocale() === $locale]) lang="{{ $locale }}">
                    {{ $language['name'] }}
                    @if (app()->getLocale() === $locale)<i class="ti ti-check ms-auto"></i>@endif
                </button>
            </form>
        @endforeach
    </div>
</div>
