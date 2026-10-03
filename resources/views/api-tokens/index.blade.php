<x-layouts.app :title="__('API tokens')">
    <x-slot:header>
        <x-page-header :pretitle="__('Account')" :title="__('API tokens')">
            <div class="text-secondary mt-1">{{ __('Tokens act with your permissions. Send one in the Authorization header of each API request:') }}</div>
            <code class="d-inline-block mt-1">Authorization: Bearer &lt;token&gt; · {{ url('/api/v1') }}</code>
        </x-page-header>
    </x-slot:header>

    @if (session('plainTextToken'))
        <div class="alert alert-warning">
            <h4 class="alert-title">{{ __('Your new token') }}</h4>
            <div class="input-group mt-2">
                <input type="text" readonly value="{{ session('plainTextToken') }}" class="form-control font-monospace" id="new-token">
            </div>
        </div>
    @endif

    <div class="row row-cards">
        <div class="col-lg-4">
            <form method="POST" action="{{ route('api-tokens.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">{{ __('Create token') }}</h3></div>
                <div class="card-body">
                    <label class="form-label required" for="name">{{ __('Token name') }}</label>
                    <input id="name" name="name" type="text" required maxlength="100" placeholder="e.g. Monitoring integration" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                    <x-field-error name="name" />
                </div>
                <div class="card-footer text-end"><button type="submit" class="btn btn-primary"><i class="ti ti-key icon"></i> {{ __('Create') }}</button></div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Created') }}</th><th>{{ __('Last used') }}</th><th class="w-1"></th></tr></thead>
                        <tbody>
                            @forelse ($tokens as $token)
                                <tr>
                                    <td class="fw-medium">{{ $token->name }}</td>
                                    <td class="text-secondary">{{ $token->created_at->diffForHumans() }}</td>
                                    <td class="text-secondary">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}" data-confirm="{{ __('Revoke this token? Applications using it will stop working.') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Revoke') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">{{ __('No tokens yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
