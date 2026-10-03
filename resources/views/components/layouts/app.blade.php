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
        @stack('head')
    </head>
    <body>
        <div class="page">
            <header class="navbar navbar-expand-md d-print-none">
                <div class="container-xl">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                            <span class="avatar avatar-sm bg-primary text-white"><i class="ti ti-headset fs-2"></i></span>
                            <span class="d-none d-sm-inline">{{ __(config('app.name')) }}</span>
                        </a>
                    </div>
                    <div class="navbar-nav flex-row order-md-last align-items-center">
                        @can('tickets.create')
                            <div class="nav-item d-none d-md-flex me-2">
                                <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                                    <i class="ti ti-plus icon"></i> {{ __('New ticket') }}
                                </a>
                            </div>
                        @endcan
                        <x-language-switcher class="nav-item" />
                        <div class="nav-item">
                            <button type="button" class="nav-link px-2" data-theme-toggle title="{{ __('Switch light / dark mode') }}" aria-label="{{ __('Switch light / dark mode') }}">
                                <i class="ti ti-moon fs-2 hide-theme-dark"></i>
                                <i class="ti ti-sun fs-2 hide-theme-light"></i>
                            </button>
                        </div>
                        <div class="nav-item dropdown me-2">
                            <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown" tabindex="-1" aria-label="{{ __('Notifications') }}">
                                <i class="ti ti-bell fs-2"></i>
                                @if ($unreadNotificationCount > 0)
                                    <span class="badge bg-red text-red-fg badge-notification badge-pill nav-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                                @endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card notification-menu">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">{{ __('Notifications') }}</h3>
                                        @if ($unreadNotificationCount > 0)
                                            <form method="POST" action="{{ route('notifications.read') }}" class="card-actions">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm p-0">{{ __('Mark all read') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable">
                                        @forelse ($recentNotifications as $notification)
                                            <a href="{{ route('notifications.show', $notification->id) }}" class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="status-dot {{ $notification->read_at ? '' : 'status-dot-animated bg-red' }} d-block"></span>
                                                    </div>
                                                    <div class="col text-truncate">
                                                        <div class="text-body d-block text-truncate">{{ $notification->data['message'] ?? '' }}</div>
                                                        <div class="d-block text-secondary text-truncate mt-n1">{{ $notification->data['subject'] ?? '' }} · {{ $notification->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="list-group-item text-secondary text-center py-4">
                                                <i class="ti ti-bell-check fs-1 d-block mb-1"></i>{{ __("You're all caught up.") }}
                                            </div>
                                        @endforelse
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="{{ route('notifications.index') }}">{{ __('View all notifications') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="{{ __('Open user menu') }}">
                                <x-avatar :user="auth()->user()" size="sm" />
                                <div class="d-none d-xl-block ps-2">
                                    <div>{{ auth()->user()->name }}</div>
                                    <div class="mt-1 small text-secondary">{{ __(auth()->user()->role->name) }} · {{ auth()->user()->organization->name }}</div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <a href="{{ route('notifications.index') }}" class="dropdown-item"><i class="ti ti-bell icon dropdown-item-icon"></i> {{ __('Notifications') }}</a>
                                @can('api.access')
                                    <a href="{{ route('api-tokens.index') }}" class="dropdown-item"><i class="ti ti-key icon dropdown-item-icon"></i> {{ __('API tokens') }}</a>
                                @endcan
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="ti ti-logout icon dropdown-item-icon"></i> {{ __('Log out') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <header class="navbar-expand-md">
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div class="navbar">
                        <div class="container-xl">
                            <ul class="navbar-nav">
                                <li @class(['nav-item', 'active' => request()->routeIs('dashboard')])>
                                    <a class="nav-link" href="{{ route('dashboard') }}">
                                        <span class="nav-link-icon"><i class="ti ti-home icon"></i></span>
                                        <span class="nav-link-title">{{ __('Home') }}</span>
                                    </a>
                                </li>
                                <li @class(['nav-item', 'active' => request()->routeIs('tickets.*')])>
                                    <a class="nav-link" href="{{ route('tickets.index') }}">
                                        <span class="nav-link-icon"><i class="ti ti-ticket icon"></i></span>
                                        <span class="nav-link-title">{{ auth()->user()->can('tickets.view_all') ? __('Tickets') : __('My tickets') }}</span>
                                    </a>
                                </li>
                                @can('reports.view')
                                    <li @class(['nav-item', 'active' => request()->routeIs('reports.*')])>
                                        <a class="nav-link" href="{{ route('reports.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-chart-bar icon"></i></span>
                                            <span class="nav-link-title">{{ __('Reports') }}</span>
                                        </a>
                                    </li>
                                @endcan
                                @canany(['sla.manage', 'workflows.manage', 'categories.manage'])
                                    <li @class(['nav-item dropdown', 'active' => request()->routeIs('sla-policies.*', 'workflows.*', 'categories.*')])>
                                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                            <span class="nav-link-icon"><i class="ti ti-adjustments icon"></i></span>
                                            <span class="nav-link-title">{{ __('Configuration') }}</span>
                                        </a>
                                        <div class="dropdown-menu">
                                            @can('sla.manage')
                                                <a class="dropdown-item" href="{{ route('sla-policies.index') }}"><i class="ti ti-clock-hour-4 icon dropdown-item-icon"></i> {{ __('SLA policies') }}</a>
                                            @endcan
                                            @can('workflows.manage')
                                                <a class="dropdown-item" href="{{ route('workflows.index') }}"><i class="ti ti-git-branch icon dropdown-item-icon"></i> {{ __('Workflows') }}</a>
                                            @endcan
                                            @can('categories.manage')
                                                <a class="dropdown-item" href="{{ route('categories.index') }}"><i class="ti ti-category icon dropdown-item-icon"></i> {{ __('Categories') }}</a>
                                            @endcan
                                        </div>
                                    </li>
                                @endcanany
                                @canany(['users.manage', 'roles.manage', 'organization.manage'])
                                    <li @class(['nav-item dropdown', 'active' => request()->routeIs('users.*', 'roles.*', 'organization.*')])>
                                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                            <span class="nav-link-icon"><i class="ti ti-shield-lock icon"></i></span>
                                            <span class="nav-link-title">{{ __('Administration') }}</span>
                                        </a>
                                        <div class="dropdown-menu">
                                            @can('users.manage')
                                                <a class="dropdown-item" href="{{ route('users.index') }}"><i class="ti ti-users icon dropdown-item-icon"></i> {{ __('Users') }}</a>
                                            @endcan
                                            @can('roles.manage')
                                                <a class="dropdown-item" href="{{ route('roles.index') }}"><i class="ti ti-lock-access icon dropdown-item-icon"></i> {{ __('Roles & permissions') }}</a>
                                            @endcan
                                            @can('organization.manage')
                                                <a class="dropdown-item" href="{{ route('organization.edit') }}"><i class="ti ti-building icon dropdown-item-icon"></i> {{ __('Organization settings') }}</a>
                                            @endcan
                                        </div>
                                    </li>
                                @endcanany
                                @can('platform.manage')
                                    <li @class(['nav-item', 'active' => request()->routeIs('platform.*')])>
                                        <a class="nav-link" href="{{ route('platform.organizations.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-world icon"></i></span>
                                            <span class="nav-link-title">{{ __('Platform') }}</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-wrapper">
                {{ $header ?? '' }}

                <div class="page-body">
                    <div class="container-xl">
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <div class="d-flex gap-2"><i class="ti ti-circle-check icon alert-icon"></i><div>{{ session('status') }}</div></div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></a>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <div class="d-flex gap-2">
                                    <i class="ti ti-alert-circle icon alert-icon"></i>
                                    <div>
                                        <h4 class="alert-title">{{ __('Please check the highlighted fields.') }}</h4>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></a>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>

                <footer class="footer footer-transparent d-print-none">
                    <div class="container-xl text-secondary small d-flex flex-wrap justify-content-between gap-2">
                        <span>{{ __(config('app.name')) }} · {{ auth()->user()->organization->name }}</span>
                        <x-copyright />
                    </div>
                </footer>
            </div>
        </div>

        @can('tickets.create')
            @unless (request()->routeIs('tickets.create'))
                <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-icon fab d-md-none" aria-label="{{ __('New ticket') }}">
                    <i class="ti ti-plus fs-1"></i>
                </a>
            @endunless
        @endcan

        @stack('scripts')
    </body>
</html>
