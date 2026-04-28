<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Action;
use Throwable;

final class DeactivateLicenseAction extends Action
{
    public function __construct(
        private readonly AnystackClient $anystackClient,
    ) {}

    public function handle(MarketplacePluginLicense $license): MarketplacePluginLicense
    {
        $plugin = $license->plugin;

        $remoteRemoved = null;
        $remoteError = null;

        if (
            $plugin !== null
            && $plugin->anystack_product_id !== null
            && $license->anystack_license_id !== null
            && $license->anystack_activation_id !== null
        ) {
            try {
                $remoteRemoved = $this->anystackClient->deactivateLicense(
                    $plugin->anystack_product_id,
                    $license->anystack_license_id,
                    $license->anystack_activation_id,
                );
            } catch (Throwable $exception) {
                // Local deletion still proceeds; surface the error in the audit log.
                $remoteError = $exception->getMessage();
            }
        }

        // Wrap the local delete + audit write in a transaction so a DB failure
        // mid-sequence rolls back to a consistent state. The remote call
        // already happened — if its side effect diverges from the rolled-back
        // local state, the caller sees the exception and can retry the whole
        // action (a second remote DELETE on an already-removed activation is
        // 404, which the client maps to `false`, not an error).
        DB::transaction(function () use ($license, $plugin, $remoteRemoved, $remoteError): void {
            $license->delete();

            if ($plugin !== null) {
                $plugin->auditLog()->create([
                    'action' => 'license_deactivated',
                    'actor_id' => auth()->id(),
                    'data' => [
                        'site_id' => $license->site_id,
                        'license_id' => $license->id,
                        'anystack_license_id' => $license->anystack_license_id,
                        'anystack_activation_id' => $license->anystack_activation_id,
                        'remote_removed' => $remoteRemoved,
                        'remote_error' => $remoteError,
                    ],
                    'created_at' => now(),
                ]);
            }
        });

        return $license;
    }
}
