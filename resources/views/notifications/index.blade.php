<x-layouts.app :title="__('Notifications')">
    <x-slot:header>
        <x-page-header :pretitle="__('Inbox')" :title="__('Notifications')">
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read') }}">
                    @csrf
                    <button type="submit" class="btn"><i class="ti ti-checks icon"></i> {{ __('Mark all read') }}</button>
                </form>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="card">
        <div class="list-group list-group-flush list-group-hoverable">
            @forelse ($notifications as $notification)
                <a href="{{ route('notifications.show', $notification->id) }}" class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="avatar {{ $notification->read_at ? '' : 'bg-primary-lt' }}"><i class="ti {{ $notification->data['icon'] ?? 'ti-bell' }}"></i></span></div>
                        <div class="col text-truncate">
                            <div @class(['text-body', 'fw-bold' => ! $notification->read_at])>{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-secondary text-truncate">{{ $notification->data['subject'] ?? '' }}</div>
                        </div>
                        <div class="col-auto text-secondary small">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <div class="empty">
                    <div class="empty-icon"><i class="ti ti-bell-off fs-1"></i></div>
                    <p class="empty-title">{{ __('No notifications') }}</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
