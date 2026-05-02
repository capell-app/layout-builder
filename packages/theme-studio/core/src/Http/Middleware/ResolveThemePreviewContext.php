<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Http\Middleware;

use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Capell\ThemeStudio\Core\Preview\ThemePreviewSigner;
use Closure;
use Illuminate\Http\Request;

class ResolveThemePreviewContext
{
    public function __construct(private readonly ThemePreviewSigner $signer) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $context = $this->signer->contextFromToken(
            is_string($request->query($this->signer->tokenParam()))
                ? $request->query($this->signer->tokenParam())
                : null,
        );

        app()->instance(ThemePreviewContext::class, $context);
        view()->share('themePreviewContext', $context);

        return $next($request);
    }
}
