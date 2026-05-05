<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsSyncResultData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsSyncRun;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class SyncGoogleAnalyticsMetricsAction
{
    use AsAction;

    public function handle(): GoogleAnalyticsSyncResultData
    {
        $window = BuildGoogleAnalyticsWindowAction::run();

        if ($window === null) {
            return new GoogleAnalyticsSyncResultData(false, __('capell-google-analytics::sync.not_configured'));
        }

        /** @var GoogleAnalyticsDataClientInterface $client */
        $client = app(GoogleAnalyticsDataClientInterface::class);

        if (! $client->isConfigured()) {
            return new GoogleAnalyticsSyncResultData(false, __('capell-google-analytics::sync.not_configured'));
        }

        $syncRun = GoogleAnalyticsSyncRun::query()->create([
            'property_id' => $window->propertyId,
            'status' => 'running',
            'window_start' => $window->startsAt->toDateString(),
            'window_end' => $window->endsAt->toDateString(),
            'started_at' => Date::now(),
        ]);

        try {
            $dailyMetrics = $client->dailyMetrics($window);
            $pageMetrics = $client->pageMetrics($window);

            foreach ($dailyMetrics as $dailyMetric) {
                PersistGoogleAnalyticsDailyMetricAction::run($dailyMetric);
            }

            foreach ($pageMetrics as $pageMetric) {
                PersistGoogleAnalyticsPageMetricAction::run($pageMetric);
            }

            $syncRun->update([
                'status' => 'succeeded',
                'daily_rows' => count($dailyMetrics),
                'page_rows' => count($pageMetrics),
                'finished_at' => Date::now(),
            ]);

            return new GoogleAnalyticsSyncResultData(
                synced: true,
                message: __('capell-google-analytics::sync.synced'),
                dailyRows: count($dailyMetrics),
                pageRows: count($pageMetrics),
            );
        } catch (Throwable $exception) {
            $syncRun->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => Date::now(),
            ]);

            return new GoogleAnalyticsSyncResultData(false, __('capell-google-analytics::sync.failed'));
        }
    }
}
