<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsJourneyStepData;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildRecentJourneysQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, array{id: int, visit: string, steps: int, landing_url: string, last_path: string}>
     */
    public function handle(?int $limit = 5): Collection
    {
        $query = AnalyticsVisit::query()
            ->whereHas('events')
            ->latest('last_seen_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(function (AnalyticsVisit $visit): array {
                $timeline = BuildJourneyTimelineAction::run($visit);
                $lastStep = $timeline->last();

                return [
                    'id' => (int) $visit->getKey(),
                    'visit' => (string) $visit->uuid,
                    'steps' => $timeline->count(),
                    'landing_url' => (string) $visit->landing_url,
                    'last_path' => $lastStep instanceof AnalyticsJourneyStepData ? $lastStep->path : '',
                ];
            })
            ->values();
    }
}
