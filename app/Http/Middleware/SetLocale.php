<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Use the user's saved language, then the one chosen this session, then the browser's preference.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('helpdesk.locales'));

        $user = $request->user() ?? ($request->bearerToken() ? $request->user('sanctum') : null);

        $locale = collect([
            $user?->locale,
            $request->hasSession() ? $request->session()->get('locale') : null,
            $user?->organization?->default_locale,
            $request->getPreferredLanguage($supported),
        ])->first(fn (?string $candidate): bool => in_array($candidate, $supported, true));

        if ($locale !== null) {
            App::setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
