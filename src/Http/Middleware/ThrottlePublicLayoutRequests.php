<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ThrottlePublicLayoutRequests
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('capell-layout-builder.public_endpoints.requests_per_minute', 600);
        $budget = is_numeric($configured) ? max(1, (int) $configured) : 600;
        $key = 'capell-layout-builder-public:' . hash('sha256', $request->ip() ?? 'unknown');

        // Decide on the increment result so concurrent requests cannot all pass
        // a separate read before any request is counted. All public routes share it.
        if (RateLimiter::hit($key, 60) > $budget) {
            return response('', Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => (string) RateLimiter::availableIn($key),
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return $next($request);
    }
}
