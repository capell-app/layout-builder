<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsWindowData;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopActionsQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, array{action: string, event_name: ?string, label: ?string, location: ?string, events: int}>
     */
    public function handle(InsightsWindowData $window, ?int $limit = 5): Collection
    {
        $query = InsightsEvent::query()
            ->select([
                'event_name',
                'label',
                'location',
                DB::raw('COUNT(*) as events'),
            ])
            ->where('type', '!=', InsightsEventType::PageView)
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('event_name')
                    ->orWhereNotNull('label')
                    ->orWhereNotNull('location');
            })
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->groupBy('event_name', 'label', 'location')
            ->orderByDesc('events')
            ->orderBy('event_name')
            ->orderBy('label')
            ->orderBy('location');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (InsightsEvent $event): array => [
                'action' => $this->actionName($event),
                'event_name' => $event->event_name,
                'label' => $event->label,
                'location' => $event->location,
                'events' => (int) $event->events,
            ])
            ->values();
    }

    private function actionName(InsightsEvent $event): string
    {
        foreach ([$event->event_name, $event->label, $event->location] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return $event->type->getLabel();
    }
}
