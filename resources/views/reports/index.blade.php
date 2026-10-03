@php
    $formatMinutes = fn (?float $minutes) => $minutes === null ? '—' : App\Models\SlaPolicy::formatMinutes((int) round($minutes));
    $formatPercent = fn (?float $percent) => $percent === null ? '—' : "{$percent}%";
@endphp

<x-layouts.app :title="__('Reports')">
    <x-slot:header>
        <x-page-header :pretitle="__('Analytics')" :title="__('Help desk report')">
            <div class="text-secondary mt-1">{{ $report->from->forViewer()->translatedFormat('j M Y') }} – {{ $report->to->forViewer()->translatedFormat('j M Y') }}</div>
            <x-slot:actions>
                <form method="GET" action="{{ route('reports.index') }}" class="d-flex flex-wrap gap-2">
                    <input type="date" name="from" value="{{ $report->from->forViewer()->toDateString() }}" class="form-control w-auto" aria-label="{{ __('From') }}">
                    <input type="date" name="to" value="{{ $report->to->forViewer()->toDateString() }}" class="form-control w-auto" aria-label="{{ __('To') }}">
                    <button type="submit" class="btn"><i class="ti ti-calendar-search icon"></i> {{ __('Apply') }}</button>
                    <a href="{{ route('reports.export', ['from' => $report->from->forViewer()->toDateString(), 'to' => $report->to->forViewer()->toDateString()]) }}" class="btn btn-primary"><i class="ti ti-download icon"></i> {{ __('Export CSV') }}</a>
                </form>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="row row-deck row-cards">
        @foreach ([
            [__('Tickets created'), $summary['created'], 'ti-ticket', 'blue'],
            [__('Tickets resolved'), $summary['resolved'], 'ti-circle-check', 'green'],
            [__('Open backlog'), $summary['backlog'], 'ti-stack-2', 'yellow'],
            [__('Escalations'), $summary['escalations'], 'ti-arrow-big-up-lines', 'red'],
        ] as [$label, $value, $icon, $color])
            <div class="col-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="avatar bg-{{ $color }} text-white"><i class="ti {{ $icon }}"></i></span></div>
                            <div class="col">
                                <div class="h2 mb-0">{{ $value }}</div>
                                <div class="text-secondary">{{ $label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach ([
            [__('First response SLA met'), $summary['first_response_compliance'], __('Avg. first response'), $formatMinutes($summary['avg_first_response_minutes'])],
            [__('Resolution SLA met'), $summary['resolution_compliance'], __('Avg. resolution time'), $formatMinutes($summary['avg_resolution_minutes'])],
        ] as [$label, $percent, $averageLabel, $average])
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ $label }}</div>
                            <div class="ms-auto text-secondary small">{{ $averageLabel }}: <strong class="text-body">{{ $average }}</strong></div>
                        </div>
                        <div class="h1 mb-3">{{ $formatPercent($percent) }}</div>
                        <div class="progress progress-sm">
                            <div @class(['progress-bar', 'bg-green' => $percent >= 90, 'bg-yellow' => $percent < 90 && $percent >= 70, 'bg-red' => $percent !== null && $percent < 70]) style="width: {{ $percent ?? 0 }}%" role="progressbar" aria-valuenow="{{ $percent ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Ticket volume') }}</h3></div>
                <div class="card-body"><div id="chart-volume"></div></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('By priority') }}</h3></div>
                <div class="card-body"><div id="chart-priority"></div></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('By category') }}</h3></div>
                <div class="card-body"><div id="chart-category"></div></div>
            </div>
        </div>

        @if ($teams->isNotEmpty())
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Team performance') }}</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Team') }}</th>
                                    <th class="text-end">{{ __('Members') }}</th>
                                    <th class="text-end">{{ __('Open now') }}</th>
                                    <th class="text-end">{{ __('Created') }}</th>
                                    <th class="text-end">{{ __('Resolved') }}</th>
                                    <th class="text-end">{{ __('SLA breaches') }}</th>
                                    <th class="text-end">{{ __('Avg. resolution') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teams as $team)
                                    <tr>
                                        <td><span class="badge bg-{{ $team->color }} me-2"></span>{{ $team->name }}</td>
                                        <td class="text-end">{{ $team->members_count }}</td>
                                        <td class="text-end"><a href="{{ route('tickets.index', ['team' => $team->id]) }}">{{ $team->open_count }}</a></td>
                                        <td class="text-end">{{ $team->created_count }}</td>
                                        <td class="text-end">{{ $team->resolved_count }}</td>
                                        <td class="text-end">@if ($team->breached_count > 0)<span class="text-red">{{ $team->breached_count }}</span>@else 0 @endif</td>
                                        <td class="text-end">{{ $team->avg_resolution_hours !== null ? __(':hours h', ['hours' => $team->avg_resolution_hours]) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Agent performance') }}</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>{{ __('Agent') }}</th>
                                <th class="text-end">{{ __('Open now') }}</th>
                                <th class="text-end">{{ __('Assigned') }}</th>
                                <th class="text-end">{{ __('Resolved') }}</th>
                                <th class="text-end">{{ __('SLA breaches') }}</th>
                                <th class="text-end">{{ __('Avg. resolution') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td>{{ $agent->name }}<div class="text-secondary small">{{ __($agent->role->name) }}</div></td>
                                    <td class="text-end">{{ $agent->open_tickets_count }}</td>
                                    <td class="text-end">{{ $agent->assigned_count }}</td>
                                    <td class="text-end">{{ $agent->resolved_count }}</td>
                                    <td class="text-end">@if ($agent->breached_count > 0)<span class="text-red">{{ $agent->breached_count }}</span>@else 0 @endif</td>
                                    <td class="text-end">{{ $agent->avg_resolution_hours !== null ? __(':hours h', ['hours' => $agent->avg_resolution_hours]) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">{{ __('No support agents yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Current status of tickets created in period') }}</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($byStatus as $label => $count)
                        <div class="list-group-item d-flex justify-content-between"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @php
        $chartData = [
            'volume' => [...$dailyVolume, 'labels' => array_map(fn ($day) => Illuminate\Support\Carbon::parse($day)->translatedFormat('j M'), $dailyVolume['labels'])],
            'priority' => $byPriority,
            'category' => $byCategory->map(fn ($row) => ['name' => __($row->name), 'total' => $row->total]),
            'labels' => ['created' => __('Created'), 'resolved' => __('Resolved'), 'tickets' => __('Tickets')],
        ];
    @endphp
    <script type="application/json" id="report-data">@json($chartData)</script>

    @push('scripts')
        @vite('resources/js/reports.js')
    @endpush
</x-layouts.app>
