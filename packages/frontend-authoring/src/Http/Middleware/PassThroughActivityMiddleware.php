<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PassThroughActivityMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}
