<x-layouts.app :title="__('SLA policies')">
    <x-slot:header>
        <x-page-header :pretitle="__('Configuration')" :title="__('SLA policies')">
            <div class="text-secondary mt-1">{{ __('The most specific active policy (category and priority, then category, then priority) applies to each ticket.') }}</div>
            <x-slot:actions>
                <a href="{{ route('sla-policies.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add policy') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>{{ __('Policy') }}</th>
                        <th>{{ __('Applies to') }}</th>
                        <th>{{ __('First response') }}</th>
                        <th>{{ __('Resolution') }}</th>
                        <th>{{ __('Calendar') }}</th>
                        <th>{{ __('On breach') }}</th>
                        <th>{{ __('Tickets') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr @class(['opacity-50' => ! $policy->is_active])>
                            <td>
                                <div class="fw-medium">{{ $policy->name }} @unless ($policy->is_active)<span class="badge bg-secondary-lt ms-1">{{ __('Inactive') }}</span>@endunless</div>
                                <div class="text-secondary small">{{ $policy->description }}</div>
                            </td>
                            <td>
                                @if ($policy->priority)<x-badge :value="$policy->priority" />@endif
                                @if ($policy->category)<span class="badge bg-secondary-lt">{{ __($policy->category->name) }}</span>@endif
                                @if (! $policy->priority && ! $policy->category)<span class="text-secondary">{{ __('All tickets') }}</span>@endif
                            </td>
                            <td>{{ App\Models\SlaPolicy::formatMinutes($policy->first_response_minutes) }}</td>
                            <td>{{ App\Models\SlaPolicy::formatMinutes($policy->resolution_minutes) }}</td>
                            <td class="text-secondary">
                                @if ($policy->business_hours_only)
                                    {{ substr($policy->business_hours_start, 0, 5) }}–{{ substr($policy->business_hours_end, 0, 5) }},
                                    {{ collect($policy->business_days)->sort()->map(fn ($day) => now()->startOfWeek()->addDays($day - 1)->translatedFormat('D'))->implode(' ') }}
                                @else
                                    24/7
                                @endif
                            </td>
                            <td class="text-secondary">
                                @if ($policy->escalate_on_breach)
                                    {{ $policy->escalateTo ? __('Escalate to :name', ['name' => $policy->escalateTo->name]) : __('Escalate') }}
                                @else
                                    {{ __('Flag only') }}
                                @endif
                            </td>
                            <td>{{ $policy->tickets_count }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('sla-policies.edit', $policy) }}" class="btn btn-sm">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('sla-policies.destroy', $policy) }}" data-confirm="{{ __('Delete this SLA policy? Existing tickets keep their due dates.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty py-4">
                                    <div class="empty-icon"><i class="ti ti-clock-off fs-1"></i></div>
                                    <p class="empty-title">{{ __('No SLA policies') }}</p>
                                    <p class="empty-subtitle text-secondary">{{ __('Tickets will not have response or resolution targets until you add one.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
