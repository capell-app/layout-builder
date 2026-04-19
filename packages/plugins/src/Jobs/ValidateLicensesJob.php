<?php

declare(strict_types=1);

namespace Capell\Plugins\Jobs;

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ValidateLicensesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(ValidateLicenseAction $action): void
    {
        // chunk() releases the DB connection between batches so the slow
        // per-license anystack HTTP calls don't hold a cursor open for the
        // whole run. For sites with hundreds of licenses this is the
        // difference between a connection timeout and a clean sweep.
        MarketplacePluginLicense::query()
            ->whereIn('status', [
                LicenseStatus::Active->value,
                LicenseStatus::Trial->value,
                LicenseStatus::PastDue->value,
            ])
            ->chunk(50, function (Collection $licenses) use ($action): void {
                foreach ($licenses as $license) {
                    try {
                        $action->handle($license);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
