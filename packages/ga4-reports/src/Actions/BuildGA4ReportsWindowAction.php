<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGA4ReportsWindowAction
{
    use AsAction;

    public function handle(?CarbonImmutable $startsAt = null, ?CarbonImmutable $endsAt = null): ?GA4ReportsWindowData
    {
        $config = ResolveGA4ReportsConfigAction::run();

        if (! $config->enabled || $config->propertyId === '') {
            return null;
        }

        $endDate = $endsAt ?? Date::now()->toImmutable()->subDay()->startOfDay();
        $startDate = $startsAt ?? $endDate->subDays($config->syncDays - 1)->startOfDay();

        return new GA4ReportsWindowData(
            startsAt: $startDate,
            endsAt: $endDate,
            propertyId: $config->propertyId,
        );
    }
}
