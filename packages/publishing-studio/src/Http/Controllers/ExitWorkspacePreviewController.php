<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Http\Controllers;

use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ExitWorkspacePreviewController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = $request->query('redirect');
        $target = is_string($redirect) && $redirect !== '' ? $this->safeRedirectTarget($redirect, $request) : '/';

        return redirect($target)->withCookie(Cookie::forget(ResolveWorkspaceContext::COOKIE_NAME));
    }

    private function safeRedirectTarget(string $redirect, Request $request): string
    {
        $target = trim($redirect);

        if ($target === '' || str_starts_with($target, '//') || str_starts_with($target, '\\')) {
            return '/';
        }

        $parts = parse_url($target);

        if ($parts === false) {
            return '/';
        }

        $host = $parts['host'] ?? null;

        if (is_string($host)) {
            if (! hash_equals($request->getHost(), $host)) {
                return '/';
            }

            $path = isset($parts['path']) && is_string($parts['path']) && $parts['path'] !== ''
                ? $parts['path']
                : '/';
            $query = isset($parts['query']) && is_string($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) && is_string($parts['fragment']) ? '#' . $parts['fragment'] : '';

            return $path . $query . $fragment;
        }

        if (isset($parts['scheme'])) {
            return '/';
        }

        return str_starts_with($target, '/') ? $target : '/' . $target;
    }
}
