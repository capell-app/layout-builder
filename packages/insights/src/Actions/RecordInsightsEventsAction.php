<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordInsightsEventsAction
{
    use AsAction;

    /**
     * @param  iterable<int, array{data: InsightsEventData, occurred_at: string|null}>  $events
     * @return Collection<int, InsightsEvent>
     */
    public function handle(?string $visitUuid, iterable $events): Collection
    {
        if (config('capell-insights.enabled', true) !== true) {
            return collect();
        }

        $visit = $this->resolveVisit($visitUuid);

        if (! $visit instanceof InsightsVisit || ! $this->canRecordForVisit($visit)) {
            return collect();
        }

        $eventRows = [];
        $now = now()->toImmutable();
        $sequence = ((int) $visit->events()->max('sequence')) + 1;

        foreach ($events as $event) {
            $eventData = $event['data'];

            if ($this->isIgnoredPath($eventData->path())) {
                continue;
            }

            $eventRows[] = [
                'visit_id' => $visit->getKey(),
                'site_id' => $visit->site_id,
                'language_id' => $visit->language_id,
                'type' => $eventData->type->value,
                'url' => $eventData->url,
                'path' => $eventData->path(),
                'title' => $eventData->title,
                'occurred_at' => $this->occurredAt($event['occurred_at']),
                'sequence' => $sequence,
                'event_name' => $eventData->eventName,
                'label' => $eventData->label,
                'location' => $eventData->location,
                'target_selector' => $eventData->targetSelector,
                'viewport_x' => $eventData->viewportX,
                'viewport_y' => $eventData->viewportY,
                'document_x' => $eventData->documentX,
                'document_y' => $eventData->documentY,
                'metadata' => $eventData->metadata !== null ? json_encode($eventData->metadata->toArray()) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $sequence++;
        }

        if ($eventRows === []) {
            return collect();
        }

        DB::table((new InsightsEvent)->getTable())->insert($eventRows);

        $visit->forceFill([
            'last_seen_at' => $now,
        ])->save();

        return $visit->events()
            ->where('sequence', '>=', $eventRows[0]['sequence'])
            ->where('sequence', '<=', $eventRows[array_key_last($eventRows)]['sequence'])
            ->orderBy('sequence')
            ->get();
    }

    private function resolveVisit(?string $visitUuid): ?InsightsVisit
    {
        if ($visitUuid === null || trim($visitUuid) === '') {
            return null;
        }

        return InsightsVisit::query()
            ->where('uuid', $visitUuid)
            ->first();
    }

    private function isIgnoredPath(string $path): bool
    {
        if ($this->isAssetPath($path)) {
            return true;
        }

        $ignoredPaths = config('capell-insights.ignored_paths', []);

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

    private function isAssetPath(string $path): bool
    {
        return preg_match('/\.(?:css|js|map|json|xml|txt|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|eot|pdf|zip)$/i', $path) === 1;
    }

    private function canRecordForVisit(InsightsVisit $visit): bool
    {
        if (config('capell-insights.require_consent_for_all_regions', false) !== true
            && $visit->consent_region === InsightsConsentRegion::OutsideUkOrEurope) {
            return true;
        }

        if ($visit->consent_region === InsightsConsentRegion::UkOrEurope
            || $visit->consent_region === InsightsConsentRegion::Unknown
            || config('capell-insights.require_consent_for_all_regions', false) === true) {
            return $this->hasInsightsConsent($visit);
        }

        return true;
    }

    private function hasInsightsConsent(InsightsVisit $visit): bool
    {
        $latestConsent = $visit->consents()
            ->latest('decided_at')
            ->first();

        if ($latestConsent instanceof InsightsConsent) {
            return $latestConsent->categories->insights;
        }

        return $visit->consent_status === InsightsConsentStatus::AcceptedAll;
    }

    private function occurredAt(?string $occurredAt): CarbonImmutable
    {
        if ($occurredAt === null || trim($occurredAt) === '') {
            return now()->toImmutable();
        }

        return CarbonImmutable::parse($occurredAt);
    }
}
