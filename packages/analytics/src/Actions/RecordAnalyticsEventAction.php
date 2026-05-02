<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Capell\Analytics\Enums\AnalyticsConsentStatus;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordAnalyticsEventAction
{
    use AsAction;

    public function handle(?string $visitUuid, AnalyticsEventData $data, ?string $occurredAt = null): ?AnalyticsEvent
    {
        if (config('capell-analytics.enabled', true) !== true) {
            return null;
        }

        if ($this->isIgnoredPath($data->path())) {
            return null;
        }

        $visit = $this->resolveVisit($visitUuid);

        if (! $visit instanceof AnalyticsVisit) {
            return null;
        }

        if (! $this->canRecordForVisit($visit)) {
            return null;
        }

        $sequence = ((int) $visit->events()->max('sequence')) + 1;

        $event = AnalyticsEvent::query()->create([
            'visit_id' => $visit->getKey(),
            'site_id' => $visit->site_id,
            'language_id' => $visit->language_id,
            'type' => $data->type,
            'url' => $data->url,
            'path' => $data->path(),
            'title' => $data->title,
            'occurred_at' => $this->occurredAt($occurredAt),
            'sequence' => $sequence,
            'event_name' => $data->eventName,
            'label' => $data->label,
            'location' => $data->location,
            'target_selector' => $data->targetSelector,
            'viewport_x' => $data->viewportX,
            'viewport_y' => $data->viewportY,
            'document_x' => $data->documentX,
            'document_y' => $data->documentY,
            'metadata' => $data->metadata,
        ]);

        $visit->forceFill([
            'last_seen_at' => now()->toImmutable(),
        ])->save();

        return $event;
    }

    private function resolveVisit(?string $visitUuid): ?AnalyticsVisit
    {
        if ($visitUuid === null || trim($visitUuid) === '') {
            return null;
        }

        return AnalyticsVisit::query()
            ->where('uuid', $visitUuid)
            ->first();
    }

    private function isIgnoredPath(string $path): bool
    {
        $ignoredPaths = config('capell-analytics.ignored_paths', []);

        if (! is_array($ignoredPaths)) {
            return false;
        }

        foreach ($ignoredPaths as $ignoredPath) {
            if (is_string($ignoredPath) && Str::is($ignoredPath, $path)) {
                return true;
            }
        }

        return false;
    }

    private function canRecordForVisit(AnalyticsVisit $visit): bool
    {
        if (config('capell-analytics.require_consent_for_all_regions', false) !== true
            && $visit->consent_region === AnalyticsConsentRegion::OutsideUkOrEurope) {
            return true;
        }

        if ($visit->consent_region === AnalyticsConsentRegion::UkOrEurope
            || $visit->consent_region === AnalyticsConsentRegion::Unknown
            || config('capell-analytics.require_consent_for_all_regions', false) === true) {
            return $this->hasAnalyticsConsent($visit);
        }

        return true;
    }

    private function hasAnalyticsConsent(AnalyticsVisit $visit): bool
    {
        $latestConsent = $visit->consents()
            ->latest('decided_at')
            ->first();

        if ($latestConsent instanceof AnalyticsConsent) {
            return $latestConsent->categories->analytics;
        }

        return $visit->consent_status === AnalyticsConsentStatus::AcceptedAll;
    }

    private function occurredAt(?string $occurredAt): CarbonImmutable
    {
        if ($occurredAt === null || trim($occurredAt) === '') {
            return now()->toImmutable();
        }

        return CarbonImmutable::parse($occurredAt);
    }
}
