<?php

declare(strict_types=1);

namespace Capell\Workspaces\Http\Controllers;

use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ExitWorkspacePreviewController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = $request->query('redirect');
        $target = is_string($redirect) && $redirect !== '' ? $redirect : '/';

        return redirect($target)->withCookie(Cookie::forget(ResolveWorkspaceContext::COOKIE_NAME));
    }
}
