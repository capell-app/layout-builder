<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGoogleAnalyticsWindowAction
{
    use AsAction;

    public function handle(?CarbonImmutable $startsAt = null, ?CarbonImmutable $endsAt = null): ?GoogleAnalyticsWindowData
    {
        $config = ResolveGoogleAnalyticsConfigAction::run();

        if (! $config->enabled || $config->propertyId === '') {
            return null;
        }

        $endDate = $endsAt ?? Date::now()->toImmutable()->subDay()->startOfDay();
        $startDate = $startsAt ?? $endDate->subDays($config->syncDays - 1)->startOfDay();

        return new GoogleAnalyticsWindowData(
            startsAt: $startDate,
            endsAt: $endDate,
            propertyId: $config->propertyId,
        );
    }
}
