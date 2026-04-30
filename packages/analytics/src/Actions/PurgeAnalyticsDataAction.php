<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Settings\AnalyticsSettings;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeAnalyticsDataAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $resolvedRetentionDays = $retentionDays ?? $this->defaultRetentionDays();
        $cutoff = now()->subDays($resolvedRetentionDays);

        $deletedEvents = AnalyticsEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $deletedConsents = AnalyticsConsent::query()
            ->where('decided_at', '<', $cutoff)
            ->delete();

        $deletedVisits = AnalyticsVisit::query()
            ->where('last_seen_at', '<', $cutoff)
            ->whereDoesntHave('events')
            ->delete();

        return $deletedEvents + $deletedConsents + $deletedVisits;
    }

    private function defaultRetentionDays(): int
    {
        if (app()->bound(AnalyticsSettings::class)) {
            /** @var AnalyticsSettings $settings */
            $settings = app(AnalyticsSettings::class);

            return $settings->retention_days;
        }

        $retentionDays = config('capell-analytics.retention_days', 365);

        return is_int($retentionDays) ? $retentionDays : 365;
    }
}
