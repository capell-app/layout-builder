<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Lorisleiva\Actions\Action;
use Throwable;

class ValidateLicenseAction extends Action
{
    public function __construct(
        private readonly AnystackClient $anystackClient,
    ) {}

    public function handle(MarketplacePluginLicense $license): MarketplacePluginLicense
    {
        $plugin = $license->plugin;

        // A license without a plugin (cascade race) or without an anystack
        // product id (free plugin, no remote entitlement) has nothing to
        // validate. Audit the skip and return so we don't call anystack with
        // a malformed URL (e.g. `/products//licenses/validate-key`).
        if ($plugin === null || $plugin->anystack_product_id === null) {
            if ($plugin !== null) {
                $plugin->auditLog()->create([
                    'action' => 'license_skipped_no_product_id',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'license_id' => $license->id,
                    ],
                    'created_at' => now(),
                ]);
            }

            return $license;
        }

        try {
            $fingerprint = $this->resolveFingerprint($license);

            $validation = $this->anystackClient->validateLicense(
                $plugin->anystack_product_id,
                $license->encrypted_license_key,
                $fingerprint,
            );

            $license->update([
                'status' => $validation->status,
                'last_heartbeat_at' => now(),
                'expires_at' => $validation->expiresAt,
            ]);

            $plugin->auditLog()->create([
                'action' => 'license_validated',
                'actor_id' => auth()->id(),
                'data' => [
                    'site_id' => $license->site_id,
                    'status' => $validation->status->value,
                    'status_code' => $validation->statusCode,
                    'expires_at' => $validation->expiresAt?->format(DATE_ATOM),
                ],
                'created_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            if (! $license->isWithinGracePeriod()) {
                $license->update(['status' => LicenseStatus::Expired]);

                $plugin->auditLog()->create([
                    'action' => 'license_expired_offline',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'reason' => 'Offline grace period exceeded',
                        'error' => $throwable->getMessage(),
                    ],
                    'created_at' => now(),
                ]);
            } else {
                $plugin->auditLog()->create([
                    'action' => 'license_validation_failed',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'error' => $throwable->getMessage(),
                        'grace_period_remaining' => true,
                    ],
                    'created_at' => now(),
                ]);
            }
        }

        return $license->refresh();
    }

    /**
     * Use the fingerprint we recorded during activation when present.
     * Older rows may not have one — fall back to the same derivation as
     * ActivateLicenseAction so heartbeats stay consistent.
     */
    private function resolveFingerprint(MarketplacePluginLicense $license): ?string
    {
        $metadata = $license->metadata;

        if ($metadata !== null && isset($metadata['fingerprint']) && is_string($metadata['fingerprint'])) {
            return $metadata['fingerprint'];
        }

        if ($license->site_id === null) {
            return null;
        }

        return hash('sha256', 'site:' . $license->site_id);
    }
}
