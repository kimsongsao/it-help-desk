<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyOrganization
{
    public function __construct(private Tenancy $tenancy) {}

    /**
     * Make the signed in user's organization current, and sign out members of suspended organizations.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $organization = $user->organization;

        if ($organization === null || ! $organization->is_active) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, __('Your organization has been suspended.'));
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('Your organization has been suspended.')]);
        }

        $this->tenancy->set($organization);

        return $next($request);
    }
}
