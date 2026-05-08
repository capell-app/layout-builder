<?php

declare(strict_types=1);

namespace Capell\AccessGate\Tests\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class FakePageCacheMiddleware
{
    public static bool $ran = false;

    public static bool $sawProtectedRequest = false;

    public function handle(Request $request, Closure $next): Response
    {
        self::$ran = true;

        if ($request->attributes->get('access_gate.protected') === true) {
            self::$sawProtectedRequest = true;

            return $next($request);
        }

        return response('cached secret');
    }
}
