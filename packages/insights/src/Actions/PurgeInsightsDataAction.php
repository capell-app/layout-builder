<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Capell\Insights\Settings\InsightsSettings;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeInsightsDataAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $resolvedRetentionDays = $retentionDays ?? $this->defaultRetentionDays();
        $cutoff = now()->subDays($resolvedRetentionDays);

        $deletedEvents = InsightsEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $deletedConsents = InsightsConsent::query()
            ->where('decided_at', '<', $cutoff)
            ->delete();

        $deletedVisits = InsightsVisit::query()
            ->where('last_seen_at', '<', $cutoff)
            ->whereDoesntHave('events')
            ->delete();

        return $deletedEvents + $deletedConsents + $deletedVisits;
    }

    private function defaultRetentionDays(): int
    {
        if (app()->bound(InsightsSettings::class)) {
            /** @var InsightsSettings $settings */
            $settings = resolve(InsightsSettings::class);

            return $settings->retention_days;
        }

        $retentionDays = config('capell-insights.retention_days', 365);

        return is_int($retentionDays) ? $retentionDays : 365;
    }
}
