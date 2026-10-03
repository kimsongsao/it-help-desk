<?php

namespace App\Http\Middleware;

use App\Enums\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    /**
     * Only let the request through when the organization's plan includes the feature.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $feature = Feature::from($feature);

        abort_unless(
            $request->user()?->organization?->hasFeature($feature),
            Response::HTTP_FORBIDDEN,
            __(':feature is not included in your plan.', ['feature' => $feature->label()]),
        );

        return $next($request);
    }
}
