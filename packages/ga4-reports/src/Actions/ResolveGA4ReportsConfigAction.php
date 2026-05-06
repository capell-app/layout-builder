<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsConfigData;
use Capell\GA4Reports\Settings\GA4ReportsSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveGA4ReportsConfigAction
{
    use AsAction;

    public function handle(): GA4ReportsConfigData
    {
        try {
            /** @var GA4ReportsSettings $settings */
            $settings = app(GA4ReportsSettings::class);
            $settingsRouteSlug = trim($settings->route_slug);

            return new GA4ReportsConfigData(
                enabled: $settings->enabled,
                propertyId: trim($settings->property_id),
                credentialsPath: trim($settings->credentials_path),
                syncDays: max(1, $settings->sync_days),
                routeSlug: $settingsRouteSlug !== '' ? $settingsRouteSlug : 'ga4-reports',
            );
        } catch (Throwable) {
            $propertyId = config('capell-ga4-reports.property_id');
            $credentialsPath = config('capell-ga4-reports.credentials_path');
            $syncDays = config('capell-ga4-reports.sync_days', 30);
            $routeSlug = config('capell-ga4-reports.route_slug', 'ga4-reports');
            $resolvedRouteSlug = is_string($routeSlug) ? trim($routeSlug) : '';

            return new GA4ReportsConfigData(
                enabled: config('capell-ga4-reports.enabled', false) === true,
                propertyId: is_string($propertyId) ? trim($propertyId) : '',
                credentialsPath: is_string($credentialsPath) ? trim($credentialsPath) : '',
                syncDays: is_int($syncDays) ? max(1, $syncDays) : 30,
                routeSlug: $resolvedRouteSlug !== '' ? $resolvedRouteSlug : 'ga4-reports',
            );
        }
    }
}
