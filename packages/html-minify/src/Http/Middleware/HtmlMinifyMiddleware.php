<?php

declare(strict_types=1);

namespace Capell\HtmlMinify\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class HtmlMinifyMiddleware
{
    /**
     * HTML minification happens at cache-write and render time, not on every request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        return $next($request);
    }
}
