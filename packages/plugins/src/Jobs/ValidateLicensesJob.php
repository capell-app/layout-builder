<?php

declare(strict_types=1);

namespace Capell\Plugins\Jobs;

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ValidateLicensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ValidateLicenseAction $action): void
    {
        MarketplacePluginLicense::query()
            ->whereIn('status', [
                LicenseStatus::Active->value,
                LicenseStatus::Trial->value,
                LicenseStatus::PastDue->value,
            ])
            ->cursor()
            ->each(function (MarketplacePluginLicense $license) use ($action): void {
                try {
                    $action->handle($license);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }
}
