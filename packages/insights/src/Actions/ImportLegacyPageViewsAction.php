<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;
use stdClass;

final class ImportLegacyPageViewsAction
{
    use AsAction;

    public function handle(int $chunkSize = 500): int
    {
        if (! Schema::hasTable('page_views')) {
            return 0;
        }

        $eventsTable = (new InsightsEvent)->getTable();
        $visitsTable = (new InsightsVisit)->getTable();
        $imported = 0;

        DB::table('page_views')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($pageViews) use ($eventsTable, $visitsTable, &$imported): void {
                foreach ($pageViews as $pageView) {
                    if ($this->legacyEventExists($eventsTable, (int) $pageView->id)) {
                        continue;
                    }

                    $visitId = $this->ensureVisit($visitsTable, $pageView);
                    $imported += $this->insertEvents($eventsTable, $visitId, $pageView);
                }
            });

        return $imported;
    }

    private function legacyEventExists(string $eventsTable, int $legacyPageViewId): bool
    {
        return DB::table($eventsTable)
            ->where('legacy_page_view_id', $legacyPageViewId)
            ->exists();
    }

    private function ensureVisit(string $visitsTable, stdClass $pageView): int
    {
        $uuid = $this->legacyVisitUuid((string) $pageView->session_id);
        $startedAt = $this->dateValue($pageView->created_at ?? null) ?? $this->dateValue($pageView->viewed_at ?? null) ?? now()->toImmutable();
        $lastSeenAt = $this->dateValue($pageView->viewed_at ?? null) ?? $startedAt;
        $now = now()->toImmutable();

        DB::table($visitsTable)->updateOrInsert(
            ['uuid' => $uuid],
            [
                'site_id' => $pageView->site_id,
                'language_id' => $pageView->language_id,
                'consent_region' => InsightsConsentRegion::Unknown->value,
                'consent_status' => InsightsConsentStatus::Pending->value,
                'landing_url' => $pageView->url,
                'referrer_url' => null,
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'ip_hash' => null,
                'user_agent_hash' => null,
                'legacy_session_id' => $pageView->session_id,
                'started_at' => $startedAt,
                'last_seen_at' => $lastSeenAt,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        return (int) DB::table($visitsTable)->where('uuid', $uuid)->value('id');
    }

    private function insertEvents(string $eventsTable, int $visitId, stdClass $pageView): int
    {
        $visits = max(1, (int) ($pageView->visits ?? 1));
        $occurredAt = $this->dateValue($pageView->viewed_at ?? null) ?? now()->toImmutable();
        $path = $this->path((string) $pageView->url);
        $now = now()->toImmutable();
        $currentSequence = ((int) DB::table($eventsTable)->where('visit_id', $visitId)->max('sequence')) + 1;
        $rows = [];

        for ($visitNumber = 1; $visitNumber <= $visits; $visitNumber++) {
            $rows[] = [
                'visit_id' => $visitId,
                'site_id' => $pageView->site_id,
                'language_id' => $pageView->language_id,
                'type' => InsightsEventType::PageView->value,
                'url' => $pageView->url,
                'path' => $path,
                'title' => null,
                'occurred_at' => $occurredAt,
                'sequence' => $currentSequence,
                'event_name' => null,
                'label' => null,
                'location' => null,
                'target_selector' => null,
                'viewport_x' => null,
                'viewport_y' => null,
                'document_x' => null,
                'document_y' => null,
                'metadata' => json_encode(['legacy_page_view_id' => (int) $pageView->id]),
                'legacy_page_view_id' => (int) $pageView->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $currentSequence++;
        }

        DB::table($eventsTable)->insert($rows);

        return count($rows);
    }

    private function legacyVisitUuid(string $sessionId): string
    {
        $hash = md5('capell-legacy-page-view:' . $sessionId);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }

    private function dateValue(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    private function path(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }
}
