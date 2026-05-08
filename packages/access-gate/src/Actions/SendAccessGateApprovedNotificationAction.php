<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

final class SendAccessGateApprovedNotificationAction
{
    use AsAction;

    public function __construct(
        private readonly CreateAccessGateClaimTokenAction $createClaimToken,
    ) {}

    public function handle(Registration $registration, Grant $grant): void
    {
        if ($registration->email === '') {
            return;
        }

        $area = $grant->area()->firstOrFail();
        $claimUrl = null;

        if ($area->identity_mode === IdentityMode::GuestLink || $area->identity_mode === IdentityMode::Hybrid) {
            $issuedClaimToken = $this->createClaimToken->handle(
                grant: $grant,
                expiresAt: now()->addMinutes((int) config('access-gate.claim_token_ttl_minutes', 10080)),
            );

            $claimUrl = $this->claimUrl($registration, $area, $issuedClaimToken->plainTextToken);
        }

        Notification::route('mail', $registration->email)
            ->notify(new AccessApprovedNotification($area, $claimUrl));
    }

    private function claimUrl(Registration $registration, Area $area, string $plainTextToken): string
    {
        $relativeClaimUrl = route('capell-access-gate.claim', ['token' => $plainTextToken], false);
        $requestedUrl = $registration->requested_url;

        if (! is_string($requestedUrl) || $requestedUrl === '') {
            return route('capell-access-gate.claim', ['token' => $plainTextToken]);
        }

        $requestedHost = parse_url($requestedUrl, PHP_URL_HOST);

        if (! is_string($requestedHost) || ! $this->isAllowedClaimHost($area, $requestedHost)) {
            return route('capell-access-gate.claim', ['token' => $plainTextToken]);
        }

        $requestedScheme = parse_url($requestedUrl, PHP_URL_SCHEME);
        $scheme = in_array($requestedScheme, ['http', 'https'], true) ? $requestedScheme : 'https';
        $requestedPort = parse_url($requestedUrl, PHP_URL_PORT);
        $port = is_int($requestedPort) ? ':' . $requestedPort : '';

        return $scheme . '://' . $requestedHost . $port . $relativeClaimUrl;
    }

    private function isAllowedClaimHost(Area $area, string $requestedHost): bool
    {
        return collect($area->claim_url_hosts ?? [])
            ->filter(fn (mixed $host): bool => is_string($host) && $host !== '')
            ->contains($requestedHost);
    }
}
