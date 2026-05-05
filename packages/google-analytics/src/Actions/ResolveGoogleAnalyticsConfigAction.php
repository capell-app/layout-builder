<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsConfigData;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveGoogleAnalyticsConfigAction
{
    use AsAction;

    public function handle(): GoogleAnalyticsConfigData
    {
        try {
            /** @var GoogleAnalyticsSettings $settings */
            $settings = app(GoogleAnalyticsSettings::class);
            $settingsRouteSlug = trim($settings->route_slug);

            return new GoogleAnalyticsConfigData(
                enabled: $settings->enabled,
                propertyId: trim($settings->property_id),
                credentialsPath: trim($settings->credentials_path),
                syncDays: max(1, $settings->sync_days),
                routeSlug: $settingsRouteSlug !== '' ? $settingsRouteSlug : 'google-analytics',
            );
        } catch (Throwable) {
            $propertyId = config('capell-google-analytics.property_id');
            $credentialsPath = config('capell-google-analytics.credentials_path');
            $syncDays = config('capell-google-analytics.sync_days', 30);
            $routeSlug = config('capell-google-analytics.route_slug', 'google-analytics');
            $resolvedRouteSlug = is_string($routeSlug) ? trim($routeSlug) : '';

            return new GoogleAnalyticsConfigData(
                enabled: config('capell-google-analytics.enabled', false) === true,
                propertyId: is_string($propertyId) ? trim($propertyId) : '',
                credentialsPath: is_string($credentialsPath) ? trim($credentialsPath) : '',
                syncDays: is_int($syncDays) ? max(1, $syncDays) : 30,
                routeSlug: $resolvedRouteSlug !== '' ? $resolvedRouteSlug : 'google-analytics',
            );
        }
    }
}
