<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Lorisleiva\Actions\Action;
use Throwable;

final class ValidateLicenseAction extends Action
{
    public function __construct(
        private readonly AnystackClient $anystackClient,
    ) {}

    public function handle(MarketplacePluginLicense $license): MarketplacePluginLicense
    {
        $plugin = $license->plugin;

        try {
            // Validate license with Anystack
            $validation = $this->anystackClient->validateLicense(
                $plugin->anystack_product_id,
                $license->encrypted_license_key,
                $license->site_id !== null ? hash('sha256', $license->site_id) : null,
            );

            // Update license with new status
            $license->update([
                'status' => $validation->status,
                'last_heartbeat_at' => now(),
                'expires_at' => $validation->expiresAt,
            ]);

            // Write audit log
            $plugin->auditLog()->create([
                'action' => 'license_validated',
                'actor_id' => auth()->id(),
                'data' => [
                    'site_id' => $license->site_id,
                    'status' => $validation->status->value,
                    'expires_at' => $validation->expiresAt?->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Handle offline grace period
            if (! $license->isWithinGracePeriod()) {
                $license->update(['status' => LicenseStatus::Expired]);

                $plugin->auditLog()->create([
                    'action' => 'license_expired_offline',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'reason' => 'Offline grace period exceeded',
                        'error' => $exception->getMessage(),
                    ],
                    'created_at' => now(),
                ]);
            } else {
                // Still within grace period, log the validation error
                $plugin->auditLog()->create([
                    'action' => 'license_validation_failed',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'error' => $exception->getMessage(),
                        'grace_period_remaining' => true,
                    ],
                    'created_at' => now(),
                ]);
            }
        }

        return $license->refresh();
    }
}
