<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Models\MarketplacePluginLicense;
use Lorisleiva\Actions\Action;

final class DeactivateLicenseAction extends Action
{
    public function handle(MarketplacePluginLicense $license): MarketplacePluginLicense
    {
        $plugin = $license->plugin;

        // Delete the license
        $license->delete();

        // Write audit log
        $plugin->auditLog()->create([
            'action' => 'license_deactivated',
            'actor_id' => auth()->id(),
            'data' => [
                'site_id' => $license->site_id,
                'license_id' => $license->id,
            ],
            'created_at' => now(),
        ]);

        return $license;
    }
}
