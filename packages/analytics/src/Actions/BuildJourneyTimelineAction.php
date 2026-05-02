<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsJourneyStepData;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildJourneyTimelineAction
{
    use AsAction;

    /**
     * @return Collection<int, AnalyticsJourneyStepData>
     */
    public function handle(AnalyticsVisit $visit): Collection
    {
        $previousOccurredAt = null;

        return $visit->events()
            ->orderBy('sequence')
            ->oldest('occurred_at')
            ->get()
            ->map(function (AnalyticsEvent $event) use (&$previousOccurredAt): AnalyticsJourneyStepData {
                $occurredAt = $event->occurred_at instanceof CarbonImmutable
                    ? $event->occurred_at
                    : CarbonImmutable::parse($event->occurred_at);
                $secondsSincePreviousStep = $previousOccurredAt instanceof CarbonImmutable
                    ? (int) $previousOccurredAt->diffInSeconds($occurredAt)
                    : null;
                $previousOccurredAt = $occurredAt;

                return new AnalyticsJourneyStepData(
                    sequence: (int) $event->sequence,
                    type: $event->type,
                    url: (string) $event->url,
                    path: (string) $event->path,
                    title: $event->title,
                    eventName: $event->event_name,
                    label: $event->label,
                    location: $event->location,
                    occurredAt: $occurredAt,
                    secondsSincePreviousStep: $secondsSincePreviousStep,
                );
            })
            ->values();
    }
}
