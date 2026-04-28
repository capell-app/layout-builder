<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Lorisleiva\Actions\Action;
use RuntimeException;

final class ActivateLicenseAction extends Action
{
    public function __construct(
        private readonly AnystackClient $anystackClient,
    ) {}

    public function handle(
        MarketplacePlugin $plugin,
        string $licenseKey,
        string $siteId,
        ?string $fingerprint = null,
        ?string $hostname = null,
    ): MarketplacePluginLicense {
        throw_if($plugin->anystack_product_id === null, RuntimeException::class, 'Cannot activate license: plugin has no anystack_product_id configured');

        // Anystack requires a non-empty fingerprint; derive one from the site identifier
        // when the caller doesn't provide one explicitly.
        $effectiveFingerprint = $fingerprint ?? hash('sha256', 'site:' . $siteId);

        $activation = $this->anystackClient->activateLicense(
            $plugin->anystack_product_id,
            $licenseKey,
            $effectiveFingerprint,
            $hostname,
        );

        if (! $activation->valid) {
            throw new RuntimeException(
                'License activation failed: ' . $activation->status->value,
            );
        }

        $license = $plugin->licenses()->updateOrCreate(
            ['site_id' => $siteId],
            [
                'encrypted_license_key' => $licenseKey,
                'status' => $activation->status,
                'anystack_license_id' => $activation->licenseId,
                'anystack_activation_id' => $activation->activationId,
                'activated_at' => now(),
                'expires_at' => $activation->expiresAt,
                'last_heartbeat_at' => now(),
                'metadata' => [
                    'activated_at' => now()->toIso8601String(),
                    'fingerprint' => $effectiveFingerprint,
                    'activation_response' => $activation->raw,
                ],
            ],
        );

        $plugin->auditLog()->create([
            'action' => 'license_activated',
            'actor_id' => auth()->id(),
            'data' => [
                'site_id' => $siteId,
                'status' => $activation->status->value,
                'anystack_license_id' => $activation->licenseId,
                'anystack_activation_id' => $activation->activationId,
                'expires_at' => $activation->expiresAt?->format(DATE_ATOM),
            ],
            'created_at' => now(),
        ]);

        return $license;
    }
}
