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
    ): MarketplacePluginLicense {
        if ($plugin->anystack_product_id === null) {
            throw new RuntimeException(
                'Cannot activate license: plugin has no anystack_product_id configured',
            );
        }

        // Generate site fingerprint from siteId
        $siteFingerprint = hash('sha256', $siteId);

        // Validate license with Anystack
        $validation = $this->anystackClient->validateLicense(
            $plugin->anystack_product_id,
            $licenseKey,
            $siteFingerprint,
        );

        if (! $validation->valid) {
            throw new RuntimeException(
                'License validation failed: ' . $validation->status->value,
            );
        }

        // Update or create license
        $license = $plugin->licenses()->updateOrCreate(
            ['site_id' => $siteId],
            [
                'encrypted_license_key' => $licenseKey,
                'status' => $validation->status,
                'activated_at' => now(),
                'expires_at' => $validation->expiresAt,
                'last_heartbeat_at' => now(),
                'metadata' => [
                    'validated_at' => now()->toIso8601String(),
                    'validation_response' => $validation->raw,
                ],
            ],
        );

        // Write audit log
        $plugin->auditLog()->create([
            'action' => 'license_activated',
            'actor_id' => auth()->id(),
            'data' => [
                'site_id' => $siteId,
                'status' => $validation->status->value,
                'expires_at' => $validation->expiresAt?->toIso8601String(),
            ],
            'created_at' => now(),
        ]);

        return $license;
    }
}
