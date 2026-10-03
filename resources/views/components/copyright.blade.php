<span {{ $attributes }}>
    &copy; {{ now()->year }}
    <a href="{{ config('helpdesk.company.url') }}" target="_blank" rel="noopener" class="link-secondary">{{ config('helpdesk.company.name') }}</a>.
    {{ __('All rights reserved.') }}
</span>
