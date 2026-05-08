<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Controllers;

use Capell\AccessGate\Actions\ConsumeAccessGateClaimTokenAction;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;

final class ClaimAccessGateTokenController
{
    public function __construct(
        private readonly ConsumeAccessGateClaimTokenAction $consumeClaimToken,
    ) {}

    public function __invoke(Request $request, string $token): RedirectResponse|Response
    {
        $issuedBrowserToken = $this->consumeClaimToken->handle($token, [
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => $request->userAgent(),
        ]);

        if ($issuedBrowserToken === null || ! $issuedBrowserToken->token instanceof BrowserToken) {
            return $this->noStore(response()->view('capell-access-gate::message', [
                'title' => __('capell-access-gate::public.claim_failed.title'),
                'message' => __('capell-access-gate::public.claim_failed.message'),
            ], 422));
        }

        $browserToken = $issuedBrowserToken->token->loadMissing('grant.registration', 'area');
        $area = $browserToken->area;
        $redirectUrl = $area instanceof Area
            ? $this->safeRedirectUrl($request, $area, $browserToken->grant?->registration?->requested_url)
            : url('/');

        return $this->noStore(
            redirect($redirectUrl)
                ->withCookie($this->browserCookie($request, $issuedBrowserToken->plainTextToken)),
        );
    }

    private function safeRedirectUrl(Request $request, Area $area, ?string $requestedUrl): string
    {
        if ($requestedUrl === null || $requestedUrl === '') {
            return url('/');
        }

        $requestedHost = parse_url($requestedUrl, PHP_URL_HOST);
        $allowedHosts = collect($area->claim_url_hosts ?? [])
            ->filter(fn (mixed $host): bool => is_string($host) && $host !== '')
            ->push($request->getHost())
            ->unique()
            ->values();

        if (! is_string($requestedHost) || ! $allowedHosts->contains($requestedHost)) {
            return url('/');
        }

        return $requestedUrl;
    }

    private function browserCookie(Request $request, string $plainTextToken): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = config('access-gate.cookies.browser_token.secure');

        return Cookie::make(
            (string) config('access-gate.cookies.browser_token.name', 'capell_access_gate_browser_token'),
            $plainTextToken,
            (int) config('access-gate.cookies.browser_token.ttl_minutes', 259200),
            (string) config('access-gate.cookies.browser_token.path', '/'),
            config('access-gate.cookies.browser_token.domain'),
            is_bool($secure) ? $secure : $request->isSecure(),
            (bool) config('access-gate.cookies.browser_token.http_only', true),
            false,
            (string) config('access-gate.cookies.browser_token.same_site', 'lax'),
        );
    }

    private function noStore(RedirectResponse|Response $response): RedirectResponse|Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
