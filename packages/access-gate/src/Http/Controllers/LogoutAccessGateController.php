<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Controllers;

use Capell\AccessGate\Actions\RevokeAccessGateBrowserTokenAction;
use Capell\AccessGate\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

final class LogoutAccessGateController
{
    public function __construct(
        private readonly RevokeAccessGateBrowserTokenAction $revokeBrowserToken,
    ) {}

    public function __invoke(Request $request, string $area): RedirectResponse
    {
        $accessArea = Area::query()->where('key', $area)->firstOrFail();
        $cookieName = config('access-gate.cookies.browser_token.name', 'capell_access_gate_browser_token');

        $this->revokeBrowserToken->handle($accessArea, $request->cookies->get($cookieName));

        return $this->noStore(
            to_route('capell-access-gate.request', ['area' => $accessArea->key])
                ->withCookie(Cookie::forget(
                    $cookieName,
                    config('access-gate.cookies.browser_token.path', '/'),
                    config('access-gate.cookies.browser_token.domain'),
                )),
        );
    }

    private function noStore(RedirectResponse $response): RedirectResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
